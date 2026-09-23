# Arquitectura del backend

El proyecto es una API REST Laravel desacoplada. El frontend independiente consume HTTP/JSON y no comparte lógica de negocio con este repositorio.

```text
Cliente → rutas/middleware → Form Request → Controller → Service/Eloquent → Resource → JSON
```

## Responsabilidades

- `routes/api.php`: versionado `/api/v1`, middleware Sanctum, cuenta activa y rol.
- `app/Http/Requests`: filtros y validación de entradas administrativas.
- `app/Http/Controllers/Api/Admin`: coordinación HTTP sin lógica de persistencia compleja.
- `app/Services`: transacciones de usuarios/resoluciones y consulta reutilizable de solicitudes.
- `app/Models`: entidades y relaciones Eloquent del modelo existente.
- `app/Http/Resources`: contrato de salida sin contraseñas ni rutas privadas.
- `storage/app/private`: archivos de resoluciones, accesibles solo mediante descarga autorizada.
- `tests/Feature`: contratos HTTP, autorización, persistencia, filtros y archivos.

## Seguridad

Sanctum autentica tokens Bearer. Spatie resuelve roles mediante `roles` y `model_has_roles`. Todas las rutas administrativas requieren `Administrador`; `EnsureAccountIsActive` impide que una cuenta desactivada reutilice un token. Los PDF no se publican mediante enlaces de storage.

## Límites del módulo actual

El Administrador gestiona usuarios, carreras de coordinadores, consultas, resoluciones y reportes JSON. Los casos de uso de Estudiante y Coordinador continúan separados y pendientes según el estado descrito en el README. La referencia contractual es [api.md](api.md).
