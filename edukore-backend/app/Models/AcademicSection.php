<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * AcademicSection — Sección de aula (ej. 1er Grado "A").
 *
 * NOTE: el modelo Section.php ya estaba ocupado para asignaciones de cursos.
 * Usamos AcademicSection para representar las secciones de la estructura académica.
 * La tabla en la BD se llama "sections".
 */
class AcademicSection extends Model
{
    use HasUuids;

    protected $table     = 'academic_sections';
    public $incrementing = false;
    protected $keyType   = 'string';

    protected $fillable = [
        'id',
        'grade_id',
        'name',
        'max_capacity',
        'tutor_id',
    ];

    protected $casts = [
        'max_capacity' => 'integer',
    ];

    public function grade(): BelongsTo
    {
        return $this->belongsTo(AcademicGrade::class, 'grade_id');
    }

    public function tutor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'tutor_id');
    }

    public function students(): HasMany
    {
        return $this->hasMany(Student::class, 'section_id');
    }
}
