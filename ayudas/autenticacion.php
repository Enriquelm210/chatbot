<?php

declare(strict_types=1);

require_once __DIR__ . '/../configuracion/conexion.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

function iniciarSesionAdministrador(string $usuario, string $contrasena): bool
{
    $usuarioValido = (string) entorno('ADMIN_USUARIO', 'admin');
    $contrasenaValida = (string) entorno('ADMIN_CONTRASENA', 'Seguros2026*');

    if (hash_equals($usuarioValido, $usuario) && hash_equals($contrasenaValida, $contrasena)) {
        $_SESSION['administrador_autenticado'] = true;
        $_SESSION['administrador_usuario'] = $usuario;
        return true;
    }

    return false;
}

function cerrarSesionAdministrador(): void
{
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
    }
    session_destroy();
}

function administradorAutenticado(): bool
{
    return !empty($_SESSION['administrador_autenticado']);
}

function exigirAdministrador(): void
{
    if (!administradorAutenticado()) {
        header('Location: iniciar_sesion.php');
        exit;
    }
}
