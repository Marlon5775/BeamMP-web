<?php

require_once __DIR__ . '/../../includes/roles.php';

try {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        header('Content-Type: application/json');
        require_once 'upload_handler.php';
        exit;
    } elseif ($_SERVER['REQUEST_METHOD'] === 'GET') {
        header('Content-Type: text/event-stream');
        header('Cache-Control: no-cache');
        header('Connection: keep-alive');
        ob_implicit_flush(true);
        require_once 'rsync_handler.php';
        exit;
    } else {
        http_response_code(405);
        echo json_encode(['success' => false, 'message' => "Méthode non autorisée."]);
        exit;
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    exit;
}
