<?php

namespace Tests\Feature\Admin;

use App\Models\Carrera;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class CoordinatorCareerTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_put_replaces_coordinator_careers_without_duplicates(): void
    {
        $this->authenticateAdministrator();
        $coordinator = User::factory()->create();
        $coordinator->assignRole('Coordinador');
        $first = Carrera::query()->create(['nombre' => 'Software']);
        $second = Carrera::query()->create(['nombre' => 'Telecomunicaciones']);
        $third = Carrera::query()->create(['nombre' => 'Industrial']);
        $coordinator->carrerasCoordinadas()->sync([$first->id]);

        $this->putJson("/api/v1/admin/coordinators/{$coordinator->id}/careers", [
            'carrera_ids' => [$second->id, $third->id],
        ])->assertOk()->assertJsonCount(2, 'data');

        $this->assertDatabaseMissing('coordinador_carreras', ['coordinador_id' => $coordinator->id, 'carrera_id' => $first->id]);
        $this->assertDatabaseHas('coordinador_carreras', ['coordinador_id' => $coordinator->id, 'carrera_id' => $second->id]);
        $this->assertSame(2, $coordinator->carrerasCoordinadas()->count());

        $this->putJson("/api/v1/admin/coordinators/{$coordinator->id}/careers", [
            'carrera_ids' => [$second->id, $second->id],
        ])->assertUnprocessable()->assertJsonValidationErrors('carrera_ids.1');
    }

    public function test_careers_cannot_be_assigned_to_a_non_coordinator(): void
    {
        $this->authenticateAdministrator();
        $student = User::factory()->create();
        $student->assignRole('Estudiante');
        $career = Carrera::query()->create(['nombre' => 'Software']);

        $this->putJson("/api/v1/admin/coordinators/{$student->id}/careers", [
            'carrera_ids' => [$career->id],
        ])->assertUnprocessable();
        $this->assertDatabaseCount('coordinador_carreras', 0);
    }

    public function test_career_catalog_is_available_to_administrator(): void
    {
        $this->authenticateAdministrator();
        Carrera::query()->create(['nombre' => 'Software']);

        $this->getJson('/api/v1/admin/careers')->assertOk()->assertJsonPath('data.0.nombre', 'Software');
    }

    private function authenticateAdministrator(): User
    {
        $this->seed(RoleSeeder::class);
        $admin = User::factory()->create();
        $admin->assignRole('Administrador');
        $this->withToken($admin->createToken('test')->plainTextToken);

        return $admin;
    }
}
