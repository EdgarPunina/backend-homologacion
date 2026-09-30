<?php

use App\Http\Middleware\EnsureAccountIsActive;
use App\Http\Middleware\EnsureUserHasRole;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->trimStrings(except: ['cedula', 'numero_celular']);
        $middleware->alias([
            'active' => EnsureAccountIsActive::class,
            'role' => EnsureUserHasRole::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (UniqueConstraintViolationException $exception, Request $request): ?JsonResponse {
            if (! $request->is('api/*')) {
                return null;
            }

            $index = $exception->index;

            if ($index === null && ($exception->errorInfo[0] ?? null) === '23505') {
                $diagnostic = explode("\n", $exception->errorInfo[2] ?? '')[0];
                if (preg_match('/["«]([^"»]+)["»]\s*$/u', $diagnostic, $matches) === 1) {
                    $index = $matches[1];
                }
            }

            $conflict = match ($index) {
                'resoluciones_solicitud_solicitud_id_unique' => 'La solicitud ya tiene una resolución registrada.',
                'asignaturas_creditos_malla_curricular_id_codigo_asignatura_unique',
                'asignaturas_creditos_malla_curricular_id_codigo_asignatura_uniq' => 'El código de asignatura ya existe en la malla.',
                default => null,
            };

            if ($conflict !== null) {
                return response()->json(['success' => false, 'message' => $conflict], 409);
            }

            $errors = match ($index) {
                'users_email_unique' => ['email' => ['El correo electrónico ya está registrado.']],
                'users_cedula_unique' => ['cedula' => ['La cédula ya está registrada.']],
                'resoluciones_solicitud_numero_resolucion_unique' => ['numero_resolucion' => ['El número de resolución ya está registrado.']],
                default => null,
            };

            if ($errors !== null) {
                return response()->json([
                    'success' => false,
                    'message' => 'Los datos proporcionados no son válidos.',
                    'errors' => $errors,
                ], 422);
            }

            return null;
        });
        $exceptions->render(function (HttpException $exception, Request $request) {
            $status = $exception->getStatusCode();
            if ($request->is('api/*') && $status >= 400 && $status < 500) {
                $message = $status === 404 ? 'Recurso no encontrado.' : ($exception->getMessage() ?: 'No se pudo completar la petición.');

                return response()->json(['success' => false, 'message' => $message], $status, $exception->getHeaders());
            }
        });
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );

        $exceptions->render(function (AuthenticationException $exception, Request $request) {
            if ($request->is('api/*')) {
                return response()->json(['success' => false, 'message' => 'No autenticado.'], 401);
            }
        });

        $exceptions->render(function (AuthorizationException $exception, Request $request) {
            if ($request->is('api/*')) {
                return response()->json([
                    'success' => false,
                    'message' => 'No tiene permisos para realizar esta acción.',
                ], 403);
            }
        });

        $exceptions->render(function (NotFoundHttpException $exception, Request $request) {
            if ($request->is('api/*')) {
                return response()->json(['success' => false, 'message' => 'Recurso no encontrado.'], 404);
            }
        });

        $exceptions->render(function (ValidationException $exception, Request $request) {
            if ($request->is('api/*')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Los datos proporcionados no son válidos.',
                    'errors' => $exception->errors(),
                ], 422);
            }
        });
    })->create();
