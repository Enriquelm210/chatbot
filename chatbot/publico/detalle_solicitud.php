<?php

declare(strict_types=1);
require_once __DIR__ . '/../ayudas/autenticacion.php';
require_once __DIR__ . '/../modulos/repositorio_datos.php';
require_once __DIR__ . '/../ayudas/utilidades.php';
exigirAdministrador();

$repositorio = new RepositorioDatos(obtenerConexion());
$id = (int) ($_GET['id'] ?? 0);
$solicitud = $repositorio->obtenerSolicitudPorId($id);
if (!$solicitud) {
    http_response_code(404);
    echo 'Solicitud no encontrada';
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detalle de solicitud</title>
    <style>
        body{font-family:Arial,sans-serif;background:#f5f8fc;margin:0;color:#1b2a38}
        .contenedor{max-width:1100px;margin:30px auto;padding:24px}
        .caja{background:#fff;border-radius:18px;padding:24px;box-shadow:0 10px 35px rgba(0,0,0,.08);margin-bottom:18px}
        .grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:14px}
        .mini{background:#f8fbff;border:1px solid #dce8f7;border-radius:14px;padding:14px}
        .imagenes{display:grid;grid-template-columns:repeat(auto-fit,minmax(280px,1fr));gap:18px}
        img{width:100%;height:auto;border-radius:14px;border:1px solid #d7e2ee}
        a.boton{display:inline-block;padding:10px 16px;background:#114b8b;color:#fff;text-decoration:none;border-radius:10px}
        ul{margin:8px 0 0;padding-left:18px}
    </style>
</head>
<body>
<div class="contenedor">
    <a class="boton" href="administracion.php">Volver</a>
    <div class="caja">
        <h2><?= escapar($solicitud['nombre_completo']) ?></h2>
        <div class="grid">
            <div class="mini"><strong>Teléfono</strong><br><?= escapar($solicitud['telefono']) ?></div>
            <div class="mini"><strong>Edad</strong><br><?= escapar((string) $solicitud['edad']) ?></div>
            <div class="mini"><strong>Correo</strong><br><?= escapar($solicitud['correo']) ?></div>
            <div class="mini"><strong>Ciudad</strong><br><?= escapar($solicitud['ciudad']) ?></div>
            <div class="mini"><strong>Código postal</strong><br><?= escapar($solicitud['codigo_postal']) ?></div>
            <div class="mini"><strong>Tipo de seguro</strong><br><?= escapar($solicitud['tipo_seguro']) ?></div>
            <div class="mini"><strong>Plan / cobertura</strong><br><?= escapar($solicitud['opcion_seguro']) ?></div>
            <div class="mini"><strong>Estatus</strong><br><?= escapar($solicitud['estatus']) ?></div>
            <div class="mini"><strong>INE completa</strong><br><?= $solicitud['validacion_ine_completa'] ? 'Sí' : 'No' ?></div>
            <div class="mini"><strong>Fecha</strong><br><?= escapar($solicitud['created_at']) ?></div>
        </div>
    </div>

    <div class="caja">
        <h3>Datos adicionales del tipo de seguro</h3>
        <?php if (!empty($solicitud['datos_adicionales'])): ?>
            <ul>
                <?php foreach ($solicitud['datos_adicionales'] as $clave => $valor): ?>
                    <li><strong><?= escapar(ucwords(str_replace('_', ' ', (string) $clave))) ?>:</strong> <?= escapar((string) $valor) ?></li>
                <?php endforeach; ?>
            </ul>
        <?php else: ?>
            <p>No se registraron datos adicionales.</p>
        <?php endif; ?>
    </div>

    <div class="caja">
        <h3>Identificación oficial</h3>
        <div class="imagenes">
            <div>
                <p><strong>INE frente</strong></p>
                <?php if (!empty($solicitud['ruta_ine_frente'])): ?>
                    <img src="ver_archivo.php?ruta=<?= urlencode($solicitud['ruta_ine_frente']) ?>" alt="INE frente">
                <?php else: ?>
                    <p>No cargada.</p>
                <?php endif; ?>
            </div>
            <div>
                <p><strong>INE reverso</strong></p>
                <?php if (!empty($solicitud['ruta_ine_reverso'])): ?>
                    <img src="ver_archivo.php?ruta=<?= urlencode($solicitud['ruta_ine_reverso']) ?>" alt="INE reverso">
                <?php else: ?>
                    <p>No cargada.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
</body>
</html>
