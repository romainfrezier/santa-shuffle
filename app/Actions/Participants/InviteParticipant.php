<?php

declare(strict_types=1);

namespace App\Actions\Participants;

use App\Models\Exchange;
use App\Services\SessionAccess;

final readonly class InviteParticipant
{
    public function __construct(private RegisterParticipant $register) {}

    public function execute(Exchange $exchange, SessionAccess $access, array $data): void
    {
        $exchange->locked(function (Exchange $locked) use ($access, $data) {
            $access->authorizeManager($locked);
            $this->register->execute($locked, $data, $locked->locale);
        });
    }
}
