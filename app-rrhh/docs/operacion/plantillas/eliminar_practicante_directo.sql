-- Borrado directo solicitado por el responsable. NO es una migración.
-- Respaldo previo y sin escrituras concurrentes. No desactivar claves foráneas.
-- Ejecutar con un cliente que se detenga al primer error.
-- Si falla, ROLLBACK en la misma conexión; no continuar hasta COMMIT.
-- Si fue líder/supervisor, las referencias ajenas pueden impedir borrar su cuenta.
-- SQL no elimina los archivos físicos de uploads.

SET @id_practicante = 0; -- REEMPLAZAR únicamente por el ID de PERSONA.
SET @id_usuario = (
    SELECT id_usuario FROM usuarios_sistema
    WHERE id_practicante = @id_practicante LIMIT 1
);

START TRANSACTION;

-- 1. Datos propios de todas las prácticas, desde hijos hacia padres.
DELETE c FROM compensaciones_tiempo c JOIN tiempos_excepcionales t USING(id_tiempo)
  JOIN jornadas_asistencia j USING(id_jornada) JOIN practicas pr USING(id_practica)
  WHERE pr.id_practicante=@id_practicante;
DELETE t FROM tiempos_excepcionales t JOIN jornadas_asistencia j USING(id_jornada)
  JOIN practicas pr USING(id_practica) WHERE pr.id_practicante=@id_practicante;
DELETE e FROM extras_historicas e JOIN jornadas_asistencia j USING(id_jornada)
  JOIN practicas pr USING(id_practica) WHERE pr.id_practicante=@id_practicante;
DELETE m FROM marcas_asistencia m JOIN jornadas_asistencia j USING(id_jornada)
  JOIN practicas pr USING(id_practica) WHERE pr.id_practicante=@id_practicante;
DELETE b FROM bloques_area_jornada b JOIN jornadas_asistencia j USING(id_jornada)
  JOIN practicas pr USING(id_practica) WHERE pr.id_practicante=@id_practicante;
DELETE j FROM jornadas_asistencia j JOIN practicas pr USING(id_practica)
  WHERE pr.id_practicante=@id_practicante;
DELETE r FROM recuperaciones_permiso r JOIN permisos_practicante pe USING(id_permiso)
  JOIN practicas pr USING(id_practica) WHERE pr.id_practicante=@id_practicante;
DELETE pe FROM permisos_practicante pe JOIN practicas pr USING(id_practica)
  WHERE pr.id_practicante=@id_practicante;
DELETE h FROM notas_semanales_historial h JOIN notas_semanales n USING(id_nota)
  JOIN practicas pr USING(id_practica) WHERE pr.id_practicante=@id_practicante;
DELETE d FROM notas_semanales_detalle d JOIN notas_semanales n USING(id_nota)
  JOIN practicas pr USING(id_practica) WHERE pr.id_practicante=@id_practicante;
DELETE n FROM notas_semanales n JOIN practicas pr USING(id_practica)
  WHERE pr.id_practicante=@id_practicante;
-- Si este liderazgo figura en notas de otros, detenerse y ejecutar ROLLBACK.
DELETE l FROM liderazgos_area l JOIN practicas pr USING(id_practica)
  WHERE pr.id_practicante=@id_practicante;
DELETE FROM evaluaciones_practicante WHERE id_practicante=@id_practicante;
DELETE a FROM acompanamiento a JOIN practicas pr USING(id_practica)
  WHERE pr.id_practicante=@id_practicante;
DELETE h FROM historial_areas_practicante h JOIN practicas pr USING(id_practica)
  WHERE pr.id_practicante=@id_practicante;
DELETE e FROM excepciones_horario_practicante e JOIN horarios_practicante h USING(id_horario)
  JOIN practicas pr USING(id_practica) WHERE pr.id_practicante=@id_practicante;
DELETE d FROM detalle_horario_practicante d JOIN horarios_practicante h USING(id_horario)
  JOIN practicas pr USING(id_practica) WHERE pr.id_practicante=@id_practicante;
DELETE h FROM horarios_practicante h JOIN practicas pr USING(id_practica)
  WHERE pr.id_practicante=@id_practicante;
DELETE FROM documentos_practicante WHERE id_practicante=@id_practicante;
DELETE FROM practicas WHERE id_practicante=@id_practicante;

-- 2. Auditoría del actor, sus sesiones y la ficha. Este borrado es definitivo.
DELETE FROM auditoria_cambios
WHERE id_sesion IN (
    SELECT id_sesion FROM sesiones_usuario WHERE id_usuario = @id_usuario
);
DELETE FROM auditoria_cambios WHERE id_usuario_actor = @id_usuario;
DELETE FROM auditoria_cambios
WHERE tabla_afectada = 'practicantes' AND id_registro_afectado = @id_practicante;

-- 3. Acceso y persona. No se borran registros ajenos para forzar estas FK.
DELETE FROM tokens_usuario WHERE id_usuario = @id_usuario;
DELETE FROM sesiones_usuario WHERE id_usuario = @id_usuario;
DELETE FROM usuarios_roles WHERE id_usuario = @id_usuario;
DELETE FROM usuarios_sistema WHERE id_usuario = @id_usuario;
DELETE FROM practicantes WHERE id_practicante = @id_practicante;

-- Solo alcanzar COMMIT si no falló ninguna instrucción anterior.
COMMIT;
