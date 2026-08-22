<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Event extends Model
{
    use HasUuids;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'id', 'tenant_id', 'title', 'description', 'date', 'event_type', 'status'
    ];

    protected $casts = [
        'date' => 'datetime'
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function participants(): HasMany
    {
        return $this->hasMany(EventParticipant::class);
    }
}
