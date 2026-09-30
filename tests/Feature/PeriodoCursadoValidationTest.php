<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class PeriodoCursadoValidationTest extends TestCase
{
    use RefreshDatabase;

    #[DataProvider('validPeriods')]
    public function test_store_and_update_accept_supported_periods(string $period): void
    {
        $student = $this->student();
        $payload = $this->payload($period);

        $id = $this->postJson('/api/v1/student/antecedentes', $payload)
            ->assertCreated()->assertJsonPath('data.periodo_cursado', $period)->json('data.id');
        $this->patchJson('/api/v1/student/antecedentes/'.$id, ['periodo_cursado' => $period])
            ->assertOk()->assertJsonPath('data.periodo_cursado', $period);

        $this->assertDatabaseHas('antecedentes_academicos', [
            'id' => $id, 'estudiante_id' => $student->id, 'periodo_cursado' => $period,
        ]);
    }

    /** @return array<string, array{string}> */
    public static function validPeriods(): array
    {
        return [
            'existing single year' => ['2025'],
            'existing year pair' => ['2024-2025'],
            'earliest year' => ['1990'],
            'earliest pair' => ['1990-1991'],
            'next year' => [(string) (now()->year + 1)],
            'latest pair' => [now()->year.'-'.(now()->year + 1)],
        ];
    }

    #[DataProvider('invalidPeriods')]
    public function test_store_and_update_return_422_for_invalid_periods(mixed $period): void
    {
        $this->student();
        $id = $this->postJson('/api/v1/student/antecedentes', $this->payload('2025'))
            ->assertCreated()->json('data.id');

        $this->postJson('/api/v1/student/antecedentes', $this->payload($period))
            ->assertUnprocessable()->assertJsonValidationErrors('periodo_cursado')->assertJsonPath('success', false);
        $this->patchJson('/api/v1/student/antecedentes/'.$id, ['periodo_cursado' => $period])
            ->assertUnprocessable()->assertJsonValidationErrors('periodo_cursado')->assertJsonPath('success', false);

        $this->assertDatabaseCount('antecedentes_academicos', 1);
        $this->assertDatabaseHas('antecedentes_academicos', ['id' => $id, 'periodo_cursado' => '2025']);
    }

    /** @return array<string, array{mixed}> */
    public static function invalidPeriods(): array
    {
        return [
            'skipped year' => ['2025-2027'],
            'short year' => ['25'],
            'wrong separator' => ['2025/2026'],
            'text' => ['período reciente'],
            'empty' => [''],
            'only spaces' => ['   '],
            'too early' => ['1989'],
            'future year' => [(string) (now()->year + 2)],
            'future pair' => [(now()->year + 1).'-'.(now()->year + 2)],
            'reversed' => ['2025-2024'],
            'array' => [['2025']],
        ];
    }

    private function student(): User
    {
        $this->seed(RoleSeeder::class);
        $student = User::factory()->create();
        $student->assignRole('estudiante');
        $this->withToken($student->createToken('test')->plainTextToken);

        return $student;
    }

    /** @return array<string, mixed> */
    private function payload(mixed $period): array
    {
        return [
            'universidad_origen' => 'Universidad de origen',
            'carrera_origen' => 'Sistemas',
            'tipo_institucion' => 'pública',
            'periodo_cursado' => $period,
        ];
    }
}
