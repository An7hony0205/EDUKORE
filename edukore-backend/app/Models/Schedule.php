<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Schedule extends Model
{
    use HasUuids;

    protected $table     = 'schedules';
    public $incrementing = false;
    protected $keyType   = 'string';

    protected $fillable = [
        'id',
        'section_id',
        'course_assignment_id',
        'day_of_week',
        'start_time',
        'end_time',
        'room',
        'type',
    ];

    protected $casts = [
        'day_of_week' => 'integer',
    ];

    public function section(): BelongsTo
    {
        return $this->belongsTo(AcademicSection::class, 'section_id');
    }

    public function courseAssignment(): BelongsTo
    {
        return $this->belongsTo(CourseAssignment::class, 'course_assignment_id');
    }
}
