<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\Participants\RegisterParticipant;
use App\Actions\Participants\RevealRecipient;
use App\Actions\Participants\UpdateParticipant;
use App\Http\Requests\ParticipantProfileRequest;
use App\Http\Requests\ParticipantRequest;
use App\Models\Exchange;
use App\Services\SessionAccess;
use App\ViewModels\ExchangeView;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ParticipantController extends Controller
{
    public function store(ParticipantRequest $request, Exchange $exchange, RegisterParticipant $register): RedirectResponse
    {
        $register->execute($exchange, $request->validated(), app()->getLocale());

        return to_route('exchanges.pending', $exchange);
    }

    public function show(Request $request, Exchange $exchange): Response
    {
        $person = (new SessionAccess($request->session()))->participant($exchange);

        return Inertia::render('Participant', ExchangeView::participant($exchange, $person));
    }

    public function update(ParticipantProfileRequest $request, Exchange $exchange, UpdateParticipant $action): RedirectResponse
    {
        $action->execute($exchange, new SessionAccess($request->session()), [...$request->validated(), 'locale' => app()->getLocale()]);

        return to_route('participants.show', $exchange)->with('notice', __('messages.wishes_saved'));
    }

    public function reveal(Request $request, Exchange $exchange, RevealRecipient $action): RedirectResponse
    {
        $action->execute($exchange, new SessionAccess($request->session()));

        return to_route('participants.show', $exchange);
    }
}
