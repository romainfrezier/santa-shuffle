<?php

declare(strict_types=1);

namespace App\Actions\Exchanges;

use App\Models\Delivery;
use App\Models\Exchange;
use App\Services\DeliveryQuota;
use App\Services\SessionAccess;
use Illuminate\Support\Str;

final readonly class RotateAccess
{
    public function __construct(private DeliveryQuota $quota) {}

    public function execute(Exchange $exchange, SessionAccess $access, ?int $participant): int
    {
        return $exchange->locked(function ($locked) use ($access, $participant) {
            $access->authorizeManager($locked);
            $actor = $participant !== null ? $locked->participants()->findOrFail($participant) : $locked;
            $this->quota->check($locked, $participant);
            $token = Str::random(64);
            $actor->update(['access_token' => $token, 'access_hash' => hash('sha256', $token), 'access_version' => $actor->access_version + 1]);
            Delivery::enqueue($locked, $participant !== null ? $actor : null, $participant !== null ? ($locked->drawn_at ? 'draw' : 'join') : 'organizer');

            return $actor->access_version;
        });
    }
}
