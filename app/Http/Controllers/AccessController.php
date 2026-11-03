<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Exchange;
use App\Models\Participant;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AccessController extends Controller
{
    private function resolve(string $token): Exchange|Participant
    {
        abort_unless(strlen($token) === 64, 404);
        $hash = hash('sha256', $token);
        $actor = Exchange::where('access_hash', $hash)->first() ?? Participant::where('access_hash', $hash)->first();
        abort_unless($actor !== null, 404);
        ($actor instanceof Exchange ? $actor : $actor->exchange)->ensureActive();

        return $actor;
    }

    public function show(string $token): RedirectResponse|Response
    {
        $actor = $this->resolve($token);
        if ($actor instanceof Exchange) {
            return to_route('login');
        }
        $exchange = $actor->exchange;

        return Inertia::render('Access', ['eventName' => $exchange->name, 'action' => route('access.activate', $token), 'organizer' => false]);
    }

    public function activate(Request $request, string $token): RedirectResponse
    {
        $actor = $this->resolve($token);
        $exchange = $actor instanceof Exchange ? $actor : $actor->exchange;

        return $exchange->locked(function ($locked) use ($request, $token) {
            $actor = $this->resolve($token);
            $request->session()->regenerate();
            if ($actor instanceof Exchange) {
                return to_route('login');
            }
            $actor->update(['confirmed_at' => $actor->confirmed_at ?? now(), 'locale' => app()->getLocale()]);
            $request->session()->put("participants.{$actor->exchange_id}", ['id' => $actor->id, 'version' => $actor->access_version]);

            return to_route('participants.show', $locked);
        });
    }

    public function logout(Request $request): RedirectResponse
    {
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        Inertia::clearHistory();

        return to_route('home');
    }
}
