<?php

declare(strict_types=1);

namespace App\Actions\Participants;

use App\Models\Exchange;
use App\Services\SessionAccess;
use Illuminate\Support\Facades\DB;

final class UpdateParticipant
{
    public function execute(Exchange $exchange, SessionAccess $access, array $data): void
    {
        DB::transaction(function () use ($exchange, $access, $data) {
            $locked = Exchange::whereKey($exchange->id)->lockForUpdate()->firstOrFail();
            $locked->ensureActive();
            abort_if($locked->drawn_at !== null, 409);
            $access->participant($locked)->update($data);
        });

    }
}
