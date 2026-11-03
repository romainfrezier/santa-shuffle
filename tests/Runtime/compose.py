"""Exercise the shipped Compose variants and restore synthetic backups only."""
import base64
import json
import os
from pathlib import Path
import secrets
import subprocess
import sys
import tempfile
import urllib.error
import urllib.parse
import urllib.request

root = Path(__file__).resolve().parents[2]
mode = sys.argv[1] if len(sys.argv) > 1 else "sqlite"
assert mode in ("sqlite", "pgsql")
project = "santa-verify-" + secrets.token_hex(4)
with tempfile.TemporaryDirectory(prefix="santa-compose-") as directory:
    temp = Path(directory)
    env_file = temp / "test.env"
    env_file.write_text("\n".join([
        "APP_KEY=base64:" + base64.b64encode(secrets.token_bytes(32)).decode(),
        "APP_URL=http://localhost", "APP_PORT=0", "DB_CONNECTION=sqlite",
        "DB_DATABASE=/app/storage/database/database.sqlite", "MAIL_MAILER=array",
        "POSTGRES_PASSWORD=" + secrets.token_hex(24),
    ]) + "\n")
    env_file.chmod(0o600)
    override = "services:\n"
    for service in ("app", "worker", "scheduler"):
        override += f"  {service}:\n    env_file: !override [{json.dumps(str(env_file))}]\n"
    override += '  app:\n    env_file: !override [' + json.dumps(str(env_file)) + ']\n    ports: !override ["127.0.0.1::8080"]\n'
    # Some developer Docker installations exhaust automatic pools. CI needs no override.
    if os.getenv("SANTA_TEST_SUBNET"):
        override += "networks:\n  default:\n    ipam:\n      config:\n        - subnet: " + os.environ["SANTA_TEST_SUBNET"] + "\n"
    # Avoid duplicate YAML service keys.
    override = override.replace(f"  app:\n    env_file: !override [{json.dumps(str(env_file))}]\n", "", 1)
    override_file = temp / "override.yaml"
    override_file.write_text(override)
    command = ["docker", "compose", "--env-file", str(env_file), "-p", project, "-f", str(root / "compose.yaml")]
    if mode == "pgsql":
        command += ["-f", str(root / "compose.postgres.yaml")]
    command += ["-f", str(override_file)]

    def run(*args, input=None):
        result = subprocess.run(command + list(args), cwd=root, input=input, capture_output=True)
        if result.returncode:
            raise RuntimeError(result.stderr.decode() + result.stdout.decode())
        return result.stdout

    bootstrap = "require 'vendor/autoload.php'; $app=require 'bootstrap/app.php'; $app->make(Illuminate\\Contracts\\Console\\Kernel::class)->bootstrap(); "
    seed = bootstrap + r"""
        $token=Illuminate\Support\Str::random(64);
        $e=App\Models\Exchange::create(['uuid'=>(string)Illuminate\Support\Str::uuid(),'name'=>'Restore proof','organizer_email'=>'synthetic@example.test','access_hash'=>hash('sha256',$token),'access_token'=>$token,'gift_date'=>now()->addMonth()->toDateString(),'budget'=>20]);
        App\Models\Delivery::enqueue($e,null,'organizer');
        echo json_encode(['id'=>$e->id,'hash'=>hash('sha256',$token)]);
    """
    try:
        if mode == "pgsql":
            run("up", "-d", "--wait", "postgres")
        run("run", "--rm", "--no-deps", "app", "php", "artisan", "migrate", "--force")
        run("up", "-d", "--wait", "--wait-timeout", "60", "app")
        # Older Compose versions reject --wait for an explicitly disabled healthcheck.
        run("up", "-d", "worker", "scheduler")
        port = run("port", "app", "8080").decode().strip()
        with urllib.request.urlopen("http://" + port + "/up") as response:
            assert response.status == 200
        with urllib.request.urlopen("http://" + port + "/login") as response:
            assert response.status == 200
            assert response.headers["Referrer-Policy"] == "no-referrer"
        class NoRedirect(urllib.request.HTTPRedirectHandler):
            def redirect_request(self, req, fp, code, msg, headers, newurl):
                return None

        # APP_URL is canonical; the synthetic published port is intentionally random.
        try:
            urllib.request.build_opener(NoRedirect).open("http://" + port + "/create")
        except urllib.error.HTTPError as response:
            assert response.code == 302
            assert urllib.parse.urlparse(response.headers["Location"]).path == "/login"
        else:
            raise AssertionError("Anonymous event creation must redirect to login")
        ids = json.loads(run("exec", "-T", "app", "php", "-r", seed))
        running = set(run("ps", "--status", "running", "--services").decode().splitlines())
        assert {"app", "worker", "scheduler"} <= running, running
        run("stop", "app", "worker", "scheduler")
        if mode == "sqlite":
            run("cp", "app:/app/storage/database/database.sqlite", str(temp / "backup.sqlite"))
            run("run", "--rm", "--no-deps", "app", "php", "-r", bootstrap + "App\\Models\\Exchange::query()->update(['name'=>'changed after backup']);")
            run("cp", str(temp / "backup.sqlite"), "app:/app/storage/database/database.sqlite")
            run("run", "--rm", "--no-deps", "--user", "root", "--entrypoint", "chown", "app", "www-data:www-data", "/app/storage/database/database.sqlite")
            extra = []
        else:
            backup = run("exec", "-T", "postgres", "pg_dump", "-U", "santa_shuffle", "-d", "santa_shuffle", "-Fc")
            (temp / "backup.dump").write_bytes(backup)
            run("exec", "-T", "postgres", "createdb", "-U", "santa_shuffle", "santa_shuffle_restore")
            run("exec", "-T", "postgres", "pg_restore", "-U", "santa_shuffle", "-d", "santa_shuffle_restore", "--exit-on-error", input=backup)
            extra = ["-e", "DB_DATABASE=santa_shuffle_restore"]
        proof = bootstrap + f"$e=App\\Models\\Exchange::findOrFail({ids['id']}); if($e->name!=='Restore proof'||hash('sha256',$e->access_token)!=='{ids['hash']}') exit(1); echo 'restored';"
        assert run("run", "--rm", "--no-deps", *extra, "app", "php", "-r", proof).decode().strip() == "restored"
        print(f"Compose {mode}: app + worker + scheduler, HTTP, migrations, backup and encrypted-token restoration passed")
    finally:
        # Only the randomly named synthetic project created above is removed.
        run("down", "-v", "--remove-orphans")
