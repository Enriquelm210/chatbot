<?php

declare(strict_types=1);
require_once __DIR__ . '/../ayudas/autenticacion.php';

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $usuario = trim((string) ($_POST['usuario'] ?? ''));
    $contrasena = (string) ($_POST['contrasena'] ?? '');
    if (iniciarSesionAdministrador($usuario, $contrasena)) {
        header('Location: administracion.php');
        exit;
    }
    $error = 'Usuario o contraseña incorrectos.';
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iniciar sesión</title>
    <style>
        body{font-family:Arial,sans-serif;background:#eef4fb;display:grid;place-items:center;min-height:100vh;margin:0}
        form{background:#fff;padding:30px;border-radius:18px;box-shadow:0 15px 45px rgba(0,0,0,.08);width:min(90%,380px)}
        input{width:100%;padding:12px;border:1px solid #c9d7e6;border-radius:10px;margin:8px 0 16px;box-sizing:border-box}
        button{width:100%;padding:12px;background:#114b8b;color:#fff;border:none;border-radius:10px;font-weight:bold;cursor:pointer}
        .error{background:#ffe8e8;color:#9b1c1c;padding:10px;border-radius:10px;margin-bottom:14px}
    </style>
</head>
<body>
<form method="post">
    <h2>Panel de seguros</h2>
    <?php if ($error !== ''): ?><div class="error"><?= htmlspecialchars($error) ?></div><?php endif; ?>
    <label>Usuario</label>
    <input type="text" name="usuario" required>
    <label>Contraseña</label>
    <input type="password" name="contrasena" required>
    <button type="submit">Entrar</button>
</form>
</body>
</html>
