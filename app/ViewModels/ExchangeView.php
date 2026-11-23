<?php

declare(strict_types=1);

namespace App\ViewModels;

use App\Models\Delivery;
use App\Models\Exchange;
use App\Models\Participant;
use Illuminate\Support\Facades\DB;

/** Explicit public projections: never expose credentials, email addresses or assignments. */
final class ExchangeView
{
    public static function summary(Exchange $exchange): array
    {
        return [
            'uuid' => $exchange->uuid,
            'name' => $exchange->name,
            'gift_date' => $exchange->gift_date->format('Y-m-d'),
            'budget' => $exchange->budget,
            'currency' => $exchange->currency,
            'message' => $exchange->message,
            'drawn' => $exchange->drawn_at !== null,
            'cancelled' => $exchange->cancelled_at !== null,
        ];
    }

    public static function management(Exchange $exchange): array
    {
        $people = $exchange->participants()->orderBy('id')->get(['id', 'name', 'confirmed_at', 'revealed_at']);
        $deliveries = Delivery::where('exchange_id', $exchange->id)->orderBy('id')->get()->keyBy('participant_id');

        return [
            'exchange' => self::summary($exchange),
            'inviteUrl' => route('exchanges.show', $exchange),
            'participants' => $people->map(fn (Participant $person) => [
                'id' => $person->id,
                'name' => $person->name,
                'confirmed' => $person->confirmed_at !== null,
                'revealed' => $person->revealed_at !== null,
                'delivery' => $deliveries->get($person->id)?->status,
            ]),
            'exclusions' => DB::table('exclusions')->where('exchange_id', $exchange->id)->get(['id', 'giver_id', 'recipient_id']),
        ];
    }

    public static function participant(Exchange $exchange, Participant $person): array
    {
        $recipient = $person->revealed_at ? $person->recipient : null;

        return [
            'exchange' => self::summary($exchange),
            'participant' => $person->only('name', 'wishes'),
            'recipient' => $recipient ? ['name' => $recipient->name, 'wishes' => $recipient->wishes] : null,
        ];
    }
}
