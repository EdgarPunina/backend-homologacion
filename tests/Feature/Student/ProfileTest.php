<?php

namespace Tests\Feature\Student;

use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

class ProfileTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_student_can_read_own_profile_without_credentials(): void
    {
        $student = $this->authenticateStudent();

        $this->getJson('/api/v1/student/profile')->assertOk()
            ->assertJsonPath('data.id', $student->id)
            ->assertJsonPath('data.email', $student->email)
            ->assertJsonMissingPath('data.password')
            ->assertJsonMissingPath('data.remember_token');
    }

    public function test_student_can_update_profile_without_changing_protected_fields(): void
    {
        $student = $this->authenticateStudent();
        $other = User::factory()->create();
        $originalPassword = $student->password;

        $this->patchJson('/api/v1/student/profile', [
            'nombres_completos' => 'Nombre actualizado',
            'email' => 'nuevo@example.com',
            'numero_celular' => '0991111111',
            'id' => $other->id,
            'cuenta_activa' => false,
            'rol_id' => 1,
            'roles' => ['Administrador'],
            'creador_id' => $other->id,
            'password' => 'ChangedPassword123!',
            'email_verified_at' => now()->toISOString(),
        ])->assertOk()->assertJsonPath('data.nombres_completos', 'Nombre actualizado');

        $student->refresh();
        $this->assertSame('nuevo@example.com', $student->email);
        $this->assertSame('0991111111', $student->numero_celular);
        $this->assertNull($student->email_verified_at);
        $this->assertNull($student->creador_id);
        $this->assertTrue($student->cuenta_activa);
        $this->assertSame($originalPassword, $student->password);
        $this->assertSame(['Estudiante'], $student->getRoleNames()->all());
        $this->assertSame($other->nombres_completos, $other->fresh()->nombres_completos);
    }

    public function test_student_can_keep_own_identity_and_email_verification(): void
    {
        $student = $this->authenticateStudent();

        $this->patchJson('/api/v1/student/profile', ['email' => $student->email, 'cedula' => $student->cedula])
            ->assertOk();

        $this->assertNotNull($student->fresh()->email_verified_at);
    }

    public function test_duplicate_identity_returns_422_without_changing_profile(): void
    {
        $student = $this->authenticateStudent();
        $other = User::factory()->create();

        $this->patchJson('/api/v1/student/profile', ['email' => $other->email, 'cedula' => $other->cedula])
            ->assertUnprocessable()->assertJsonValidationErrors(['email', 'cedula']);

        $this->assertSame($student->email, $student->fresh()->email);
        $this->assertSame($student->cedula, $student->fresh()->cedula);
    }

    public function test_invalid_profile_fields_return_422(): void
    {
        $this->authenticateStudent();

        $this->patchJson('/api/v1/student/profile', [
            'nombres_completos' => '', 'cedula' => '1', 'email' => 'invalid', 'numero_celular' => '1',
        ])->assertUnprocessable()->assertJsonValidationErrors(['nombres_completos', 'cedula', 'email', 'numero_celular']);
    }

    public function test_profile_requires_authentication_and_student_role(): void
    {
        $this->getJson('/api/v1/student/profile')->assertUnauthorized();
        $this->patchJson('/api/v1/student/profile', [])->assertUnauthorized();
        $this->seed(RoleSeeder::class);

        foreach (['Administrador', 'Coordinador'] as $role) {
            $user = User::factory()->create();
            $user->assignRole($role);
            Auth::forgetGuards();
            $this->withToken($user->createToken('test')->plainTextToken);
            $this->getJson('/api/v1/student/profile')->assertForbidden();
            $this->patchJson('/api/v1/student/profile', ['nombres_completos' => 'No permitido'])->assertForbidden();
            $this->assertSame($user->nombres_completos, $user->fresh()->nombres_completos);
        }
    }

    public function test_inactive_student_cannot_update_profile(): void
    {
        $student = $this->authenticateStudent();
        $student->update(['cuenta_activa' => false]);

        $this->patchJson('/api/v1/student/profile', ['nombres_completos' => 'No permitido'])->assertForbidden();

        $this->assertSame($student->nombres_completos, $student->fresh()->nombres_completos);
    }

    private function authenticateStudent(): User
    {
        $this->seed(RoleSeeder::class);
        $student = User::factory()->create();
        $student->assignRole('Estudiante');
        $this->withToken($student->createToken('test')->plainTextToken);

        return $student;
    }
}
