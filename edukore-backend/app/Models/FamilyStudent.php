<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FamilyStudent extends Model
{
    use HasFactory;

    protected $fillable = [
        'family_id',
        'student_id',
        'relation_description'
    ];

    public function family()
    {
        return $this->belongsTo(Family::class);
    }

    public function student()
    {
        return $this->belongsTo(Student::class);
    }
}
