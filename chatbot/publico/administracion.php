<?php

declare(strict_types=1);
require_once __DIR__ . '/../ayudas/autenticacion.php';
require_once __DIR__ . '/../modulos/repositorio_datos.php';
require_once __DIR__ . '/../ayudas/utilidades.php';
exigirAdministrador();

$repositorio = new RepositorioDatos(obtenerConexion());
$tipos = $repositorio->obtenerTiposSeguroActivos();
$opciones = $repositorio->obtenerTodasLasOpcionesActivas();
$filtros = [
    'tipo_seguro_id' => $_GET['tipo_seguro_id'] ?? '',
    'opcion_seguro_id' => $_GET['opcion_seguro_id'] ?? '',
    'estatus' => $_GET['estatus'] ?? '',
];
$solicitudes = $repositorio->listarSolicitudes($filtros);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Administración de solicitudes</title>
    <style>
        body{font-family:Arial,sans-serif;background:#f3f7fb;margin:0;color:#21303d}
        .contenedor{max-width:1280px;margin:30px auto;padding:20px}
        .encabezado,.caja{background:#fff;border-radius:18px;padding:22px;box-shadow:0 10px 35px rgba(0,0,0,.07);margin-bottom:18px}
        .fila{display:flex;justify-content:space-between;align-items:center;gap:12px;flex-wrap:wrap}
        form{display:flex;gap:10px;flex-wrap:wrap;margin-top:10px}
        select,button,a.boton{padding:10px 14px;border-radius:10px;border:1px solid #cfdceb;text-decoration:none}
        button,.boton{background:#114b8b;color:#fff;border:none;cursor:pointer}
        table{width:100%;border-collapse:collapse;font-size:14px}
        th,td{padding:12px;border-bottom:1px solid #e1eaf3;text-align:left;vertical-align:top}
        th{background:#f7fbff}.estado{padding:6px 10px;border-radius:999px;background:#edf4ff;display:inline-block}
        .si{color:#0f6d2f;font-weight:bold}.no{color:#9b1c1c;font-weight:bold}
    </style>
</head>
<body>
<div class="contenedor">
    <div class="encabezado">
        <div class="fila">
            <div>
                <h2>Panel del equipo de seguros</h2>
                <p>Consulta solicitudes, planes elegidos, datos validados y fotografías del INE.</p>
            </div>
            <div>
                <a class="boton" href="cerrar_sesion.php">Cerrar sesión</a>
            </div>
        </div>
        <form method="get">
            <select name="tipo_seguro_id">
                <option value="">Todos los tipos de seguro</option>
                <?php foreach ($tipos as $tipo): ?>
                    <option value="<?= escapar((string) $tipo['id']) ?>" <?= (string) $filtros['tipo_seguro_id'] === (string) $tipo['id'] ? 'selected' : '' ?>><?= escapar($tipo['nombre']) ?></option>
                <?php endforeach; ?>
            </select>
            <select name="opcion_seguro_id">
                <option value="">Todas las opciones / planes</option>
                <?php foreach ($opciones as $opcion): ?>
                    <option value="<?= escapar((string) $opcion['id']) ?>" <?= (string) $filtros['opcion_seguro_id'] === (string) $opcion['id'] ? 'selected' : '' ?>><?= escapar($opcion['tipo_seguro_nombre'] . ' - ' . $opcion['nombre']) ?></option>
                <?php endforeach; ?>
            </select>
            <select name="estatus">
                <option value="">Todos los estatus</option>
                <option value="pendiente_revision" <?= $filtros['estatus'] === 'pendiente_revision' ? 'selected' : '' ?>>Pendiente de revisión</option>
                <option value="en_contacto" <?= $filtros['estatus'] === 'en_contacto' ? 'selected' : '' ?>>En contacto</option>
                <option value="cerrada" <?= $filtros['estatus'] === 'cerrada' ? 'selected' : '' ?>>Cerrada</option>
            </select>
            <button type="submit">Filtrar</button>
        </form>
    </div>

    <div class="caja">
        <table>
            <thead>
                <tr>
                    <th>Fecha</th>
                    <th>Cliente</th>
                    <th>Tipo</th>
                    <th>Plan</th>
                    <th>Contacto</th>
                    <th>INE</th>
                    <th>Estatus</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!$solicitudes): ?>
                    <tr><td colspan="8">No hay solicitudes registradas con esos filtros.</td></tr>
                <?php endif; ?>
                <?php foreach ($solicitudes as $solicitud): ?>
                    <tr>
                        <td><?= escapar($solicitud['created_at']) ?></td>
                        <td>
                            <strong><?= escapar($solicitud['nombre_completo']) ?></strong><br>
                            Edad: <?= escapar((string) $solicitud['edad']) ?><br>
                            <?= escapar($solicitud['ciudad']) ?>, CP <?= escapar($solicitud['codigo_postal']) ?>
                        </td>
                        <td><?= escapar($solicitud['tipo_seguro']) ?></td>
                        <td><?= escapar($solicitud['opcion_seguro']) ?></td>
                        <td><?= escapar($solicitud['telefono']) ?><br><?= escapar($solicitud['correo']) ?></td>
                        <td class="<?= $solicitud['validacion_ine_completa'] ? 'si' : 'no' ?>"><?= $solicitud['validacion_ine_completa'] ? 'Completa' : 'Incompleta' ?></td>
                        <td><span class="estado"><?= escapar($solicitud['estatus']) ?></span></td>
                        <td><a class="boton" href="detalle_solicitud.php?id=<?= (int) $solicitud['id'] ?>">Ver detalle</a></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
</body>
</html>
