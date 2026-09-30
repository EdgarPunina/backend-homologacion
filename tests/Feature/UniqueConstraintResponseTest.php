<?php

namespace Tests\Feature;

use App\Models\AsignaturaCredito;
use App\Models\MallaCurricular;
use App\Models\ResolucionSolicitud;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use Illuminate\Testing\TestResponse;
use PDOException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\TestWith;
use Tests\Feature\Coordinator\CoordinatorWorkflowTestCase;

class UniqueConstraintResponseTest extends CoordinatorWorkflowTestCase
{
    #[DataProvider('identityFields')]
    public function test_admin_creation_returns_422_and_rolls_back_when_identity_collides_after_validation(string $field, string $message): void
    {
        $this->authenticateAdministrator();
        $existing = User::factory()->create();
        User::creating(function (User $user) use ($existing, $field): void {
            $user->{$field} = $existing->{$field};
        });

        $response = $this->postJson('/api/v1/admin/users', [
            ...$this->userPayload(), 'rol_id' => Role::query()->where('nombre', 'estudiante')->firstOrFail()->id,
        ]);

        $this->assertValidationResponse($response, $field, $message);
        $this->assertDatabaseCount('users', 2);
        $this->assertDatabaseCount('user_has_rol', 1);
    }

    #[DataProvider('identityFields')]
    public function test_admin_update_returns_422_and_preserves_user_when_identity_collides_after_validation(string $field, string $message): void
    {
        $this->authenticateAdministrator();
        $existing = User::factory()->create();
        $target = User::factory()->create();
        $original = $target->fresh()->getAttributes();
        User::updating(function (User $user) use ($existing, $field): void {
            $user->{$field} = $existing->{$field};
        });

        $response = $this->patchJson('/api/v1/admin/users/'.$target->id, [$field => $this->userPayload()[$field]]);

        $this->assertValidationResponse($response, $field, $message);
        $this->assertSame($original, $target->fresh()->getAttributes());
    }

    /** @return array<string, array{string, string}> */
    public static function identityFields(): array
    {
        return [
            'email' => ['email', 'El correo electrónico ya está registrado.'],
            'identity' => ['cedula', 'La cédula ya está registrada.'],
        ];
    }

    #[TestWith(['admin', 'resolucion'])]
    #[TestWith(['coordinator', 'resolution'])]
    public function test_resolution_number_collision_returns_422_and_removes_uploaded_file(string $prefix, string $suffix): void
    {
        $context = $this->scenario('en_consejo');
        $existing = $this->existingResolution($context);
        if ($prefix === 'admin') {
            $this->authenticateAdministrator();
        }
        ResolucionSolicitud::creating(function (ResolucionSolicitud $resolution) use ($existing): void {
            $resolution->numero_resolucion = $existing->numero_resolucion;
        });

        $response = $this->post('/api/v1/'.$prefix.'/solicitudes/'.$context['solicitud']->id.'/'.$suffix, [
            'numero_resolucion' => 'RES-NEW', 'fecha_aprobacion' => '2026-09-23',
            'archivo' => UploadedFile::fake()->createWithContent('resolucion.pdf', "%PDF-1.4\ncontenido"),
        ], ['Accept' => 'application/json']);

        $this->assertValidationResponse($response, 'numero_resolucion', 'El número de resolución ya está registrado.');
        $this->assertDatabaseCount('resoluciones_solicitud', 1);
        $this->assertSame([], Storage::disk('local')->allFiles('resoluciones'));
        $this->assertSame('en_consejo', $context['solicitud']->fresh()->ultimoHistorialEstado->estadoSolicitud->nombre);
    }

    public function test_resolution_per_request_collision_preserves_existing_409_contract(): void
    {
        $context = $this->scenario('en_consejo');
        $existing = $this->existingResolution($context);
        ResolucionSolicitud::creating(function (ResolucionSolicitud $resolution) use ($existing): void {
            $resolution->solicitud_id = $existing->solicitud_id;
        });

        $this->post('/api/v1/coordinator/solicitudes/'.$context['solicitud']->id.'/resolution', [
            'numero_resolucion' => 'RES-NEW', 'fecha_aprobacion' => '2026-09-23',
            'archivo' => UploadedFile::fake()->createWithContent('resolucion.pdf', "%PDF-1.4\ncontenido"),
        ], ['Accept' => 'application/json'])->assertConflict()->assertExactJson([
            'success' => false, 'message' => 'La solicitud ya tiene una resolución registrada.',
        ]);

        $this->assertDatabaseCount('resoluciones_solicitud', 1);
        $this->assertSame([], Storage::disk('local')->allFiles('resoluciones'));
    }

    #[TestWith(['create'])]
    #[TestWith(['update'])]
    public function test_subject_code_collision_after_precheck_returns_409(string $operation): void
    {
        $context = $this->scenario();
        $curriculum = MallaCurricular::query()->create([
            'nombre' => 'Malla institucional', 'tipo' => 'institucional',
            'carrera_id' => $context['career']->id, 'creador_id' => $context['coordinator']->id,
        ]);
        $payload = ['codigo_asignatura' => 'EXISTING', 'nombre_asignatura' => 'Matemática', 'numero_creditos' => 4, 'nivel_ciclo' => 'primero', 'hr_carga_horaria' => 64];
        $curriculum->asignaturas()->create($payload);
        $target = $curriculum->asignaturas()->create([...$payload, 'codigo_asignatura' => 'TARGET']);
        AsignaturaCredito::saving(function (AsignaturaCredito $subject): void {
            $subject->codigo_asignatura = 'EXISTING';
        });

        DB::beginTransaction();
        try {
            $response = $operation === 'create'
                ? $this->postJson('/api/v1/coordinator/curricula/'.$curriculum->id.'/subjects', [...$payload, 'codigo_asignatura' => 'NEW'])
                : $this->patchJson('/api/v1/coordinator/subjects/'.$target->id, ['codigo_asignatura' => 'NEW']);
        } finally {
            DB::rollBack();
        }

        $response->assertConflict()->assertExactJson([
            'success' => false, 'message' => 'El código de asignatura ya existe en la malla.',
        ]);
        $this->assertDatabaseCount('asignaturas_creditos', 2);
        $this->assertSame('TARGET', $target->fresh()->codigo_asignatura);
    }

    public function test_known_index_metadata_returns_422_without_diagnostic_parsing(): void
    {
        $exception = new UniqueConstraintViolationException('pgsql', 'insert into users ...', [], new PDOException);
        $exception->setIndex('users_email_unique');
        Route::get('/api/v1/testing/duplicate', function () use ($exception): never {
            throw $exception;
        });

        $this->assertValidationResponse($this->getJson('/api/v1/testing/duplicate'), 'email', 'El correo electrónico ya está registrado.');
    }

    public function test_unrelated_unique_constraint_keeps_default_error_response(): void
    {
        config(['app.debug' => false]);
        $this->seed(RoleSeeder::class);
        Route::post('/api/v1/testing/unrelated-constraint', fn () => DB::transaction(
            fn () => Role::query()->create(['nombre' => 'estudiante']),
        ));

        $this->postJson('/api/v1/testing/unrelated-constraint')->assertInternalServerError()->assertExactJson(['message' => 'Server Error']);

        $this->assertDatabaseCount('roles', 3);
    }

    public function test_non_unique_database_error_keeps_default_error_response(): void
    {
        config(['app.debug' => false]);
        $user = User::factory()->create();
        Route::post('/api/v1/testing/foreign-key', fn () => DB::transaction(
            fn () => $user->update(['creador_id' => 999999]),
        ));

        $this->postJson('/api/v1/testing/foreign-key')->assertInternalServerError()->assertExactJson(['message' => 'Server Error']);

        $this->assertNull($user->fresh()->creador_id);
    }

    public function test_web_requests_keep_default_error_response_for_known_unique_index(): void
    {
        config(['app.debug' => false]);
        $exception = new UniqueConstraintViolationException('pgsql', 'insert into users ...', [], new PDOException);
        $exception->setIndex('users_email_unique');
        Route::get('/testing/duplicate', function () use ($exception): never {
            throw $exception;
        });

        $this->get('/testing/duplicate')->assertInternalServerError()->assertHeader('Content-Type', 'text/html; charset=utf-8');
    }

    private function assertValidationResponse(TestResponse $response, string $field, string $message): void
    {
        $response->assertUnprocessable()->assertExactJson([
            'success' => false,
            'message' => 'Los datos proporcionados no son válidos.',
            'errors' => [$field => [$message]],
        ]);
    }

    private function authenticateAdministrator(): void
    {
        $this->seed(RoleSeeder::class);
        $admin = User::factory()->create();
        $admin->assignRole('administrador');
        $this->asUser($admin);
    }

    /** @param array<string, mixed> $context */
    private function existingResolution(array $context): ResolucionSolicitud
    {
        return ResolucionSolicitud::query()->create([
            'solicitud_id' => $context['otherSolicitud']->id, 'coordinador_id' => $context['otherCoordinator']->id,
            'numero_resolucion' => 'RES-EXISTING', 'fecha_aprobacion' => '2026-09-23', 'ruta_archivo' => 'existing.pdf',
        ]);
    }

    /** @return array<string, string> */
    private function userPayload(): array
    {
        return [
            'nombres_completos' => 'Usuario concurrente', 'cedula' => '0912345678',
            'email' => 'concurrent@example.com', 'numero_celular' => '0991234567', 'password' => 'Password123!',
        ];
    }
}
