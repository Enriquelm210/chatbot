<?php

declare(strict_types=1);

require_once __DIR__ . '/variables.php';

cargarVariablesEntorno(dirname(__DIR__) . '/.env');

date_default_timezone_set(entorno('APP_ZONA_HORARIA', 'America/Mexico_City'));

function obtenerConexion(): PDO
{
    static $pdo = null;

    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $host = entorno('DB_HOST', 'localhost');
    $puerto = entorno('DB_PUERTO', '3306');
    $base = entorno('DB_NOMBRE', 'chatbot_seguros');
    $usuario = entorno('DB_USUARIO', 'root');
    $contrasena = entorno('DB_CONTRASENA', '');

    $dsn = "mysql:host={$host};port={$puerto};dbname={$base};charset=utf8mb4";

    $pdo = new PDO($dsn, $usuario, $contrasena, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);

    return $pdo;
}
