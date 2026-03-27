<?php

declare(strict_types=1);

function responderJson(array $datos, int $codigo = 200): void
{
    http_response_code($codigo);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($datos, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function responderTexto(string $texto, int $codigo = 200): void
{
    http_response_code($codigo);
    header('Content-Type: text/plain; charset=utf-8');
    echo $texto;
    exit;
}

function escapar(?string $valor): string
{
    return htmlspecialchars((string) $valor, ENT_QUOTES, 'UTF-8');
}

function rutaPublica(string $relativa): string
{
    $base = rtrim((string) entorno('APP_URL', ''), '/');
    return $base . '/' . ltrim($relativa, '/');
}

function registrarBitacora(string $mensaje, array $contexto = []): void
{
    $carpeta = dirname(__DIR__) . '/almacen/temp';
    if (!is_dir($carpeta)) {
        mkdir($carpeta, 0775, true);
    }

    $linea = '[' . date('Y-m-d H:i:s') . '] ' . $mensaje;
    if ($contexto !== []) {
        $linea .= ' | ' . json_encode($contexto, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }
    $linea .= PHP_EOL;

    file_put_contents($carpeta . '/bitacora.log', $linea, FILE_APPEND);
}
