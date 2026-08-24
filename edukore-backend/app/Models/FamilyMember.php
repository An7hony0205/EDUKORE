<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FamilyMember extends Model
{
    use HasFactory;

    protected $fillable = [
        'family_id',
        'user_id',
        'relation_type',
        'is_primary_contact',
        'can_view_info',        // DEFAULT false (regla de negocio: el acceso al portal es opt-in)
    ];

    protected $casts = [
        'is_primary_contact' => 'boolean',
        'can_view_info'      => 'boolean',
    ];

    public function family()
    {
        return $this->belongsTo(Family::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
