<?php

declare(strict_types=1);

namespace App\Actions\Auth;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;

final class SendLoginCode
{
    /** @return array{email: string, challenge: string} */
    public function execute(string $address): array
    {
        $email = Str::lower(trim($address));
        $key = hash('sha256', $email);
        $challenge = Str::random(64);
        $code = (string) random_int(100000, 999999);
        try {
            DB::transaction(function () use ($key, $challenge, $code, $email) {
                // The unique address row serializes concurrent requests, including first login.
                DB::table('login_codes')->insertOrIgnore(['email_hash' => $key, 'challenge' => '', 'code_hash' => '', 'attempts' => 0, 'expires_at' => now(), 'sent_at' => now()->subMinutes(2)]);
                $row = DB::table('login_codes')->where('email_hash', $key)->lockForUpdate()->first();
                if (Carbon::parse($row->sent_at)->gt(now()->subMinute())) {
                    throw ValidationException::withMessages(['email' => __('messages.code_wait')]);
                }
                DB::table('login_codes')->where('email_hash', $key)->update(['challenge' => $challenge, 'code_hash' => hash_hmac('sha256', $challenge.':'.$code, config('app.key')), 'attempts' => 0, 'expires_at' => now()->addMinutes(10), 'sent_at' => now()]);
                // ponytail: synchronous SMTP holds the SQLite write lock for up to the configured timeout; use a dedicated encrypted mail outbox if instance traffic outgrows team scale.
                Mail::send('mail.login', ['code' => $code], fn ($mail) => $mail->to($email)->subject(__('mail.login_subject').' · Santa Shuffle'));
            });
        } catch (TransportExceptionInterface $e) {
            throw ValidationException::withMessages(['email' => __('messages.mail_unavailable')]);
        }

        return ['email' => $email, 'challenge' => $challenge];
    }
}
