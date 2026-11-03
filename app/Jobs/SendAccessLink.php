<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldBeEncrypted;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Mail;

class SendAccessLink implements ShouldBeEncrypted, ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $timeout = 30;

    public function __construct(public string $email, public string $eventName, public string $url, public string $kind) {}

    public function backoff(): array
    {
        return [30, 120, 300];
    }

    public function handle(): void
    {
        $subject = match ($this->kind) {
            'organizer' => 'Votre Secret Santa est prêt à accueillir votre équipe',
            'draw' => 'Votre enveloppe vous attend !',
            default => 'Confirmez votre participation',
        };
        Mail::send('mail.access', ['eventName' => $this->eventName, 'url' => $this->url, 'kind' => $this->kind], function ($mail) use ($subject) {
            $mail->to($this->email)->subject($subject.' · Santa Shuffle');
        });
    }
}
