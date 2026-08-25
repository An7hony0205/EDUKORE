<?php
use App\Models\User;
use App\Models\Tenant;
use App\Models\Student;
use App\Models\TeacherProfile;
use App\Models\ParentProfile;
use App\Models\Family;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

$tenant = Tenant::first();

// Ensure roles exist
$teacherRole = Role::firstOrCreate(['name' => 'Teacher']);
$studentRole = Role::firstOrCreate(['name' => 'Student']);
$parentRole = Role::firstOrCreate(['name' => 'Parent']);

// Create Teacher
$teacherUser = User::firstOrCreate(
    ['email' => 'profesor@demo.edu'],
    [
        'id' => \Illuminate\Support\Str::uuid(),
        'tenant_id' => $tenant->id,
        'name' => 'Profesor Demo',
        'password' => Hash::make('password'),
        'is_active' => true
    ]
);
$teacherUser->assignRole('Teacher');
TeacherProfile::firstOrCreate(
    ['user_id' => $teacherUser->id],
    ['id' => \Illuminate\Support\Str::uuid()]
);

// Create Student
$studentUser = User::firstOrCreate(
    ['email' => 'estudiante1@demo.edu'],
    [
        'id' => \Illuminate\Support\Str::uuid(),
        'tenant_id' => $tenant->id,
        'name' => 'Estudiante Uno',
        'password' => Hash::make('password'),
        'is_active' => true
    ]
);
$studentUser->assignRole('Student');
$student = Student::firstOrCreate(
    ['user_id' => $studentUser->id],
    ['id' => \Illuminate\Support\Str::uuid(), 'status' => 'activo']
);

// Create Parent
$parentUser = User::firstOrCreate(
    ['email' => 'padre@demo.edu'],
    [
        'id' => \Illuminate\Support\Str::uuid(),
        'tenant_id' => $tenant->id,
        'name' => 'Padre García',
        'password' => Hash::make('password'),
        'is_active' => true
    ]
);
$parentUser->assignRole('Parent');
ParentProfile::firstOrCreate(
    ['user_id' => $parentUser->id],
    ['id' => \Illuminate\Support\Str::uuid()]
);

// Link Parent and Student through Family
$family = Family::firstOrCreate(
    ['tenant_id' => $tenant->id, 'name' => 'Familia García'],
    ['id' => \Illuminate\Support\Str::uuid()]
);

\Illuminate\Support\Facades\DB::table('family_members')->updateOrInsert(
    ['family_id' => $family->id, 'user_id' => $parentUser->id],
    ['relation_type' => 'Padre', 'can_view_info' => true, 'is_primary_contact' => true, 'created_at' => now(), 'updated_at' => now()]
);

\Illuminate\Support\Facades\DB::table('family_students')->updateOrInsert(
    ['family_id' => $family->id, 'student_id' => $student->id],
    ['relation_description' => 'Hijo', 'created_at' => now(), 'updated_at' => now()]
);

echo "Cuentas creadas exitosamente.\n";
