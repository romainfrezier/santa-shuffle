<?php

declare(strict_types=1);

namespace App\Actions\Exchanges;

use App\Models\Delivery;
use App\Models\Exchange;
use App\Services\SessionAccess;

final class CancelExchange
{
    public function execute(Exchange $exchange, SessionAccess $access): void
    {
        $exchange->locked(function ($locked) use ($access) {
            $access->authorizeManager($locked);
            abort_if($locked->drawn_at !== null, 409);
            $locked->update(['cancelled_at' => now(), 'draw_version' => $locked->draw_version + 1]);
            foreach ($locked->participants()->get() as $person) {
                Delivery::enqueue($locked, $person, 'cancel');
            }
        });
    }
}
