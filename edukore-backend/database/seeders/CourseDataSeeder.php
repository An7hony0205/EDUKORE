<?php

namespace Database\Seeders;

use App\Models\AcademicYear;
use App\Models\Level;
use App\Models\GradeLevel;
use App\Models\Section;
use App\Models\Tenant;
use App\Models\User;
use App\Models\Student;
use App\Models\Enrollment;
use App\Models\Course;
use App\Models\CourseAssignment;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class CourseDataSeeder extends Seeder
{
    public function run(): void
    {
        $tenant = Tenant::first();
        $year = AcademicYear::first();
        $section = Section::first(); // 4

        $teacherUser = User::firstOrCreate(
            ['email' => 'profesor@demo.edu', 'tenant_id' => $tenant->id],
            [
                'id' => Str::uuid(),
                'name' => 'Profesor de Matematicas',
                'password' => Hash::make('password'),
                'is_active' => true
            ]
        );

        $course = Course::firstOrCreate(
            ['name' => 'Matematicas', 'tenant_id' => $tenant->id],
            [
                'id' => Str::uuid(),
                'description' => 'Matematicas de Secundaria',
                'is_active' => true,
            ]
        );

        $assignment = CourseAssignment::firstOrCreate(
            ['course_id' => $course->id, 'section_id' => $section->id],
            [
                'id' => Str::uuid(),
                'teacher_id' => $teacherUser->id,
                'weekly_hours' => 5
            ]
        );

        $studentRole = Role::firstOrCreate(['name' => 'Student', 'guard_name' => 'web'], ['id' => Str::uuid()]);

        // Create 32 students
        for ($i = 1; $i <= 32; $i++) {
            $studentUser = User::firstOrCreate(
                ['email' => "estudiante$i@demo.edu", 'tenant_id' => $tenant->id],
                [
                    'id' => Str::uuid(),
                    'name' => "Estudiante $i",
                    'password' => Hash::make('password'),
                    'is_active' => true
                ]
            );

            $student = Student::firstOrCreate(
                ['user_id' => $studentUser->id],
                [
                    'id' => Str::uuid(),
                    'date_of_birth' => '2010-01-01',
                    'status' => 'activo'
                ]
            );

            Enrollment::firstOrCreate(
                ['student_id' => $student->id, 'academic_year_id' => $year->id],
                [
                    'id' => Str::uuid(),
                    'section_id' => $section->id,
                    'tenant_id' => $tenant->id,
                    'status' => 'matriculado'
                ]
            );
        }
    }
}
