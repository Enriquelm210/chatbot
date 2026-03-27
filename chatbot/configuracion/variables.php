<?php

declare(strict_types=1);

function cargarVariablesEntorno(string $rutaArchivo): void
{
    if (!file_exists($rutaArchivo)) {
        return;
    }

    $lineas = file($rutaArchivo, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    if ($lineas === false) {
        return;
    }

    foreach ($lineas as $linea) {
        $linea = trim($linea);
        if ($linea === '' || str_starts_with($linea, '#') || !str_contains($linea, '=')) {
            continue;
        }

        [$clave, $valor] = explode('=', $linea, 2);
        $clave = trim($clave);
        $valor = trim($valor);
        $valor = trim($valor, "\"'");

        $_ENV[$clave] = $valor;
        putenv("{$clave}={$valor}");
    }
}

function entorno(string $clave, ?string $valorPredeterminado = null): ?string
{
    $valor = $_ENV[$clave] ?? getenv($clave);
    return ($valor === false || $valor === null || $valor === '') ? $valorPredeterminado : (string) $valor;
}
