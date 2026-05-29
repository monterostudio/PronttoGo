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
    if ($type === 'bcv' || $type === 'trm') {
        $ch = curl_init('https://open.er-api.com/v6/latest/USD');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 5);
        $res = curl_exec($ch);
        curl_close($ch);
        if ($res) {
            $data = json_decode($res, true);
            $key = $type === 'bcv' ? 'VES' : 'COP';
            $rate = floatval($data['rates'][$key] ?? null);
            return $rate > 0 ? $rate : null;
        }
    }
    return null;
}

function get_logo_svg(string $class = 'h-8 w-auto'): string {
    $path = dirname(__DIR__) . '/assets/img/logo.svg';
    if (!file_exists($path)) {
        $path = dirname(__DIR__) . '/assets/logo.svg'; // Fallback for old path
        if (!file_exists($path)) {
            return '<span style="font-weight:900;font-size:1.25rem;background:linear-gradient(to right,#10B981,#06B6D4);-webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text;">PronttoGo</span>';
        }
    }
    $svg = file_get_contents($path);
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
