<?php

declare(strict_types=1);
require_once __DIR__ . '/../modulos/manejador_chatbot.php';
require_once __DIR__ . '/../ayudas/utilidades.php';

$metodo = $_SERVER['REQUEST_METHOD'] ?? 'GET';

if ($metodo === 'GET') {
    $modo = $_GET['hub_mode'] ?? $_GET['hub.mode'] ?? '';
    $token = $_GET['hub_verify_token'] ?? $_GET['hub.verify_token'] ?? '';
    $challenge = $_GET['hub_challenge'] ?? $_GET['hub.challenge'] ?? '';

    registrarBitacora('Verificacion webhook GET', [
        'query_string' => $_SERVER['QUERY_STRING'] ?? '',
        'modo' => $modo,
        'token_recibido' => $token,
        'token_esperado' => (string) entorno('WHATSAPP_TOKEN_VERIFICACION', ''),
        'challenge' => $challenge,
    ]);

    if ($modo === 'subscribe' && trim((string) entorno('WHATSAPP_TOKEN_VERIFICACION', '')) === trim((string) $token)) {
        responderTexto((string) $challenge);
        exit;
    }

    responderTexto('Webhook activo');
    exit;
}

if ($metodo === 'POST') {
    $entradaCruda = file_get_contents('php://input') ?: '{}';
    $datos = json_decode($entradaCruda, true);

    if (!is_array($datos)) {
        responderJson(['ok' => false, 'mensaje' => 'JSON inválido'], 400);
        exit;
    }

    registrarBitacora('Webhook recibido', ['payload' => $datos]);

    $manejador = new ManejadorChatbot();
    $manejador->procesarWebhook($datos);

    responderJson(['ok' => true]);
    exit;
}

responderJson(['ok' => false, 'mensaje' => 'Método no permitido'], 405);
exit;