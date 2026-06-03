<?php
require_once __DIR__ . '/config.php';

function check_supabase_config() {
    if (empty(SUPABASE_URL) || empty(SUPABASE_KEY)) {
        die('<div style="font-family:sans-serif;padding:20px;background:#FEF2F2;color:#991B1B;border:1px solid #FCA5A5;border-radius:6px;max-width:500px;margin:50px auto;">
            <h3 style="margin-top:0;">Error de Conexión</h3>
            <p>Las variables de entorno <strong>SUPABASE_URL</strong> y <strong>SUPABASE_KEY</strong> no están configuradas.</p>
        </div>');
    }
}

function supabase_request(string $method, string $path, array $data = null) {
    check_supabase_config();
    $url = SUPABASE_URL . '/rest/v1/' . ltrim($path, '/');
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
    $headers = [
        'apikey: ' . SUPABASE_KEY,
        'Authorization: Bearer ' . SUPABASE_KEY
    ];
    if ($data !== null) {
        $jsonData = json_encode($data);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonData);
        $headers[] = 'Content-Type: application/json';
        if ($method === 'POST' || $method === 'PATCH') {
            $headers[] = 'Prefer: return=representation';
        }
    }
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    $sslVerify = true;
    if (defined('SUPABASE_SSL_VERIFY') && SUPABASE_SSL_VERIFY === false) {
        $sslVerify = false;
    }
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, $sslVerify);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, $sslVerify ? 2 : 0);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    if (curl_errno($ch)) {
        $errorMsg = curl_error($ch);
        curl_close($ch);
        error_log("PronttoGo cURL Error: " . $errorMsg);
        return ['success' => false, 'status' => 500, 'error' => 'Error de conexión.'];
    }
    curl_close($ch);
    return [
        'success' => ($httpCode >= 200 && $httpCode < 300),
        'status' => $httpCode,
        'data' => json_decode($response, true),
        'raw' => $response
    ];
}

function fetch_automatic_rate(string $type): ?float {
    if ($type === 'bcv') {
        $ch = curl_init('https://open.er-api.com/v6/latest/USD');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 5);
        $res = curl_exec($ch);
        curl_close($ch);
        if ($res) {
            $data = json_decode($res, true);
            $rate = floatval($data['rates']['VES'] ?? null);
            return $rate > 0 ? $rate : null;
        }
    }
    return null;
}

function get_logo_svg(string $class = 'h-8 w-auto', bool $darkMode = false): string {
    $path = dirname(__DIR__) . '/assets/img/logo.svg';
    if (!file_exists($path)) {
        $path = dirname(__DIR__) . '/assets/logo.svg'; // Fallback for old path
        if (!file_exists($path)) {
            return '<span style="font-weight:900;font-size:1.25rem;background:linear-gradient(to right,#4F46E5,#8B5CF6);-webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text;">PronttoGo</span>';
        }
    }
    $svg = file_get_contents($path);
    
    // Cambiar el viejo color turquesa (#00CFBD) por el definitivo de la app (Índigo #4F46E5)
    $svg = str_replace('#00CFBD', '#4F46E5', $svg);
    
    // Si está en modo oscuro (fondo negro), el texto "Prontto" (#2A3543) pasa a ser blanco (#FFFFFF)
    if ($darkMode) {
        $svg = str_replace('#2A3543', '#FFFFFF', $svg);
    }
    
    return str_replace('width="100%" height="100%"', 'class="' . htmlspecialchars($class, ENT_QUOTES | ENT_HTML5, 'UTF-8') . '"', $svg);
}

function split_whatsapp_number(string $number): array {
    $prefixes = ['57' => 'Colombia (+57)', '58' => 'Venezuela (+58)'];
    foreach ($prefixes as $prefix => $name) {
        if (strpos($number, $prefix) === 0) {
            return ['code' => $prefix, 'local' => substr($number, strlen($prefix))];
        }
    }
    return ['code' => '', 'local' => $number];
}

/**
 * Renderiza el logo de la aplicación adaptándose a cualquier forma (rectangular, cuadrado, circular).
 * Si hay un logo personalizado configurado, lo muestra en un contenedor adaptativo.
 * Si no lo hay, muestra el logo SVG por defecto de PronttoGo o el nombre en texto.
 */
function render_logo(string $context, array $config, bool $darkMode = false): string {
    $logo_url = !empty($config['logo_url']) ? $config['logo_url'] : '';
    $nombre = !empty($config['nombre']) && $config['nombre'] !== 'Mi Tienda' ? $config['nombre'] : 'PronttoGo';
    $es_default_brand = (strtolower($nombre) === 'pronttogo' || $nombre === 'Mi Tienda');
    
    $safe_logo_url = htmlspecialchars($logo_url, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    $safe_nombre = htmlspecialchars($nombre, ENT_QUOTES | ENT_HTML5, 'UTF-8');

    if (!empty($logo_url)) {
        // Logo personalizado subido por el usuario
        switch ($context) {
            case 'header':
                return '<img src="' . $safe_logo_url . '" alt="' . $safe_nombre . '" class="h-9 w-auto max-w-[140px] object-contain rounded-lg shrink-0 logo-adaptable">';
            case 'hero':
                return '<div class="logo-hero-container flex items-center justify-center bg-white p-3 rounded-2xl shadow-md border border-slate-100/80 hover:scale-[1.02] transition-transform duration-300 w-fit max-w-[240px] mx-auto">' .
                       '<img src="' . $safe_logo_url . '" alt="' . $safe_nombre . '" class="h-20 w-auto max-w-[200px] object-contain rounded-xl logo-adaptable">' .
                       '</div>';
            case 'login':
                return '<div class="logo-login-container flex items-center justify-center bg-white p-4 rounded-3xl shadow-sm border border-slate-100 w-fit max-w-[240px] mx-auto mb-6">' .
                       '<img src="' . $safe_logo_url . '" alt="' . $safe_nombre . '" class="h-20 w-auto max-w-[200px] object-contain rounded-2xl logo-adaptable">' .
                       '</div>';
            case 'admin':
                // Si es admin sidebar (modo oscuro) o cabecera móvil de admin
                $bg_class = $darkMode ? 'bg-white/10 border-white/10' : 'bg-slate-100 border-slate-200';
                $text_class = $darkMode ? 'text-white' : 'text-slate-800';
                return '<div class="flex items-center gap-2.5 px-3 py-1.5 rounded-xl border ' . $bg_class . ' max-w-[200px] shrink-0">' .
                       '<img src="' . $safe_logo_url . '" alt="' . $safe_nombre . '" class="h-7 w-auto max-w-[80px] object-contain rounded-md shrink-0 logo-adaptable">' .
                       '<span class="font-extrabold text-xs truncate ' . $text_class . '">' . $safe_nombre . '</span>' .
                       '</div>';
            case 'footer':
                return '<img src="' . $safe_logo_url . '" alt="' . $safe_nombre . '" class="h-8 w-auto max-w-[120px] object-contain rounded-md shrink-0 logo-adaptable">';
            default:
                return '<img src="' . $safe_logo_url . '" alt="' . $safe_nombre . '" class="h-8 w-auto object-contain logo-adaptable">';
        }
    } else {
        // Mostrar logo SVG por defecto de PronttoGo o texto
        switch ($context) {
            case 'header':
                if ($es_default_brand) {
                    return '<div class="max-w-[120px] sm:max-w-[140px] shrink-0">' . get_logo_svg('w-full h-auto block', $darkMode) . '</div>';
                } else {
                    return '<span class="font-extrabold text-lg tracking-tight text-slate-800 truncate max-w-[140px] sm:max-w-none block">' . $safe_nombre . '</span>';
                }
            case 'hero':
                if ($es_default_brand) {
                    return '<div class="rounded-2xl shadow-md bg-white px-6 py-4 inline-flex items-center justify-center border border-slate-100 hover:scale-[1.02] transition-transform duration-300 w-fit max-w-[240px] mx-auto">' .
                           get_logo_svg('h-12 w-auto max-w-full', $darkMode) .
                           '</div>';
                } else {
                    return '<span class="text-4xl md:text-5xl font-extrabold tracking-tight text-slate-800">' . $safe_nombre . '</span>';
                }
            case 'login':
                return '<div class="max-w-[200px] sm:max-w-[240px] mx-auto mb-4 drop-shadow-sm flex justify-center">' .
                       get_logo_svg('w-full h-auto block', $darkMode) .
                       '</div>';
            case 'admin':
                return get_logo_svg('h-8 w-auto', $darkMode);
            case 'footer':
                if ($es_default_brand) {
                    return '<div class="max-w-[100px] shrink-0 opacity-80 hover:opacity-100 transition-opacity">' . get_logo_svg('w-full h-auto block', $darkMode) . '</div>';
                } else {
                    return '<span class="font-extrabold text-sm tracking-tight text-slate-600">' . $safe_nombre . '</span>';
                }
            default:
                return get_logo_svg('h-8 w-auto', $darkMode);
        }
    }
}
