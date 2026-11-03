<?php

// Real processes, dedicated SQLite file or temporary PostgreSQL schema. No live database reset.
require __DIR__.'/../../vendor/autoload.php';
use App\Actions\Auth\VerifyLoginCode;
use App\Actions\Exchanges\DeleteExchange;
use App\Actions\Exchanges\DrawExchange;
use App\Actions\Exchanges\ReopenExchange;
use App\Actions\Exchanges\UpdateExchange;
use App\Actions\Participants\RegisterParticipant;
use App\Actions\Participants\RevealRecipient;
use App\Models\Delivery;
use App\Models\Exchange;
use App\Services\SessionAccess;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Session\ArraySessionHandler;
use Illuminate\Session\Store;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\Process\Process;

function boot(string $directory): void
{
    $app = require __DIR__.'/../../bootstrap/app.php';
    $app->make(Kernel::class)->bootstrap();
    config(require "$directory/config.php");
    DB::purge();
}
function check(bool $condition, string $message): void
{
    if (! $condition) {
        throw new RuntimeException($message);
    }
}
if (($argv[1] ?? '') === 'actor') {
    [$script, $mode, $directory, $action, $eventId, $personId, $slot] = $argv;
    boot($directory);
    $event = Exchange::findOrFail($eventId);
    $session = new Store('race', new ArraySessionHandler(120));
    $session->start();
    $session->put("managed.$eventId", 1);
    $session->put('organizer_email', $event->organizer_email);
    $session->put("participants.$eventId", ['id' => (int) $personId, 'version' => 1]);
    $session->put('login', ['email' => $event->organizer_email, 'challenge' => str_repeat('c', 64)]);
    $request = Request::create('/', 'POST', ['code' => '123456', 'confirm' => 1, 'name' => 'Late', 'email' => 'late@example.test', 'gift_date' => now()->addMonth()->toDateString(), 'budget' => 25]);
    $request->setLaravelSession($session);
    app()->instance('request', $request);
    file_put_contents("$directory/ready$slot", '1');
    $deadline = microtime(true) + 15;
    while (! file_exists("$directory/go") && microtime(true) < $deadline) {
        usleep(1000);
    }
    try {
        $access = new SessionAccess($session);
        match ($action) {
            'verify' => app(VerifyLoginCode::class)->execute($event->organizer_email, str_repeat('c', 64), '123456'),
            'draw' => app(DrawExchange::class)->execute($event, $access),
            'reveal' => app(RevealRecipient::class)->execute($event, $access),
            'reopen' => app(ReopenExchange::class)->execute($event, $access),
            'join' => app(RegisterParticipant::class)->execute($event, ['name' => 'Late', 'email' => 'late@example.test'], 'en'),
            'delete' => app(DeleteExchange::class)->execute($event, $access),
            'update' => app(UpdateExchange::class)->execute($event, $access, ['name' => 'Updated', 'gift_date' => now()->addMonth()->toDateString(), 'budget' => 25]),
        };
        $status = 200;
    } catch (ValidationException $e) {
        $status = 422;
    } catch (HttpExceptionInterface $e) {
        $status = $e->getStatusCode();
    } catch (ModelNotFoundException $e) {
        $status = 404;
    }
    echo json_encode(['status' => $status]);
    exit;
}
$directory = sys_get_temp_dir().'/santa-race-'.bin2hex(random_bytes(8));
mkdir($directory, 0700);
$app = require __DIR__.'/../../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();
$driver = getenv('DB_CONNECTION') ?: 'sqlite';
check(in_array($driver, ['sqlite', 'pgsql']), 'Unsupported test driver');
$schema = 'santa_test_'.bin2hex(random_bytes(8));
$settings = ['app.env' => 'testing', 'app.key' => 'base64:'.base64_encode(random_bytes(32)), 'cache.default' => 'array', 'queue.default' => 'database', 'database.default' => $driver];
if ($driver === 'sqlite') {
    touch("$directory/database.sqlite");
    $settings['database.connections.sqlite.database'] = "$directory/database.sqlite";
    $settings['database.connections.sqlite.url'] = null;
} else {
    // Explicit dedicated test database is required. Never use the project default DB.
    check(str_contains((string) getenv('DB_DATABASE'), 'test'), 'DB_DATABASE must name an isolated test database');
    DB::connection('pgsql')->statement("CREATE SCHEMA $schema");
    $settings['database.connections.pgsql.search_path'] = $schema;
}
file_put_contents("$directory/config.php", '<?php return '.var_export($settings, true).';');
chmod("$directory/config.php", 0600);
config($settings);
DB::purge();
try {
    Artisan::call('migrate', ['--force' => true]);
    foreach ([['draw', 'draw'], ['reveal', 'reopen'], ['join', 'draw'], ['delete', 'update'], ['verify', 'verify']] as $pair) {
        for ($round = 0; $round < 5; $round++) {
            $token = Str::random(64);
            $event = Exchange::create(['uuid' => (string) Str::uuid(), 'name' => 'Race', 'organizer_email' => 'test@example.test', 'access_hash' => hash('sha256', $token), 'access_token' => $token, 'gift_date' => now()->addMonth()->toDateString(), 'budget' => 20]);
            $people = collect(range(1, 3))->map(function ($n) use ($event) {
                $token = Str::random(64);

                return $event->participants()->create(['name' => "P$n", 'email' => "p$n@example.test", 'access_hash' => hash('sha256', $token), 'access_token' => $token, 'confirmed_at' => now()]);
            });
            if ($pair[0] === 'reveal') {
                $event->update(['drawn_at' => now(), 'draw_version' => 1]);
                foreach ($people as $i => $p) {
                    $p->update(['recipient_id' => $people[($i + 1) % 3]->id]);
                }
            }
            if ($pair[0] === 'verify') {
                DB::table('login_codes')->insert(['email_hash' => hash('sha256', $event->organizer_email), 'challenge' => str_repeat('c', 64), 'code_hash' => hash_hmac('sha256', str_repeat('c', 64).':123456', config('app.key')), 'attempts' => 0, 'expires_at' => now()->addMinutes(10), 'sent_at' => now()]);
            }
            foreach (['go', 'ready0', 'ready1'] as $file) {
                if (file_exists("$directory/$file")) {
                    unlink("$directory/$file");
                }
            }
            $processes = [];
            foreach ($pair as $slot => $action) {
                $p = new Process([PHP_BINARY, __FILE__, 'actor', $directory, $action, (string) $event->id, (string) $people[0]->id, (string) $slot]);
                $p->setTimeout(25);
                $p->start();
                $processes[] = $p;
            }
            $deadline = microtime(true) + 15;
            while ((! file_exists("$directory/ready0") || ! file_exists("$directory/ready1")) && microtime(true) < $deadline) {
                usleep(1000);
            }
            check(file_exists("$directory/ready0") && file_exists("$directory/ready1"), 'Actors did not reach barrier');
            file_put_contents("$directory/go", '1');
            $statuses = [];
            foreach ($processes as $p) {
                $p->wait();
                check($p->isSuccessful(), $p->getErrorOutput().$p->getOutput());
                $statuses[] = json_decode($p->getOutput(), true, 512, JSON_THROW_ON_ERROR)['status'];
            }
            $fresh = $event->fresh();
            if ($pair[0] === 'verify') {
                sort($statuses);
                check($statuses === [200, 422], 'Code consumed more than once');
                check(DB::table('login_codes')->count() === 0, 'Consumed code retained');
            }
            if ($pair[0] === 'draw') {
                sort($statuses);
                check($statuses === [200, 422], 'Double draw did not serialize');
                check(Delivery::where('exchange_id', $event->id)->count() === 3, 'Duplicate delivery intents');
            }
            if ($pair[0] === 'reveal') {
                check(in_array($statuses, [[200, 422], [409, 200]], true), 'Reveal/reopen conflict');
                check(! (! $fresh->drawn_at && $fresh->participants()->whereNotNull('revealed_at')->exists()), 'Reopened revealed event');
            }
            if ($pair[0] === 'join') {
                check(in_array($statuses, [[200, 422], [422, 200]], true), 'Join/draw conflict');
            }
            if ($pair[0] === 'delete') {
                check($statuses[0] === 200 && in_array($statuses[1], [200, 404]), 'Delete/update conflict');
                check($fresh === null && Delivery::where('exchange_id', $event->id)->count() === 0, 'Deletion incomplete');
            }
            if ($fresh?->drawn_at) {
                $pairs = $fresh->participants()->pluck('recipient_id', 'id')->all();
                check(count(array_unique($pairs)) === count($pairs) && ! in_array(null, $pairs, true), 'Partial draw');
                foreach ($pairs as $a => $b) {
                    check($a !== $b, 'Self gift');
                }
            }
            $fresh?->delete();
        }
        echo implode(' / ', $pair).": 5 races passed ($driver)\n";
    }
} finally {
    DB::disconnect();
    if ($driver === 'pgsql') {
        config(['database.connections.pgsql.search_path' => 'public']);
        DB::purge();
        DB::statement("DROP SCHEMA $schema CASCADE");
    }
    foreach (glob("$directory/*") as $file) {
        unlink($file);
    } rmdir($directory);
}
