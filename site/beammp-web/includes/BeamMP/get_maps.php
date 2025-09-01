<?php
// Désactiver les avis et les messages de debug
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);
ini_set('display_errors', 0);
require_once 'shared_functions.php';

header('Content-Type: application/json');

try {
    $mapType = isset($_GET['type']) ? $_GET['type'] : 'map';
    $maps = getMaps($mapType);

    echo json_encode($maps, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}

?>
