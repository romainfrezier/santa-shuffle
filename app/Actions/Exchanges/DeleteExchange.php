<?php

declare(strict_types=1);

namespace App\Actions\Exchanges;

use App\Models\Exchange;
use App\Services\SessionAccess;

final class DeleteExchange
{
    public function execute(Exchange $exchange, SessionAccess $access): void
    {
        $exchange->locked(function ($locked) use ($access) {
            $access->authorizeManager($locked, true);
            $locked->delete();
        });
    }
}
