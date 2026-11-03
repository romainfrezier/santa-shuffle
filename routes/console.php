<?php

use App\Models\Exchange;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schedule;

Artisan::command('events:purge', function () {
    $count = 0;
    Exchange::whereDate('gift_date', '<=', now()->subDays(30)->toDateString())->eachById(function ($event) use (&$count) {
        DB::transaction(function () use ($event, &$count) {
            $locked = Exchange::whereKey($event->id)->lockForUpdate()->first();
            if ($locked && $locked->gift_date->copy()->addDays(30)->isPast()) {
                $locked->delete();
                $count++;
            }
        });
    });
    $this->info("{$count} event(s) deleted.");
})->purpose('Delete expired events and their data');
Schedule::command('events:purge')->dailyAt('03:00')->withoutOverlapping();
Schedule::command('queue:prune-failed --hours=168')->daily();

Schedule::call(fn () => DB::table('login_codes')->where('expires_at', '<', now())->delete())->hourly();
