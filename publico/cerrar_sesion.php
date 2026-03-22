<?php

declare(strict_types=1);
require_once __DIR__ . '/../ayudas/autenticacion.php';
cerrarSesionAdministrador();
header('Location: iniciar_sesion.php');
exit;
