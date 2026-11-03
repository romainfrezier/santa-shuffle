import { spawn, spawnSync } from "node:child_process";
import {
    mkdtempSync,
    mkdirSync,
    writeFileSync,
    rmSync,
    openSync,
} from "node:fs";
import { tmpdir } from "node:os";
import { randomBytes } from "node:crypto";
const directory = mkdtempSync(`${tmpdir()}/santa-browser-`);
writeFileSync(`${directory}/database.sqlite`, "");
const overrides = {
    APP_ENV: "e2e",
    APP_DEBUG: "false",
    APP_KEY: `base64:${randomBytes(32).toString("base64")}`,
    APP_URL: "http://127.0.0.1:8137",
    DB_CONNECTION: "sqlite",
    DB_DATABASE: `${directory}/database.sqlite`,
    DB_URL: "",
    CACHE_STORE: "database",
    SESSION_DRIVER: "database",
    SESSION_SECURE_COOKIE: "false",
    QUEUE_CONNECTION: "database",
    MAIL_MAILER: "log",
    LOG_CHANNEL: "stderr",
};
const env = { ...process.env, ...overrides };
mkdirSync("artifacts", { recursive: true });
writeFileSync("artifacts/browser-runtime.json", JSON.stringify(overrides), {
    mode: 0o600,
});
const migrate = spawnSync("php", ["artisan", "migrate", "--force"], {
    env,
    encoding: "utf8",
});
if (migrate.status !== 0) throw Error(migrate.stderr + migrate.stdout);
const server = spawn("php", ["-S", "127.0.0.1:8137", "-t", "public"], {
    env,
    stdio: [
        "ignore",
        "ignore",
        openSync("artifacts/browser-server.log", "w", 0o600),
    ],
});
let stopping = false;
function stop() {
    if (stopping) return;
    stopping = true;
    server.kill("SIGTERM");
    rmSync(directory, { recursive: true, force: true });
    rmSync("artifacts/browser-runtime.json", { force: true });
}
process.on("SIGTERM", () => {
    stop();
    process.exit(0);
});
process.on("SIGINT", () => {
    stop();
    process.exit(0);
});
server.on("exit", () => {
    stop();
    process.exit(0);
});
