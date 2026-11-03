<?php

namespace App\Http\Controllers;

use App\Jobs\SendAccessLink;
use App\Models\Exchange;
use App\Services\DrawNames;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

class ExchangeController extends Controller
{
    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'organizer_email' => ['required', 'email', 'max:254'],
            'gift_date' => ['required', 'date_format:Y-m-d', 'after_or_equal:today', 'before_or_equal:'.now()->addYear()->format('Y-m-d')],
            'budget' => ['required', 'integer', 'min:1', 'max:10000'],
            'message' => ['nullable', 'string', 'max:1000'],
        ]);
        $token = Str::random(64);
        $exchange = DB::transaction(function () use ($data, $token) {
            $exchange = Exchange::create([...$data, 'organizer_email' => Str::lower($data['organizer_email']), 'uuid' => (string) Str::uuid(), 'access_hash' => hash('sha256', $token)]);
            SendAccessLink::dispatch($exchange->organizer_email, $exchange->name, route('access.show', $token), 'organizer');

            return $exchange;
        });
        $request->session()->regenerate();
        $request->session()->put("managed.{$exchange->id}", true);

        return to_route('exchanges.manage', $exchange)->with('notice', 'Votre Secret Santa est créé. Votre lien de gestion vous est envoyé par e-mail.');
    }

    public function show(Exchange $exchange)
    {
        $exchange->ensureActive();

        return Inertia::render('Join', ['exchange' => $exchange->publicData(), 'count' => $exchange->participants()->whereNotNull('confirmed_at')->count()]);
    }

    public function manage(Request $request, Exchange $exchange)
    {
        $exchange->authorizeManager($request);

        return Inertia::render('Manage', [
            'exchange' => $exchange->publicData(),
            'inviteUrl' => route('exchanges.show', $exchange),
            'participants' => $exchange->participants()->orderBy('id')->get()->map(fn ($p) => ['id' => $p->id, 'name' => $p->name, 'confirmed' => $p->confirmed_at !== null, 'revealed' => $p->revealed_at !== null]),
            'exclusions' => DB::table('exclusions')->where('exchange_id', $exchange->id)->get(['id', 'giver_id', 'recipient_id']),
        ]);
    }

    public function exclude(Request $request, Exchange $exchange)
    {
        $exchange->authorizeManager($request);
        DB::transaction(function () use ($exchange, $request) {
            $locked = Exchange::whereKey($exchange->id)->lockForUpdate()->firstOrFail();
            abort_if($locked->drawn_at, 409);
            $member = Rule::exists('participants', 'id')->where('exchange_id', $exchange->id);
            $data = $request->validate(['giver_id' => ['required', 'integer', $member], 'recipient_id' => ['required', 'integer', 'different:giver_id', $member]]);
            DB::table('exclusions')->updateOrInsert(['exchange_id' => $exchange->id, ...$data], []);
        });

        return back()->with('notice', 'Exclusion ajoutée. Elle s’applique dans le sens indiqué.');
    }

    public function removeExclusion(Request $request, Exchange $exchange, int $exclusion)
    {
        $exchange->authorizeManager($request);
        DB::transaction(function () use ($exchange, $exclusion) {
            $locked = Exchange::whereKey($exchange->id)->lockForUpdate()->firstOrFail();
            abort_if($locked->drawn_at, 409);
            DB::table('exclusions')->where('exchange_id', $exchange->id)->where('id', $exclusion)->delete();
        });

        return back();
    }

    public function removeParticipant(Request $request, Exchange $exchange, int $participant)
    {
        $exchange->authorizeManager($request);
        DB::transaction(function () use ($exchange, $participant) {
            $locked = Exchange::whereKey($exchange->id)->lockForUpdate()->firstOrFail();
            abort_if($locked->drawn_at, 409);
            $exchange->participants()->findOrFail($participant)->delete();
        });

        return back()->with('notice', 'Participation retirée.');
    }

    public function draw(Request $request, Exchange $exchange, DrawNames $draw)
    {
        $exchange->authorizeManager($request);
        $request->validate(['confirm' => ['accepted']]);
        DB::transaction(function () use ($exchange, $draw) {
            $locked = Exchange::whereKey($exchange->id)->lockForUpdate()->firstOrFail();
            if ($locked->drawn_at) {
                throw ValidationException::withMessages(['draw' => 'Le tirage a déjà eu lieu. Les attributions sont conservées.']);
            }
            $people = $locked->participants()->get();
            if ($people->contains(fn ($p) => ! $p->confirmed_at)) {
                throw ValidationException::withMessages(['draw' => 'Chaque participant doit confirmer son e-mail avant le tirage.']);
            }
            $excluded = DB::table('exclusions')->where('exchange_id', $exchange->id)->get()->groupBy('giver_id')->map(fn ($rows) => $rows->pluck('recipient_id')->map(fn ($id) => (int) $id)->all())->all();
            $pairs = $draw->assign($people->modelKeys(), $excluded);
            foreach ($people as $person) {
                $person->update(['recipient_id' => $pairs[$person->id]]);
                SendAccessLink::dispatch($person->email, $locked->name, route('access.show', $person->access_token), 'draw');
            }
            $locked->update(['drawn_at' => now()]);
        });

        return back()->with('notice', 'Le tirage est terminé ! Les enveloppes sont prêtes et les e-mails sont en cours d’envoi.');
    }
}
