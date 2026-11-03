<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\Auth\SendLoginCode;
use App\Actions\Auth\VerifyLoginCode;
use App\Http\Requests\LoginCodeRequest;
use App\Http\Requests\VerifyLoginCodeRequest;
use App\Models\Exchange;
use App\ViewModels\ExchangeView;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class OrganizerController extends Controller
{
    public function login(Request $request): RedirectResponse|Response
    {
        if ($request->session()->has('organizer_email')) {
            return to_route('organizer.events');
        }

        return Inertia::render('Login', ['pendingEmail' => $request->session()->get('login.email')]);
    }

    public function send(LoginCodeRequest $request, SendLoginCode $send): RedirectResponse
    {
        $request->session()->put('login', $send->execute($request->validated('email')));

        return to_route('login')->with('notice', __('messages.code_sent'));
    }

    public function verify(VerifyLoginCodeRequest $request, VerifyLoginCode $verify): RedirectResponse
    {
        $email = $request->session()->get('login.email');
        $verify->execute($email, $request->session()->get('login.challenge'), $request->validated('code'));
        $request->session()->regenerate(true);
        $request->session()->forget(['login', 'managed']);
        $request->session()->put('organizer_email', $email);
        $request->session()->put('managed', Exchange::where('organizer_email', $email)->pluck('access_version', 'id')->all());

        Inertia::clearHistory();

        return to_route('organizer.events');
    }

    public function events(Request $request): RedirectResponse|Response
    {
        $email = $request->session()->get('organizer_email');
        if (! $email) {
            return to_route('login');
        }
        $events = Exchange::where('organizer_email', $email)->whereDate('gift_date', '>', now()->subDays(30)->toDateString())->latest('id')->get()
            ->filter(fn ($event) => $request->session()->get("managed.{$event->id}") === $event->access_version)
            ->map(fn ($event) => ExchangeView::summary($event))->values();

        return Inertia::render('Events', ['events' => $events]);
    }
}
