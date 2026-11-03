<?php

declare(strict_types=1);

namespace App\Actions\Participants;

use App\Models\Delivery;
use App\Models\Exchange;
use App\Services\DeliveryQuota;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

final readonly class RegisterParticipant
{
    public function __construct(private DeliveryQuota $quota) {}

    public function execute(Exchange $exchange, array $data, string $locale): void
    {
        $email = Str::lower($data['email']);
        $rateKey = 'join:'.$exchange->id.':'.hash('sha256', $email);
        if (RateLimiter::tooManyAttempts($rateKey, 3)) {
            throw ValidationException::withMessages(['email' => __('messages.join_limit')]);
        }
        RateLimiter::hit($rateKey, 3600);
        DB::transaction(function () use ($exchange, $data, $email, $locale) {
            $locked = Exchange::whereKey($exchange->id)->lockForUpdate()->firstOrFail();
            $locked->ensureActive();
            if ($locked->drawn_at) {
                throw ValidationException::withMessages(['email' => __('messages.registration_closed')]);
            }
            $person = $locked->participants()->where('email', $email)->first();
            if (! $person) {
                if ($locked->participants()->count() >= 100) {
                    throw ValidationException::withMessages(['email' => __('messages.participant_limit')]);
                }
                $token = Str::random(64);
                $person = $locked->participants()->create([...$data, 'locale' => $locale, 'email' => $email, 'access_hash' => hash('sha256', $token), 'access_token' => $token]);
            }
            $person->refresh();
            $this->quota->check($locked, $person->id, 'email');
            Delivery::enqueue($locked, $person, 'join');
        });

    }
}
