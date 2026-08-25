<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TimeSlot extends Model
{
    use HasUuids;

    protected $table = 'time_slots';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'level_id',
        'name',
        'start_time',
        'end_time',
        'type',
        'order_index',
    ];

    public function level(): BelongsTo
    {
        return $this->belongsTo(AcademicLevel::class, 'level_id');
    }
}
