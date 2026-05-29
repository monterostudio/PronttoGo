<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

require_once __DIR__ . '/../core/config.php';
require_once __DIR__ . '/../core/db.php';

$tipo = $_GET['tipo'] ?? 'manual';
$rate = 0;

function fetch_bcv_rate() {
    $ctx = stream_context_create(['http' => ['timeout' => 3]]);
    $res = @file_get_contents('https://s3.amazonaws.com/dolartoday/data.json', false, $ctx);
    if ($res) {
        $data = json_decode($res, true);
        if (isset($data['USD']['bcv'])) {
            return floatval($data['USD']['bcv']);
        }
    }
    $res = @file_get_contents('https://open.er-api.com/v6/latest/USD', false, $ctx);
    if ($res) {
        $data = json_decode($res, true);
        if (isset($data['rates']['VES'])) {
            return floatval($data['rates']['VES']);
        }
    }
    return 0;
}

function fetch_trm_rate() {
    $ctx = stream_context_create(['http' => ['timeout' => 3]]);
    $res = @file_get_contents('https://open.er-api.com/v6/latest/USD', false, $ctx);
    if ($res) {
        $data = json_decode($res, true);
        if (isset($data['rates']['COP'])) {
            return floatval($data['rates']['COP']);
        }
    }
    return 0;
}

if ($tipo === 'bcv') {
    $rate = fetch_bcv_rate();
} elseif ($tipo === 'trm') {
    $rate = fetch_trm_rate();
}

if ($rate > 0) {
    echo json_encode(['success' => true, 'rate' => $rate]);
} else {
    echo json_encode(['success' => false, 'error' => 'No se pudo obtener la tasa desde el servidor.']);
}
