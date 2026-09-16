# API REST

## Estado y alcance

**Implementado:** `GET /api/v1/health`. **Propuesto:** las convenciones generales de este documento. **Pendiente:** aprobación e implementación de los contratos de los módulos funcionales.

La URL base propuesta es `/api/v1`, ya utilizada por salud. Laravel registra `routes/api.php` con el prefijo `/api`; el archivo define `/v1/health`.

## Versionado y formato

Se propone versionar en la URL. Los cambios incompatibles deberán evaluarse como una nueva versión, sin cambiar silenciosamente contratos existentes. No hay otras versiones implementadas.

La API utilizará exclusivamente JSON para los cuerpos de petición y respuesta. Los clientes enviarán `Accept: application/json` y, cuando envíen un cuerpo JSON, `Content-Type: application/json`. Una respuesta 204 no lleva cuerpo. El transporte de futuros documentos está pendiente de definición; aquí no se establece un contrato de carga.

## Endpoint implementado

```http
GET /api/v1/health
Accept: application/json
```

Estado HTTP: **200**. Respuesta exacta:

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

No requiere autenticación y no consulta la base de datos. Comprueba que la ruta de la API responde; no certifica la disponibilidad de la base de datos u otros servicios. `HealthCheckTest` verifica el estado 200 y el JSON exacto.

## Convenciones propuestas de respuesta

Éxito: `success` booleano, `message` legible y `data` con el resultado. Este formato ya se usa en salud, pero su aplicación general sigue propuesta.

Error: `success`, `message` y `errors`. Ejemplo ilustrativo, no contrato de un endpoint implementado:

```json
{
  "success": false,
  "message": "Los datos enviados no son válidos",
  "errors": {
    "campo": ["Descripción del error de validación"]
  }
}
```

La estructura de errores no está implementada de forma global. La configuración actual solicita JSON para excepciones de la API, pero no transforma automáticamente los errores de Laravel al formato propuesto. Queda pendiente definir `errors` para errores sin campos y aprobar el contrato común. No se expondrán credenciales ni detalles internos en respuestas de producción.

## Códigos HTTP principales

| Código | Uso previsto |
|---|---|
| 200 | Operación correcta; utilizado por salud. |
| 201 | Recurso creado. |
| 204 | Operación correcta sin cuerpo; no incluye envoltorio JSON. |
| 400 | Petición mal formada. |
| 401 | Autenticación ausente o inválida. |
| 403 | Acción no autorizada. |
| 404 | Recurso o ruta inexistente. |
| 409 | Conflicto con el estado actual del recurso. |
| 422 | Datos que no superan la validación. |
| 500 | Error interno inesperado. |

Salvo el contrato de salud, esta tabla expresa convenciones propuestas, no pruebas de funcionalidades disponibles.

## Paginación, filtros y nombres de rutas

- **Paginación propuesta:** parámetros `page` y `per_page`, colección en `data` y metadatos/enlaces de navegación. Los valores predeterminados, máximos y la forma final de esos metadatos deben aprobarse antes de implementar listados.
- **Filtros propuestos:** parámetros de consulta explícitos, validados y permitidos por cada recurso. Campos, operadores y ordenación están pendientes; no se aceptarán nombres arbitrarios de columnas como contrato.
- **URIs propuestas:** nombres del dominio en español, plurales y minúsculas; `kebab-case` si hay varias palabras. La ruta técnica `health` ya está aprobada como excepción.
- **Nombres internos propuestos:** patrón `api.v1.<recurso>.<accion>` para rutas que necesiten nombre. Salud actualmente no tiene nombre interno asignado.

## Módulos con endpoints futuros

Autenticación, usuarios, estudiantes, solicitudes, documentos, verificación, mallas, análisis académico, estados, resoluciones, notificaciones y reportes.

Los endpoints funcionales son todavía preliminares y **no están implementados**. No se fijan aquí URIs, operaciones, permisos ni esquemas definitivos para estos módulos. Requieren revisión del SRS y aprobación del equipo antes de su desarrollo.
