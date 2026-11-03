<?php

declare(strict_types=1);

namespace App\Actions\Participants;

use App\Models\Exchange;
use App\Services\SessionAccess;

final class RevealRecipient
{
    public function execute(Exchange $exchange, SessionAccess $access): void
    {
        $exchange->locked(function ($locked) use ($access) {
            $person = $access->participant($locked);
            abort_unless($locked->drawn_at && $person->recipient_id, 409);
            if (! $person->revealed_at) {
                $person->update(['revealed_at' => now()]);
            }
        });

    }
}
