<?php

declare(strict_types=1);

namespace App\Actions\Exchanges;

use App\Models\Delivery;
use App\Models\Exchange;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final class CreateExchange
{
    public function execute(array $data, string $email, string $locale): Exchange
    {
        $token = Str::random(64);
        $exchange = DB::transaction(function () use ($data, $token, $email, $locale) {
            $exchange = Exchange::create([...$data, 'locale' => $locale, 'organizer_email' => $email, 'uuid' => (string) Str::uuid(), 'access_hash' => hash('sha256', $token), 'access_token' => $token]);
            $exchange->refresh();
            Delivery::enqueue($exchange, null, 'organizer');

            return $exchange;
        });

        return $exchange;
    }
}
