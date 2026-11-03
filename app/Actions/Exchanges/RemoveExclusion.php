<?php

declare(strict_types=1);

namespace App\Actions\Exchanges;

use App\Models\Exchange;
use App\Services\SessionAccess;
use Illuminate\Support\Facades\DB;

final class RemoveExclusion
{
    public function execute(Exchange $exchange, SessionAccess $access, int $exclusion): void
    {
        $access->authorizeManager($exchange);
        DB::transaction(function () use ($exchange, $exclusion, $access) {
            $locked = Exchange::whereKey($exchange->id)->lockForUpdate()->firstOrFail();
            $access->authorizeManager($locked);
            abort_if($locked->drawn_at !== null, 409);
            DB::table('exclusions')->where('exchange_id', $exchange->id)->where('id', $exclusion)->delete();
        });

    }
}
