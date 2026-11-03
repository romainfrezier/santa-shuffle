<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Http\Request;

class Exchange extends Model
{
    protected $guarded = ['id'];

    protected $hidden = ['organizer_email', 'access_hash'];

    protected function casts(): array
    {
        return ['gift_date' => 'date', 'drawn_at' => 'datetime'];
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    public function participants(): HasMany
    {
        return $this->hasMany(Participant::class);
    }

    public function authorizeManager(Request $request): void
    {
        abort_unless($request->session()->get("managed.{$this->id}") === true, 403);
        $this->ensureActive();
    }

    public function ensureActive(): void
    {
        abort_if($this->gift_date->copy()->addDays(30)->isPast(), 410);
    }

    public function publicData(): array
    {
        return ['uuid' => $this->uuid, 'name' => $this->name, 'gift_date' => $this->gift_date->format('Y-m-d'), 'budget' => $this->budget, 'message' => $this->message, 'drawn' => $this->drawn_at !== null];
    }
}
