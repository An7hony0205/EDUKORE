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
        'relation_description',
        'is_primary',           // Familia principal del estudiante (solo una por student_id)
    ];

    protected $casts = [
        'is_primary' => 'boolean',
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
