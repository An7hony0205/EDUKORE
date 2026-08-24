<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\StudentFinancesController;
use App\Http\Controllers\EventController;
use App\Http\Controllers\EventParticipantController;
use App\Http\Controllers\ParentDashboardController;
use App\Http\Controllers\StudentDashboardController;
use App\Http\Controllers\AnnouncementController;

use App\Http\Controllers\GradeLevelController;
use App\Http\Controllers\RubricController;

use App\Http\Controllers\AuthController;
use App\Http\Controllers\AcademicYearController;
use App\Http\Controllers\CourseController;
use App\Http\Controllers\SectionController;
use App\Http\Controllers\EnrollmentController;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\EvaluationController;
use App\Http\Controllers\GradeController;
use App\Http\Controllers\FeeController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\TenantSettingController;
use App\Http\Controllers\StudentPortalController;
use App\Http\Controllers\ParentPortalController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\AcademicPeriodController;
use App\Http\Controllers\AcademicStructureController;
use App\Http\Middleware\TenantScopeMiddleware;

use App\Http\Controllers\StudentController;
use App\Http\Controllers\StudentAcademicController;
use App\Http\Controllers\CourseAssignmentController;
use App\Http\Controllers\TeacherDashboardController;
use App\Http\Controllers\Admin\FamilyController;

// ─── Public Routes ────────────────────────────────────────────────────────────

Route::prefix('v1')->group(function () {
    Route::post('/login', [AuthController::class, 'login']);

    // ─── Protected Routes (auth + tenant scoping) ─────────────────────────────
    Route::middleware(['auth:sanctum', TenantScopeMiddleware::class])->group(function () {

        // Auth (available to any authenticated user)
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::get('/me', [AuthController::class, 'me']);

        // ── Portales de Solo Lectura (acceso restringido por rol) ──────────────

        // Portal del Estudiante — solo students
        Route::middleware('role:student')->group(function () {
            Route::get('student-portal/grades', [StudentPortalController::class, 'myGrades']);
            Route::get('student-portal/attendance', [StudentPortalController::class, 'myAttendance']);
            Route::get('student-dashboard', [StudentDashboardController::class, 'index']);
        });

        // Portal del Padre/Apoderado — solo parents
        Route::middleware('role:parent')->group(function () {
            Route::get('parent-portal/children', [ParentPortalController::class, 'myChildren']);
            Route::get('parent-portal/children/{studentId}', [ParentPortalController::class, 'childDetail']);
            Route::get('parent-dashboard', [ParentDashboardController::class, 'index']);
        });

        // ── Dashboard del Docente — teacher o admin ────────────────────────────
        Route::middleware('role:admin|teacher')->group(function () {
            Route::get('teacher/dashboard', [TeacherDashboardController::class, 'index']);
            Route::get('course-assignments/{id}/gradebook', [TeacherDashboardController::class, 'gradebook']);
        });

        // ── Gestión Académica (Asistencia y Calificaciones) — admin o teacher ──
        Route::middleware('role:admin|teacher')->group(function () {
            Route::get('attendance', [AttendanceController::class, 'index']);
            Route::post('attendance/bulk', [AttendanceController::class, 'storeBulk']);

            Route::apiResource('evaluations', EvaluationController::class);
            Route::post('evaluations/{evaluation}/publish', [EvaluationController::class, 'publish']);

            Route::get('grades', [GradeController::class, 'index']);
            Route::post('grades/bulk', [GradeController::class, 'storeBulk']);
        });

        // ── Administración General — solo admin ────────────────────────────────
        Route::middleware('role:admin|super_admin')->group(function () {

            // Students Core
            Route::patch('students/{student}/status', [StudentController::class, 'updateStatus']);
            Route::get('students/{student}/attendance', [StudentController::class, 'attendance']);
            Route::get('students/{student}/audit', [StudentController::class, 'audit']);
            Route::get('students/{student}/academic', [StudentAcademicController::class, 'getHistory']);

            // Academic Hierarchy
            Route::apiResource('academic-years', AcademicYearController::class);
            Route::apiResource('grade-levels', GradeLevelController::class);
            Route::apiResource('sections', SectionController::class);
            Route::get('academic-structure', [AcademicStructureController::class, 'index']);
            Route::get('sections/{section}/details', [SectionController::class, 'details']);
            Route::apiResource('academic-periods', AcademicPeriodController::class);
            Route::patch('academic-periods/{academic_period}/toggle-lock', [AcademicPeriodController::class, 'toggleLock']);

            // Courses CRUD
            Route::apiResource('courses', CourseController::class);

            // Enrollments, Students, Teachers, Users
            Route::apiResource('enrollments', EnrollmentController::class);
            Route::apiResource('students', StudentController::class);
            Route::apiResource('parents', \App\Http\Controllers\ParentController::class)->only(['index', 'store', 'show', 'destroy']);
            Route::apiResource('teachers', \App\Http\Controllers\TeacherController::class);
            Route::patch('teachers/{teacher}/status', [\App\Http\Controllers\TeacherController::class, 'toggleStatus']);
            Route::get('users', [\App\Http\Controllers\UserController::class, 'index']);
            Route::apiResource('course-assignments', CourseAssignmentController::class);

            // Settings (Configuración Institucional)
            Route::get('settings', [TenantSettingController::class, 'show']);
            Route::put('settings', [TenantSettingController::class, 'update']); // PUT soportado vía POST + _method

            // Reports
            Route::get('reports/student-report-card/{studentId}', [ReportController::class, 'studentReportCard']);
            Route::get('reports/section/{sectionId}/report-cards', [ReportController::class, 'generateSectionPdfs']);
            Route::get('reports/student-report-card/{studentId}/export', [ReportController::class, 'exportReportCardPdf']);
            Route::get('reports/enrollments/csv', [ReportController::class, 'exportEnrollmentsCsv']);

            // Student 360 Finance view (admin read)
            Route::get('students/{id}/finances', [StudentFinancesController::class, 'show']);

            // Events & Announcements
            Route::apiResource('events', EventController::class);
            Route::apiResource('event-participants', EventParticipantController::class);
            Route::apiResource('announcements', AnnouncementController::class);

            // Finanzas (protegido además por módulo activo)
            Route::middleware('module:finances')->group(function () {
                Route::apiResource('fees', FeeController::class)->only(['index', 'store', 'show']);
                Route::apiResource('payments', PaymentController::class)->only(['index', 'store']);
                Route::post('payments/{id}/void', [PaymentController::class, 'voidPayment']);
            });

            // ── Gestión de Familias ────────────────────────────────────────────
            Route::apiResource('families', FamilyController::class);

            // Miembros (apoderados/guardianes) de una familia
            Route::post('families/{family}/members', [FamilyController::class, 'addMember']);
            Route::delete('families/{family}/members/{member}', [FamilyController::class, 'removeMember']);
            Route::patch('families/{family}/members/{member}/toggle-access', [FamilyController::class, 'toggleMemberAccess']);

            // Estudiantes vinculados a una familia
            Route::post('families/{family}/students', [FamilyController::class, 'addStudent']);
            Route::delete('families/{family}/students/{student}', [FamilyController::class, 'removeStudent']);
        });
    });
});
