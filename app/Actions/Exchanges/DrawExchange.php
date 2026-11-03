<?php

declare(strict_types=1);

namespace App\Actions\Exchanges;

use App\Models\Delivery;
use App\Models\Exchange;
use App\Services\DrawNames;
use App\Services\SessionAccess;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final readonly class DrawExchange
{
    public function __construct(private DrawNames $draw) {}

    public function execute(Exchange $exchange, SessionAccess $access): void
    {
        DB::transaction(function () use ($exchange, $access) {
            $locked = Exchange::whereKey($exchange->id)->lockForUpdate()->firstOrFail();
            $access->authorizeManager($locked);
            if ($locked->drawn_at) {
                throw ValidationException::withMessages(['draw' => __('messages.already_drawn')]);
            }
            $people = $locked->participants()->get();
            if ($people->contains(fn ($p) => ! $p->confirmed_at)) {
                throw ValidationException::withMessages(['draw' => __('messages.confirmation_required')]);
            }
            $excluded = DB::table('exclusions')->where('exchange_id', $exchange->id)->get()->groupBy('giver_id')->map(fn ($rows) => $rows->pluck('recipient_id')->map(fn ($id) => (int) $id)->all())->all();
            $pairs = $this->draw->assign($people->modelKeys(), $excluded);
            $locked->update(['draw_version' => $locked->draw_version + 1, 'drawn_at' => now()]);
            foreach ($people as $person) {
                $person->update(['recipient_id' => $pairs[$person->id]]);
                Delivery::enqueue($locked, $person, 'draw');
            }
        });

    }
}
