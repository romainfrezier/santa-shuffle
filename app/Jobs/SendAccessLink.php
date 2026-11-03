<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\Delivery;
use App\Models\Exchange;
use Illuminate\Contracts\Queue\ShouldBeEncrypted;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Traits\Localizable;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Throwable;

class SendAccessLink implements ShouldBeEncrypted, ShouldQueue
{
    use Localizable;
    use Queueable;

    public int $tries = 3;

    public int $timeout = 30;

    public function __construct(public int $deliveryId) {}

    public function backoff(): array
    {
        return [30, 120, 300];
    }

    public function handle(): void
    {
        // Legacy queued payloads have no delivery ID: never send their stale links.
        if (! isset($this->deliveryId)) {
            return;
        }
        $delivery = Delivery::find($this->deliveryId);
        if (! $delivery || ! $event = Exchange::find($delivery->exchange_id)) {
            return;
        }
        DB::transaction(function () use ($event) {
            $event = Exchange::whereKey($event->id)->lockForUpdate()->first();
            if (! $event) {
                return;
            }
            $d = Delivery::find($this->deliveryId);
            if (! $d || in_array($d->status, ['accepted', 'obsolete'])) {
                return;
            }
            $person = $d->participant_id ? $event->participants()->find($d->participant_id) : null;
            $actor = $person ?? $event;
            $version = $actor->access_version;
            if (($d->participant_id && ! $person) || $version != $d->access_version
                || $event->draw_version != $d->draw_version
                || (bool) $event->cancelled_at !== ($d->kind === 'cancel')
                || $event->gift_date->copy()->addDays(30)->isPast()) {
                $d->update(['status' => 'obsolete']);

                return;
            }
            $token = $actor->access_token;
            if (! $token && $d->kind !== 'cancel') {
                $d->update(['status' => 'obsolete']);

                return;
            }
            // ponytail: hold the event lock during SMTP (bounded by timeout) so revocation/deletion cannot overtake an authorized send. Split delivery locking if team-scale throughput becomes insufficient.
            try {
                $this->withLocale($d->locale, function () use ($event, $d, $person, $token) {
                    Mail::send('mail.access', ['eventName' => $event->name,
                        'url' => $d->kind === 'cancel' ? null : ($d->kind === 'organizer' ? route('login') : route('access.show', $token)), 'kind' => $d->kind],
                        fn ($mail) => $mail->to($person !== null ? $person->email : $event->organizer_email)->subject(match ($d->kind) {
                            'draw' => __('mail.draw_subject').' · Santa Shuffle',
                            'reopen' => __('mail.reopen_subject').' · Santa Shuffle',
                            'cancel' => __('mail.cancel_subject').' · Santa Shuffle',
                            'organizer' => __('mail.organizer_subject').' · Santa Shuffle',
                            default => __('mail.join_subject').' · Santa Shuffle',
                        }));
                });
            } catch (TransportExceptionInterface $exception) {
                // SMTP diagnostics can contain addresses, URLs or credentials.
                throw new \RuntimeException('SMTP delivery unavailable. Check configuration and retry.');
            }
            $d->update(['status' => 'accepted', 'accepted_at' => now()]);
        });
    }

    public function failed(?Throwable $exception): void
    {
        if (isset($this->deliveryId)) {
            Delivery::whereKey($this->deliveryId)->where('status', 'pending')->update(['status' => 'failed']);
        }
    }
}
