<?php

declare(strict_types=1);
require_once __DIR__ . '/../configuracion/conexion.php';
$appNombre = entorno('APP_NOMBRE', 'Chatbot de Seguros por WhatsApp');
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($appNombre) ?></title>
    <style>
        body{font-family:Arial,sans-serif;background:#f4f7fb;margin:0;color:#1c2833}
        .contenedor{max-width:900px;margin:40px auto;background:#fff;padding:40px;border-radius:18px;box-shadow:0 12px 40px rgba(0,0,0,.08)}
        h1{margin-top:0;color:#114b8b}.tarjetas{display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:18px;margin-top:28px}
        .tarjeta{background:#f8fbff;border:1px solid #d9e8f7;border-radius:16px;padding:20px}
        .boton{display:inline-block;background:#114b8b;color:#fff;text-decoration:none;padding:12px 18px;border-radius:10px;margin-top:10px}
        code{background:#eef3f8;padding:2px 6px;border-radius:6px}
    </style>
</head>
<body>
<div class="contenedor">
    <h1><?= htmlspecialchars($appNombre) ?></h1>
    <p>Proyecto en PHP y MySQL para recibir solicitudes de cotización por WhatsApp, validar datos, pedir foto de INE por ambos lados y mostrar la información al personal encargado de seguros.</p>

    <div class="tarjetas">
        <div class="tarjeta">
            <h3>Webhook de WhatsApp</h3>
            <p>Configura Meta con esta ruta:</p>
            <p><code><?= htmlspecialchars(rtrim(entorno('APP_URL', 'http://localhost/chatbot/publico'), '/') . '/webhook_whatsapp.php') ?></code></p>
        </div>
        <div class="tarjeta">
            <h3>Panel de administración</h3>
            <p>Consulta solicitudes, filtra por tipo de seguro y revisa fotos del INE.</p>
            <a class="boton" href="iniciar_sesion.php">Entrar al panel</a>
        </div>
        <div class="tarjeta">
            <h3>Base de datos</h3>
            <p>Importa el archivo <code>sql/base_de_datos.sql</code> desde phpMyAdmin.</p>
        </div>
    </div>
</div>
</body>
</html>
