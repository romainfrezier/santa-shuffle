<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property Carbon|null $confirmed_at
 * @property Carbon|null $revealed_at
 * @property int $access_version
 * @property string $locale
 */
class Participant extends Model
{
    protected $attributes = ['access_version' => 1];

    protected $guarded = ['id'];

    protected $hidden = ['email', 'access_hash', 'access_token', 'recipient_id'];

    protected function casts(): array
    {
        return ['access_version' => 'integer', 'access_token' => 'encrypted', 'confirmed_at' => 'datetime', 'revealed_at' => 'datetime'];
    }

    /** @return BelongsTo<Exchange, $this> */
    public function exchange(): BelongsTo
    {
        return $this->belongsTo(Exchange::class);
    }

    /** @return BelongsTo<self, $this> */
    public function recipient(): BelongsTo
    {
        return $this->belongsTo(self::class, 'recipient_id');
    }
}
