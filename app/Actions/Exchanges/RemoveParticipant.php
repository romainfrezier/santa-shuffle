<?php

declare(strict_types=1);

namespace App\Actions\Exchanges;

use App\Models\Exchange;
use App\Services\SessionAccess;
use Illuminate\Support\Facades\DB;

final class RemoveParticipant
{
    public function execute(Exchange $exchange, SessionAccess $access, int $participant): void
    {
        $access->authorizeManager($exchange);
        DB::transaction(function () use ($exchange, $participant, $access) {
            $locked = Exchange::whereKey($exchange->id)->lockForUpdate()->firstOrFail();
            $access->authorizeManager($locked);
            abort_if($locked->drawn_at !== null, 409);
            $exchange->participants()->findOrFail($participant)->delete();
        });

    }
}
