# Backend de homologación

API REST en Laravel 13 para la gestión de trámites de homologación y reconocimiento. La autenticación por token usa Laravel Sanctum y los roles usan Spatie Laravel Permission. Las rutas actuales pertenecen a la versión `/api/v1`.

## Preparación local

Requisitos: PHP `^8.3`, Composer y la extensión de base de datos correspondiente. Copia `.env.example` a `.env`, configura `APP_KEY` y la conexión `DB_*`, e instala las dependencias:

```sh
composer install --no-interaction --prefer-dist
php artisan key:generate
php artisan migrate
php artisan db:seed
php artisan serve
```

Ejecuta `key:generate` solo si `APP_KEY` está vacía. Las migraciones y seeders afectan la base indicada en `.env`; comprueba esa conexión antes de ejecutarlos. El seeder crea `Estudiante`, `Coordinador` y `Administrador`. En entornos `local` y `testing` también conserva la cuenta de prueba `test@example.com` con contraseña `password` y rol Estudiante. No se crea esa cuenta en producción.

Para crear el administrador inicial, configura `INITIAL_ADMIN_EMAIL` e `INITIAL_ADMIN_PASSWORD` en `.env` antes de `php artisan db:seed`. Puedes definir `INITIAL_ADMIN_NAME`. Si faltan email o contraseña, el seeder muestra una advertencia y no crea la cuenta. Repetir el seeder no cambia la contraseña de una cuenta existente. No subas `.env` ni compartas el token o la contraseña.

## Guía rápida para frontend

Base de ejemplo con `php artisan serve`: `http://127.0.0.1:8000/api/v1`. Ajusta el host y puerto según tu entorno. Enviar `Accept: application/json` y, para rutas protegidas, `Authorization: Bearer <token>`.

| Método | Ruta | JSON de entrada | Acceso | Resultado |
| --- | --- | --- | --- | --- |
| GET | `/health` | — | Público | Estado del servicio |
| POST | `/register` | `name`, `email`, `password`, `password_confirmation` | Público | `201`: `user`, `token`, `token_type` |
| POST | `/login` | `email`, `password` | Público | `200`: `user`, `token`, `token_type` |
| GET | `/me` | — | Token válido | `200`: `user` |
| POST | `/logout` | — | Token válido | Revoca el token usado en esa solicitud |
| GET | `/roles` | — | Administrador | Lista `roles` |

Ejemplo de registro:

```json
{
  "name": "Ana Pérez",
  "email": "ana@example.com",
  "password": "password123",
  "password_confirmation": "password123"
}
```

Respuesta de registro o login (el valor del token es ilustrativo):

```json
{
  "user": {
    "id": 1,
    "name": "Ana Pérez",
    "email": "ana@example.com",
    "roles": ["Estudiante"]
  },
  "token": "<token>",
  "token_type": "Bearer"
}
```

El registro asigna siempre `Estudiante`; el cliente no puede elegir otro rol. `401` indica token ausente o credenciales incorrectas, `403` rol insuficiente y `422` errores de validación. Registro y login están limitados a 10 solicitudes por minuto por cliente. Usa `/me` al iniciar la aplicación para conocer el usuario y sus roles, y `/logout` para revocar el token actual.

Los roles son `Estudiante`, `Coordinador` y `Administrador`. Todavía no hay endpoints de trámites que apliquen permisos por rol; el acceso restringido disponible es `/roles` para Administrador. Para los detalles completos del contrato, consulta [API_AUTH.md](API_AUTH.md). Para las decisiones de arquitectura, consulta [docs/arquitectura-backend.md](docs/arquitectura-backend.md).

## Verificación

```sh
php artisan route:list --path=api --except-vendor
php artisan test --compact
vendor/bin/pint --test
git diff --check
```

Las pruebas usan SQLite en memoria y no modifican la base configurada para la aplicación. La ruta de salud existente sigue disponible en `GET /api/v1/health`.
