# Arquitectura inicial del backend

## Objetivo y estado

El backend de `backend-homologacion` será responsable de gestionar los procesos del dominio de homologación y reconocimiento y exponer sus capacidades mediante una API REST con JSON.

- **Implementado:** aplicación Laravel, registro de rutas API, `GET /api/v1/health` y prueba Feature de su contrato. Las excepciones de peticiones `api/*` se configuran para responder en JSON.
- **Propuesto:** arquitectura por capas y organización de los futuros módulos descritas aquí.
- **Pendiente:** módulos funcionales, autenticación, autorización y revisión de la base de datos. La existencia del modelo inicial `User` no implica que estos módulos estén implementados.

## Tecnologías confirmadas

| Componente | Versión o contrato |
|---|---|
| Laravel Framework instalado | 13.30.1 |
| PHP requerido por `composer.json` | `^8.3` |
| PHP local verificado | 8.4.24 |
| Composer local verificado | 2.8.8 |
| Interfaz | API REST con JSON |

Estas versiones locales corresponden al entorno revisado en esta fase; no sustituyen las restricciones de `composer.json` ni las versiones de `composer.lock`.

## Separación frontend y backend

El frontend será responsable de la presentación y la interacción con el usuario. Consumirá contratos HTTP/JSON del backend, que concentrará validación, autorización, reglas de negocio y persistencia. No se ha definido aquí una tecnología de frontend ni una integración funcional. La vista web inicial de Laravel no representa el frontend del dominio.

## Capas propuestas

| Capa | Responsabilidad |
|---|---|
| Routes | Asociar método y URI a una acción y aplicar middleware. No contener reglas de negocio. |
| Form Requests | Validar entradas y expresar la autorización de la petición cuando corresponda, apoyándose en Policies. |
| Controllers | Coordinar la petición, invocar la operación necesaria y devolver la respuesta. Deben ser delgados. |
| Services | Centralizar reglas de negocio y operaciones de casos de uso cuando exista esa lógica. |
| Models | Representar entidades, relaciones y comportamiento propio de la persistencia con Eloquent. |
| Policies | Centralizar la autorización sobre acciones y entidades. Los permisos concretos siguen pendientes de implementación. |
| API Resources | Transformar los resultados en representaciones JSON con un contrato controlado. |
| Notifications | Encapsular las comunicaciones a usuarios; eventos, canales y contenido están pendientes. |

No todas las tablas necesitan Services. Se crearán por necesidad de lógica de negocio, no como una copia de cada tabla ni como envoltorios de operaciones triviales. Las reglas de negocio deberán centralizarse en Services para evitar duplicarlas en Controllers. La ruta de salud es una excepción sencilla aceptada: usa una función directamente en `routes/api.php`, sin Service ni acceso a datos.

## Flujo propuesto de una petición funcional

```text
Frontend → Route → FormRequest → Controller → Service → Model → Base de datos → Resource → JSON
```

Es un esquema conceptual: el resultado de persistencia vuelve a la operación y al Controller antes de transformarse con el Resource. La autorización se aplica donde corresponda mediante middleware, Form Requests y Policies. No todas las peticiones recorren todas las capas; salud responde directamente desde la ruta. Las Notifications se incorporarán solo a los casos de uso que lo requieran.

## Estructura actual y propuesta

Actualmente `app` contiene `Http/Controllers/Controller.php`, `Models/User.php` y `Providers/AppServiceProvider.php`. Las pruebas están separadas en `tests/Feature` y `tests/Unit`; existe además `tests/TestCase.php`.

Estructura objetivo, sin crear todavía las carpetas o clases faltantes:

```text
app/
├── Http/
│   ├── Controllers/Api/V1/   # Propuesto: acciones HTTP de la API
│   ├── Requests/            # Propuesto: validación
│   └── Resources/           # Propuesto: representación JSON
├── Models/                 # Existe; entidades del dominio pendientes
├── Policies/               # Propuesto: autorización
├── Services/               # Propuesto: lógica de negocio
├── Notifications/          # Propuesto: comunicaciones
└── Providers/              # Existe
routes/api.php              # Existe: health
tests/
├── Feature/                # Existe: comportamiento HTTP
└── Unit/                   # Existe: unidades aisladas
docs/                       # Documentación de esta fase
```

## Módulos previstos

Autenticación, usuarios, estudiantes, solicitudes, documentos, verificación, mallas, análisis académico, estados, resoluciones, notificaciones y reportes. Esta lista es una previsión de alcance, no evidencia de implementación ni un contrato de endpoints.

## Decisiones pendientes y límites

El diseño de Arquitectura Backend, API REST y configuración del repositorio corresponde a la fase con límite el **15 de septiembre de 2026**. La implementación de autenticación y roles está planificada del **16 al 22 de septiembre de 2026** y queda fuera de esta fase inicial.

Todavía no se integran migraciones del dominio: serán revisadas posteriormente. Las migraciones que incluye el proyecto base no constituyen un esquema funcional aprobado. No deben ejecutarse migraciones hasta que la base sea revisada y autorizada.

Quedan pendientes la revisión del modelo de datos contra el SRS, los estados y transiciones, el alcance de los permisos, los contratos funcionales y el detalle de notificaciones y reportes. El SRS no forma parte de la información revisada para esta documentación; no se infieren sus relaciones ni reglas.

Esta fase documenta y comprueba la base técnica. No implementa módulos, autenticación, roles ni persistencia del dominio. Véanse las [convenciones](convenciones.md) y el [contrato de API](api.md).

## Coordinación con el equipo de base de datos

El proceso de coordinación para la integración posterior es el siguiente:

1. El equipo de base de datos entrega el modelo y las migraciones validadas.
2. El equipo backend compara la estructura recibida con las Historias de Usuario y el SRS.
3. Las inconsistencias se registran y se comunican entre ambos equipos antes de integrar.
4. Las migraciones solamente se ejecutarán después de la revisión y autorización correspondiente.
5. Los cambios posteriores deben comunicarse tanto al equipo de base de datos como al equipo backend.

Los responsables se identifican por sus roles: **equipo de base de datos** y **equipo backend**. Este proceso no acredita que la entrega, revisión o autorización ya se hayan realizado; no se atribuyen nombres ni autorizaciones que no estén confirmados. La integración definitiva de la base de datos continúa pendiente de autorización.
