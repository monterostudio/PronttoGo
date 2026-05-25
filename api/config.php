<?php
/**
 * PronttoGo - Archivo de Configuración Simplificado (Single-Store)
 * Gestiona la sesión, credenciales de Supabase y funciones de utilidad.
 */

// 1. Configuración de Sesión Segura
if (session_status() === PHP_SESSION_NONE) {
    $isSecure = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on';
    
    session_start([
        'cookie_lifetime' => 0,             // Expira al cerrar el navegador
        'cookie_path'     => '/',
        'cookie_domain'   => '',
        'cookie_secure'   => $isSecure,     // HTTPS si está disponible
        'cookie_httponly' => true,          // Evita robo de sesión por JS
        'cookie_samesite' => 'Lax',         // Mitigación CSRF básica
        'use_only_cookies'=> true,
    ]);
}

// 2. Cargar variables de entorno (Supabase y Admin Credentials)
// TODO(security): Se definen las credenciales por defecto directamente en el código por solicitud del usuario para simplificar la publicación en Vercel y evitar fricción de configuración.
$supabaseUrl = getenv('SUPABASE_URL') ?: 'https://pusrebyszbtyefcjmvsh.supabase.co';
$supabaseKey = getenv('SUPABASE_KEY') ?: 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJpc3MiOiJzdXBhYmFzZSIsInJlZiI6InB1c3JlYnlzemJ0eWVmY2ptdnNoIiwicm9sZSI6InNlcnZpY2Vfcm9sZSIsImlhdCI6MTc3OTIxNjgzOCwiZXhwIjoyMDk0NzkyODM4fQ.UUbI-8OuKFxE6lMja0s8SYNMkSpJD2V2lwv2rjEa0kk';
$adminUser = getenv('ADMIN_USER') ?: 'admin'; // Usuario por defecto
$adminPassword = getenv('ADMIN_PASSWORD') ?: 'admin123'; // Contraseña por defecto

// Soporte opcional para desarrollo local
$localConfigPath = dirname(__DIR__) . '/config.local.php';
if (file_exists($localConfigPath)) {
    $localConfig = include $localConfigPath;
    if (is_array($localConfig)) {
        $supabaseUrl = $localConfig['SUPABASE_URL'] ?? $supabaseUrl;
        $supabaseKey = $localConfig['SUPABASE_KEY'] ?? $supabaseKey;
        $adminUser = $localConfig['ADMIN_USER'] ?? $adminUser;
        $adminPassword = $localConfig['ADMIN_PASSWORD'] ?? $adminPassword;
    }
}

define('SUPABASE_URL', rtrim($supabaseUrl ?: '', '/'));
define('SUPABASE_KEY', $supabaseKey ?: '');
define('ADMIN_USER', $adminUser);
define('ADMIN_PASSWORD', $adminPassword);

// Autodetectar entorno local para desactivar validación SSL de cURL en Windows/Laragon
$isLocalhost = in_array($_SERVER['REMOTE_ADDR'] ?? '', ['127.0.0.1', '::1'])
    || (isset($_SERVER['HTTP_HOST']) && preg_match('/(localhost|127\.0\.0\.1|\.local|\.test)$/i', $_SERVER['HTTP_HOST']));

if (!defined('SUPABASE_SSL_VERIFY')) {
    define('SUPABASE_SSL_VERIFY', !$isLocalhost);
}

// Validar credenciales de Supabase al usarlas
function check_supabase_config() {
    if (empty(SUPABASE_URL) || empty(SUPABASE_KEY)) {
        die('<div style="font-family:sans-serif;padding:20px;background:#FEF2F2;color:#991B1B;border:1px solid #FCA5A5;border-radius:6px;max-width:500px;margin:50px auto;">
            <h3 style="margin-top:0;">Error de Conexión</h3>
            <p>Las variables de entorno <strong>SUPABASE_URL</strong> y <strong>SUPABASE_KEY</strong> no están configuradas.</p>
            <p>Por favor, configúralas en el panel de Vercel o crea un archivo local <code>config.local.php</code> en la raíz del proyecto.</p>
        </div>');
    }
}

// 3. Cliente cURL para Supabase REST API
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
    
    // Permitir desactivar verificación SSL en local si se define la constante
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
        return [
            'success' => false,
            'status' => 500,
            'error' => 'Error de conexión con la base de datos.'
        ];
    }
    
    curl_close($ch);
    
    $decodedData = json_decode($response, true);
    
    return [
        'success' => ($httpCode >= 200 && $httpCode < 300),
        'status' => $httpCode,
        'data' => $decodedData,
        'raw' => $response
    ];
}

function split_whatsapp_number(string $number): array {
    $prefixes = [
        '57'  => 'Colombia (+57)',
        '58'  => 'Venezuela (+58)'
    ];
    
    foreach ($prefixes as $prefix => $name) {
        if (strpos($number, $prefix) === 0) {
            return [
                'code' => $prefix,
                'local' => substr($number, strlen($prefix))
            ];
        }
    }
    
    return [
        'code' => '',
        'local' => $number
    ];
}

// 4. Prevención XSS: Helper de escape para HTML
function h(?string $value): string {
    if ($value === null) {
        return '';
    }
    return htmlspecialchars($value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
}

// 5. Prevención CSRF: Generación y Validación de Tokens
function generate_csrf_token(): string {
    if (empty($_SESSION['_csrf_token'])) {
        $_SESSION['_csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['_csrf_token'];
}

function verify_csrf_token(?string $token): bool {
    if (empty($_SESSION['_csrf_token']) || empty($token)) {
        return false;
    }
    return hash_equals($_SESSION['_csrf_token'], $token);
}

function csrf_input(): string {
    $token = generate_csrf_token();
    return '<input type="hidden" name="csrf_token" value="' . h($token) . '">';
}

// 6. Redirecciones
function redirect(string $url): void {
    header("Location: " . $url);
    exit;
}

// 7. Verificar estado de Administrador
function is_admin_logged_in(): bool {
    return !empty($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true;
}

// 8. Obtener tasa de cambio del Banco Central de Venezuela (BCV) o TRM Colombia
function fetch_automatic_rate(string $type): ?float {
    if ($type === 'bcv') {
        $url = 'https://open.er-api.com/v6/latest/USD';
        $ch = curl_init($url);
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
    } elseif ($type === 'trm') {
        $url = 'https://open.er-api.com/v6/latest/USD';
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 5);
        $res = curl_exec($ch);
        curl_close($ch);
        if ($res) {
            $data = json_decode($res, true);
            $rate = floatval($data['rates']['COP'] ?? null);
            return $rate > 0 ? $rate : null;
        }
    }
    return null;
}
