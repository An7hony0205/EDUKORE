<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EvaluationCriterion extends Model
{
    use HasUuids;

    protected $table = 'evaluation_criteria';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'id', 'course_assignment_id', 'term_id', 'name', 'weight', 'order_index'
    ];

    public function courseAssignment(): BelongsTo
    {
        return $this->belongsTo(CourseAssignment::class);
    }

    public function term(): BelongsTo
    {
        return $this->belongsTo(AcademicTerm::class);
    }
}
