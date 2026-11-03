<?php

declare(strict_types=1);

namespace App\Actions\Exchanges;

use App\Models\Exchange;
use App\Services\SessionAccess;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

final class AddExclusion
{
    public function execute(Exchange $exchange, SessionAccess $access, array $input): void
    {
        $access->authorizeManager($exchange);
        DB::transaction(function () use ($exchange, $access, $input) {
            $locked = Exchange::whereKey($exchange->id)->lockForUpdate()->firstOrFail();
            $access->authorizeManager($locked);
            abort_if($locked->drawn_at !== null, 409);
            $member = Rule::exists('participants', 'id')->where('exchange_id', $exchange->id);
            $data = Validator::make($input, ['giver_id' => ['required', 'integer', $member], 'recipient_id' => ['required', 'integer', 'different:giver_id', $member]])->validate();
            DB::table('exclusions')->updateOrInsert(['exchange_id' => $exchange->id, ...$data], []);
        });

    }
}
