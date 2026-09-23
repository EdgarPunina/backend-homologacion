# Contrato de la API REST

Base: `/api/v1`. Los clientes deben enviar `Accept: application/json`. Las rutas protegidas requieren `Authorization: Bearer {token}`. Los cuerpos normales usan `Content-Type: application/json`; la carga de resoluciones usa `multipart/form-data`.

## Respuestas y errores

Las respuestas exitosas incluyen `success: true`. Los listados paginados usan la estructura de Laravel: `data`, `links` y `meta` (`current_page`, `last_page`, `per_page`, `total`). `per_page` admite de 1 a 100 y vale 15 por defecto, salvo el reporte, cuyo valor por defecto es 50.

Los errores de API tienen `success: false` y `message`. Los errores 422 añaden `errors` por campo.

| Código | Significado |
| --- | --- |
| 200 | Consulta o actualización correcta. |
| 201 | Recurso creado. |
| 401 | Token ausente o inválido, o credenciales incorrectas. |
| 403 | Rol insuficiente o cuenta inactiva. |
| 404 | Recurso o archivo no encontrado. |
| 409 | Conflicto de estado, como auto-desactivación o resolución ya existente. |
| 422 | Validación fallida. |
| 500 | Error interno; no se exponen trazas ni SQL en producción. |

## Autenticación

| Método | Endpoint | Acceso | Entrada |
| --- | --- | --- | --- |
| POST | `/register` | Público, limitado | `nombres_completos`, `cedula`, `email`, `numero_celular`, `password`, `password_confirmation` |
| POST | `/login` | Público, limitado | `email`, `password` |
| GET | `/me` | Cualquier cuenta activa | — |
| POST | `/logout` | Cualquier cuenta activa | — |
| GET | `/roles` | Administrador | — |

El registro público siempre asigna `Estudiante`; cualquier rol enviado por el cliente se ignora. Login y registro están limitados a 10 solicitudes por minuto. Login devuelve `user`, `token` y `token_type: Bearer`. Logout revoca solo el token actual. Una cuenta inactiva no puede iniciar sesión ni reutilizar un token previo.

```json
{
  "email": "admin@example.com",
  "password": "contraseña-segura"
}
```

## Usuarios administrativos

Todas las rutas siguientes requieren token activo y rol `Administrador`.

| Método | Endpoint | Descripción |
| --- | --- | --- |
| GET | `/admin/users` | Lista usuarios con paginación y filtros. |
| POST | `/admin/users` | Crea usuario, asigna un rol y registra al creador en una transacción. |
| GET | `/admin/users/{id}` | Devuelve perfil, rol, creador y carreras coordinadas. |
| PUT/PATCH | `/admin/users/{id}` | Actualiza exclusivamente campos permitidos. |
| PATCH | `/admin/users/{id}/status` | Activa o desactiva la cuenta. |

Filtros de listado: `search` (nombre, cédula o correo), `rol`, `cuenta_activa`, `order_by` (`id`, `nombres_completos`, `cedula`, `email`, `created_at`), `direction` (`asc`, `desc`), `page` y `per_page`.

Creación:

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

`cedula` admite de 10 a 20 caracteres; `numero_celular`, de 7 a 20. Cédula y correo son únicos. `rol_id` debe existir. La contraseña se almacena con hash. Una actualización omite la contraseña si no se envía. El administrador autenticado no puede desactivar su propia cuenta ni quitarse su propio rol administrativo; esos conflictos devuelven 409. Al desactivar a otro usuario se revocan todos sus tokens.

Estado:

```json
{ "cuenta_activa": false }
```

## Coordinadores y carreras

| Método | Endpoint | Entrada/resultado |
| --- | --- | --- |
| GET | `/admin/careers` | Catálogo completo de carreras ordenado por nombre. |
| GET | `/admin/coordinators/{id}/careers` | Carreras asignadas al coordinador. |
| PUT | `/admin/coordinators/{id}/careers` | Reemplaza todas las asignaciones actuales. |

```json
{ "carrera_ids": [1, 3, 5] }
```

Los IDs deben existir y no repetirse. El usuario indicado debe tener el rol `Coordinador`; de lo contrario se devuelve 422. El índice único existente en `coordinador_carreras` también impide duplicados.

## Consulta de estudiantes

| Método | Endpoint | Descripción |
| --- | --- | --- |
| GET | `/admin/students` | Lista estudiantes; admite `search`, `cuenta_activa`, `page`, `per_page`. |
| GET | `/admin/students/{id}` | Perfil, antecedentes, carrera/coordinador y solicitudes existentes. |

Es un módulo administrativo de solo lectura. No crea antecedentes ni modifica análisis académicos.

## Solicitudes

| Método | Endpoint | Descripción |
| --- | --- | --- |
| GET | `/admin/solicitudes` | Listado paginado con filtros. |
| GET | `/admin/solicitudes/{id}` | Detalle administrativo estructurado. |

Filtros: `estado` (ID o nombre), `carrera` (ID), `tipo_tramite` (ID o nombre), `tipo_proceso` (ID o nombre), `estudiante`, `coordinador`, `fecha_desde`, `fecha_hasta`, `page`, `per_page`. `fecha_hasta` no puede ser anterior a `fecha_desde`. El filtro de estado usa el último registro de `historial_estados_solicitud`.

El detalle contiene, si existen: estudiante, coordinador, trámite/proceso, procedencia, estado actual, documentos y observaciones, historial, oficios, resultado y resolución. No expone rutas privadas de archivos.

## Resoluciones

| Método | Endpoint | Descripción |
| --- | --- | --- |
| POST | `/admin/solicitudes/{id}/resolucion` | Registra metadatos y almacena un PDF privado. |
| GET | `/admin/solicitudes/{id}/resolucion/download` | Descarga autorizada del PDF. |

La carga usa `multipart/form-data` con `numero_resolucion` único (máximo 100 caracteres), `fecha_aprobacion` y `archivo` PDF de hasta 10 MB. Solo se admite una resolución por solicitud. La ruta interna nunca se devuelve; la respuesta incluye `download_url`.

## Reportes y estadísticas

| Método | Endpoint | Descripción |
| --- | --- | --- |
| GET | `/admin/reports/solicitudes` | Aplica los mismos filtros de solicitudes y devuelve filtros aplicados, total, agregados por estado, registros y paginación. |
| GET | `/admin/dashboard` | Totales de usuarios, usuarios por rol, activos/inactivos, solicitudes y solicitudes por estado. |

No se instalaron dependencias de PDF/Excel ni se añadieron exportaciones porque no son requisito confirmado. El dashboard solo entrega datos JSON; no incluye interfaz.

## Alcance pendiente

No están implementados los flujos de creación/corrección/seguimiento del estudiante (HU-04, HU-05, HU-07, HU-08, HU-10, HU-18, HU-19 y HU-20) ni la revisión documental y académica del coordinador (HU-09 y HU-11 a HU-16). El Administrador puede consultar esos datos existentes, pero no ejecutar acciones reservadas al Coordinador.
