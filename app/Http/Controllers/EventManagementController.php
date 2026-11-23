<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\Exchanges\CancelExchange;
use App\Actions\Exchanges\ConfirmParticipant;
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
use Illuminate\Validation\ValidationException;
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

    public function confirm(Request $request, Exchange $exchange, int $participant, ConfirmParticipant $action): RedirectResponse
    {
        $access = new SessionAccess($request->session());
        $access->authorizeManager($exchange);
        $data = $request->validate(['email' => ['required', 'email', 'max:254']]);
        $action->execute($exchange, $access, $participant, $data['email']);

        return to_route('exchanges.manage', $exchange)->with('notice', __('messages.participant_confirmed'));
    }

    public function rename(Request $request, Exchange $exchange, int $participant): RedirectResponse
    {
        $access = new SessionAccess($request->session());
        $access->authorizeManager($exchange);
        $data = $request->validate(['name' => ['required', 'string', 'max:80'], 'email' => ['required', 'email', 'max:254']]);
        $exchange->locked(function (Exchange $locked) use ($access, $participant, $data) {
            $access->authorizeManager($locked);
            abort_if($locked->drawn_at !== null, 409);
            $person = $locked->participants()->findOrFail($participant);
            if (mb_strtolower($data['email']) !== $person->email) {
                throw ValidationException::withMessages(['email' => __('messages.confirm_email_mismatch')]);
            }
            $person->update(['name' => $data['name']]);
        });

        return to_route('exchanges.manage', $exchange);
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
