<?php

use App\Http\Controllers\AccessController;
use App\Http\Controllers\ExchangeController;
use App\Http\Controllers\ParticipantController;
use App\Models\Exchange;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', fn () => Inertia::render('Home'))->name('home');
Route::get('/create', fn () => Inertia::render('Create'))->name('exchanges.create');
Route::post('/events', [ExchangeController::class, 'store'])->middleware('throttle:create-exchange')->name('exchanges.store');
Route::get('/access/{token}', [AccessController::class, 'show'])->middleware('throttle:access-link')->name('access.show');
Route::post('/access/{token}', [AccessController::class, 'activate'])->middleware('throttle:access-link')->name('access.activate');
Route::post('/logout', [AccessController::class, 'logout'])->name('logout');
Route::prefix('/events/{exchange}')->group(function () {
    Route::get('/', [ExchangeController::class, 'show'])->name('exchanges.show');
    Route::get('/pending', function (Exchange $exchange) {
        $exchange->ensureActive();

        return Inertia::render('Pending', ['exchange' => $exchange->publicData()]);
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
