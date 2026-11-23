<?php

declare(strict_types=1);

namespace App\Actions\Exchanges;

use App\Models\Exchange;
use App\Services\SessionAccess;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

final class ConfirmParticipant
{
    public function execute(Exchange $exchange, SessionAccess $access, int $participant, string $email): void
    {
        $exchange->locked(function (Exchange $locked) use ($access, $participant, $email) {
            $access->authorizeManager($locked);
            abort_if($locked->drawn_at !== null, 409);
            $person = $locked->participants()->findOrFail($participant);
            if (Str::lower($email) !== $person->email) {
                throw ValidationException::withMessages(['email' => __('messages.confirm_email_mismatch')]);
            }
            if ($person->confirmed_at === null) {
                $person->update(['confirmed_at' => now()]);
            }
        });
    }
}
