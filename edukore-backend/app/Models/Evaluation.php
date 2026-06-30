<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Evaluation extends Model
{
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'id',
        'course_assignment_id',
        'academic_period_id',
        'rubric_id',
        'title',
        'description',
        'due_date',
        'category',
        'weight',
        'is_published',
    ];

    protected $casts = [
        'due_date'     => 'datetime',
        'weight'       => 'float',
        'is_published' => 'boolean',
    ];

    public function courseAssignment(): BelongsTo
    {
        return $this->belongsTo(CourseAssignment::class);
    }

    public function grades(): HasMany
    {
        return $this->hasMany(Grade::class);
    }

    public function academicPeriod(): BelongsTo
    {
        return $this->belongsTo(AcademicPeriod::class);
    }
}
