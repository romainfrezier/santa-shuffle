<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\Exchanges\AddExclusion;
use App\Actions\Exchanges\CreateExchange;
use App\Actions\Exchanges\DrawExchange;
use App\Actions\Exchanges\RemoveExclusion;
use App\Actions\Exchanges\RemoveParticipant;
use App\Http\Requests\ConfirmEventRequest;
use App\Http\Requests\ExchangeRequest;
use App\Models\Exchange;
use App\Services\SessionAccess;
use App\ViewModels\ExchangeView;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ExchangeController extends Controller
{
    public function store(ExchangeRequest $request, CreateExchange $create): RedirectResponse
    {
        $exchange = $create->execute($request->validated(), $request->session()->get('organizer_email'), app()->getLocale());
        $request->session()->regenerate();
        $request->session()->put("managed.{$exchange->id}", $exchange->access_version);

        return to_route('exchanges.manage', $exchange)->with('notice', __('messages.event_created'));
    }

    public function show(Exchange $exchange): Response
    {
        $exchange->ensureActive();

        return Inertia::render('Join', ['exchange' => ExchangeView::summary($exchange), 'count' => $exchange->participants()->whereNotNull('confirmed_at')->count()]);
    }

    public function manage(Request $request, Exchange $exchange): Response
    {
        (new SessionAccess($request->session()))->authorizeManager($exchange, true);

        return Inertia::render('Manage', ExchangeView::management($exchange));
    }

    public function exclude(Request $request, Exchange $exchange, AddExclusion $action): RedirectResponse
    {
        $action->execute($exchange, new SessionAccess($request->session()), $request->only('giver_id', 'recipient_id'));

        return to_route('exchanges.manage', $exchange)->with('notice', __('messages.exclusion_added'));
    }

    public function removeExclusion(Request $request, Exchange $exchange, int $exclusion, RemoveExclusion $action): RedirectResponse
    {
        $action->execute($exchange, new SessionAccess($request->session()), $exclusion);

        return to_route('exchanges.manage', $exchange);
    }

    public function removeParticipant(Request $request, Exchange $exchange, int $participant, RemoveParticipant $action): RedirectResponse
    {
        $action->execute($exchange, new SessionAccess($request->session()), $participant);

        return to_route('exchanges.manage', $exchange)->with('notice', __('messages.participant_removed'));
    }

    public function draw(ConfirmEventRequest $request, Exchange $exchange, DrawExchange $draw): RedirectResponse
    {
        $draw->execute($exchange, new SessionAccess($request->session()));

        return to_route('exchanges.manage', $exchange)->with('notice', __('messages.draw_completed'));
    }
}
