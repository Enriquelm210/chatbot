<?php

declare(strict_types=1);
require_once __DIR__ . '/../ayudas/autenticacion.php';
require_once __DIR__ . '/../configuracion/conexion.php';
exigirAdministrador();

$ruta = (string) ($_GET['ruta'] ?? '');
$ruta = str_replace(['..\\', '../', "\0"], '', $ruta);
$absoluta = dirname(__DIR__) . '/' . ltrim($ruta, '/');

if ($ruta === '' || !file_exists($absoluta) || !is_file($absoluta)) {
    http_response_code(404);
    echo 'Archivo no encontrado';
    exit;
}

$extension = strtolower(pathinfo($absoluta, PATHINFO_EXTENSION));
$mime = match ($extension) {
    'png' => 'image/png',
    'webp' => 'image/webp',
    default => 'image/jpeg',
};

header('Content-Type: ' . $mime);
header('Content-Length: ' . filesize($absoluta));
readfile($absoluta);
exit;
