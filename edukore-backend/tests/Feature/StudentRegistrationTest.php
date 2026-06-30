<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\User;
use App\Models\Tenant;
use App\Models\Student;
use App\Models\ParentProfile;

class StudentRegistrationTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    private User $admin;
    private Tenant $tenant;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->tenant = Tenant::factory()->create();
        $this->admin = User::factory()->create([
            'tenant_id' => $this->tenant->id,
        ]);
        
        // Asignar permiso a admin
        // ... (asumimos que en el Test de integración se usa withoutMiddleware o se mockea el permiso)
        $this->actingAs($this->admin);
    }

    public function test_can_register_student_with_parents_in_a_single_request()
    {
        $payload = [
            'student' => [
                'name' => 'Juanito Perez',
                'email' => 'juanito@example.com',
                'enrollment_number' => 'STD-2026-001',
                'date_of_birth' => '2010-05-15',
            ],
            'parents' => [
                [
                    'name' => 'Carlos Perez',
                    'email' => 'carlos.perez@example.com',
                    'phone' => '123456789',
                    'relationship' => 'Padre'
                ],
                [
                    'name' => 'Maria Gomez',
                    'email' => 'maria.gomez@example.com',
                    'phone' => '987654321',
                    'relationship' => 'Madre'
                ]
            ]
        ];

        $response = $this->postJson('/api/v1/students', $payload);

        $response->assertStatus(201);
        $response->assertJsonStructure([
            'message',
            'data' => [
                'id',
                'user' => ['name', 'email'],
                'parents' => [
                    '*' => ['user' => ['name', 'email'], 'pivot' => ['relationship']]
                ]
            ]
        ]);

        $this->assertDatabaseHas('users', ['email' => 'juanito@example.com', 'tenant_id' => $this->tenant->id]);
        $this->assertDatabaseHas('users', ['email' => 'carlos.perez@example.com', 'tenant_id' => $this->tenant->id]);
        $this->assertDatabaseHas('users', ['email' => 'maria.gomez@example.com', 'tenant_id' => $this->tenant->id]);

        $student = Student::whereHas('user', fn($q) => $q->where('email', 'juanito@example.com'))->first();
        $this->assertCount(2, $student->parents);
    }

    public function test_registration_rolls_back_if_validation_fails_midway()
    {
        $payload = [
            'student' => [
                'name' => 'Pedrito',
                'email' => 'pedrito@example.com',
                'enrollment_number' => 'STD-2026-002',
            ],
            'parents' => [
                [
                    'name' => 'Padre Sin Email',
                    // Falta el email, lo que causará un fallo en la validación
                    'phone' => '123456789',
                    'relationship' => 'Padre'
                ]
            ]
        ];

        $response = $this->postJson('/api/v1/students', $payload);

        $response->assertStatus(422);
        
        // Verify database is clean due to transaction rollback
        $this->assertDatabaseMissing('users', ['email' => 'pedrito@example.com']);
    }

    public function test_can_soft_delete_student_by_updating_status_to_retirado()
    {
        // Setup: Crear un estudiante
        $studentUser = User::factory()->create(['tenant_id' => $this->tenant->id]);
        $student = Student::create([
            'id' => \Illuminate\Support\Str::uuid(),
            'user_id' => $studentUser->id,
            'enrollment_number' => 'STD-003',
            'status' => 'activo'
        ]);

        $response = $this->patchJson("/api/v1/students/{$student->id}/status", [
            'status' => 'retirado'
        ]);

        $response->assertStatus(200);
        $response->assertJsonPath('data.status', 'retirado');

        $this->assertSoftDeleted('students', ['id' => $student->id]);
    }
}
