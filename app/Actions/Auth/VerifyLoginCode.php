<?php

declare(strict_types=1);

namespace App\Actions\Auth;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class VerifyLoginCode
{
    public function execute(?string $email, ?string $challenge, string $code): void
    {
        $valid = $email && $challenge && DB::transaction(function () use ($email, $challenge, $code) {
            $query = DB::table('login_codes')->where('email_hash', hash('sha256', $email));
            $row = (clone $query)->lockForUpdate()->first();
            if (! $row || ! hash_equals($row->challenge, $challenge) || $row->attempts >= 5 || Carbon::parse($row->expires_at)->lte(now())) {
                return false;
            }
            if (! hash_equals($row->code_hash, hash_hmac('sha256', $challenge.':'.$code, config('app.key')))) {
                $query->increment('attempts');

                return false;
            }
            $query->delete();

            return true;
        });
        if (! $valid) {
            throw ValidationException::withMessages(['code' => __('messages.code_invalid')]);
        }
    }
}
