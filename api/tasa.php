<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

require_once __DIR__ . '/../core/config.php';
require_once __DIR__ . '/../core/db.php';

$tipo = $_GET['tipo'] ?? 'manual';
$rate = 0;

function fetch_bcv_rate() {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, 'https://ve.dolarapi.com/v1/dolares/oficial');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 5);
    $res = curl_exec($ch);
    curl_close($ch);
    if ($res) {
        $data = json_decode($res, true);
        if (isset($data['promedio'])) return floatval($data['promedio']);
    }
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, 'https://s3.amazonaws.com/dolartoday/data.json');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 5);
    $res = curl_exec($ch);
    curl_close($ch);
    if ($res) {
        $data = json_decode($res, true);
        if (isset($data['USD']['bcv'])) return floatval($data['USD']['bcv']);
    }
    
    return 0;
}



if ($tipo === 'bcv') {
    $rate = fetch_bcv_rate();
}

if ($rate > 0) {
    echo json_encode(['success' => true, 'rate' => $rate]);
} else {
    echo json_encode(['success' => false, 'error' => 'No se pudo obtener la tasa desde el servidor.']);
}
