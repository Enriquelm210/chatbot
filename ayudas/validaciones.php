<?php

declare(strict_types=1);

function limpiarTexto(string $texto): string
{
    $texto = trim($texto);
    $texto = preg_replace('/\s+/', ' ', $texto) ?? $texto;
    return $texto;
}

function validarNombreCompleto(string $nombre): bool
{
    $nombre = limpiarTexto($nombre);
    if (mb_strlen($nombre) < 6 || mb_strlen($nombre) > 100) {
        return false;
    }

    return preg_match('/^[\p{L}][\p{L}\s\.-]+[\p{L}]$/u', $nombre) === 1 && substr_count($nombre, ' ') >= 1;
}

function validarEdad(string $edad): bool
{
    if (!ctype_digit($edad)) {
        return false;
    }

    $valor = (int) $edad;
    return $valor >= 18 && $valor <= 85;
}

function validarCorreo(string $correo): bool
{
    return filter_var($correo, FILTER_VALIDATE_EMAIL) !== false && mb_strlen($correo) <= 120;
}

function validarCiudad(string $ciudad): bool
{
    $ciudad = limpiarTexto($ciudad);
    return preg_match('/^[\p{L}][\p{L}\s\.-]{2,79}$/u', $ciudad) === 1;
}

function validarCodigoPostal(string $codigoPostal): bool
{
    return preg_match('/^\d{5}$/', $codigoPostal) === 1;
}

function validarTelefonoSeguro(string $telefono): bool
{
    return preg_match('/^\d{10,15}$/', $telefono) === 1;
}

function validarAnioVehiculo(string $anio): bool
{
    if (!ctype_digit($anio)) {
        return false;
    }

    $valor = (int) $anio;
    $anioActual = (int) date('Y') + 1;
    return $valor >= 1995 && $valor <= $anioActual;
}

function validarCantidadAsegurados(string $cantidad): bool
{
    if (!ctype_digit($cantidad)) {
        return false;
    }

    $valor = (int) $cantidad;
    return $valor >= 1 && $valor <= 15;
}

function validarValorMonetario(string $valor): bool
{
    $valor = str_replace([',', '$', ' '], '', $valor);
    return preg_match('/^\d{4,9}(\.\d{1,2})?$/', $valor) === 1;
}

function textoPareceBasura(string $texto): bool
{
    $texto = limpiarTexto(mb_strtolower($texto));
    if ($texto === '') {
        return true;
    }

    $patrones = ['asdf', 'qwerty', '12345', 'prueba', 'xxx', 'aaa', 'hola hola'];
    foreach ($patrones as $patron) {
        if (str_contains($texto, $patron)) {
            return true;
        }
    }

    return preg_match('/^(.)\1{4,}$/u', str_replace(' ', '', $texto)) === 1;
}
