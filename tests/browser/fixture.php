<?php

use App\Models\Exchange;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

require __DIR__.'/../../vendor/autoload.php';
$app = require __DIR__.'/../../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();
if (app()->environment() !== 'e2e' || ! str_contains(config('database.connections.sqlite.database'), 'santa-browser-')) {
    throw new RuntimeException('Synthetic browser database required');
}

if (($argv[1] ?? '') === 'reset-rate-limits') {
    Cache::flush();
    echo json_encode(['cleared' => true]);
    exit;
}

if (($argv[1] ?? '') === 'participant') {
    $p = Exchange::where('uuid', $argv[2])->firstOrFail()->participants()->where('email', $argv[3])->firstOrFail();
    echo json_encode(['link' => route('access.show', $p->access_token)]);
    exit;
}
$token = Str::random(64);
$event = Exchange::create(['uuid' => (string) Str::uuid(), 'name' => $argv[2] ?? 'Équipe navigateur', 'organizer_email' => ($email = $argv[3] ?? 'owner-'.Str::lower(Str::random(10)).'@example.test'), 'access_token' => $token, 'access_hash' => hash('sha256', $token), 'gift_date' => now()->addMonth()->toDateString(), 'budget' => 20]);
$links = [];
foreach (range(1, 3) as $n) {
    $t = Str::random(64);
    $event->participants()->create(['name' => "Personne $n", 'email' => "p$n@example.test", 'wishes' => '<img src=x onerror=window.pwned=1>', 'access_hash' => hash('sha256', $t), 'access_token' => $t, 'confirmed_at' => now()]);
    $links[] = route('access.show', $t);
}
echo json_encode(['email' => $email, 'uuid' => $event->uuid, 'manager' => route('access.show', $token), 'people' => $links]);
