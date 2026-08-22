<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GradeAudit extends Model
{
    use HasFactory;

    protected $fillable = [
        'grade_id',
        'user_id',
        'old_score',
        'new_score',
        'reason'
    ];

    public function grade()
    {
        return $this->belongsTo(Grade::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
