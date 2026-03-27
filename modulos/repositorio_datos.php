<?php

declare(strict_types=1);

require_once __DIR__ . '/../configuracion/conexion.php';

class RepositorioDatos
{
    public function __construct(private readonly PDO $conexion)
    {
    }

    public function obtenerConversacionPorTelefono(string $telefono): ?array
    {
        $sql = 'SELECT * FROM conversaciones WHERE telefono = :telefono LIMIT 1';
        $stmt = $this->conexion->prepare($sql);
        $stmt->execute(['telefono' => $telefono]);
        $fila = $stmt->fetch();

        if (!$fila) {
            return null;
        }

        $fila['datos_temporales'] = $fila['datos_temporales'] ? json_decode((string) $fila['datos_temporales'], true) : [];
        return $fila;
    }

    public function crearOActualizarConversacion(string $telefono, string $estado, array $datosTemporales = [], ?int $tipoSeguroId = null, ?int $opcionSeguroId = null): void
    {
        $existente = $this->obtenerConversacionPorTelefono($telefono);
        $jsonDatos = json_encode($datosTemporales, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        if ($existente) {
            $sql = 'UPDATE conversaciones
                    SET estado_actual = :estado, datos_temporales = :datos_temporales, tipo_seguro_id = :tipo_seguro_id,
                        opcion_seguro_id = :opcion_seguro_id, updated_at = NOW()
                    WHERE telefono = :telefono';
        } else {
            $sql = 'INSERT INTO conversaciones (telefono, estado_actual, datos_temporales, tipo_seguro_id, opcion_seguro_id, created_at, updated_at)
                    VALUES (:telefono, :estado, :datos_temporales, :tipo_seguro_id, :opcion_seguro_id, NOW(), NOW())';
        }

        $stmt = $this->conexion->prepare($sql);
        $stmt->execute([
            'telefono' => $telefono,
            'estado' => $estado,
            'datos_temporales' => $jsonDatos,
            'tipo_seguro_id' => $tipoSeguroId,
            'opcion_seguro_id' => $opcionSeguroId,
        ]);
    }

    public function reiniciarConversacion(string $telefono): void
    {
        $stmt = $this->conexion->prepare('DELETE FROM conversaciones WHERE telefono = :telefono');
        $stmt->execute(['telefono' => $telefono]);
    }

    public function obtenerTiposSeguroActivos(): array
    {
        $sql = 'SELECT id, nombre, descripcion FROM tipos_seguro WHERE activo = 1 ORDER BY orden_visual ASC, nombre ASC';
        return $this->conexion->query($sql)->fetchAll();
    }

    public function obtenerTipoSeguroPorId(int $id): ?array
    {
        $stmt = $this->conexion->prepare('SELECT * FROM tipos_seguro WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        return $stmt->fetch() ?: null;
    }

    public function obtenerOpcionesPorTipo(int $tipoSeguroId): array
    {
        $stmt = $this->conexion->prepare('SELECT id, nombre, descripcion FROM opciones_seguro WHERE tipo_seguro_id = :tipo AND activo = 1 ORDER BY orden_visual ASC, nombre ASC');
        $stmt->execute(['tipo' => $tipoSeguroId]);
        return $stmt->fetchAll();
    }


    public function obtenerTodasLasOpcionesActivas(): array
    {
        $sql = 'SELECT os.id, os.nombre, ts.nombre AS tipo_seguro_nombre
                FROM opciones_seguro os
                INNER JOIN tipos_seguro ts ON ts.id = os.tipo_seguro_id
                WHERE os.activo = 1
                ORDER BY ts.orden_visual ASC, os.orden_visual ASC, os.nombre ASC';
        return $this->conexion->query($sql)->fetchAll();
    }

    public function obtenerOpcionPorId(int $id): ?array
    {
        $stmt = $this->conexion->prepare('SELECT os.*, ts.nombre AS tipo_seguro_nombre
                                         FROM opciones_seguro os
                                         INNER JOIN tipos_seguro ts ON ts.id = os.tipo_seguro_id
                                         WHERE os.id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        return $stmt->fetch() ?: null;
    }

    public function guardarSolicitud(array $datos): int
    {
        $sql = 'INSERT INTO solicitudes_seguro (
                    telefono, nombre_completo, edad, correo, ciudad, codigo_postal,
                    tipo_seguro_id, opcion_seguro_id, datos_adicionales, estatus,
                    validacion_ine_completa, created_at, updated_at
                ) VALUES (
                    :telefono, :nombre_completo, :edad, :correo, :ciudad, :codigo_postal,
                    :tipo_seguro_id, :opcion_seguro_id, :datos_adicionales, :estatus,
                    :validacion_ine_completa, NOW(), NOW()
                )';

        $stmt = $this->conexion->prepare($sql);
        $stmt->execute([
            'telefono' => $datos['telefono'],
            'nombre_completo' => $datos['nombre_completo'],
            'edad' => $datos['edad'],
            'correo' => $datos['correo'],
            'ciudad' => $datos['ciudad'],
            'codigo_postal' => $datos['codigo_postal'],
            'tipo_seguro_id' => $datos['tipo_seguro_id'],
            'opcion_seguro_id' => $datos['opcion_seguro_id'],
            'datos_adicionales' => json_encode($datos['datos_adicionales'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            'estatus' => $datos['estatus'] ?? 'pendiente_revision',
            'validacion_ine_completa' => $datos['validacion_ine_completa'] ?? 0,
        ]);

        return (int) $this->conexion->lastInsertId();
    }

    public function actualizarSolicitudConINE(int $solicitudId, string $lado, string $rutaArchivo, string $mime, ?string $idMedia = null): void
    {
        $camposPermitidos = ['frente', 'reverso'];
        if (!in_array($lado, $camposPermitidos, true)) {
            throw new InvalidArgumentException('Lado de INE no permitido');
        }

        $columnaRuta = $lado === 'frente' ? 'ruta_ine_frente' : 'ruta_ine_reverso';
        $columnaMime = $lado === 'frente' ? 'mime_ine_frente' : 'mime_ine_reverso';
        $columnaMedia = $lado === 'frente' ? 'media_id_frente' : 'media_id_reverso';

        $sql = "UPDATE solicitudes_seguro
                SET {$columnaRuta} = :ruta, {$columnaMime} = :mime, {$columnaMedia} = :media, updated_at = NOW(),
                    validacion_ine_completa = CASE WHEN ruta_ine_frente IS NOT NULL AND ruta_ine_reverso IS NOT NULL THEN 1 ELSE validacion_ine_completa END
                WHERE id = :id";

        $stmt = $this->conexion->prepare($sql);
        $stmt->execute([
            'ruta' => $rutaArchivo,
            'mime' => $mime,
            'media' => $idMedia,
            'id' => $solicitudId,
        ]);

        $this->marcarINECompletaSiProcede($solicitudId);
    }

    public function marcarINECompletaSiProcede(int $solicitudId): void
    {
        $stmt = $this->conexion->prepare('UPDATE solicitudes_seguro
                                         SET validacion_ine_completa = CASE WHEN ruta_ine_frente IS NOT NULL AND ruta_ine_reverso IS NOT NULL THEN 1 ELSE 0 END,
                                             updated_at = NOW()
                                         WHERE id = :id');
        $stmt->execute(['id' => $solicitudId]);
    }

    public function obtenerSolicitudPorId(int $id): ?array
    {
        $stmt = $this->conexion->prepare('SELECT ss.*, ts.nombre AS tipo_seguro, os.nombre AS opcion_seguro
                                         FROM solicitudes_seguro ss
                                         INNER JOIN tipos_seguro ts ON ts.id = ss.tipo_seguro_id
                                         INNER JOIN opciones_seguro os ON os.id = ss.opcion_seguro_id
                                         WHERE ss.id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        $fila = $stmt->fetch();
        if (!$fila) {
            return null;
        }
        $fila['datos_adicionales'] = $fila['datos_adicionales'] ? json_decode((string) $fila['datos_adicionales'], true) : [];
        return $fila;
    }

    public function listarSolicitudes(array $filtros = []): array
    {
        $condiciones = [];
        $parametros = [];

        if (!empty($filtros['tipo_seguro_id'])) {
            $condiciones[] = 'ss.tipo_seguro_id = :tipo_seguro_id';
            $parametros['tipo_seguro_id'] = $filtros['tipo_seguro_id'];
        }

        if (!empty($filtros['opcion_seguro_id'])) {
            $condiciones[] = 'ss.opcion_seguro_id = :opcion_seguro_id';
            $parametros['opcion_seguro_id'] = $filtros['opcion_seguro_id'];
        }

        if (!empty($filtros['estatus'])) {
            $condiciones[] = 'ss.estatus = :estatus';
            $parametros['estatus'] = $filtros['estatus'];
        }

        $where = $condiciones ? 'WHERE ' . implode(' AND ', $condiciones) : '';

        $sql = "SELECT ss.*, ts.nombre AS tipo_seguro, os.nombre AS opcion_seguro
                FROM solicitudes_seguro ss
                INNER JOIN tipos_seguro ts ON ts.id = ss.tipo_seguro_id
                INNER JOIN opciones_seguro os ON os.id = ss.opcion_seguro_id
                {$where}
                ORDER BY ss.created_at DESC";

        $stmt = $this->conexion->prepare($sql);
        $stmt->execute($parametros);
        $filas = $stmt->fetchAll();

        foreach ($filas as &$fila) {
            $fila['datos_adicionales'] = $fila['datos_adicionales'] ? json_decode((string) $fila['datos_adicionales'], true) : [];
        }

        return $filas;
    }
}
