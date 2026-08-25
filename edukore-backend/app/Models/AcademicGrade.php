<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * AcademicGrade — Nivel de grado (ej. 1er Grado, 2do Grado).
 *
 * NOTE: el modelo Grade.php ya estaba ocupado para el registro de calificaciones.
 * Usamos AcademicGrade para representar los grados de la estructura académica.
 * La tabla en la BD se llama "grades".
 */
class AcademicGrade extends Model
{
    use HasUuids;

    protected $table     = 'academic_grades';
    public $incrementing = false;
    protected $keyType   = 'string';

    protected $fillable = ['id', 'academic_level_id', 'name'];

    public function academicLevel(): BelongsTo
    {
        return $this->belongsTo(AcademicLevel::class);
    }

    public function sections(): HasMany
    {
        return $this->hasMany(AcademicSection::class, 'grade_id');
    }
}
