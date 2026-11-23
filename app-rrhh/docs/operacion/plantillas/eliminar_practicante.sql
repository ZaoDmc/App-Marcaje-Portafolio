-- Plantilla excepcional solicitada por el responsable el 26/10/2026.
-- No es una migración ni una acción normal de la aplicación.
-- Ejecutar sobre la base elegida, con respaldo y sin escrituras concurrentes.
-- Requiere MariaDB, esquema completo del proyecto y permiso CREATE ROUTINE.
-- No desactiva FOREIGN_KEY_CHECKS ni elimina archivos físicos de uploads.
-- No elimina auditoría ni autorías de terceros para forzar un borrado.
-- Usar una sesión sin otra transacción pendiente. Ejecutar el archivo completo.

DELIMITER $$
CREATE PROCEDURE rrhh_plantilla_eliminar_practicante_20261026(
    IN p_id_practicante INT,
    IN p_confirmar TINYINT,
    IN p_eliminar_cuenta TINYINT
)
SQL SECURITY INVOKER
principal: BEGIN
    DECLARE v_id INT DEFAULT NULL;
    DECLARE v_usuario INT DEFAULT NULL;

    -- Evité cerrar una transacción ajena al invocar este auxiliar.
    IF @@in_transaction <> 0 THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Cierra la transaccion previa antes de usar la plantilla.';
    END IF;
    IF p_id_practicante IS NULL OR p_id_practicante <= 0
       OR p_confirmar IS NULL OR p_confirmar NOT IN (0,1)
       OR p_eliminar_cuenta IS NULL OR p_eliminar_cuenta NOT IN (0,1) THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Completa un ID positivo y opciones 0 o 1.';
    END IF;

    -- Agregué una reversión conjunta si cualquier relación impide la operación.
    BEGIN
        DECLARE EXIT HANDLER FOR SQLEXCEPTION
        BEGIN
            ROLLBACK;
            RESIGNAL;
        END;
        START TRANSACTION;

        IF EXISTS (
            SELECT 1 FROM information_schema.TABLES
            WHERE TABLE_SCHEMA=DATABASE() AND TABLE_TYPE='BASE TABLE'
              AND COALESCE(ENGINE,'') <> 'InnoDB'
        ) THEN
            SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Hay tablas no InnoDB; no se garantiza reversion. Revisar esquema.';
        END IF;

        -- Bloqueé tablas adicionales con identificadores operativos no inventariados.
        IF EXISTS (
            SELECT 1 FROM information_schema.COLUMNS
            WHERE TABLE_SCHEMA=DATABASE()
              AND COLUMN_NAME IN ('id_practicante','id_practica','id_usuario','id_horario',
                  'id_jornada','id_nota','id_liderazgo','id_tiempo','id_permiso')
              AND TABLE_NAME NOT IN ('practicantes','practicas','documentos_practicante',
                  'horarios_practicante','detalle_horario_practicante','excepciones_horario_practicante',
                  'usuarios_sistema','usuarios_roles','sesiones_usuario','tokens_usuario',
                  'jornadas_asistencia','marcas_asistencia','historial_areas_practicante',
                  'liderazgos_area','bloques_area_jornada','evaluaciones_practicante',
                  'permisos_practicante','recuperaciones_permiso','extras_historicas',
                  'tiempos_excepcionales','compensaciones_tiempo','acompanamiento',
                  'notas_semanales','notas_semanales_detalle','notas_semanales_historial')
        ) THEN
            SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Hay tablas adicionales relacionadas; actualizar la plantilla antes de borrar.';
        END IF;

        -- Bloqueé referencias nuevas, incluso si usan nombres de columna distintos.
        IF EXISTS (
            SELECT 1 FROM information_schema.KEY_COLUMN_USAGE k
            WHERE k.REFERENCED_TABLE_SCHEMA=DATABASE()
              AND k.REFERENCED_TABLE_NAME IN ('practicantes','practicas','usuarios_sistema',
                  'horarios_practicante','detalle_horario_practicante','excepciones_horario_practicante',
                  'documentos_practicante','usuarios_roles','sesiones_usuario','tokens_usuario',
                  'jornadas_asistencia','marcas_asistencia','historial_areas_practicante',
                  'liderazgos_area','bloques_area_jornada','evaluaciones_practicante',
                  'permisos_practicante','recuperaciones_permiso','extras_historicas',
                  'tiempos_excepcionales','compensaciones_tiempo','acompanamiento',
                  'notas_semanales','notas_semanales_detalle','notas_semanales_historial')
              AND (k.TABLE_SCHEMA<>DATABASE() OR k.TABLE_NAME NOT IN (
                  'practicantes','practicas','usuarios_sistema','usuarios_roles','sesiones_usuario',
                  'tokens_usuario','horarios_practicante','detalle_horario_practicante',
                  'excepciones_horario_practicante','documentos_practicante','jornadas_asistencia',
                  'marcas_asistencia','historial_areas_practicante','liderazgos_area',
                  'bloques_area_jornada','evaluaciones_practicante','auditoria_cambios',
                  'permisos_practicante','recuperaciones_permiso','extras_historicas',
                  'tiempos_excepcionales','compensaciones_tiempo','acompanamiento',
                  'notas_semanales','notas_semanales_detalle','notas_semanales_historial',
                  'configuracion_notas'))
        ) THEN
            SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Una relacion FK adicional requiere revisar el alcance del borrado.';
        END IF;

        SELECT id_practicante INTO v_id FROM practicantes
        WHERE id_practicante=p_id_practicante FOR UPDATE;
        IF v_id IS NULL THEN
            SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'No existe ese id_practicante.';
        END IF;
        SELECT id_usuario INTO v_usuario FROM usuarios_sistema
        WHERE id_practicante=v_id FOR UPDATE;

        -- Agregué identidad y resumen para revisar el alcance antes de confirmar.
        SELECT p.id_practicante,p.nombre_1,p.apellido_paterno,p.email,
               v_usuario AS id_usuario_asociado,
               (SELECT COUNT(*) FROM practicas WHERE id_practicante=v_id) AS practicas,
               (SELECT COUNT(*) FROM jornadas_asistencia j JOIN practicas pr USING(id_practica)
                WHERE pr.id_practicante=v_id) AS jornadas,
               (SELECT COUNT(*) FROM notas_semanales n JOIN practicas pr USING(id_practica)
                WHERE pr.id_practicante=v_id) AS notas_semanales,
               (SELECT COUNT(*) FROM documentos_practicante WHERE id_practicante=v_id) AS documentos,
               p_confirmar AS confirmar_borrado,p_eliminar_cuenta AS eliminar_cuenta
        FROM practicantes p WHERE p.id_practicante=v_id;
        SELECT id_documento,ruta_relativa FROM documentos_practicante WHERE id_practicante=v_id;
        IF p_confirmar=0 THEN
            ROLLBACK;
            SELECT 'VISTA PREVIA: no se borraron ni modificaron registros.' AS resultado;
            LEAVE principal;
        END IF;

        -- Evité eliminar documentos o evaluaciones con pertenencias contradictorias.
        IF EXISTS (
            SELECT 1 FROM documentos_practicante d JOIN practicas pr USING(id_practica)
            WHERE (d.id_practicante=v_id AND pr.id_practicante<>v_id)
               OR (d.id_practicante<>v_id AND pr.id_practicante=v_id)
        ) OR EXISTS (
            SELECT 1 FROM evaluaciones_practicante e JOIN practicas pr USING(id_practica)
            WHERE (e.id_practicante=v_id AND pr.id_practicante<>v_id)
               OR (e.id_practicante<>v_id AND pr.id_practicante=v_id)
        ) THEN
            SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Hay pertenencias contradictorias entre persona y practica; revisar antes de borrar.';
        END IF;

        -- Esta FK usa SET NULL: bloqueé borrar el revisor de permisos de terceros.
        IF p_eliminar_cuenta=1 AND v_usuario IS NOT NULL AND EXISTS (
            SELECT 1 FROM permisos_practicante pe JOIN practicas pr USING(id_practica)
            WHERE pe.revisado_por=v_usuario AND pr.id_practicante<>v_id
        ) THEN
            SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'La cuenta reviso permisos ajenos. Conservar cuenta o revisar el caso.';
        END IF;

        -- Agregué borrado por pertenencia, de las tablas hijas hacia sus padres.
        DELETE c FROM compensaciones_tiempo c JOIN tiempos_excepcionales t USING(id_tiempo)
          JOIN jornadas_asistencia j USING(id_jornada) JOIN practicas pr USING(id_practica)
          WHERE pr.id_practicante=v_id;
        DELETE t FROM tiempos_excepcionales t JOIN jornadas_asistencia j USING(id_jornada)
          JOIN practicas pr USING(id_practica) WHERE pr.id_practicante=v_id;
        DELETE e FROM extras_historicas e JOIN jornadas_asistencia j USING(id_jornada)
          JOIN practicas pr USING(id_practica) WHERE pr.id_practicante=v_id;
        DELETE m FROM marcas_asistencia m JOIN jornadas_asistencia j USING(id_jornada)
          JOIN practicas pr USING(id_practica) WHERE pr.id_practicante=v_id;
        DELETE b FROM bloques_area_jornada b JOIN jornadas_asistencia j USING(id_jornada)
          JOIN practicas pr USING(id_practica) WHERE pr.id_practicante=v_id;
        DELETE j FROM jornadas_asistencia j JOIN practicas pr USING(id_practica)
          WHERE pr.id_practicante=v_id;
        DELETE r FROM recuperaciones_permiso r JOIN permisos_practicante pe USING(id_permiso)
          JOIN practicas pr USING(id_practica) WHERE pr.id_practicante=v_id;
        DELETE pe FROM permisos_practicante pe JOIN practicas pr USING(id_practica)
          WHERE pr.id_practicante=v_id;
        DELETE h FROM notas_semanales_historial h JOIN notas_semanales n USING(id_nota)
          JOIN practicas pr USING(id_practica) WHERE pr.id_practicante=v_id;
        DELETE d FROM notas_semanales_detalle d JOIN notas_semanales n USING(id_nota)
          JOIN practicas pr USING(id_practica) WHERE pr.id_practicante=v_id;
        DELETE n FROM notas_semanales n JOIN practicas pr USING(id_practica)
          WHERE pr.id_practicante=v_id;
        -- Si este liderazgo figura en notas de otros, la FK revierte el conjunto.
        DELETE l FROM liderazgos_area l JOIN practicas pr USING(id_practica)
          WHERE pr.id_practicante=v_id;
        DELETE FROM evaluaciones_practicante WHERE id_practicante=v_id;
        DELETE a FROM acompanamiento a JOIN practicas pr USING(id_practica)
          WHERE pr.id_practicante=v_id;
        DELETE h FROM historial_areas_practicante h JOIN practicas pr USING(id_practica)
          WHERE pr.id_practicante=v_id;
        DELETE e FROM excepciones_horario_practicante e JOIN horarios_practicante h USING(id_horario)
          JOIN practicas pr USING(id_practica) WHERE pr.id_practicante=v_id;
        DELETE d FROM detalle_horario_practicante d JOIN horarios_practicante h USING(id_horario)
          JOIN practicas pr USING(id_practica) WHERE pr.id_practicante=v_id;
        DELETE h FROM horarios_practicante h JOIN practicas pr USING(id_practica)
          WHERE pr.id_practicante=v_id;
        DELETE FROM documentos_practicante WHERE id_practicante=v_id;
        DELETE FROM practicas WHERE id_practicante=v_id;

        IF v_usuario IS NOT NULL THEN
            IF p_eliminar_cuenta=1 THEN
                -- Conservar auditoría puede impedir estos DELETE; no se fuerza su eliminación.
                DELETE FROM tokens_usuario WHERE id_usuario=v_usuario;
                DELETE FROM usuarios_roles WHERE id_usuario=v_usuario;
                DELETE FROM sesiones_usuario WHERE id_usuario=v_usuario;
                DELETE FROM usuarios_sistema WHERE id_usuario=v_usuario;
            ELSE
                -- Conservé la cuenta como autor histórico, desvinculada y sin acceso.
                UPDATE sesiones_usuario SET activa=0,fecha_cierre=COALESCE(fecha_cierre,NOW())
                  WHERE id_usuario=v_usuario;
                UPDATE tokens_usuario SET usado=1,fecha_uso=COALESCE(fecha_uso,NOW())
                  WHERE id_usuario=v_usuario;
                UPDATE usuarios_roles SET rol_activo=0 WHERE id_usuario=v_usuario;
                UPDATE usuarios_sistema SET id_practicante=NULL,activo=0,estado_cuenta='INACTIVO'
                  WHERE id_usuario=v_usuario;
            END IF;
        END IF;
        DELETE FROM practicantes WHERE id_practicante=v_id;
        COMMIT;
        SELECT 'BORRADO CONFIRMADO. Auditoria y archivos fisicos no eliminados.' AS resultado,
               v_id AS id_practicante,p_eliminar_cuenta AS eliminar_cuenta;
    END;
END$$
DELIMITER ;

-- REEMPLAZAR SOLO ESTOS PARAMETROS. NULL impide borrar por una copia accidental.
SET @id_practicante = NULL;     -- Sustituir por el ID de PERSONA, no el de práctica/usuario.
SET @confirmar_borrado = 0;     -- 0: vista previa sin borrado. 1: borrar y confirmar.
SET @eliminar_cuenta = 0;       -- 0: conservar cuenta inactiva. 1: borrar si las FK lo permiten.
CALL rrhh_plantilla_eliminar_practicante_20261026(
    @id_practicante,@confirmar_borrado,@eliminar_cuenta
);
DROP PROCEDURE rrhh_plantilla_eliminar_practicante_20261026;

-- Si CALL falla, el manejador revierte sus cambios. El cliente puede detenerse
-- antes del DROP: en ese caso retirar SOLO este auxiliar antes de reimportar:
-- DROP PROCEDURE rrhh_plantilla_eliminar_practicante_20261026;
