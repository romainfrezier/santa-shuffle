<?php

declare(strict_types=1);

namespace App\Actions\Exchanges;

use App\Models\Delivery;
use App\Models\Exchange;
use App\Services\DeliveryQuota;
use App\Services\SessionAccess;

final readonly class ResendInvitation
{
    public function __construct(private DeliveryQuota $quota) {}

    public function execute(Exchange $exchange, SessionAccess $access, int $participant): void
    {
        $exchange->locked(function ($locked) use ($access, $participant) {
            $access->authorizeManager($locked);
            $person = $locked->participants()->findOrFail($participant);
            $this->quota->check($locked, $person->id);
            Delivery::enqueue($locked, $person, $locked->drawn_at ? 'draw' : 'join');
        });
    }
}
