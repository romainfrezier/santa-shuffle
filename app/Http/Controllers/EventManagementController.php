<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\Exchanges\CancelExchange;
use App\Actions\Exchanges\DeleteExchange;
use App\Actions\Exchanges\ReopenExchange;
use App\Actions\Exchanges\ResendInvitation;
use App\Actions\Exchanges\RotateAccess;
use App\Actions\Exchanges\UpdateExchange;
use App\Actions\Participants\InviteParticipant;
use App\Http\Requests\ConfirmEventRequest;
use App\Http\Requests\ExchangeRequest;
use App\Http\Requests\ParticipantRequest;
use App\Models\Exchange;
use App\Services\SessionAccess;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;

class EventManagementController extends Controller
{
    public function invite(ParticipantRequest $request, Exchange $exchange, InviteParticipant $invite): RedirectResponse
    {
        $invite->execute($exchange, new SessionAccess($request->session()), $request->validated());

        return to_route('exchanges.manage', $exchange)->with('notice', __('messages.invitation_sent'));
    }

    public function update(ExchangeRequest $request, Exchange $exchange, UpdateExchange $action): RedirectResponse
    {
        $action->execute($exchange, new SessionAccess($request->session()), $request->validated());

        return to_route('exchanges.manage', $exchange)->with('notice', __('messages.event_updated'));
    }

    public function reopen(ConfirmEventRequest $request, Exchange $exchange, ReopenExchange $action): RedirectResponse
    {
        $action->execute($exchange, new SessionAccess($request->session()));

        return to_route('exchanges.manage', $exchange)->with('notice', __('messages.event_reopened'));
    }

    public function cancel(ConfirmEventRequest $request, Exchange $exchange, CancelExchange $action): RedirectResponse
    {
        $action->execute($exchange, new SessionAccess($request->session()));

        return to_route('exchanges.manage', $exchange)->with('notice', __('messages.event_cancelled'));
    }

    public function destroy(ConfirmEventRequest $request, Exchange $exchange, DeleteExchange $action): RedirectResponse
    {
        $action->execute($exchange, new SessionAccess($request->session()));
        $request->session()->forget(["managed.{$exchange->id}", "participants.{$exchange->id}"]);
        Inertia::clearHistory();

        return to_route('home')->with('notice', __('messages.event_deleted'));
    }

    public function resend(Request $request, Exchange $exchange, int $participant, ResendInvitation $action): RedirectResponse
    {
        $action->execute($exchange, new SessionAccess($request->session()), $participant);

        return to_route('exchanges.manage', $exchange)->with('notice', __('messages.delivery_requested'));
    }

    public function rotate(ConfirmEventRequest $request, Exchange $exchange, RotateAccess $action, ?int $participant = null): RedirectResponse
    {
        $version = $action->execute($exchange, new SessionAccess($request->session()), $participant);
        if ($participant === null) {
            $request->session()->regenerate();
            $request->session()->put("managed.{$exchange->id}", $version);
        }
        Inertia::clearHistory();

        return to_route('exchanges.manage', $exchange)->with('notice', $participant === null ? __('messages.sessions_revoked') : __('messages.link_rotated'));
    }
}
