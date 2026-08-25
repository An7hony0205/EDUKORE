<?php
use App\Models\User;
use App\Models\Tenant;
use App\Models\Student;
use App\Models\TeacherProfile;
use App\Models\ParentProfile;
use App\Models\Family;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

$tenant = Tenant::first();

// Ensure roles
$roles = ['Teacher', 'Student', 'Parent', 'Admin'];
foreach($roles as $role) {
    if (!DB::table('roles')->where('name', $role)->exists()) {
        DB::table('roles')->insert([
            'id' => Str::uuid(),
            'name' => $role,
            'guard_name' => 'web',
            'created_at' => now(),
            'updated_at' => now()
        ]);
    }
}

// Create Teacher
$teacherUser = User::firstOrCreate(
    ['email' => 'profesor@demo.edu'],
    [
        'id' => Str::uuid(),
        'tenant_id' => $tenant->id,
        'name' => 'Profesor Demo',
        'password' => Hash::make('password'),
        'is_active' => true
    ]
);
$teacherUser->assignRole('Teacher');
TeacherProfile::firstOrCreate(
    ['user_id' => $teacherUser->id],
    ['id' => Str::uuid()]
);

// Create Student
$studentUser = User::firstOrCreate(
    ['email' => 'estudiante@demo.edu'],
    [
        'id' => Str::uuid(),
        'tenant_id' => $tenant->id,
        'name' => 'Estudiante Demo',
        'password' => Hash::make('password'),
        'is_active' => true
    ]
);
$studentUser->assignRole('Student');
$student = Student::firstOrCreate(
    ['user_id' => $studentUser->id],
    ['id' => Str::uuid(), 'status' => 'activo']
);

// Create Parent
$parentUser = User::firstOrCreate(
    ['email' => 'padre@demo.edu'],
    [
        'id' => Str::uuid(),
        'tenant_id' => $tenant->id,
        'name' => 'Padre Demo',
        'password' => Hash::make('password'),
        'is_active' => true
    ]
);
$parentUser->assignRole('Parent');
ParentProfile::firstOrCreate(
    ['user_id' => $parentUser->id],
    ['id' => Str::uuid()]
);

// Link Parent and Student through Family
$family = Family::firstOrCreate(
    ['tenant_id' => $tenant->id, 'name' => 'Familia Demo'],
    ['id' => Str::uuid()]
);

DB::table('family_members')->updateOrInsert(
    ['family_id' => $family->id, 'user_id' => $parentUser->id],
    ['relation_type' => 'Padre', 'can_view_info' => true, 'is_primary_contact' => true, 'created_at' => now(), 'updated_at' => now()]
);

DB::table('family_students')->updateOrInsert(
    ['family_id' => $family->id, 'student_id' => $student->id],
    ['relation_description' => 'Hijo', 'created_at' => now(), 'updated_at' => now()]
);

echo "Cuentas creadas exitosamente.\n";
