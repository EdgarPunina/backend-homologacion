<?php

namespace Tests\Feature;

use App\Models\Carrera;
use App\Models\MallaCurricular;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\TestWith;
use Tests\Feature\Coordinator\CoordinatorWorkflowTestCase;

class InputValidationTest extends CoordinatorWorkflowTestCase
{
    #[DataProvider('invalidUserFields')]
    public function test_admin_creation_returns_422_for_invalid_identity_name_or_password(string $field, string $value, string $message): void
    {
        $this->authenticate('administrador');
        $payload = [...$this->userPayload(), $field => $value, 'rol_id' => Role::query()->where('nombre', 'estudiante')->firstOrFail()->id];

        $this->postJson('/api/v1/admin/users', $payload)->assertUnprocessable()
            ->assertJsonValidationErrors([$field => $message]);

        $this->assertDatabaseCount('users', 1);
    }

    #[DataProvider('invalidUserFields')]
    public function test_admin_update_returns_422_without_changing_invalid_fields(string $field, string $value, string $message): void
    {
        $this->authenticate('administrador');
        $user = User::factory()->create();
        $original = $user->fresh()->getAttributes();

        $this->patchJson('/api/v1/admin/users/'.$user->id, [$field => $value])->assertUnprocessable()
            ->assertJsonValidationErrors([$field => $message]);

        $this->assertSame($original, $user->fresh()->getAttributes());
    }

    /** @return array<string, array{string, string, string}> */
    public static function invalidUserFields(): array
    {
        return [
            'short identity' => ['cedula', '091234567', 'La cédula debe contener exactamente 10 dígitos numéricos.'],
            'long identity' => ['cedula', '09123456789', 'La cédula debe contener exactamente 10 dígitos numéricos.'],
            'identity with letters' => ['cedula', '091234567A', 'La cédula debe contener exactamente 10 dígitos numéricos.'],
            'identity with symbol' => ['cedula', '+912345678', 'La cédula debe contener exactamente 10 dígitos numéricos.'],
            'identity with internal whitespace' => ['cedula', '09123 5678', 'La cédula debe contener exactamente 10 dígitos numéricos.'],
            'short phone' => ['numero_celular', '099123456', 'El número celular debe contener exactamente 10 dígitos numéricos.'],
            'long phone' => ['numero_celular', '09912345678', 'El número celular debe contener exactamente 10 dígitos numéricos.'],
            'phone with letters' => ['numero_celular', '099123456A', 'El número celular debe contener exactamente 10 dígitos numéricos.'],
            'short name' => ['nombres_completos', 'An', 'Los nombres completos deben tener al menos 3 caracteres.'],
            'short password' => ['password', 'Clave12', 'La contraseña debe tener al menos 8 caracteres.'],
            'password without numbers' => ['password', 'password', 'La contraseña debe contener al menos un número.'],
            'password without letters' => ['password', '12345678', 'La contraseña debe contener al menos una letra.'],
        ];
    }

    public function test_admin_update_keeps_own_unique_identity_and_accepts_minimum_password_without_confirmation(): void
    {
        $this->authenticate('administrador');
        $user = User::factory()->create(['cedula' => '0012345678']);

        $this->patchJson('/api/v1/admin/users/'.$user->id, [
            'cedula' => $user->cedula, 'email' => $user->email,
            'numero_celular' => '0012345678', 'password' => 'Abcdefg1',
        ])->assertOk()->assertJsonPath('data.cedula', '0012345678');

        $this->assertSame('0012345678', $user->fresh()->numero_celular);
    }

    #[TestWith(['099123456'])]
    #[TestWith(['09912345678'])]
    #[TestWith(['099123456A'])]
    #[TestWith(['+991234567'])]
    public function test_profile_returns_422_for_invalid_phone_without_changing_it(string $phone): void
    {
        $student = $this->authenticate('estudiante');

        $this->patchJson('/api/v1/student/profile', ['numero_celular' => $phone])->assertUnprocessable()
            ->assertJsonValidationErrors(['numero_celular' => 'El número celular debe contener exactamente 10 dígitos numéricos.']);

        $this->assertSame($student->numero_celular, $student->fresh()->numero_celular);
    }

    #[DataProvider('catalogEndpoints')]
    public function test_catalog_filters_accept_existing_names_and_ids(string $role, string $endpoint, string $recordsPath): void
    {
        $context = $this->scenario();
        if ($role === 'administrador') {
            $this->authenticate($role);
        }
        $state = $context['solicitud']->ultimoHistorialEstado->estadoSolicitud;
        $process = $context['solicitud']->tramiteProceso;

        foreach (['nombre', 'id'] as $attribute) {
            $query = http_build_query([
                'estado' => $state->{$attribute}, 'tipo_tramite' => $process->tipoTramite->{$attribute},
                'tipo_proceso' => $process->tipoProceso->{$attribute}, 'estudiante' => $context['student']->email,
            ]);

            $this->getJson($endpoint.'?'.$query)->assertOk()->assertJsonCount(1, $recordsPath);
        }
    }

    #[DataProvider('catalogEndpoints')]
    public function test_unknown_catalog_values_return_422(string $role, string $endpoint, string $recordsPath): void
    {
        $this->scenario();
        if ($role === 'administrador') {
            $this->authenticate($role);
        }

        foreach (['estado', 'tipo_tramite', 'tipo_proceso'] as $field) {
            foreach (['inexistente', '999999', '1.5', '1e0', '999999999999999999999999999'] as $value) {
                $this->getJson($endpoint.'?'.http_build_query([$field => $value]))
                    ->assertUnprocessable()->assertJsonValidationErrors($field);
            }
        }
    }

    #[DataProvider('catalogEndpoints')]
    public function test_date_filters_require_iso_dates_and_preserve_chronological_order(string $role, string $endpoint, string $recordsPath): void
    {
        $this->scenario();
        if ($role === 'administrador') {
            $this->authenticate($role);
        }

        $this->getJson($endpoint.'?fecha_desde=2026-9-01&fecha_hasta=2026-9-30')
            ->assertUnprocessable()->assertJsonValidationErrors(['fecha_desde', 'fecha_hasta']);
        $this->getJson($endpoint.'?fecha_desde=2026-09-30&fecha_hasta=2026-09-01')
            ->assertUnprocessable()->assertJsonValidationErrors('fecha_hasta');
        $this->getJson($endpoint.'?fecha_desde=2026-09-01&fecha_hasta=2026-09-30')->assertOk();
    }

    /** @return array<string, array{string, string, string}> */
    public static function catalogEndpoints(): array
    {
        return [
            'admin list' => ['administrador', '/api/v1/admin/solicitudes', 'data'],
            'admin report' => ['administrador', '/api/v1/admin/reports/solicitudes', 'data.registros'],
            'coordinator list' => ['coordinador', '/api/v1/coordinator/solicitudes', 'data'],
            'coordinator report' => ['coordinador', '/api/v1/coordinator/reports/solicitudes', 'data.registros'],
        ];
    }

    #[TestWith(['admin', 'administrador', 'resolucion'])]
    #[TestWith(['coordinator', 'coordinador', 'resolution'])]
    public function test_resolution_rejects_non_iso_approval_date_before_storing_file(string $prefix, string $role, string $suffix): void
    {
        $context = $this->scenario('en_consejo');
        if ($role === 'administrador') {
            $this->authenticate($role);
        }

        $this->post('/api/v1/'.$prefix.'/solicitudes/'.$context['solicitud']->id.'/'.$suffix, [
            'numero_resolucion' => 'RES-FECHA', 'fecha_aprobacion' => '2026-9-23',
            'archivo' => UploadedFile::fake()->createWithContent('resolucion.pdf', "%PDF-1.4\ncontenido"),
        ], ['Accept' => 'application/json'])->assertUnprocessable()
            ->assertJsonValidationErrors(['fecha_aprobacion' => 'La fecha de aprobación debe tener el formato AAAA-MM-DD.']);

        $this->assertDatabaseCount('resoluciones_solicitud', 0);
        $this->assertSame([], Storage::disk('local')->allFiles('resoluciones'));
    }

    public function test_career_assignment_accepts_50_and_rejects_51_without_changing_assignments(): void
    {
        $this->authenticate('administrador');
        $coordinator = User::factory()->create();
        $coordinator->assignRole('coordinador');
        $careerIds = [];
        for ($number = 1; $number <= 51; $number++) {
            $careerIds[] = Carrera::query()->create(['nombre' => 'Carrera '.$number])->id;
        }
        $endpoint = '/api/v1/admin/coordinators/'.$coordinator->id.'/careers';

        $this->putJson($endpoint, ['carrera_ids' => array_slice($careerIds, 0, 50)])->assertOk()->assertJsonCount(50, 'data');
        $this->putJson($endpoint, ['carrera_ids' => $careerIds])->assertUnprocessable()
            ->assertJsonValidationErrors(['carrera_ids' => 'No puede asignar más de 50 carreras a la vez.']);

        $this->assertSame(50, $coordinator->carrerasCoordinadas()->count());
        $this->assertDatabaseMissing('coordinador_carreras', ['coordinador_id' => $coordinator->id, 'carrera_id' => $careerIds[50]]);
    }

    #[TestWith(['institucional', 'estudiante_id'])]
    #[TestWith(['origen', 'carrera_id'])]
    public function test_curriculum_rejects_the_field_for_the_other_type(string $type, string $prohibited): void
    {
        $context = $this->scenario();

        $this->postJson('/api/v1/coordinator/curricula', [
            'nombre' => 'Malla de prueba', 'tipo' => $type,
            'carrera_id' => $context['career']->id, 'estudiante_id' => $context['student']->id,
        ])->assertUnprocessable()->assertJsonValidationErrors($prohibited);

        $this->assertDatabaseCount('mallas_curriculares', 0);
    }

    #[TestWith([0])]
    #[TestWith([2147483647])]
    public function test_subject_store_and_update_accept_integer_boundaries(int $value): void
    {
        $context = $this->scenario();
        $curriculum = $this->curriculum($context);
        $payload = $this->subjectPayload();
        $payload['numero_creditos'] = $value;
        $payload['hr_carga_horaria'] = $value;

        $id = $this->postJson('/api/v1/coordinator/curricula/'.$curriculum->id.'/subjects', $payload)
            ->assertCreated()->json('data.id');
        $this->patchJson('/api/v1/coordinator/subjects/'.$id, [
            'numero_creditos' => $value, 'hr_carga_horaria' => $value,
        ])->assertOk();

        $this->assertDatabaseHas('asignaturas_creditos', ['id' => $id, 'numero_creditos' => $value, 'hr_carga_horaria' => $value]);
    }

    public function test_subject_store_and_update_reject_integer_overflow_without_writes(): void
    {
        $context = $this->scenario();
        $curriculum = $this->curriculum($context);
        $subject = $curriculum->asignaturas()->create($this->subjectPayload());
        $original = $subject->fresh()->getAttributes();
        $invalid = ['numero_creditos' => 2147483648, 'hr_carga_horaria' => 2147483648];

        $this->postJson('/api/v1/coordinator/curricula/'.$curriculum->id.'/subjects', [...$this->subjectPayload(), ...$invalid])
            ->assertUnprocessable()->assertJsonValidationErrors(array_keys($invalid));
        $this->patchJson('/api/v1/coordinator/subjects/'.$subject->id, $invalid)
            ->assertUnprocessable()->assertJsonValidationErrors(array_keys($invalid));

        $this->assertDatabaseCount('asignaturas_creditos', 1);
        $this->assertSame($original, $subject->fresh()->getAttributes());
    }

    public function test_comparisons_reject_excess_decimals_on_create_and_update_and_preserve_boundaries(): void
    {
        $context = $this->scenario('en_proceso');
        $origin = $this->curriculum($context, 'origen')->asignaturas()->create($this->subjectPayload());
        $destination = $this->curriculum($context)->asignaturas()->create($this->subjectPayload());
        $endpoint = '/api/v1/coordinator/solicitudes/'.$context['solicitud']->id.'/comparisons';
        $payload = ['asignatura_origen_id' => $origin->id, 'asignatura_destino_id' => $destination->id];

        $this->postJson($endpoint, [...$payload, 'porcentaje_coincidencia' => '82.555'])->assertUnprocessable()
            ->assertJsonValidationErrors(['porcentaje_coincidencia' => 'El porcentaje de coincidencia debe tener como máximo 2 decimales.']);
        $this->assertDatabaseCount('comparaciones_asignatura', 0);
        $id = $this->postJson($endpoint, [...$payload, 'porcentaje_coincidencia' => '82.55'])
            ->assertCreated()->assertJsonPath('data.porcentaje_coincidencia', '82.55')->json('data.id');
        $updateEndpoint = '/api/v1/coordinator/comparisons/'.$id;
        $this->putJson($updateEndpoint, [...$payload, 'porcentaje_coincidencia' => '82.555'])->assertUnprocessable()
            ->assertJsonValidationErrors('porcentaje_coincidencia');
        $this->assertDatabaseHas('comparaciones_asignatura', ['id' => $id, 'porcentaje_coincidencia' => '82.55']);
        $this->putJson($updateEndpoint, [...$payload, 'porcentaje_coincidencia' => 0])->assertOk()->assertJsonPath('data.porcentaje_coincidencia', '0.00');
        $this->putJson($updateEndpoint, [...$payload, 'porcentaje_coincidencia' => 100])->assertOk()->assertJsonPath('data.porcentaje_coincidencia', '100.00');
    }

    private function authenticate(string $role): User
    {
        $this->seed(RoleSeeder::class);
        $user = User::factory()->create();
        $user->assignRole($role);
        $this->asUser($user);

        return $user;
    }

    /** @return array<string, string> */
    private function userPayload(): array
    {
        return [
            'nombres_completos' => 'Ana Pérez', 'cedula' => '0912345678',
            'email' => 'validation@example.com', 'numero_celular' => '0991234567', 'password' => 'Abcdefg1',
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
