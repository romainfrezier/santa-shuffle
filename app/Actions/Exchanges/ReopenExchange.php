<?php

declare(strict_types=1);

namespace App\Actions\Exchanges;

use App\Models\Delivery;
use App\Models\Exchange;
use App\Services\SessionAccess;
use Illuminate\Validation\ValidationException;

final class ReopenExchange
{
    public function execute(Exchange $exchange, SessionAccess $access): void
    {
        $exchange->locked(function ($locked) use ($access) {
            $access->authorizeManager($locked);
            if (! $locked->drawn_at || $locked->participants()->whereNotNull('revealed_at')->exists()) {
                throw ValidationException::withMessages(['reopen' => __('messages.reopen_forbidden')]);
            }
            $locked->participants()->update(['recipient_id' => null]);
            $locked->update(['drawn_at' => null, 'draw_version' => $locked->draw_version + 1]);
            foreach ($locked->participants()->get() as $person) {
                Delivery::enqueue($locked, $person, 'reopen');
            }
        });
    }
}
