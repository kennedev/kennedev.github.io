<?php
// Helpers para a API JSON (usados por /api/verificar.php).

function json_input() {
    $data = json_decode(file_get_contents('php://input'), true);
    return is_array($data) ? $data : array();
}

function json_out($status, array $body) {
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($body, JSON_UNESCAPED_UNICODE);
    exit;
}

/** Libera CORS para a webview do app (origem tauri://) e responde o preflight. */
function cors() {
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: POST, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type');
    if (($_SERVER['REQUEST_METHOD'] ?? '') === 'OPTIONS') {
        http_response_code(204);
        exit;
    }
}

function require_post() {
    if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
        json_out(405, array('ok' => false, 'motivo' => 'metodo_nao_permitido'));
    }
}
