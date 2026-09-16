<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Laravel\Sanctum\PersonalAccessToken;
use Tests\TestCase;

class ApiAuthenticationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
    }

    public function test_registration_assigns_only_student_role_and_issues_token(): void
    {
        $response = $this->postJson('/api/v1/register', [
            'name' => 'Ana',
            'email' => 'ana@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'role' => 'Administrador',
        ]);

        $response->assertCreated()->assertJsonPath('user.roles.0', 'Estudiante')->assertJsonStructure(['token']);
        $this->assertFalse(User::whereEmail('ana@example.com')->firstOrFail()->hasRole('Administrador'));
    }

    public function test_login_me_and_logout_revoke_only_current_token(): void
    {
        $user = User::factory()->create(['password' => 'password123']);
        $user->assignRole('Coordinador');
        $otherToken = $user->createToken('other')->plainTextToken;

        $this->postJson('/api/v1/login', ['email' => $user->email, 'password' => 'wrong'])->assertUnauthorized();
        $token = $this->postJson('/api/v1/login', ['email' => $user->email, 'password' => 'password123'])
            ->assertOk()->json('token');

        $this->withToken($token)->getJson('/api/v1/me')->assertOk()->assertJsonPath('user.roles.0', 'Coordinador');
        $this->withToken($token)->postJson('/api/v1/logout')->assertOk();
        $this->assertSame(1, PersonalAccessToken::count());
        Auth::forgetGuards();
        $this->withToken($token)->getJson('/api/v1/me')->assertUnauthorized();
        Auth::forgetGuards();
        $this->withToken($otherToken)->getJson('/api/v1/me')->assertOk();
    }

    public function test_administrator_route_rejects_guests_and_other_roles(): void
    {
        $this->getJson('/api/v1/roles')->assertUnauthorized();

        $student = User::factory()->create();
        $student->assignRole('Estudiante');
        $this->withToken($student->createToken('test')->plainTextToken)
            ->getJson('/api/v1/roles')->assertForbidden();

        $admin = User::factory()->create();
        $admin->assignRole('Administrador');
        Auth::forgetGuards();
        $this->withToken($admin->createToken('test')->plainTextToken)
            ->getJson('/api/v1/roles')->assertOk()->assertJsonFragment(['Administrador']);
    }

    public function test_registration_validates_input(): void
    {
        $this->postJson('/api/v1/register', [
            'name' => 'Ana',
            'email' => 'invalid',
            'password' => 'short',
        ])->assertUnprocessable()->assertJsonValidationErrors(['email', 'password']);

    }
}
