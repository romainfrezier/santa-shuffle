<?php

use App\Http\Controllers\AccessController;
use App\Http\Controllers\EventManagementController;
use App\Http\Controllers\ExchangeController;
use App\Http\Controllers\OrganizerController;
use App\Http\Controllers\ParticipantController;
use App\Http\Middleware\SetLocale;
use App\Models\Exchange;
use App\ViewModels\ExchangeView;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

Route::get('/', fn () => Inertia::render('Home'))->name('home');
Route::get('/login', [OrganizerController::class, 'login'])->name('login');
Route::post('/login/code', [OrganizerController::class, 'send'])->middleware('throttle:login-send');
Route::post('/login/verify', [OrganizerController::class, 'verify'])->middleware('throttle:login-verify');
Route::get('/my-events', [OrganizerController::class, 'events'])->name('organizer.events');
Route::get('/create', fn () => request()->session()->has('organizer_email') ? Inertia::render('Create') : to_route('login'))->name('exchanges.create');
Route::post('/events', [ExchangeController::class, 'store'])->middleware('throttle:create-exchange')->name('exchanges.store');
Route::get('/access/{token}', [AccessController::class, 'show'])->middleware('throttle:access-link')->name('access.show');
Route::post('/access/{token}', [AccessController::class, 'activate'])->middleware('throttle:access-link')->name('access.activate');
Route::post('/logout', [AccessController::class, 'logout'])->name('logout');
Route::prefix('/events/{exchange}')->whereUuid('exchange')->group(function () {
    Route::middleware('throttle:manage-event')->group(function () {
        Route::patch('/', [EventManagementController::class, 'update'])->name('exchanges.update');
        Route::delete('/', [EventManagementController::class, 'destroy'])->name('exchanges.destroy');
        Route::post('/invite', [EventManagementController::class, 'invite'])->name('exchanges.invite');
        Route::post('/reopen', [EventManagementController::class, 'reopen'])->name('exchanges.reopen');
        Route::post('/cancel', [EventManagementController::class, 'cancel'])->name('exchanges.cancel');
        Route::post('/rotate', [EventManagementController::class, 'rotate'])->name('exchanges.rotate');
        Route::post('/participants/{participant}/resend', [EventManagementController::class, 'resend'])->name('participants.resend');
        Route::post('/participants/{participant}/rotate', [EventManagementController::class, 'rotate'])->name('participants.rotate');
    });
    Route::get('/', [ExchangeController::class, 'show'])->name('exchanges.show');
    Route::get('/pending', function (Exchange $exchange) {
        $exchange->ensureActive();

        return Inertia::render('Pending', ['exchange' => ExchangeView::summary($exchange)]);
    })->name('exchanges.pending');
    Route::post('/join', [ParticipantController::class, 'store'])->middleware('throttle:join-exchange')->name('participants.store');
    Route::get('/manage', [ExchangeController::class, 'manage'])->name('exchanges.manage');
    Route::post('/exclusions', [ExchangeController::class, 'exclude'])->name('exclusions.store');
    Route::delete('/exclusions/{exclusion}', [ExchangeController::class, 'removeExclusion'])->name('exclusions.destroy');
    Route::delete('/participants/{participant}', [ExchangeController::class, 'removeParticipant'])->name('participants.destroy');
    Route::post('/draw', [ExchangeController::class, 'draw'])->middleware('throttle:draw-exchange')->name('exchanges.draw');
    Route::get('/me', [ParticipantController::class, 'show'])->name('participants.show');
    Route::patch('/me', [ParticipantController::class, 'update'])->name('participants.update');
    Route::post('/reveal', [ParticipantController::class, 'reveal'])->name('participants.reveal');
});

Route::post('/locale', function (Request $request) {
    $data = $request->validate(['locale' => ['required', Rule::in(SetLocale::SUPPORTED)]]);
    $request->session()->put('locale', $data['locale']);

    return back();
})->name('locale.update');

// Keep unknown pages in the web stack so error pages can use the session language.
Route::fallback(fn () => abort(404));
