<?php

declare(strict_types=1);

namespace App\Models;

use App\Jobs\SendAccessLink;
use Illuminate\Database\Eloquent\Model;

/** @property string $locale */
class Delivery extends Model
{
    protected $guarded = ['id'];

    public static function enqueue(Exchange $event, ?Participant $person, string $kind): self
    {
        $actor = $person ?? $event;
        $delivery = self::create(['exchange_id' => $event->id, 'participant_id' => $person?->id, 'kind' => $kind, 'locale' => $actor->locale ?? app()->getLocale(),
            'access_version' => $actor->access_version, 'draw_version' => $event->draw_version]);
        SendAccessLink::dispatch($delivery->id)->onConnection('database');

        return $delivery;
    }
}
