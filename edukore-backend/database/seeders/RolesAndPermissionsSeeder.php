<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Role;
use App\Models\Permission;

class RolesAndPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Limpiar el caché de permisos de spatie
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // 1. Definir los Permisos Granulares
        $permissions = [
            // Académicos: Cursos
            'courses.create', 'courses.read', 'courses.update', 'courses.delete',
            // Académicos: Calificaciones
            'grades.create', 'grades.read', 'grades.update', 'grades.delete',
            // Académicos: Asistencias
            'attendance.create', 'attendance.read', 'attendance.update', 'attendance.delete',

            // Finanzas
            'finance.read', 'finance.issue_fee', 'finance.register_payment',

            // Configuración
            'settings.manage'
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        // 2. Definir Roles y asignar permisos
        // NOTA: Nombres en inglés/snake_case para coincidir con el frontend y las guardas del router.

        // super_admin - Todo
        $roleSuperAdmin = Role::firstOrCreate(['name' => 'super_admin']);
        $roleSuperAdmin->givePermissionTo(Permission::all());

        // admin (Coordinador de colegio)
        $roleAdmin = Role::firstOrCreate(['name' => 'admin']);
        $roleAdmin->givePermissionTo([
            'courses.create', 'courses.read', 'courses.update', 'courses.delete',
            'grades.read', 'attendance.read',
            'finance.read', 'finance.issue_fee', 'finance.register_payment',
            'settings.manage'
        ]);

        // teacher (Docente)
        $roleTeacher = Role::firstOrCreate(['name' => 'teacher']);
        $roleTeacher->givePermissionTo([
            'courses.read',
            'grades.create', 'grades.read', 'grades.update',
            'attendance.create', 'attendance.read', 'attendance.update',
        ]);

        // student (Estudiante)
        $roleStudent = Role::firstOrCreate(['name' => 'student']);
        $roleStudent->givePermissionTo([
            'courses.read', 'grades.read', 'attendance.read'
        ]);

        // parent (Padre/Apoderado)
        $roleParent = Role::firstOrCreate(['name' => 'parent']);
        $roleParent->givePermissionTo([
            'grades.read', 'attendance.read', 'finance.read'
        ]);
    }
}
