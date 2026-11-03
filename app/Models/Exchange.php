<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * @property Carbon $gift_date
 * @property Carbon|null $drawn_at
 * @property Carbon|null $cancelled_at
 * @property int $access_version
 * @property int $draw_version
 * @property string $locale
 */
class Exchange extends Model
{
    public const CURRENCIES = ['EUR' => 'Euro', 'CHF' => 'Swiss franc', 'USD' => 'US dollar', 'CAD' => 'Canadian dollar', 'GBP' => 'Pound sterling', 'JPY' => 'Japanese yen', 'AUD' => 'Australian dollar'];

    protected $attributes = ['currency' => 'EUR', 'access_version' => 1, 'draw_version' => 0];

    protected $guarded = ['id'];

    protected $hidden = ['organizer_email', 'access_hash', 'access_token'];

    protected function casts(): array
    {
        return ['gift_date' => 'date', 'drawn_at' => 'datetime', 'cancelled_at' => 'datetime', 'access_token' => 'encrypted', 'access_version' => 'integer', 'draw_version' => 'integer'];
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    /** @return HasMany<Participant, $this> */
    public function participants(): HasMany
    {
        return $this->hasMany(Participant::class);
    }

    public function ensureActive(bool $allowCancelled = false): void
    {
        abort_if(! $allowCancelled && $this->cancelled_at, 410);
        abort_if($this->gift_date->copy()->addDays(30)->isPast(), 410);
    }

    /**
     * @template T
     *
     * @param  callable(self): T  $operation
     * @return T
     */
    public function locked(callable $operation): mixed
    {
        return DB::transaction(fn () => $operation(self::whereKey($this->id)->lockForUpdate()->firstOrFail()));
    }
}
