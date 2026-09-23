# Sistema de Reconocimiento y Homologación — Backend

## Descripción

Backend API REST para gestionar el Sistema de Reconocimiento y Homologación. Forma parte de una arquitectura desacoplada:

```text
Base de datos
      ↑
Backend API REST
      ↑
Frontend independiente
```

Este repositorio contiene reglas de negocio, autenticación, autorización, persistencia, validación, archivos privados, reportes y respuestas HTTP. No contiene el frontend administrativo.

## Tecnologías

- PHP requerido: `^8.3` (entorno verificado: PHP 8.5.9).
- Laravel Framework 13.30.1.
- Laravel Sanctum 4.3.3.
- Spatie Laravel Permission 8.3.0.
- Eloquent ORM.
- Dedoc Scramble 0.13.45 para OpenAPI inferido.
- PHPUnit 12.5.34.
- SQLite para desarrollo local y tests; producción puede usar el motor acordado mediante `DB_*`.

## Instalación

```sh
composer install --no-interaction --prefer-dist
cp .env.example .env
php artisan key:generate
touch database/database.sqlite
php artisan migrate --seed
php artisan serve
```

La configuración local recomendada es `DB_CONNECTION=sqlite`; Laravel utiliza `database/database.sqlite`. Tanto `.env` como el archivo SQLite están ignorados por Git: cada integrante reconstruye su base con las migraciones y seeders, sin intercambiar dumps con credenciales o datos personales. Para MySQL/PostgreSQL configura primero las variables `DB_*` correspondientes.

Los PDF de resolución se guardan en el disco privado `local` (`storage/app/private`), por lo que no necesitan `php artisan storage:link`. Ese enlace solo sería necesario para futuros archivos expresamente públicos.

Para crear el administrador inicial, define estas variables antes de ejecutar el seeder:

```dotenv
INITIAL_ADMIN_NAME="Administrador inicial"
INITIAL_ADMIN_CEDULA=
INITIAL_ADMIN_EMAIL=
INITIAL_ADMIN_PHONE=
INITIAL_ADMIN_PASSWORD=
```

No hay credenciales reales o predeterminadas en producción. Si falta un valor obligatorio, `AdminUserSeeder` omite la creación. En `local` y `testing`, `DatabaseSeeder` conserva una cuenta de prueba de estudiante; no se crea en producción.

## Base de datos

El modelo relacional se administra de forma independiente y el backend utiliza sus tablas de catálogos, usuarios, asignaciones, solicitudes, documentos, mallas, resultados y resoluciones. Las migraciones actuales incorporan claves foráneas y restricciones únicas para `coordinador_carreras`, combinaciones trámite/proceso y otros vínculos.

La implementación de roles usa las tablas estándar de Spatie (`roles` y `model_has_roles`). Su clave primaria compuesta impide roles duplicados. Esto difiere del nombre `user_has_rol` citado en documentación funcional y se conserva como decisión técnica existente; véase Deuda técnica.

La base local tiene 15 migraciones y 38 tablas, incluida la tabla de notificaciones. Los seeders son idempotentes y crean 3 roles, 2 tipos de trámite, 3 tipos de proceso, 4 estados de documento, 8 estados de solicitud y 3 combinaciones trámite/proceso. La guía operativa completa se encuentra en [docs/base-de-datos.md](docs/base-de-datos.md).

## Roles y autorización

Los roles persistidos son `Administrador`, `Coordinador` y `Estudiante`. Distinguen mayúsculas. Las rutas `/api/v1/admin/*` aplican, en servidor:

1. `auth:sanctum`;
2. comprobación de cuenta activa;
3. rol `Administrador`.

Un token ausente o inválido devuelve 401. Un rol insuficiente o una cuenta inactiva devuelve 403. Al desactivar una cuenta se revocan todos sus tokens. La seguridad no depende de que el frontend oculte opciones.

## Autenticación

La API entrega tokens Bearer mediante Sanctum:

| Método | Endpoint | Acceso | Descripción |
| --- | --- | --- | --- |
| POST | `/api/v1/register` | Público | Registra exclusivamente un Estudiante. |
| POST | `/api/v1/login` | Público | Valida credenciales y cuenta activa; entrega token. |
| GET | `/api/v1/me` | Autenticado y activo | Datos mínimos, estado y roles. |
| POST | `/api/v1/logout` | Autenticado y activo | Revoca el token actual. |

Registro y login están limitados a 10 solicitudes por minuto.

## Módulo Administrador

- Alta, búsqueda, paginación, detalle y edición de usuarios.
- Activación/desactivación sin borrado físico.
- Asignación transaccional de rol y trazabilidad mediante `creador_id`.
- Asignación reemplazable de una o varias carreras a Coordinadores.
- Catálogo de carreras.
- Consulta administrativa de estudiantes y sus relaciones existentes.
- Consulta paginada de solicitudes, filtros y detalle con eager loading.
- Registro y descarga autorizada de resoluciones PDF privadas.
- Reporte JSON filtrado de solicitudes.
- Estadísticas administrativas obtenidas de la base de datos.

## Endpoints administrativos

Todos requieren token activo y rol `Administrador`.

| Método | Endpoint | Descripción |
| --- | --- | --- |
| GET | `/api/v1/admin/users` | Usuarios paginados y filtrados. |
| POST | `/api/v1/admin/users` | Crear usuario y asignar rol. |
| GET | `/api/v1/admin/users/{id}` | Detalle del usuario. |
| PUT/PATCH | `/api/v1/admin/users/{id}` | Editar campos permitidos. |
| PATCH | `/api/v1/admin/users/{id}/status` | Activar o desactivar. |
| GET | `/api/v1/admin/careers` | Catálogo de carreras. |
| GET | `/api/v1/admin/coordinators/{id}/careers` | Carreras del Coordinador. |
| PUT | `/api/v1/admin/coordinators/{id}/careers` | Reemplazar sus carreras. |
| GET | `/api/v1/admin/students` | Estudiantes paginados. |
| GET | `/api/v1/admin/students/{id}` | Detalle académico disponible. |
| GET | `/api/v1/admin/solicitudes` | Solicitudes paginadas y filtradas. |
| GET | `/api/v1/admin/solicitudes/{id}` | Detalle integral de consulta. |
| POST | `/api/v1/admin/solicitudes/{id}/resolucion` | Registrar PDF de resolución. |
| GET | `/api/v1/admin/solicitudes/{id}/resolucion/download` | Descargar resolución privada. |
| GET | `/api/v1/admin/reports/solicitudes` | Reporte JSON filtrado. |
| GET | `/api/v1/admin/dashboard` | Estadísticas administrativas. |

El contrato detallado de cuerpos, filtros, validaciones y respuestas está en [docs/api.md](docs/api.md).

## Ejemplos

Crear un usuario:

```http
POST /api/v1/admin/users
Accept: application/json
Content-Type: application/json
Authorization: Bearer {token}
```

```json
{
  "nombres_completos": "Juan Pérez",
  "cedula": "1234567890",
  "email": "juan@example.com",
  "numero_celular": "0999999999",
  "password": "UnaClaveSegura123!",
  "rol_id": 2
}
```

Respuesta 201:

```json
{
  "success": true,
  "message": "Usuario registrado correctamente.",
  "data": {
    "id": 10,
    "nombres_completos": "Juan Pérez",
    "email": "juan@example.com",
    "cuenta_activa": true,
    "roles": ["Coordinador"]
  }
}
```

Asignar carreras (el `PUT` reemplaza el conjunto anterior):

```json
{ "carrera_ids": [1, 3, 5] }
```

La resolución se carga como `multipart/form-data` con `numero_resolucion`, `fecha_aprobacion` y `archivo`. El PDF puede pesar como máximo 10 MB y se valida por su contenido MIME.

## Integración con frontend

- URL base local habitual: `http://127.0.0.1:8000/api/v1`; usa el `APP_URL` real del entorno desplegado.
- Enviar `Accept: application/json` siempre.
- Enviar `Authorization: Bearer {token}` en rutas protegidas.
- Usar `Content-Type: application/json` salvo uploads `multipart/form-data`.
- No interpretar un HTTP 200 como único indicador: manejar 201, 401, 403, 404, 409 y 422.
- Los listados devuelven `data`, `links` y `meta`; usar `meta.current_page`, `meta.last_page`, `meta.per_page` y `meta.total`.
- Los errores 422 contienen `errors` con arreglos de mensajes por campo.
- No construir URLs a `storage`: usar siempre `download_url` para resoluciones.

## Manejo de errores

| Código | Uso |
| --- | --- |
| 401 | No autenticado o credenciales inválidas. |
| 403 | Cuenta inactiva o rol insuficiente. |
| 404 | Recurso/archivo inexistente. |
| 409 | Conflicto con el estado actual. |
| 422 | Validación de campos. |
| 500 | Error inesperado sin exponer detalles internos en producción. |

## Tests y verificación

```sh
php artisan test --compact
vendor/bin/pint --dirty --format agent
php artisan route:list --path=api --except-vendor
php artisan scramble:analyze --fail-on-unknown
git diff --check
```

Los Feature Tests cubren autenticación, cuenta inactiva, autorización por roles, gestión de usuarios, hashes, creador, estados, carreras de Coordinador, estudiantes, solicitudes, filtros, resoluciones privadas, reportes y estadísticas. Usan SQLite en memoria y `Storage::fake`, sin modificar la base configurada en `.env`.

Verificación final realizada en esta entrega:

- 86 pruebas aprobadas y 508 aserciones.
- 39 rutas API registradas (17 de Estudiante).
- 15 migraciones aplicadas sobre la base SQLite local.
- Autenticación, aislamiento entre estudiantes, estados, archivos privados y notificaciones verificados con Feature Tests.
- OpenAPI analizado por Scramble sin tipos desconocidos.

## Estado actual del backend

- [x] Autenticación con Sanctum.
- [x] Autorización por roles en servidor.
- [x] Bloqueo de cuentas inactivas.
- [x] Registro y gestión administrativa de usuarios.
- [x] Activación/desactivación y revocación de tokens.
- [x] Asignación de roles.
- [x] Asignación Coordinador–Carrera.
- [x] Consulta administrativa de estudiantes.
- [x] Consulta y filtros de solicitudes.
- [x] Registro/descarga privada de resoluciones por Administrador.
- [x] Reporte JSON y estadísticas administrativas.
- [ ] Exportaciones PDF/Excel de reportes.
- [x] Flujo backend de Estudiante: perfil, antecedentes, solicitudes, documentos, correcciones, seguimiento, avisos y resolución final.
- [ ] Módulo completo de Coordinador.

## Historias de usuario

| HU | Funcionalidad | Backend | Estado |
| --- | --- | --- | --- |
| HU-01 | Registro administrativo de usuarios | Alta transaccional, rol, creador y validación | COMPLETADA |
| HU-02 | Gestión de usuarios | Listado, filtros, detalle, edición y estado | COMPLETADA |
| HU-03 | Control de acceso | Sanctum, cuenta activa y rol Administrador | COMPLETADA |
| HU-06 | Consulta administrativa de estudiantes | Listado y detalle de solo lectura | COMPLETADA |
| HU-17 | Registro de resolución | Implementado para Administrador; Coordinador queda fuera de esta etapa | PARCIAL |
| HU-21 | Consulta y filtrado de solicitudes | Paginación, filtros y detalle | COMPLETADA |
| HU-22 | Reportes | Consulta JSON y agregados; sin exportaciones no confirmadas | PARCIAL |

## Próximas etapas del Backend

### Módulo Estudiante

Implementado el flujo backend acordado: perfil y antecedentes propios, catálogo de asignaciones, creación de solicitudes pendientes, PDF privados, envío a revisión, correcciones de documentos observados, historial, notificaciones internas y descarga final cuando la solicitud está `listo`. Los 17 endpoints `/api/v1/student/*` requieren cuenta activa y rol Estudiante. Véase [el contrato de API](docs/api.md#estudiante-solicitudes-archivos-y-seguimiento).

Para preparar una demostración local reproducible:

```sh
php artisan migrate --no-interaction
php artisan db:seed --class=StudentDemoSeeder --no-interaction
php artisan serve
```

Usar la cuenta local `test@example.com` / `password`, registrar antecedentes y consultar `/api/v1/student/catalogo`. El seeder añade una carrera, un coordinador y requisitos `[DEMO]` para probar el flujo; no representan normativa institucional y el seeder rechaza producción. No crea automáticamente una solicitud ni simula su aprobación. La revisión y los estados finales se implementarán en Coordinador.

### Módulo Coordinador

Pendientes HU-09 y HU-11 a HU-16: revisión documental, observaciones, estados, mallas, asignaturas, sílabos, comparaciones, matriz académica, dictamen e informe técnico. El Administrador no puede aprobar u observar documentación mediante los endpoints actuales.

## Deuda técnica y decisiones

- El documento funcional menciona `user_has_rol`, pero el repositorio ya usa `model_has_roles` de Spatie. Migrar el nombre requiere una decisión conjunta con el equipo de base de datos; no se creó una tabla paralela.
- Los roles sembrados están en Title Case, aunque parte de la narrativa los escribe en minúsculas. La API conserva los valores existentes y distingue mayúsculas.
- `resoluciones_solicitud.coordinador_id` es el único campo de autor disponible. Cuando registra un Administrador, ese campo guarda al actor administrador; convendría renombrarlo a `registrado_por_id` mediante una migración coordinada si el diccionario de datos lo permite.
- Las solicitudes nuevas guardan la carrera como FK. Para solicitudes anteriores con `carrera_id=null`, el filtro administrativo conserva la derivación desde documentos o asignaciones; no se deduce una carrera histórica ambigua durante la migración.
- No se añadieron exportaciones PDF/Excel ni nuevas librerías porque los criterios no las hacen obligatorias.
- SQLite es apropiado para desarrollo y pruebas, pero el motor de producción debe acordarse con el equipo de base de datos y probarse en CI antes del despliegue.
- No editar migraciones que ya hayan sido ejecutadas en ambientes compartidos; toda corrección posterior debe hacerse mediante una migración nueva.
- Antes de producción deben eliminarse cuentas de prueba, rotarse credenciales administrativas y gestionarse secretos mediante el proveedor de despliegue.
- Conviene añadir auditoría persistente de acciones administrativas, antivirus para archivos, límites por usuario y respaldos/restauración probados.
- Los filtros y estadísticas deben revisarse con datos representativos y planes de consulta antes de añadir índices adicionales.
- La instalación local de PHP emite una advertencia por una extensión `pgsql` incompatible. SQLite y las pruebas funcionan, pero el paquete PostgreSQL global debe reinstalarse o desactivarse si no se utiliza.
