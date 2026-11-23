# Auditoría e historial

## Propósito

Permitir reconstruir qué se cambió, quién actuó, cuándo y con qué motivo, conservando el registro anterior. La auditoría complementa la validación; no legitima una acción sin permisos.

## Reglas de conservación

- Bajas y anulaciones funcionales son lógicas; no borrar marcas, jornadas, pertenencias o notas para ocultar cambios.
- Conservar motivo, actor y valores anteriores/nuevos cuando el flujo lo requiere. El actor se obtiene de la sesión y se revalida, no se acepta como identidad libre del formulario.
- Una operación crítica y su auditoría deben mantener consistencia transaccional; un fallo no puede dejar autorización, saldo o publicación a medio guardar.
- Las notas conservan además revisiones y versiones propias. Cambiar liderazgo no transfiere la autoría.
- No registrar contraseñas, tokens planos, contenido de `.env` o documentos personales dentro de evidencias públicas.
- Los logs técnicos y el historial de negocio cumplen funciones distintas; un error PHP no acredita que una operación haya quedado auditada correctamente.

## Código y datos

`src/Services/AuditoriaService.php`, llamadas desde los servicios funcionales, `auditoria_cambios` y tablas de historial propias. ADMIN consulta auditoría según sus rutas. Para corregir un flujo, comprobar tanto el resultado funcional como la persistencia o rollback del evento.

Procedencia: [correcciones y autorización](../historial/operativa/FASE_3_CORRECCIONES.md); reglas académicas en [Notas](NOTAS.md).
