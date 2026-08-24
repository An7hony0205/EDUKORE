<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class Attendance extends Model
{
    use HasUuids, LogsActivity;

    protected $table = 'attendance';
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'id',
        'course_assignment_id',
        'enrollment_id',
        'date',
        'status',
        'notes',
        'created_by', // UUID del docente que registró la asistencia (autoría legal)
    ];

    protected $casts = [
        'date' => 'date',
    ];

    /**
     * Auditoría quirúrgica: solo registra cambios de 'status' (PRESENT/ABSENT/LATE).
     * No loguea 'notes' ni 'created_at' para evitar saturar activity_log.
     * Motivo: las asistencias son registros de alto volumen (N alumnos × N clases/día).
     * Solo las modificaciones retroactivas de estado son legalmente relevantes.
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['status'])           // Solo el campo que importa legalmente
            ->logOnlyDirty()               // Solo si realmente cambió
            ->dontSubmitEmptyLogs()        // No crear registro si no hay cambio real
            ->setDescriptionForEvent(fn(string $eventName) => "Asistencia {$eventName}");
    }

    public function courseAssignment(): BelongsTo
    {
        return $this->belongsTo(CourseAssignment::class);
    }

    public function enrollment(): BelongsTo
    {
        return $this->belongsTo(Enrollment::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
