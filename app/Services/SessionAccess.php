<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Exchange;
use App\Models\Participant;
use Illuminate\Contracts\Session\Session;

final readonly class SessionAccess
{
    public function __construct(private Session $session) {}

    public function authorizeManager(Exchange $exchange, bool $allowCancelled = false): void
    {
        abort_unless($this->session->get('organizer_email') === $exchange->organizer_email, 403);
        abort_unless($this->session->get("managed.{$exchange->id}") === $exchange->access_version, 403);
        $exchange->ensureActive($allowCancelled);
    }

    public function participant(Exchange $exchange): Participant
    {
        $exchange->ensureActive();
        $access = $this->session->get("participants.{$exchange->id}");
        abort_unless(is_array($access) && isset($access['id'], $access['version']), 403);
        $person = $exchange->participants()->findOrFail($access['id']);
        abort_unless($person->access_version === $access['version'], 403);

        return $person;
    }
}
