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

        return $this->currentParticipant($exchange) ?? abort(403);
    }

    public function currentParticipant(Exchange $exchange): ?Participant
    {
        $access = $this->session->get("participants.{$exchange->id}");
        if (! isset($access['id'], $access['version'])) {
            return null;
        }

        return $exchange->participants()->where('access_version', $access['version'])->find($access['id']);
    }

    public function envelopes(): array
    {
        $grants = $this->session->get('participants', []);
        if (! $grants) {
            return [];
        }

        return Participant::whereIntegerInRaw('id', array_column($grants, 'id'))
            ->whereHas('exchange', fn ($query) => $query->whereNull('cancelled_at')->whereDate('gift_date', '>', now()->subDays(30)->toDateString()))
            ->with('exchange:id,uuid,name')->get(['id', 'exchange_id', 'access_version'])
            ->filter(fn ($person) => ($grants[$person->exchange_id]['id'] ?? null) === $person->id
                && ($grants[$person->exchange_id]['version'] ?? null) === $person->access_version)
            ->map(fn ($person) => $person->exchange->only('uuid', 'name'))->values()->all();
    }
}
