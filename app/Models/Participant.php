<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Participant extends Model
{
    protected $guarded = ['id'];

    protected $hidden = ['email', 'access_hash', 'access_token', 'recipient_id'];

    protected function casts(): array
    {
        return ['access_token' => 'encrypted', 'confirmed_at' => 'datetime', 'revealed_at' => 'datetime'];
    }

    public function exchange(): BelongsTo
    {
        return $this->belongsTo(Exchange::class);
    }

    public function recipient(): BelongsTo
    {
        return $this->belongsTo(self::class, 'recipient_id');
    }
}
