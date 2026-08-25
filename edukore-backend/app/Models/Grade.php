<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Grade extends Model
{
    use HasUuids;

    protected $table = 'grades';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'id', 'evaluation_criterion_id', 'student_id', 'score', 'letter_grade', 'feedback', 'created_by'
    ];

    public function criterion(): BelongsTo
    {
        return $this->belongsTo(EvaluationCriterion::class, 'evaluation_criterion_id');
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }
}
