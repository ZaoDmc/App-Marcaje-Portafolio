<?php

declare(strict_types=1);

require_once __DIR__ . '/../bootstrap.php';

/*
|--------------------------------------------------------------------------
| Auditoría del sistema
|--------------------------------------------------------------------------
|
| Funciones para registrar eventos de seguridad y acciones funcionales en la
| tabla auditoria_cambios. La auditoría no debe romper el flujo principal si
| la base de datos aún no está disponible o si ocurre un error de registro.
|
*/

if (!function_exists('auditoria_eventos_seguridad')) {
    function auditoria_eventos_seguridad(): array
    {
        return [
            'LOGIN_EXITOSO',
            'LOGIN_FALLIDO',
            'LOGOUT',
            'RECUPERACION_SOLICITADA',
            'TOKEN_GENERADO',
            'TOKEN_REGENERADO',
            'PASSWORD_CAMBIADA',
            'CUENTA_ACTIVADA',
            'USUARIO_BLOQUEADO',
            'ROL_ASIGNADO',
            'ROL_REMOVIDO',
            'CREACION_USUARIO',
            'EDICION_USUARIO',
            'CAMBIO_ESTADO_CUENTA',
            'INTENTO_ASIGNACION_ADMIN_RECHAZADO',
            'SELECCION_ROL_CONTEXTO',
        ];
    }
}

if (!function_exists('auditoria_eventos_funcionales')) {
    function auditoria_eventos_funcionales(): array
    {
        return [
            'EDICION_DATOS_PROPIOS_PRACTICANTE',
            'CREAR_PRACTICANTE',
            'EDITAR_PRACTICANTE',
            'CORREGIR_ASISTENCIA',
            'ANULAR_ASISTENCIA',
            'ANULAR_MARCA_ASISTENCIA',
            'CAMBIAR_AREA',
            // Registré altas, reemplazos y cierres de liderazgo sin reutilizar eventos de notas.
            'DESIGNAR_LIDER_AREA',
            'REEMPLAZAR_LIDER_AREA',
            'RETIRAR_LIDER_AREA',
            // Agregué auditoría atómica de las revisiones semanales, sin publicar borradores.
            'GUARDAR_NOTA_SEMANAL',
            // Distinguí aprobación inicial y correcciones publicadas sin perder sus valores anteriores.
            'PUBLICAR_NOTA_SEMANAL',
            'CORREGIR_NOTA_PUBLICADA',
            // Registré aparte la aprobación excepcional ADMIN de borradores sin responsable vigente.
            'RESOLVER_BORRADOR_NOTA',
            // Registré quién cambia los criterios y qué versión queda vigente para notas nuevas.
            'CAMBIAR_RUBRICA_NOTAS',
            'APROBAR_HORAS_EXTRA',
            'SUBIR_DOCUMENTO_PRACTICANTE',
            'CERRAR_PRACTICA',
            // Registré las modificaciones de horas requeridas y acumuladas de práctica en Reportes.
            'MODIFICAR_HORAS_PRACTICA',
            'EXPORTAR_XLSX',
            'IMPORTAR_PRACTICANTES_CSV',
            // Agregué los eventos que antes se descartaban y las operaciones
            // nuevas para conservar actor, registro y cambios de cada revisión.
            'SOLICITUD_PERMISO',
            // Conservé solicitud y revisión de colación como eventos independientes.
            'SOLICITAR_CAMBIO_COLACION',
            'REVISAR_CAMBIO_COLACION',
            // Identifiqué el rechazo automático por el corte del viernes.
            'RECHAZO_PLAZO_RECUPERACION',
            'APROBAR_PERMISO',
            'RECHAZAR_PERMISO',
            // Agregué trazas para validar recuperación y reabrirla cuando cambia su evidencia.
            'VALIDAR_RECUPERACION',
            'REABRIR_RECUPERACION',
            // Registré planes que dejan de ser elegibles tras cambiar horario o calendario.
            'REPROGRAMAR_RECUPERACION',
            // Conservé solicitud y autorización del saldo adicional como acciones distintas.
            'SOLICITAR_RECUPERACION_ADICIONAL',
            'AUTORIZAR_RECUPERACION_ADICIONAL',
            // Incluí las trazas del catálogo que mantiene el calendario común de feriados.
            'CATALOGO_CREADO',
            'CATALOGO_EDITADO',
            'CATALOGO_ACTIVADO',
            'CATALOGO_DESACTIVADO',
            'REVISAR_JORNADA',
            // Agregué trazas del crédito excepcional y sus canjes, separadas de aprobar extras antiguas.
            'SOLICITAR_TIEMPO_EXCEPCIONAL',
            'REVISAR_TIEMPO_EXCEPCIONAL',
            'REABRIR_TIEMPO_EXCEPCIONAL',
            'SOLICITAR_COMPENSACION',
            // Registré al practicante como actor del canje autónomo, sin atribuirlo al supervisor.
            'CANJEAR_COMPENSACION',
            'REVISAR_COMPENSACION',
            'APLICAR_COMPENSACION',
            'EDITAR_ACOMPANAMIENTO',
            'GENERAR_FORMULARIO_VALIDACION',
            'CONFIGURAR_FORMULARIO_VALIDACION',
            'GUARDAR_PLANTILLA_FORMULARIO_VALIDACION',
        ];
    }
}

if (!function_exists('auditoria_evento_permitido')) {
    function auditoria_evento_permitido(string $evento): bool
    {
        return in_array($evento, array_merge(auditoria_eventos_seguridad(), auditoria_eventos_funcionales()), true);
    }
}

if (!function_exists('auditoria_valor_sesion')) {
    function auditoria_valor_sesion(string $key): mixed
    {
        // La auditoría solo lee sesión si ya existe; no inicia una sesión nueva.
        if (session_status() !== PHP_SESSION_ACTIVE) {
            return null;
        }

        return $_SESSION[$key] ?? null;
    }
}

if (!function_exists('auditoria_ip')) {
    function auditoria_ip(): string
    {
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'CLI';

        return is_string($ip) && $ip !== '' ? substr($ip, 0, 45) : 'CLI';
    }
}

if (!function_exists('auditoria_user_agent')) {
    function auditoria_user_agent(): ?string
    {
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? null;

        return is_string($userAgent) && $userAgent !== '' ? substr($userAgent, 0, 255) : null;
    }
}

if (!function_exists('auditoria_serializar_valores')) {
    function auditoria_serializar_valores(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        if (is_string($value)) {
            return $value;
        }

        // Se guarda JSON como texto para no depender de funciones JSON de MySQL/MariaDB.
        $encoded = json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        return $encoded === false ? null : $encoded;
    }
}

if (!function_exists('auditoria_registrar')) {
    function auditoria_registrar(string $evento, array $datos = []): bool
    {
        if (!auditoria_evento_permitido($evento)) {
            return false;
        }

        $payload = [
            'id_usuario_actor' => $datos['id_usuario_actor'] ?? auditoria_valor_sesion('id_usuario'),
            'id_rol_contexto' => $datos['id_rol_contexto'] ?? auditoria_valor_sesion('id_rol_activo'),
            'id_sesion' => $datos['id_sesion'] ?? auditoria_valor_sesion('id_sesion'),
            'evento' => $evento,
            'tabla_afectada' => $datos['tabla_afectada'] ?? null,
            'id_registro_afectado' => $datos['id_registro_afectado'] ?? null,
            'valores_anteriores' => auditoria_serializar_valores($datos['valores_anteriores'] ?? null),
            'valores_nuevos' => auditoria_serializar_valores($datos['valores_nuevos'] ?? null),
            'ip' => $datos['ip'] ?? auditoria_ip(),
            'user_agent' => $datos['user_agent'] ?? auditoria_user_agent(),
        ];

        try {
            $statement = db()->prepare(
                'INSERT INTO auditoria_cambios (
                    id_usuario_actor,
                    id_rol_contexto,
                    id_sesion,
                    evento,
                    tabla_afectada,
                    id_registro_afectado,
                    valores_anteriores,
                    valores_nuevos,
                    ip,
                    user_agent
                ) VALUES (
                    :id_usuario_actor,
                    :id_rol_contexto,
                    :id_sesion,
                    :evento,
                    :tabla_afectada,
                    :id_registro_afectado,
                    :valores_anteriores,
                    :valores_nuevos,
                    :ip,
                    :user_agent
                )'
            );

            return $statement->execute($payload);
        } catch (Throwable $exception) {
            error_log('Error al registrar auditoría: ' . $exception->getMessage());

            return false;
        }
    }
}
