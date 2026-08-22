<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Tenant extends Model
{
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'id',
        'name',
        'subdomain',
        'is_active',
        'active_modules',
        'institution_type'
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'active_modules' => 'array',
    ];

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function roles(): HasMany
    {
        return $this->hasMany(Role::class);
    }

    public function courses(): HasMany
    {
        return $this->hasMany(Course::class);
    }

    public function academicYears(): HasMany
    {
        return $this->hasMany(AcademicYear::class);
    }
}
