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

        // SuperAdmin - Todo
        $roleSuperAdmin = Role::firstOrCreate(['name' => 'SuperAdmin']);
        $roleSuperAdmin->givePermissionTo(Permission::all());

        // Administrador (Coordinador de colegio)
        $roleAdmin = Role::firstOrCreate(['name' => 'Admin']);
        $roleAdmin->givePermissionTo([
            'courses.create', 'courses.read', 'courses.update', 'courses.delete',
            'grades.read', 'attendance.read',
            'finance.read', 'finance.issue_fee', 'finance.register_payment',
            'settings.manage'
        ]);

        // Docente
        $roleTeacher = Role::firstOrCreate(['name' => 'Docente']);
        $roleTeacher->givePermissionTo([
            'courses.read',
            'grades.create', 'grades.read', 'grades.update',
            'attendance.create', 'attendance.read', 'attendance.update',
        ]);

        // Estudiante
        $roleStudent = Role::firstOrCreate(['name' => 'Estudiante']);
        $roleStudent->givePermissionTo([
            'courses.read', 'grades.read', 'attendance.read'
        ]);

        // Padre
        $roleParent = Role::firstOrCreate(['name' => 'Padre']);
        $roleParent->givePermissionTo([
            'grades.read', 'attendance.read', 'finance.read'
        ]);
    }
}
