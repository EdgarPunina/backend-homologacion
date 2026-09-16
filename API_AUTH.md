# Autenticación y roles API

Base: `/api/v1`. Enviar `Accept: application/json`. Las rutas protegidas requieren `Authorization: Bearer <token>`.

| Método | Ruta | Body JSON | Acceso | Respuesta |
| --- | --- | --- | --- | --- |
| GET | `/health` | — | Público | Estado del servicio |
| POST | `/register` | `name`, `email`, `password`, `password_confirmation` | Público | `201`: `user`, `token`, `token_type` |
| POST | `/login` | `email`, `password` | Público | `200`: `user`, `token`, `token_type` |
| GET | `/me` | — | Token válido, cualquier rol | `200`: `user` |
| POST | `/logout` | — | Token válido, cualquier rol | `200`: mensaje; revoca solo el token actual |
| GET | `/roles` | — | Administrador | `200`: lista `roles` |

`user` contiene `id`, `name`, `email` y `roles` (arreglo de nombres). El registro siempre asigna `Estudiante`; cualquier campo `role` enviado por el cliente se ignora. El token se entrega solo al registrarse o iniciar sesión. Los errores de validación devuelven `422`, credenciales inválidas o token ausente `401`, y rol insuficiente `403`. Registro y login tienen límite de 10 solicitudes por minuto por cliente.

Roles disponibles: `Estudiante`, `Coordinador`, `Administrador`. En términos generales, Estudiante accede a sus propios recursos, Coordinador gestiona los procesos académicos asignados y Administrador gestiona la configuración y el acceso global. Como aún no existen rutas de negocio, solo `/roles` aplica una restricción concreta de rol. Para rutas futuras, usar `auth:sanctum` y después `role:Coordinador` o `role:Administrador`; los nombres distinguen mayúsculas.

## Preparación

1. Ejecutar `php artisan migrate` y `php artisan db:seed` sobre la base de datos configurada. El seeder crea los tres roles. En entornos `local` o `testing` también crea o conserva `test@example.com` con rol Estudiante; no crea esa cuenta en producción.
2. Para crear el administrador inicial, definir `INITIAL_ADMIN_EMAIL` e `INITIAL_ADMIN_PASSWORD` en el entorno antes de ejecutar `php artisan db:seed`. Se puede definir `INITIAL_ADMIN_NAME`. Si faltan email o contraseña, el seeder lo omite y muestra una advertencia. Volver a ejecutarlo no cambia la contraseña de un usuario existente.

Ejemplo de registro:

```json
{"name":"Ana","email":"ana@example.com","password":"password123","password_confirmation":"password123"}
```
