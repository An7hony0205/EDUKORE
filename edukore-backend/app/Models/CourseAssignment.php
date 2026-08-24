<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class CourseAssignment extends Model
{
    protected $table = 'course_assignments';

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'id',
        'course_id',
        'section_id',
        'teacher_id',
        'academic_year_id',
        'schedule',
        'room',
        'weekly_hours',
        'is_substitute',
    ];

    /**
     * Auditoría quirúrgica: solo campos de alto impacto.
     * - teacher_id: cambio de docente asignado (impacto en acceso del docente y calificaciones)
     * - section_id: reasignación de sección (impacto en qué alumnos ve el docente)
     * - course_id: cambio de materia (raro, pero de alto impacto)
     * No se loguea schedule/room/weekly_hours (metadata logística sin impacto de seguridad).
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['teacher_id', 'section_id', 'course_id'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->setDescriptionForEvent(fn(string $eventName) => "Asignación docente {$eventName}");
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function section(): BelongsTo
    {
        return $this->belongsTo(Section::class);
    }

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }

    public function attendance(): HasMany
    {
        return $this->hasMany(Attendance::class);
    }

    public function evaluations(): HasMany
    {
        return $this->hasMany(Evaluation::class);
    }
}
