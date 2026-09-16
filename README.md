# backend-homologacion

Backend para la gestión de trámites de homologación y reconocimiento mediante una API REST con JSON, separado del frontend.

## Estado actual

- **Implementado:** base Laravel, registro de rutas API y `GET /api/v1/health`, con prueba Feature de HTTP 200 y JSON exacto.
- **Propuesto:** arquitectura por capas, convenciones y organización de módulos descritas en `docs`.
- **Pendiente:** módulos funcionales, autenticación, roles y revisión de la base de datos.

Todavía no deben ejecutarse migraciones hasta que la base sea revisada y autorizada.

Calendario de fases:

- Diseño de Arquitectura Backend, API REST y configuración del repositorio: hasta el **15 de septiembre de 2026**.
- Autenticación y roles: del **16 al 22 de septiembre de 2026**; no forman parte de esta fase inicial.

## Requisitos técnicos

- PHP requerido por el proyecto: `^8.3`; versión local verificada: **8.4.24**.
- Composer local verificado: **2.8.8**.
- Laravel Framework instalado: **13.30.1**, resuelto mediante `composer.lock`.
- Git para obtener el repositorio.

## Instalación local

Repositorio: [backend-homologacion en GitHub](https://github.com/EdgarPunina/backend-homologacion.git).

Obtener el proyecto y seleccionar la rama de desarrollo:

```sh
git clone https://github.com/EdgarPunina/backend-homologacion.git
cd backend-homologacion
git switch develop
```

Para trabajar en una funcionalidad, crear una rama `feature/*` desde `develop` antes de realizar cambios.

Desde la raíz del repositorio, instalar las dependencias fijadas en el lockfile:

```sh
composer install --no-interaction --prefer-dist
```

En PowerShell, crear `.env` solamente si no existe:

```powershell
if (-not (Test-Path -LiteralPath '.env')) {
    Copy-Item .env.example .env
}
```

Si `APP_KEY` está vacía en una instalación local nueva, configurarla con:

```sh
php artisan key:generate
```

No regenerar una clave ya configurada como paso habitual de arranque. No publicar su valor ni agregar `.env` a Git. Tras ajustar la configuración local:

```sh
php artisan config:clear
```

No usar `composer setup` en esta fase: su script incluye migraciones e instalación de dependencias adicionales. No ejecutar migraciones ni seeders hasta la revisión y autorización de la base de datos. El endpoint de salud no requiere acceso a la base de datos.

## Ejecutar el servidor y comprobar la API

```sh
php artisan serve
```

Usar la dirección que indique el servidor; normalmente `http://127.0.0.1:8000`. Consultar `GET http://127.0.0.1:8000/api/v1/health`.

Respuesta HTTP 200:

```json
{
  "success": true,
  "message": "API backend operativa",
  "data": {
    "service": "backend-homologacion",
    "status": "ok"
  }
}
```

Para comprobar el registro:

```sh
php artisan route:list --path=api
```

## Pruebas y estilo

Con `.env` y `APP_KEY` configurados:

```sh
php artisan test
vendor/bin/pint --test
git diff --check
```

Prueba específica de salud: `php artisan test --filter=HealthCheckTest`.

## Estrategia de ramas

Flujo acordado: `main → develop → feature/*`. Las ramas `feature/*` nacen desde `develop`. Crear Pull Requests de `feature/*` hacia `develop`; integrar `develop` en `main` únicamente después de revisión. No subir directamente a `main`. El trabajo de API base se realiza en `feature/backend-api-base`.

## Documentación del proyecto

- [Arquitectura del backend](docs/arquitectura-backend.md).
- [API REST](docs/api.md).
- [Convenciones de desarrollo](docs/convenciones.md).

## Referencias del framework

Se conserva a continuación la información de referencia de la plantilla Laravel. Describe capacidades y recursos del framework, no funcionalidades implementadas en este backend.

<p align="center"><a href="https://laravel.com" target="_blank"><img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400" alt="Laravel Logo"></a></p>

<p align="center">
<a href="https://github.com/laravel/framework/actions"><img src="https://github.com/laravel/framework/workflows/tests/badge.svg" alt="Build Status"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/dt/laravel/framework" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/v/laravel/framework" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/l/laravel/framework" alt="License"></a>
</p>

## About Laravel

Laravel is a web application framework with expressive, elegant syntax. We believe development must be an enjoyable and creative experience to be truly fulfilling. Laravel takes the pain out of development by easing common tasks used in many web projects, such as:

- [Simple, fast routing engine](https://laravel.com/docs/routing).
- [Powerful dependency injection container](https://laravel.com/docs/container).
- Multiple back-ends for [session](https://laravel.com/docs/session) and [cache](https://laravel.com/docs/cache) storage.
- Expressive, intuitive [database ORM](https://laravel.com/docs/eloquent).
- Database agnostic [schema migrations](https://laravel.com/docs/migrations).
- [Robust background job processing](https://laravel.com/docs/queues).
- [Real-time event broadcasting](https://laravel.com/docs/broadcasting).

Laravel is accessible, powerful, and provides tools required for large, robust applications.

## Learning Laravel

Laravel has the most extensive and thorough [documentation](https://laravel.com/docs) and video tutorial library of all modern web application frameworks, making it a breeze to get started with the framework.

In addition, [Laracasts](https://laracasts.com) contains thousands of video tutorials on a range of topics including Laravel, modern PHP, unit testing, and JavaScript. Boost your skills by digging into our comprehensive video library.

You can also watch bite-sized lessons with real-world projects on [Laravel Learn](https://laravel.com/learn), where you will be guided through building a Laravel application from scratch while learning PHP fundamentals.

## Agentic Development

La plantilla incluye referencias a [Laravel Boost](https://laravel.com/docs/ai) como herramienta opcional del framework. No está integrado en este proyecto y su instalación no forma parte de esta fase.

## Contributing

Thank you for considering contributing to the Laravel framework! The contribution guide can be found in the [Laravel documentation](https://laravel.com/docs/contributions).

## Code of Conduct

In order to ensure that the Laravel community is welcoming to all, please review and abide by the [Code of Conduct](https://laravel.com/docs/contributions#code-of-conduct).

## Security Vulnerabilities

If you discover a security vulnerability within Laravel, please send an e-mail to Taylor Otwell via [taylor@laravel.com](mailto:taylor@laravel.com). All security vulnerabilities will be promptly addressed.

## License

The Laravel framework is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
