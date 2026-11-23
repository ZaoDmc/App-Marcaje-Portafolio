<?php

declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/config/app.php';

/*
|--------------------------------------------------------------------------
| Helpers generales
|--------------------------------------------------------------------------
|
| Funciones pequeñas y reutilizables para rutas, respuestas HTTP, formularios
| y sesiones. No contienen reglas de negocio críticas ni lógica de login.
|
*/

if (!function_exists('e')) {
    function e(?string $value): string
    {
        // Escapa salida HTML para prevenir XSS en vistas y mensajes.
        return htmlspecialchars($value ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}

if (!function_exists('url')) {
    function url(string $path = ''): string
    {
        // Construye enlaces internos desde BASE_URL, evitando rutas quemadas.
        $cleanPath = ltrim($path, '/');

        return $cleanPath === '' ? BASE_URL : BASE_URL . '/' . $cleanPath;
    }
}

if (!function_exists('asset')) {
    function asset(string $path = ''): string
    {
        // Construye URLs de assets desde ASSETS_URL para soportar /asistencia y subdominios.
        $cleanPath = ltrim($path, '/');

        return $cleanPath === '' ? ASSETS_URL : ASSETS_URL . '/' . $cleanPath;
    }
}

if (!function_exists('redirect_to')) {
    function redirect_to(string $path, int $statusCode = 302): never
    {
        // Redirige usando URLs derivadas para evitar redirects con rutas rigidas.
        header('Location: ' . url($path), true, $statusCode);
        exit;
    }
}

if (!function_exists('is_post')) {
    function is_post(): bool
    {
        return strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST';
    }
}

if (!function_exists('input')) {
    function input(string $key, mixed $default = null): mixed
    {
        // Lee datos de formulario sin asumir que la clave existe.
        return $_POST[$key] ?? $default;
    }
}

if (!function_exists('query_param')) {
    function query_param(string $key, mixed $default = null): mixed
    {
        // Lee parametros GET sin acoplar vistas o controladores a superglobales.
        return $_GET[$key] ?? $default;
    }
}

if (!function_exists('is_https_request')) {
    function is_https_request(): bool
    {
        return (
            (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
            || (($_SERVER['SERVER_PORT'] ?? null) === '443')
        );
    }
}

if (!function_exists('start_secure_session')) {
    function start_secure_session(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            return;
        }

        ini_set('session.gc_maxlifetime', (string) max(
            SESSION_TIMEOUT_MINUTES * 60,
            REMEMBER_SESSION_DAYS * 24 * 60 * 60
        ));

        // Define parametros de cookie antes de iniciar la sesion PHP.
        session_name(SESSION_NAME);
        session_set_cookie_params(session_cookie_options(0));

        session_start();
    }
}

if (!function_exists('session_cookie_options')) {
    function session_cookie_options(int $lifetime): array
    {
        // URL-codifica el path preservando las barras (/) para evitar problemas con espacios
        // y caracteres especiales. RFC 6265 requiere que los atributos de la cookie sean válidos.
        if (BASE_PATH === '') {
            $cookiePath = '/';
        } else {
            // Codifica cada segmento entre barras, pero preserva las barras mismas
            $segments = array_map('rawurlencode', explode('/', BASE_PATH));
            $cookiePath = implode('/', $segments);
        }
        
        return [
            'lifetime' => $lifetime,
            'path' => $cookiePath,
            'domain' => '',
            'secure' => is_https_request(),
            'httponly' => true,
            'samesite' => 'Lax',
        ];
    }
}

if (!function_exists('session_extend_cookie')) {
    function session_extend_cookie(int $expiresAt): void
    {
        start_secure_session();

        $options = session_cookie_options(max(0, $expiresAt - time()));
        unset($options['lifetime']);
        $options['expires'] = $expiresAt;

        setcookie(session_name(), session_id(), $options);
    }
}

if (!function_exists('session_clear_cookie')) {
    function session_clear_cookie(): void
    {
        $options = session_cookie_options(0);
        unset($options['lifetime']);
        $options['expires'] = time() - 42000;

        setcookie(session_name(), '', $options);
    }
}

if (!function_exists('session_get')) {
    function session_get(string $key, mixed $default = null): mixed
    {
        start_secure_session();

        return $_SESSION[$key] ?? $default;
    }
}

if (!function_exists('session_put')) {
    function session_put(string $key, mixed $value): void
    {
        start_secure_session();
        $_SESSION[$key] = $value;
    }
}

if (!function_exists('session_forget')) {
    function session_forget(string $key): void
    {
        start_secure_session();
        unset($_SESSION[$key]);
    }
}

if (!function_exists('csrf_token')) {
    /**
     * Genera o retorna el token CSRF de la sesión actual.
     *
     * @param string $clave Clave de sesión (por defecto 'csrf_token_global').
     * @return string Token CSRF seguro.
     */
    function csrf_token(string $clave = 'csrf_token_global'): string
    {
        start_secure_session();
        if (empty($_SESSION[$clave]) || !is_string($_SESSION[$clave])) {
            $_SESSION[$clave] = bin2hex(random_bytes(32));
        }

        return (string) $_SESSION[$clave];
    }
}

if (!function_exists('csrf_validar')) {
    /**
     * Valida un token CSRF contra el almacenado en sesión usando comparación de tiempo constante.
     *
     * @param string|null $token Token recibido del formulario.
     * @param string $clave Clave de sesión esperada.
     * @return bool True si el token es válido.
     */
    function csrf_validar(?string $token, string $clave = 'csrf_token_global'): bool
    {
        if ($token === null || $token === '') {
            return false;
        }

        start_secure_session();
        $tokenSesion = $_SESSION[$clave] ?? '';

        return is_string($tokenSesion) && $tokenSesion !== '' && hash_equals($tokenSesion, $token);
    }
}

if (!function_exists('sql_construir_busqueda_practicante')) {
    /**
     * Construye una condición SQL y parámetros PDO para búsqueda inteligente de practicantes.
     * 
     * Resuelve:
     * - Búsqueda por nombre completo concatenado (ej: "vicente duran" coincidirá con "Vicente Claudio Durán Correa").
     * - Descomposición de la búsqueda en múltiples palabras clave separadas por espacios.
     * - Búsqueda insensible a mayúsculas, minúsculas y tildes.
     * - Búsqueda combinada en nombre completo, email y RUT.
     *
     * @param string $busqueda Texto ingresado en el buscador.
     * @param string $prefijoTabla Alias de la tabla 'practicantes' (por defecto 'p').
     * @param string $prefijoParam Prefijo de los parámetros PDO para evitar colisiones.
     * @return array{condicion: string, where_sql: string, params: array<string, string>}
     */
    function sql_construir_busqueda_practicante(string $busqueda, string $prefijoTabla = 'p', string $prefijoParam = 'busq'): array
    {
        $busqueda = trim($busqueda);
        if ($busqueda === '') {
            return ['condicion' => '', 'where_sql' => '', 'params' => []];
        }

        // Dividir la búsqueda por espacios para obtener términos individuales
        $palabras = preg_split('/\s+/', $busqueda);
        $palabras = array_values(array_filter((array) $palabras, static fn (string $palabra): bool => trim($palabra) !== ''));

        if ($palabras === []) {
            return ['condicion' => '', 'where_sql' => '', 'params' => []];
        }

        $condiciones = [];
        $params = [];
        $campoNombreCompleto = "CONCAT_WS(' ', {$prefijoTabla}.nombre_1, {$prefijoTabla}.nombre_2, {$prefijoTabla}.apellido_paterno, {$prefijoTabla}.apellido_materno)";

        foreach ($palabras as $indice => $palabra) {
            // Nombres de parámetros únicos por columna para compatibilidad estricta con PDO (PDO::ATTR_EMULATE_PREPARES = false)
            $pNom = "{$prefijoParam}_nom_{$indice}";
            $pEmail = "{$prefijoParam}_em_{$indice}";
            $pRut = "{$prefijoParam}_rut_{$indice}";
            $valorLike = '%' . $palabra . '%';

            $condiciones[] = "({$campoNombreCompleto} LIKE :{$pNom} OR {$prefijoTabla}.email LIKE :{$pEmail} OR {$prefijoTabla}.rut_numero LIKE :{$pRut})";
            $params[$pNom] = $valorLike;
            $params[$pEmail] = $valorLike;
            $params[$pRut] = $valorLike;
        }

        // Se unen las condiciones de cada término con AND para que se cumplan todos
        $condicion = '(' . implode(' AND ', $condiciones) . ')';
        $whereSql = ' AND ' . $condicion;

        return [
            'condicion' => $condicion,
            'where_sql' => $whereSql,
            'params' => $params,
        ];
    }
}

if (!function_exists('formatear_horas_enteras')) {
    /**
     * Formatea horas para visualización redondeando al número entero cerrado más cercano.
     * Mantiene la precisión decimal internamente en BD y cálculos, pero muestra horas redondas (ej: 7.2 -> "7", 7.8 -> "8").
     *
     * @param mixed $horas Cantidad de horas (float, int o string numérico).
     * @return string Representación entera cerrada.
     */
    function formatear_horas_enteras(mixed $horas): string
    {
        // Redondeo visual de horas a números enteros cerrados (ej: 7.2 -> 7, 7.8 -> 8)
        if ($horas === null || $horas === '') {
            return '0';
        }

        return (string) (int) round((float) $horas);
    }
}

if (!function_exists('formatear_horas_extra_minutos')) {
    /**
     * Convierte y formatea horas decimales de tiempo extra al minuto más próximo.
     * Ejemplo: 0.15 hrs -> "9 minutos extra", 0.50 hrs -> "30 minutos extra", 1.25 hrs -> "1 h 15 min extra".
     *
     * @param mixed $horas Cantidad de horas extra decimales.
     * @param bool $incluirSufijo Indica si agrega el sufijo "extra".
     * @return string Tiempo formateado en minutos o formato h:min.
     */
    function formatear_horas_extra_minutos(mixed $horas, bool $incluirSufijo = true): string
    {
        // Conversión de horas extra decimales al minuto más próximo
        if ($horas === null || $horas === '') {
            return $incluirSufijo ? '0 minutos extra' : '0 min';
        }

        $minutosTotales = (int) round((float) $horas * 60);

        if ($minutosTotales <= 0) {
            return $incluirSufijo ? '0 minutos extra' : '0 min';
        }

        if ($minutosTotales < 60) {
            // Menor a una hora: mostrar directamente los minutos (ej: "9 minutos extra", "15 minutos extra")
            $texto = $minutosTotales === 1 ? '1 minuto' : "{$minutosTotales} minutos";
            return $incluirSufijo ? "{$texto} extra" : $texto;
        }

        // Una hora o más: desglosar en horas y minutos (ej: "1 hora extra", "1 h 15 min extra")
        $horasEnteras = intdiv($minutosTotales, 60);
        $minutosRestantes = $minutosTotales % 60;

        if ($minutosRestantes === 0) {
            $texto = $horasEnteras === 1 ? '1 hora' : "{$horasEnteras} horas";
            return $incluirSufijo ? "{$texto} extra" : $texto;
        }

        $texto = $horasEnteras === 1
            ? "1 h {$minutosRestantes} min"
            : "{$horasEnteras} h {$minutosRestantes} min";

        return $incluirSufijo ? "{$texto} extra" : $texto;
    }
}

if (!function_exists('formatear_horas_avance')) {
    /**
     * Formatea el indicador de avance en horas redondeadas a números enteros (ej: "7 / 360 hrs").
     *
     * @param mixed $horasRealizadas Horas completadas.
     * @param mixed $horasRequeridas Horas meta o requeridas.
     * @return string Texto con formato cerrado "X / Y hrs".
     */
    function formatear_horas_avance(mixed $horasRealizadas, mixed $horasRequeridas): string
    {
        // Formato visual cerrado de avance de horas para practicantes y supervisores (ej: 7 / 360)
        return formatear_horas_enteras($horasRealizadas) . ' / ' . formatear_horas_enteras($horasRequeridas);
    }
}


if (!function_exists('icono')) {
    /**
     * Inserta en línea un ícono Lucide copiado a public/assets/icons/lucide (ver scripts/copy-icons.cjs).
     * Es decorativo (aria-hidden); el tamaño y el grosor del trazo los define el CSS (.rrhh-icono).
     */
    function icono(string $nombre, string $clases = ''): string
    {
        static $cache = [];
        // basename() evita salir de la carpeta de íconos con nombres como "../x".
        $nombre = basename($nombre);
        if (!array_key_exists($nombre, $cache)) {
            $ruta = ASSETS_PATH . '/icons/lucide/' . $nombre . '.svg';
            $svg = is_file($ruta) ? (string) file_get_contents($ruta) : '';
            // Conservé solo el contenido interno (trazos) para reconstruir la etiqueta <svg> con nuestros atributos.
            $cache[$nombre] = preg_match('/<svg\b[^>]*>(.*)<\/svg>/s', $svg, $m) === 1 ? trim($m[1]) : '';
        }
        if ($cache[$nombre] === '') {
            return '';
        }

        return '<svg class="rrhh-icono ' . e($clases) . '" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"'
            . ' fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"'
            . ' aria-hidden="true" focusable="false">' . $cache[$nombre] . '</svg>';
    }
}
