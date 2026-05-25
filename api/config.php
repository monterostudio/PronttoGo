<?php
/**
 * PronttoGo - Archivo de Configuración y Utilidades
 * Desarrollado con PHP nativo y seguridad robusta.
 */

// 1. Configuración de Sesión Segura
if (session_status() === PHP_SESSION_NONE) {
    // Definir parámetros seguros para las cookies de sesión
    $isSecure = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on';
    
    session_start([
        'cookie_lifetime' => 0,             // Expira al cerrar el navegador
        'cookie_path'     => '/',
        'cookie_domain'   => '',            // Usar el dominio actual
        'cookie_secure'   => $isSecure,     // HTTPS obligatorio si está disponible
        'cookie_httponly' => true,          // Impedir acceso de JavaScript (Mitigación XSS)
        'cookie_samesite' => 'Lax',         // Mitigación CSRF básica
        'use_only_cookies'=> true,          // Evitar secuestro de sesión vía URL
    ]);
}

// 2. Cargar variables de entorno (Supabase)
// Intentar leer de variables de entorno de Vercel / Servidor
$supabaseUrl = getenv('SUPABASE_URL');
$supabaseKey = getenv('SUPABASE_KEY');

// Soporte para desarrollo local mediante config.local.php
$localConfigPath = __DIR__ . '/config.local.php';
if (file_exists($localConfigPath)) {
    $localConfig = include $localConfigPath;
    if (is_array($localConfig)) {
        $supabaseUrl = $localConfig['SUPABASE_URL'] ?? $supabaseUrl;
        $supabaseKey = $localConfig['SUPABASE_KEY'] ?? $supabaseKey;
    }
}

// Definir constantes si están disponibles
define('SUPABASE_URL', rtrim($supabaseUrl ?: '', '/'));
define('SUPABASE_KEY', $supabaseKey ?: '');

// Validar que las credenciales no estén vacías al usarlas
function check_supabase_config() {
    if (empty(SUPABASE_URL) || empty(SUPABASE_KEY)) {
        die('<div style="font-family:sans-serif;padding:20px;background:#FEF2F2;color:#991B1B;border:1px solid #FCA5A5;border-radius:6px;max-width:500px;margin:50px auto;">
            <h3 style="margin-top:0;">Error de Configuración</h3>
            <p>Las variables de entorno <strong>SUPABASE_URL</strong> y <strong>SUPABASE_KEY</strong> no están configuradas.</p>
            <p>Configúralas en Vercel o crea un archivo local <code>config.local.php</code> con el siguiente formato:</p>
            <pre style="background:#FFF;padding:10px;border:1px solid #E5E7EB;overflow-x:auto;">&lt;?php
return [
  "SUPABASE_URL" =&gt; "https://tu-proyecto.supabase.co",
  "SUPABASE_KEY" =&gt; "tu-anon-public-key"
];</pre>
        </div>');
    }
}

// 3. Cliente cURL Genérico para Supabase REST API
function supabase_request(string $method, string $path, array $data = null) {
    check_supabase_config();
    
    $url = SUPABASE_URL . '/rest/v1/' . ltrim($path, '/');
    $ch = curl_init();
    
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
    
    // Cabeceras de autenticación de Supabase
    $headers = [
        'apikey: ' . SUPABASE_KEY,
        'Authorization: Bearer ' . SUPABASE_KEY
    ];
    
    if ($data !== null) {
        $jsonData = json_encode($data);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonData);
        $headers[] = 'Content-Type: application/json';
        
        // Solicitar al PostgREST que devuelva el objeto insertado/actualizado
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
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    
    if (curl_errno($ch)) {
        $errorMsg = curl_error($ch);
        curl_close($ch);
        error_log("PronttoGo cURL Error: " . $errorMsg);
        return [
            'success' => false,
            'status' => 500,
            'error' => 'Error de conexión con la base de datos remota.'
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
        // Generar un token criptográficamente seguro
        $_SESSION['_csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['_csrf_token'];
}

function verify_csrf_token(?string $token): bool {
    if (empty($_SESSION['_csrf_token']) || empty($token)) {
        return false;
    }
    // Comparación en tiempo constante para evitar ataques de temporización
    return hash_equals($_SESSION['_csrf_token'], $token);
}

function csrf_input(): string {
    $token = generate_csrf_token();
    return '<input type="hidden" name="csrf_token" value="' . h($token) . '">';
}

// 6. Redirecciones Seguras
function redirect(string $url): void {
    header("Location: " . $url);
    exit;
}

// 7. Sanitizador de Slugs básico
function sanitize_slug(string $slug): string {
    // Convertir a minúsculas, remover caracteres especiales y espacios
    $slug = strtolower($slug);
    $slug = preg_replace('/[^a-z0-9\-]/', '', $slug);
    return trim($slug, '-');
}
