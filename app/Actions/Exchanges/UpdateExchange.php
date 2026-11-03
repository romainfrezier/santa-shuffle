<?php

declare(strict_types=1);

namespace App\Actions\Exchanges;

use App\Models\Exchange;
use App\Services\SessionAccess;

final class UpdateExchange
{
    public function execute(Exchange $exchange, SessionAccess $access, array $data): void
    {
        $exchange->locked(function ($locked) use ($access, $data) {
            $access->authorizeManager($locked);
            abort_if($locked->drawn_at !== null, 409);
            $locked->update($data);
        });
    }
}
