<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class Family extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = ['id', 'tenant_id', 'name'];

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    public function members()
    {
        return $this->hasMany(FamilyMember::class);
    }

    /**
     * Estudiantes vinculados a esta familia.
     * is_primary indica si esta familia es la familia principal del estudiante.
     */
    public function students()
    {
        return $this->belongsToMany(Student::class, 'family_students')
            ->withPivot(['relation_description', 'is_primary'])
            ->withTimestamps();
    }
}
