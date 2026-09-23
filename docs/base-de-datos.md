# Base de datos: configuración, operación y decisiones

## Estado implementado

El entorno local utiliza SQLite mediante:

```dotenv
DB_CONNECTION=sqlite
```

Laravel resuelve el archivo como `database/database.sqlite`. El archivo contiene datos locales y está ignorado por Git, al igual que `.env`. La fuente versionada de la estructura y los datos iniciales está formada por:

- `database/migrations` para esquema, claves foráneas, índices y restricciones;
- `database/seeders` para catálogos y roles;
- `database/factories` para datos de pruebas automáticas.

No se requiere un archivo `.sql` para instalar el proyecto.

## Inicialización reproducible

```sh
cp .env.example .env
php artisan key:generate
touch database/database.sqlite
php artisan migrate --seed
php artisan migrate:status
```

La inicialización fue comprobada con 12 migraciones y 37 tablas. Los datos iniciales esperados son:

| Catálogo | Cantidad |
| --- | ---: |
| Roles | 3 |
| Tipos de trámite | 2 |
| Tipos de proceso | 3 |
| Estados de documento | 4 |
| Estados de solicitud | 8 |
| Combinaciones trámite/proceso | 3 |

Los seeders usan operaciones idempotentes; ejecutarlos nuevamente no debe duplicar estos registros.

## Administrador inicial

`AdminUserSeeder` solo crea una cuenta si el entorno define todos los valores:

```dotenv
INITIAL_ADMIN_NAME=
INITIAL_ADMIN_CEDULA=
INITIAL_ADMIN_EMAIL=
INITIAL_ADMIN_PHONE=
INITIAL_ADMIN_PASSWORD=
```

No deben colocarse contraseñas en `.env.example`, documentación o Git. En producción deben inyectarse mediante secretos del entorno y rotarse después de la instalación. El seeder no cambia la contraseña de una cuenta existente.

## Verificación realizada

- Todas las migraciones aparecen como `Ran` en `php artisan migrate:status`.
- Las restricciones únicas de correo, cédula, roles y asignaciones están activas.
- Las claves foráneas de SQLite están habilitadas.
- El login real de un Administrador local produjo un token Sanctum válido.
- La suite ejecutó 32 pruebas y 159 aserciones correctamente usando SQLite en memoria.
- Los tokens creados durante la verificación manual fueron eliminados al finalizar.

## Producción y otros motores

SQLite facilita una instalación local sin servidor externo. Para producción se debe confirmar con el equipo de base de datos si se usará MySQL, MariaDB o PostgreSQL y ejecutar, como mínimo:

1. migraciones sobre una base vacía del mismo motor;
2. suite de integración contra ese motor;
3. pruebas de restauración desde backup;
4. pruebas de concurrencia para creación de usuarios, roles y resoluciones;
5. revisión de índices con datos representativos;
6. verificación de permisos del directorio privado de archivos.

No importar un dump `.sql` y ejecutar migraciones encima sin comparar previamente ambos esquemas. Si el equipo entrega un dump oficial, debe definirse si ese dump o las migraciones serán la fuente de verdad.

## Correcciones y decisiones futuras

### Esquema y nombres

- La documentación funcional menciona `user_has_rol`, mientras Spatie usa `model_has_roles`. No deben coexistir ambas tablas. El equipo debe aprobar una única estrategia antes de una integración externa.
- Los roles están almacenados como `Administrador`, `Coordinador` y `Estudiante`; se debe decidir si se conserva la distinción de mayúsculas o se normaliza mediante una migración coordinada.
- `resoluciones_solicitud.coordinador_id` también guarda al Administrador que registra una resolución. Un nombre como `registrado_por_id` representaría mejor el dato, pero el cambio exige actualizar diccionario, migración, modelo y consumidores.
- `solicitudes` no tiene una FK directa de carrera. Hoy el filtro la deriva desde documentos requeridos o la asignación del estudiante. Debe decidirse si la carrera forma parte inmutable de la solicitud.

### Seguridad y operación

- Eliminar o deshabilitar cuentas de demostración fuera de `local` y `testing`.
- Añadir auditoría de altas, cambios de rol, activaciones, desactivaciones, asignaciones y resoluciones.
- Incorporar análisis antivirus/antimalware para PDFs antes de conservarlos definitivamente.
- Establecer retención, backup y restauración para base de datos y archivos privados como una sola unidad operativa.
- Configurar expiración de tokens Sanctum según la política institucional.

### Rendimiento y calidad

- Medir listados y reportes con volúmenes reales antes de añadir índices especulativos.
- Evaluar procesos asíncronos para futuras exportaciones PDF/Excel y notificaciones.
- Ejecutar tests en CI con SQLite y con el motor elegido para producción.
- Añadir monitoreo de errores, consultas lentas, almacenamiento y trabajos en cola.

### Entorno PHP

La máquina local muestra una advertencia al cargar `pgsql` por incompatibilidad binaria. Esto no afecta SQLite, pero debe corregirse a nivel del sistema reinstalando una versión de la extensión compatible con PHP o desactivándola si PostgreSQL no se utilizará. No se modifica desde este repositorio porque es configuración global compartida.
