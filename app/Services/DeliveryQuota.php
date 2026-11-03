<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Delivery;
use App\Models\Exchange;
use Illuminate\Validation\ValidationException;

final class DeliveryQuota
{
    public function check(Exchange $event, ?int $participant, string $field = 'delivery'): void
    {
        // Count under the event lock: concurrent resends cannot bypass the recipient quota.
        $count = Delivery::where('exchange_id', $event->id)->where('participant_id', $participant)->where('created_at', '>', now()->subHour())->count();
        if ($count >= 3) {
            throw ValidationException::withMessages([$field => __('messages.delivery_limit')]);
        }
    }
}
