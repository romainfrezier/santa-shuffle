<?php

require __DIR__.'/../../vendor/autoload.php';
use App\Models\Delivery;
use App\Models\Exchange;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Symfony\Component\Process\Process;

function check(bool $condition, string $message): void
{
    if (! $condition) {
        throw new RuntimeException($message);
    }
}
$app = require __DIR__.'/../../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();
if (($argv[1] ?? '') === 'worker') {
    config(require $argv[2].'/config.php');
    DB::purge();
    Artisan::call('queue:work', ['connection' => 'database', '--once' => true, '--tries' => 3, '--timeout' => 15]);
    exit;
}
$dir = sys_get_temp_dir().'/santa-mail-'.bin2hex(random_bytes(8));
mkdir($dir, 0700);
touch("$dir/database.sqlite");
touch("$dir/messages");
file_put_contents("$dir/mode", 'accept');
$smtp = new Process(['node', __DIR__.'/smtp.mjs', $dir]);
$smtp->start();
try {
    $deadline = microtime(true) + 5;
    while (! file_exists("$dir/port") && microtime(true) < $deadline) {
        usleep(1000);
    }
    check(file_exists("$dir/port"), 'SMTP fixture did not start');
    $settings = ['app.key' => 'base64:'.base64_encode(random_bytes(32)), 'app.env' => 'testing', 'app.url' => 'http://localhost:8137', 'database.default' => 'sqlite', 'database.connections.sqlite.database' => "$dir/database.sqlite", 'database.connections.sqlite.url' => null, 'queue.default' => 'database', 'cache.default' => 'array', 'mail.default' => 'smtp', 'mail.mailers.smtp' => ['transport' => 'smtp', 'host' => '127.0.0.1', 'port' => (int) file_get_contents("$dir/port"), 'timeout' => 10, 'auto_tls' => false]];
    file_put_contents("$dir/config.php", '<?php return '.var_export($settings, true).';');
    chmod("$dir/config.php", 0600);
    config($settings);
    DB::purge();
    Artisan::call('migrate', ['--force' => true]);
    $token = Str::random(64);
    $event = Exchange::create(['uuid' => (string) Str::uuid(), 'name' => 'SMTP test', 'organizer_email' => 'test@example.test', 'access_hash' => hash('sha256', $token), 'access_token' => $token, 'gift_date' => now()->addMonth()->toDateString(), 'budget' => 20]);
    $run = function () use ($dir) {
        $p = new Process([PHP_BINARY, __FILE__, 'worker', $dir]);
        $p->setTimeout(20);
        $p->mustRun();
    };
    $count = fn () => count(file("$dir/messages", FILE_IGNORE_NEW_LINES));
    $d = Delivery::enqueue($event, null, 'organizer');
    $run();
    check($d->fresh()->status === 'accepted' && $count() === 1, 'SMTP success failed');
    file_put_contents("$dir/mode", 'temporary');
    $d = Delivery::enqueue($event, null, 'organizer');
    $run();
    check($d->fresh()->status === 'pending' && DB::table('jobs')->count() === 1, 'Temporary failure lost retry');
    file_put_contents("$dir/mode", 'accept');
    DB::table('jobs')->update(['available_at' => 0]);
    $run();
    check($d->fresh()->status === 'accepted' && $count() === 2, 'Retry did not recover');
    file_put_contents("$dir/mode", 'permanent');
    $d = Delivery::enqueue($event, null, 'organizer');
    for ($i = 0; $i < 3; $i++) {
        DB::table('jobs')->update(['available_at' => 0]);
        $run();
    }
    check($d->fresh()->status === 'failed' && DB::table('failed_jobs')->count() === 1, 'Permanent failure not visible');
    $exception = DB::table('failed_jobs')->value('exception');
    check(! str_contains($exception, 'test@example.test') && ! str_contains($exception, $token), 'SMTP secrets in failure diagnostics');
    file_put_contents("$dir/mode", 'hold');
    $d = Delivery::enqueue($event, null, 'organizer');
    $p = new Process([PHP_BINARY, __FILE__, 'worker', $dir]);
    $p->start();
    $deadline = microtime(true) + 5;
    while ($count() < 3 && microtime(true) < $deadline) {
        usleep(1000);
    }check($count() === 3, 'Worker never reached SMTP acceptance');
    $p->stop(0, 9);
    check($d->fresh()->status === 'pending', 'Killed worker committed incomplete transaction');
    file_put_contents("$dir/mode", 'accept');
    DB::table('jobs')->update(['reserved_at' => time() - 120, 'available_at' => 0]);
    $run();
    check($d->fresh()->status === 'accepted' && $count() === 4, 'Killed worker did not recover');
    // SMTP cannot promise exactly-once: both messages must contain the same still-valid access link.
    $messages = file("$dir/messages", FILE_IGNORE_NEW_LINES);
    check(str_contains(quoted_printable_decode(json_decode($messages[2])), '/login') && str_contains(quoted_printable_decode(json_decode($messages[3])), '/login'), 'Retry changed link');
    $d = Delivery::enqueue($event, null, 'organizer');
    $event->delete();
    $run();
    check($count() === 4, 'Deleted event still mailed');
    echo "SMTP: success, temporary retry, permanent failure, worker kill/recovery, safe duplicate, deletion passed\n";
} finally {
    $smtp->stop(0);
    DB::disconnect();
    foreach (glob("$dir/*") as $f) {
        unlink($f);
    }rmdir($dir);
}
