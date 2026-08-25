<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EvaluationActivity extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'evaluation_criterion_id',
        'name',
        'due_date',
        'order_index',
    ];

    public function evaluationCriterion()
    {
        return $this->belongsTo(EvaluationCriterion::class);
    }

    public function grades()
    {
        return $this->hasMany(Grade::class, 'activity_id');
    }
}
