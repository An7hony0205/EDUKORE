<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Migración de seguridad: estandariza los nombres de roles de español/PascalCase
 * a inglés/snake_case para alinear backend (Spatie) con frontend (Vue Router).
 *
 * Es idempotente: si el rol en inglés ya existe, el UPDATE afecta 0 filas
 * sin lanzar errores. Puede ejecutarse con seguridad varias veces.
 */
return new class extends Migration
{
    /**
     * Mapa de migración: nombre antiguo → nombre nuevo.
     * La tabla 'roles' es la tabla estándar de spatie/laravel-permission.
     */
    private array $map = [
        'SuperAdmin' => 'super_admin',
        'Admin'      => 'admin',
        'Docente'    => 'teacher',
        'Estudiante' => 'student',
        'Padre'      => 'parent',
    ];

    public function up(): void
    {
        DB::transaction(function () {
            foreach ($this->map as $old => $new) {
                DB::table('roles')
                    ->where('name', $old)
                    ->update(['name' => $new]);
            }
        });

        // Invalida el caché de permisos de Spatie para que los cambios
        // sean efectivos de inmediato sin reiniciar el servidor.
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
    }

    public function down(): void
    {
        DB::transaction(function () {
            // Inversión exacta: restaura los nombres originales en español.
            foreach ($this->map as $old => $new) {
                DB::table('roles')
                    ->where('name', $new)
                    ->update(['name' => $old]);
            }
        });

        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
    }
};
