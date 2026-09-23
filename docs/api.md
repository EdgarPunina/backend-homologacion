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

Filtros de listado: `search` (nombre, cédula o correo), `rol`, `cuenta_activa`, `order_by` (`id`, `nombres_completos`, `cedula`, `email`, `created_at`), `direction` (`asc`, `desc`), `page` y `per_page`. Un `rol` inexistente para el guard `web` devuelve 422 con un error en `errors.rol`.

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

Si se intenta retirar una carrera que tiene estudiantes asignados, la operación devuelve 409 sin modificar ninguna asignación. Primero deben reasignarse esos estudiantes. La clave foránea impide también que un borrado directo elimine sus vínculos académicos.

## Estudiante: perfil y antecedentes

Estas rutas requieren token, cuenta activa y rol `Estudiante`. Siempre operan sobre el usuario autenticado; no aceptan un propietario elegido por el cliente.

| Método | Endpoint | Descripción |
| --- | --- | --- |
| GET | `/student/profile` | Perfil propio, antecedentes y carreras asignadas. |
| PATCH | `/student/profile` | Actualización parcial de nombre, cédula, correo y celular. |
| GET | `/student/antecedentes` | Antecedentes propios paginados (`page`, `per_page`, máximo 100). |
| POST | `/student/antecedentes` | Registra un antecedente propio. |
| GET | `/student/antecedentes/{id}` | Consulta un antecedente propio. |
| PATCH | `/student/antecedentes/{id}` | Actualiza parcialmente un antecedente propio. |

El perfil admite `nombres_completos` (máximo 255), `cedula` (10–20 caracteres, única), `email` (correo válido, máximo 255, único) y `numero_celular` (7–20 caracteres). Cambiar el correo elimina su marca de verificación anterior. Roles, estado de cuenta, creador y contraseña no se modifican desde este endpoint.

Crear un antecedente requiere los cuatro campos siguientes; `PATCH` admite cualquier subconjunto, sin valores vacíos:

```json
{
  "universidad_origen": "Universidad de origen",
  "carrera_origen": "Sistemas",
  "tipo_institucion": "publica",
  "periodo_cursado": "2024-2025"
}
```

Universidad y carrera admiten hasta 255 caracteres; tipo de institución y período, hasta 100. Se conserva el esquema actual de texto libre. Un antecedente ajeno o inexistente devuelve el mismo 404. No se incluye borrado de antecedentes en esta primera entrega.

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

## Estudiante: solicitudes, archivos y seguimiento

Todas las rutas requieren Sanctum, cuenta activa y rol `Estudiante`. El propietario se obtiene del token. Los recursos ajenos y los inexistentes devuelven el mismo 404, también cuando un documento pertenece a otra solicitud.

| Método | Endpoint | Descripción |
| --- | --- | --- |
| GET | `/student/catalogo` | Combinaciones de trámite/proceso y carreras/coordinadores asignados al estudiante. |
| GET | `/student/solicitudes` | Solicitudes propias paginadas; filtros `estado` (nombre), `page` y `per_page` (1–100). |
| POST | `/student/solicitudes` | Crea una solicitud pendiente con sus documentos requeridos. |
| GET | `/student/solicitudes/{id}` | Detalle, requisitos, archivos, observaciones, historial y resultado propios. |
| PATCH | `/student/solicitudes/{id}` | Modifica `procedencia_estudios` mientras esté pendiente. |
| POST | `/student/solicitudes/{id}/enviar` | Envía la solicitud completa o corregida a revisión. |
| POST | `/student/solicitudes/{id}/documentos/{documento}` | Carga o reemplaza el PDF de un documento requerido. |
| GET | `/student/solicitudes/{id}/documentos/{documento}/download` | Descarga privada del documento propio. |
| GET | `/student/solicitudes/{id}/resolucion/download` | Descarga privada de la resolución cuando el estado es `listo`. |
| GET | `/student/notificaciones` | Avisos propios paginados; `sin_leer=1` filtra los pendientes. |
| PATCH | `/student/notificaciones/{uuid}/leer` | Marca un aviso propio como leído; repetirlo conserva la fecha de lectura. |

### Creación y requisitos

Primero se consulta el catálogo y se selecciona una asignación del estudiante. Si no tiene asignaciones, debe gestionarlas con la administración; el estudiante no puede asignarse una carrera o coordinador arbitrarios.

```json
{
  "coordinador_carrera_id": 1,
  "tramite_proceso_id": 1,
  "procedencia_estudios": "Universidad de origen"
}
```

`procedencia_estudios` admite hasta 255 caracteres. Se requiere un coordinador activo y al menos un requisito configurado. Los requisitos generales del trámite y los específicos de la carrera se incorporan a la solicitud como documentos pendientes. Incorporar requisitos nuevos al catálogo no modifica solicitudes ya creadas. La carrera queda guardada en `solicitudes.carrera_id`; no cambia cuando se modifica una asignación posterior.

No puede existir otra solicitud activa del mismo estudiante, carrera y trámite. Para esta regla, `listo` y `rechazado` son estados finales. La creación devuelve 201; los conflictos de configuración, coordinador o duplicidad devuelven 409 sin dejar registros parciales. La creación está limitada a 20 solicitudes/minuto y los uploads a 30/minuto.

### Envío y correcciones

Flujo acordado:

```text
pendiente → completar documentos → enviar → en_revision
observado → corregir documentos señalados → reenviar → en_revision
```

El envío exige antecedentes académicos y todos los archivos requeridos presentes en almacenamiento, con estado `presentado` o `aprobado` (estos últimos deben tener `validez=true`). Los faltantes devuelven 422 en `errors.antecedentes` o `errors.documentos`. Enviar de nuevo una solicitud ya en revisión devuelve 409 y no duplica el historial. El estudiante no puede aprobar documentos ni establecer estados administrativos.

La carga usa `multipart/form-data`, campo `archivo`, PDF de hasta 10 MB validado por contenido MIME. El servidor PHP debe permitir ese tamaño (`upload_max_filesize` al menos `10M` y `post_max_size` mayor, por ejemplo `12M`). Cambiar la extensión de un archivo no lo convierte en PDF válido.

En una solicitud pendiente se pueden cargar/reemplazar documentos `pendiente` o `presentado`. En una solicitud observada se permiten documentos `observado` o todavía `pendiente`; los aprobados quedan protegidos. Una corrección vuelve a `presentado`, reinicia `validez=false` y conserva las observaciones históricas. El archivo anterior se elimina solo después de persistir el reemplazo; si falla la base, se conserva el anterior y se elimina el nuevo. No se ofrece un archivo histórico de versiones de PDF.

Los archivos se guardan en el disco privado `local`. La API entrega enlaces de descarga autenticada, nunca rutas físicas. Un archivo ausente devuelve 404. La resolución no se expone al estudiante antes del estado `listo`, aunque ya haya sido registrada por el Administrador.

### Notificaciones

Los avisos se guardan en la base de datos y se consultan desde el frontend; no se envía correo ni se necesita un trabajador de colas. Se generan al crear registros Eloquent de historial de estado, observación documental o resolución. El módulo Coordinador deberá usar esos modelos y transacciones; las escrituras SQL directas o eventos deshabilitados no generan avisos.

Los avisos contienen `solicitud_id`, `evento`, `mensaje` y `url`. Los tipos son `estado_actualizado`, `documento_observado` y `resolucion_registrada`. El listado devuelve `data`, `links`, `meta` y el total `sin_leer`. Los avisos de una transacción revertida se revierten junto con sus datos.

## Alcance restante

El flujo backend de Estudiante acordado está implementado. La revisión documental/académica, emisión del dictamen y transición a estados finales pertenecen al módulo Coordinador, que sigue pendiente. El frontend es independiente. Los catálogos y asignaciones institucionales reales deben configurarse antes de operar con estudiantes reales; los datos `[DEMO]` son exclusivamente de desarrollo.
