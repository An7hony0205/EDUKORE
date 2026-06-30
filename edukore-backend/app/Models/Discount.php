<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Discount extends Model
{
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'id',
        'tenant_id',
        'name',
        'type', // percentage, fixed
        'value',
        'valid_until',
    ];

    protected $casts = [
        'value' => 'decimal:2',
        'valid_until' => 'date',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function fees(): BelongsToMany
    {
        return $this->belongsToMany(Fee::class, 'fee_discount')
            ->withPivot('applied_by')
            ->withTimestamps();
    }
}
