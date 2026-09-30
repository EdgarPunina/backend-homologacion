<?php

namespace Tests\Feature;

use App\Models\MallaCurricular;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Feature\Coordinator\CoordinatorWorkflowTestCase;

class ContentValidationTest extends CoordinatorWorkflowTestCase
{
    #[DataProvider('invalidNames')]
    public function test_person_names_return_422_in_admin_store_and_update(string $name): void
    {
        $this->seed(RoleSeeder::class);
        $payload = $this->userPayload($name);
        $admin = User::factory()->create();
        $admin->assignRole('administrador');
        $this->asUser($admin);
        $target = User::factory()->create();
        $roleId = Role::query()->where('nombre', 'estudiante')->firstOrFail()->id;
        $this->postJson('/api/v1/admin/users', [...$payload, 'rol_id' => $roleId])
            ->assertUnprocessable()->assertJsonValidationErrors('nombres_completos')->assertJsonPath('success', false);
        $this->patchJson('/api/v1/admin/users/'.$target->id, ['nombres_completos' => $name])
            ->assertUnprocessable()->assertJsonValidationErrors('nombres_completos')->assertJsonPath('success', false);

        $this->assertDatabaseMissing('users', ['email' => 'content-validation@example.com']);
    }

    /** @return array<string, array{string}> */
    public static function invalidNames(): array
    {
        return [
            'digits after letters' => ['Juan123'],
            'at sign' => ['Juan@'],
            'only digits' => ['12345'],
            'html tag' => ['<b>Juan</b>'],
            'only spaces' => ['   '],
            'emoji' => ['Juan 😀'],
            'multiple spaces' => ['Juan  Pérez'],
        ];
    }

    #[DataProvider('validNames')]
    public function test_admin_creation_accepts_valid_person_names(string $name): void
    {
        $this->seed(RoleSeeder::class);
        $admin = User::factory()->create();
        $admin->assignRole('administrador');
        $this->asUser($admin);
        $roleId = Role::query()->where('nombre', 'estudiante')->firstOrFail()->id;
        $this->postJson('/api/v1/admin/users', [...$this->userPayload($name), 'rol_id' => $roleId])
            ->assertCreated()->assertJsonPath('data.nombres_completos', $name);

        $this->assertDatabaseHas('users', ['email' => 'content-validation@example.com', 'nombres_completos' => $name]);
    }

    /** @return array<string, array{string}> */
    public static function validNames(): array
    {
        return [
            'accented' => ['María José'],
            'hyphenated' => ['Núñez-Pérez'],
            'apostrophe' => ["O'Connor"],
        ];
    }

    public function test_name_rule_returns_a_clear_spanish_message(): void
    {
        $this->seed(RoleSeeder::class);
        $admin = User::factory()->create();
        $admin->assignRole('administrador');
        $this->asUser($admin);
        $roleId = Role::query()->where('nombre', 'estudiante')->firstOrFail()->id;
        $this->postJson('/api/v1/admin/users', [
            ...$this->userPayload('Juan123'), 'rol_id' => $roleId,
        ])->assertUnprocessable()->assertJsonValidationErrors([
            'nombres_completos' => 'El campo nombres completos solo puede contener letras y espacios.',
        ]);
    }

    public function test_admin_creation_trims_outer_spaces_from_person_name(): void
    {
        $this->seed(RoleSeeder::class);
        $admin = User::factory()->create();
        $admin->assignRole('administrador');
        $this->asUser($admin);
        $roleId = Role::query()->where('nombre', 'estudiante')->firstOrFail()->id;
        $this->postJson('/api/v1/admin/users', [
            ...$this->userPayload('  María José  '), 'rol_id' => $roleId,
        ])->assertCreated()->assertJsonPath('data.nombres_completos', 'María José');

        $this->assertDatabaseHas('users', ['email' => 'content-validation@example.com', 'nombres_completos' => 'María José']);
    }

    #[DataProvider('paddedTenDigitFields')]
    public function test_identity_and_phone_reject_outer_spaces_in_admin_and_profile_requests(string $field, string $value): void
    {
        $this->seed(RoleSeeder::class);
        $payload = [...$this->userPayload('Ana Pérez'), $field => $value];
        $admin = User::factory()->create();
        $admin->assignRole('administrador');
        $this->asUser($admin);
        $target = User::factory()->create();
        $roleId = Role::query()->where('nombre', 'estudiante')->firstOrFail()->id;
        $this->postJson('/api/v1/admin/users', [...$payload, 'rol_id' => $roleId])
            ->assertUnprocessable()->assertJsonValidationErrors($field);
        $this->patchJson('/api/v1/admin/users/'.$target->id, [$field => $value])
            ->assertUnprocessable()->assertJsonValidationErrors($field);

        if ($field === 'numero_celular') {
            $student = User::factory()->create();
            $student->assignRole('estudiante');
            $this->asUser($student);
            $this->patchJson('/api/v1/student/profile', [$field => $value])
                ->assertUnprocessable()->assertJsonValidationErrors($field);
        }
    }

    /** @return array<string, array{string, string}> */
    public static function paddedTenDigitFields(): array
    {
        return [
            'identity leading' => ['cedula', ' 0912345678'],
            'identity trailing' => ['cedula', '0912345678 '],
            'phone leading' => ['numero_celular', ' 0991234567'],
            'phone trailing' => ['numero_celular', '0991234567 '],
        ];
    }

    public function test_factory_and_seeder_passwords_still_allow_login(): void
    {
        $context = $this->scenario();
        $this->postJson('/api/v1/login', [
            'email' => $context['student']->email, 'password' => 'password',
        ])->assertOk()->assertJsonStructure(['token']);
        $this->postJson('/api/v1/login', [
            'email' => 'test@example.com', 'password' => 'password',
        ])->assertOk()->assertJsonStructure(['token']);
    }

    #[DataProvider('invalidIntegers')]
    public function test_subject_store_and_update_reject_non_integer_content(mixed $value): void
    {
        $context = $this->scenario();
        $curriculum = $this->curriculum($context);
        $payload = $this->subjectPayload();
        $subject = $curriculum->asignaturas()->create($payload);
        $original = $subject->fresh()->getAttributes();

        $this->postJson('/api/v1/coordinator/curricula/'.$curriculum->id.'/subjects', [
            ...$payload, 'numero_creditos' => $value, 'hr_carga_horaria' => $value,
        ])->assertUnprocessable()->assertJsonValidationErrors(['numero_creditos', 'hr_carga_horaria']);
        $this->patchJson('/api/v1/coordinator/subjects/'.$subject->id, [
            'numero_creditos' => $value, 'hr_carga_horaria' => $value,
        ])->assertUnprocessable()->assertJsonValidationErrors(['numero_creditos', 'hr_carga_horaria']);

        $this->assertDatabaseCount('asignaturas_creditos', 1);
        $this->assertSame($original, $subject->fresh()->getAttributes());
    }

    /** @return array<string, array{mixed}> */
    public static function invalidIntegers(): array
    {
        return [
            'letters' => ['abc'],
            'alphanumeric' => ['12a45'],
            'scientific' => ['1e3'],
            'hexadecimal' => ['0x1F'],
            'negative' => [-5],
            'decimal' => [5.5],
            'spaces' => [' 12'],
            'plus sign' => ['+12'],
            'numeric string' => ['12'],
            'array' => [[12]],
        ];
    }

    #[DataProvider('invalidTotals')]
    public function test_total_credits_return_422_for_invalid_content(mixed $total): void
    {
        $context = $this->scenario('en_proceso');
        $this->postJson('/api/v1/coordinator/solicitudes/'.$context['solicitud']->id.'/result', [
            'conclusion_general' => 'parcial', 'total_creditos_reconocidos' => $total,
        ])->assertUnprocessable()->assertJsonValidationErrors('total_creditos_reconocidos')->assertJsonPath('success', false);

        $this->assertDatabaseCount('resultados_solicitud', 0);
    }

    /** @return array<string, array{mixed}> */
    public static function invalidTotals(): array
    {
        return [
            'negative' => [-1],
            'above column maximum' => [2147483648],
            'decimal' => [1.5],
            'numeric string' => ['4'],
            'array' => [[4]],
        ];
    }

    #[DataProvider('validTotals')]
    public function test_total_credits_accept_column_boundaries_without_changing_service_calculation(int $total): void
    {
        $context = $this->scenario('en_revision');
        $document = $context['solicitud']->documentos()->firstOrFail();
        $this->patchJson('/api/v1/coordinator/documents/'.$document->id.'/review', ['estado' => 'aprobado'])->assertOk();
        $this->postJson('/api/v1/coordinator/documents/'.$document->id.'/verification', ['estado' => true])->assertOk();
        $origin = $this->curriculum($context, 'origen')->asignaturas()->create($this->subjectPayload());
        $destination = $this->curriculum($context)->asignaturas()->create($this->subjectPayload());
        $this->postJson('/api/v1/coordinator/solicitudes/'.$context['solicitud']->id.'/comparisons', [
            'asignatura_origen_id' => $origin->id, 'asignatura_destino_id' => $destination->id,
            'porcentaje_coincidencia' => 87.5,
        ])->assertCreated();

        $this->postJson('/api/v1/coordinator/solicitudes/'.$context['solicitud']->id.'/result', [
            'conclusion_general' => 'parcial', 'total_creditos_reconocidos' => $total,
        ])->assertCreated()->assertJsonPath('data.total_creditos_reconocidos', $total);

        $this->assertDatabaseHas('resultados_solicitud', [
            'solicitud_id' => $context['solicitud']->id, 'total_creditos_reconocidos' => $total,
        ]);
    }

    /** @return array<string, array{int}> */
    public static function validTotals(): array
    {
        return ['zero' => [0], 'column maximum' => [2147483647]];
    }

    #[DataProvider('invalidPercentages')]
    public function test_comparison_store_and_update_reject_invalid_decimal_content(mixed $percentage): void
    {
        $context = $this->scenario('en_proceso');
        $origin = $this->curriculum($context, 'origen')->asignaturas()->create($this->subjectPayload());
        $destination = $this->curriculum($context)->asignaturas()->create($this->subjectPayload());
        $base = ['asignatura_origen_id' => $origin->id, 'asignatura_destino_id' => $destination->id];
        $comparison = $context['solicitud']->comparacionesAsignaturas()->create([
            ...$base, 'porcentaje_coincidencia' => 50,
        ]);

        $this->postJson('/api/v1/coordinator/solicitudes/'.$context['solicitud']->id.'/comparisons', [
            ...$base, 'porcentaje_coincidencia' => $percentage,
        ])->assertUnprocessable()->assertJsonValidationErrors('porcentaje_coincidencia');
        $this->putJson('/api/v1/coordinator/comparisons/'.$comparison->id, [
            ...$base, 'porcentaje_coincidencia' => $percentage,
        ])->assertUnprocessable()->assertJsonValidationErrors('porcentaje_coincidencia');

        $this->assertDatabaseHas('comparaciones_asignatura', ['id' => $comparison->id, 'porcentaje_coincidencia' => '50.00']);
    }

    /** @return array<string, array{mixed}> */
    public static function invalidPercentages(): array
    {
        return [
            'comma' => ['10,5'],
            'three decimals' => ['10.555'],
            'over 100' => [100.01],
            'negative' => [-0.01],
            'scientific' => ['1e2'],
            'array' => [[10]],
        ];
    }

    /** @return array<string, string> */
    private function userPayload(string $name): array
    {
        return [
            'nombres_completos' => $name, 'cedula' => '0912345678',
            'email' => 'content-validation@example.com', 'numero_celular' => '0991234567',
            'password' => 'Abcdefg1',
        ];
    }

    /** @param array<string, mixed> $context */
    private function curriculum(array $context, string $type = 'institucional'): MallaCurricular
    {
        return MallaCurricular::query()->create([
            'nombre' => 'Malla '.$type, 'tipo' => $type, 'creador_id' => $context['coordinator']->id,
            'carrera_id' => $type === 'institucional' ? $context['career']->id : null,
            'estudiante_id' => $type === 'origen' ? $context['student']->id : null,
        ]);
    }

    /** @return array<string, int|string> */
    private function subjectPayload(): array
    {
        return [
            'codigo_asignatura' => 'MAT-101', 'nombre_asignatura' => 'Matemática',
            'numero_creditos' => 4, 'nivel_ciclo' => 'primero', 'hr_carga_horaria' => 64,
        ];
    }
}
