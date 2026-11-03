<?php

namespace App\Http\Controllers;

use App\Jobs\SendAccessLink;
use App\Models\Exchange;
use App\Models\Participant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

class ParticipantController extends Controller
{
    public function store(Request $request, Exchange $exchange)
    {
        $exchange->ensureActive();
        $data = $request->validate(['name' => ['required', 'string', 'max:80'], 'email' => ['required', 'email', 'max:254'], 'wishes' => ['nullable', 'string', 'max:1000']]);
        $email = Str::lower($data['email']);
        $rateKey = 'join:'.$exchange->id.':'.hash('sha256', $email);
        if (RateLimiter::tooManyAttempts($rateKey, 3)) {
            throw ValidationException::withMessages(['email' => 'Un lien a déjà été demandé. Consultez vos e-mails ou réessayez dans une heure.']);
        }
        RateLimiter::hit($rateKey, 3600);
        DB::transaction(function () use ($exchange, $data, $email) {
            $locked = Exchange::whereKey($exchange->id)->lockForUpdate()->firstOrFail();
            if ($locked->drawn_at) {
                throw ValidationException::withMessages(['email' => 'Les inscriptions sont closes : le tirage a déjà eu lieu.']);
            }
            $person = $locked->participants()->where('email', $email)->first();
            if (! $person) {
                if ($locked->participants()->count() >= 100) {
                    throw ValidationException::withMessages(['email' => 'Ce groupe a atteint sa limite de 100 participants.']);
                }
                $token = Str::random(64);
                $person = $locked->participants()->create([...$data, 'email' => $email, 'access_hash' => hash('sha256', $token), 'access_token' => $token]);
            }
            SendAccessLink::dispatch($person->email, $locked->name, route('access.show', $person->access_token), 'join');
        });

        return to_route('exchanges.pending', $exchange);
    }

    private function current(Request $request, Exchange $exchange): Participant
    {
        $exchange->ensureActive();
        $id = $request->session()->get("participants.{$exchange->id}");
        abort_unless($id, 403);

        return $exchange->participants()->findOrFail($id);
    }

    public function show(Request $request, Exchange $exchange)
    {
        $person = $this->current($request, $exchange);

        return Inertia::render('Participant', ['exchange' => $exchange->publicData(), 'participant' => $person->only('name', 'wishes'), 'recipient' => $person->revealed_at ? $person->recipient?->only('name', 'wishes') : null]);
    }

    public function update(Request $request, Exchange $exchange)
    {
        $person = $this->current($request, $exchange);
        $data = $request->validate(['name' => ['required', 'string', 'max:80'], 'wishes' => ['nullable', 'string', 'max:1000']]);
        DB::transaction(function () use ($exchange, $person, $data) {
            $locked = Exchange::whereKey($exchange->id)->lockForUpdate()->firstOrFail();
            abort_if($locked->drawn_at, 409);
            $person->update($data);
        });

        return back()->with('notice', 'Vos envies sont enregistrées.');
    }

    public function reveal(Request $request, Exchange $exchange)
    {
        $person = $this->current($request, $exchange);
        abort_unless($exchange->drawn_at && $person->recipient_id, 409);
        if (! $person->revealed_at) {
            $person->update(['revealed_at' => now()]);
        }

        return back();
    }
}
