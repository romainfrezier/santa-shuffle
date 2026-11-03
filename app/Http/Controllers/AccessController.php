<?php

namespace App\Http\Controllers;

use App\Models\Exchange;
use App\Models\Participant;
use Illuminate\Http\Request;
use Inertia\Inertia;

class AccessController extends Controller
{
    private function resolve(string $token): Exchange|Participant
    {
        abort_unless(strlen($token) === 64, 404);
        $hash = hash('sha256', $token);
        $actor = Exchange::where('access_hash', $hash)->first() ?? Participant::where('access_hash', $hash)->first();
        abort_unless($actor, 404);
        ($actor instanceof Exchange ? $actor : $actor->exchange)->ensureActive();

        return $actor;
    }

    public function show(string $token)
    {
        $actor = $this->resolve($token);
        $exchange = $actor instanceof Exchange ? $actor : $actor->exchange;

        return Inertia::render('Access', ['eventName' => $exchange->name, 'action' => route('access.activate', $token), 'organizer' => $actor instanceof Exchange]);
    }

    public function activate(Request $request, string $token)
    {
        $actor = $this->resolve($token);
        $request->session()->regenerate();
        if ($actor instanceof Exchange) {
            $request->session()->put("managed.{$actor->id}", true);

            return to_route('exchanges.manage', $actor);
        }
        if (! $actor->confirmed_at) {
            $actor->update(['confirmed_at' => now()]);
        }
        $request->session()->put("participants.{$actor->exchange_id}", $actor->id);

        return to_route('participants.show', $actor->exchange);
    }

    public function logout(Request $request)
    {
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        Inertia::clearHistory();

        return to_route('home');
    }
}
