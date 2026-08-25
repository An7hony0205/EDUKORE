<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SectionAttendance extends Model
{
    use HasUuids;

    protected $table     = 'section_attendances';
    public $incrementing = false;
    protected $keyType   = 'string';

    protected $fillable = [
        'id',
        'section_id',
        'student_id',
        'date',
        'status',   // present | late | absent | justified
        'remarks',
        'created_by',
    ];

    protected $casts = [
        'date' => 'date',
    ];

    public function section(): BelongsTo
    {
        return $this->belongsTo(AcademicSection::class, 'section_id');
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class, 'student_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
