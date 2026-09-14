# Decisiones funcionales del grupo

Este registro recoge únicamente las decisiones proporcionadas por el grupo. Las justificaciones expresan su propósito técnico, sin atribuir requisitos adicionales al SRS. **Definida** significa que la decisión está tomada, no que esté implementada. **Pendiente** indica que falta precisar o revisar su contenido. No se modifican tablas ni código en esta fase.

El SRS y las Historias de Usuario mencionadas no se han aportado para esta revisión; las referencias a ellos requieren validación posterior.

## 1. Tipo de trámite y tipo de proceso

- **Decisión tomada:** distinguir ambos conceptos. Homologación y Reconocimiento son tipos de trámite.
- **Justificación:** evitar tratar como equivalentes conceptos distintos del dominio.
- **Consecuencia para el backend:** conservar la distinción al revisar el modelo y los contratos; no fijar aún un catálogo de tipos de proceso.
- **Estado:** definida.

## 2. Cédula o pasaporte

- **Decisión tomada:** mantener la funcionalidad actual y ampliar únicamente el campo de 10 a 20 caracteres.
- **Justificación:** admitir la longitud acordada sin rediseñar el tratamiento del documento de identidad.
- **Consecuencia para el backend:** revisar posteriormente la longitud del campo y sus validaciones asociadas, preservando el comportamiento existente. No se aplica ahora una migración.
- **Estado:** definida.

## 3. Expediente

- **Decisión tomada:** no será una tabla independiente; corresponde al conjunto de documentos cargados por el estudiante.
- **Justificación:** representar el expediente a partir de sus documentos, sin introducir una entidad persistente adicional.
- **Consecuencia para el backend:** construir su representación a partir de los documentos y las relaciones aprobadas posteriormente.
- **Estado:** definida.

## 4. Identificador visual de solicitud

- **Decisión tomada:** utilizar el ID de la solicitud como identificador visual.
- **Justificación:** mantener una referencia única y consistente con la solicitud.
- **Consecuencia para el backend:** exponer ese ID para su visualización; no crear un código visual alternativo por esta decisión.
- **Estado:** definida.

## 5. Carrera del estudiante

- **Decisión tomada:** asociar la carrera al estudiante según el modelo establecido en el SRS.
- **Justificación:** mantener la correspondencia con el modelo de requisitos acordado.
- **Consecuencia para el backend:** consultar el SRS antes de definir claves y relaciones; aquí no se infiere su cardinalidad.
- **Estado:** definida; la concreción técnica requiere revisar el SRS.

## 6. Estados de la solicitud

- **Decisión tomada:** revisar los estados tomando el SRS como referencia.
- **Justificación:** asegurar que el ciclo de vida corresponda a los requisitos vigentes.
- **Consecuencia para el backend:** dejar pendientes el catálogo y las transiciones hasta completar la revisión.
- **Estado:** pendiente.

## 7. Responsable en el historial

- **Decisión tomada:** registrar al responsable de cada cambio de estado.
- **Justificación:** permitir atribuir y revisar cada transición.
- **Consecuencia para el backend:** contemplar la relación con el responsable al diseñar el historial y la operación de cambio de estado.
- **Estado:** definida.

## 8. Revisión documental

- **Decisión tomada:** el único usuario autorizado para la revisión documental será el Coordinador.
- **Justificación:** delimitar la responsabilidad de esa revisión conforme al acuerdo del grupo.
- **Consecuencia para el backend:** aplicar esta restricción cuando se implemente la autorización; no extenderla por inferencia a otras acciones.
- **Estado:** definida.

## 9. Verificaciones académicas

- **Decisión tomada:** simplificarlas mediante campos booleanos.
- **Justificación:** expresar los resultados binarios de forma directa.
- **Consecuencia para el backend:** contemplar validación y representación booleana; los campos concretos se precisarán al revisar el modelo.
- **Estado:** definida.

## 10. Historia de Usuario de mallas y horas

- **Decisión tomada:** reconocer que estaba desactualizada y alinearla con la estructura vigente.
- **Justificación:** evitar implementar requisitos que ya no describen el funcionamiento esperado.
- **Consecuencia para el backend:** esperar la revisión de esa historia antes de fijar las reglas y contratos correspondientes.
- **Estado:** pendiente.

## 11. Historia de Usuario de resultado e informe técnico

- **Decisión tomada:** corregir la historia desactualizada según el funcionamiento actual.
- **Justificación:** mantener consistencia entre la especificación y el proceso vigente.
- **Consecuencia para el backend:** precisar las reglas y la representación del resultado e informe después de actualizar la historia.
- **Estado:** pendiente.

## 12. Notificaciones y formatos de reportes

- **Decisión tomada:** todavía deben precisarse las notificaciones y los formatos de reportes.
- **Justificación:** la información disponible no define contratos suficientes para implementarlos.
- **Consecuencia para el backend:** mantener pendientes eventos, destinatarios, canales y contenido de notificaciones, así como formatos y contenido de reportes, sin asumir opciones definitivas.
- **Estado:** pendiente.
