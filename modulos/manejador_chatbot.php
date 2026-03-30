<?php

declare(strict_types=1);

require_once __DIR__ . '/repositorio_datos.php';
require_once __DIR__ . '/servicio_whatsapp.php';
require_once __DIR__ . '/../ayudas/validaciones.php';
require_once __DIR__ . '/../ayudas/utilidades.php';

class ManejadorChatbot
{
    private RepositorioDatos $repositorio;
    private ServicioWhatsApp $whatsapp;

    public function __construct()
    {
        $this->repositorio = new RepositorioDatos(obtenerConexion());
        $this->whatsapp = new ServicioWhatsApp();
    }

    public function procesarWebhook(array $entrada): void
    {
        $mensajes = $this->extraerMensajes($entrada);

        foreach ($mensajes as $mensaje) {
            $telefono = preg_replace('/\D+/', '', (string) ($mensaje['from'] ?? ''));
            $mensajeId = (string) ($mensaje['id'] ?? '');

            if ($telefono === '' || $mensajeId === '') {
                continue;
            }

            if ($this->mensajeYaProcesado($mensajeId)) {
                registrarBitacora('Mensaje duplicado ignorado', [
                    'telefono' => $telefono,
                    'mensaje_id' => $mensajeId,
                ]);
                continue;
            }

            $bloqueo = $this->abrirBloqueoTelefono($telefono);
            if ($bloqueo === null) {
                registrarBitacora('No se pudo abrir bloqueo de telefono', ['telefono' => $telefono]);
                continue;
            }

            try {
                if (!flock($bloqueo, LOCK_EX)) {
                    registrarBitacora('No se pudo adquirir bloqueo exclusivo', ['telefono' => $telefono]);
                    fclose($bloqueo);
                    continue;
                }

                if ($this->mensajeYaProcesado($mensajeId)) {
                    registrarBitacora('Mensaje duplicado detectado tras adquirir bloqueo', [
                        'telefono' => $telefono,
                        'mensaje_id' => $mensajeId,
                    ]);
                    flock($bloqueo, LOCK_UN);
                    fclose($bloqueo);
                    continue;
                }

                $conversacion = $this->repositorio->obtenerConversacionPorTelefono($telefono);
                $estado = (string) ($conversacion['estado_actual'] ?? 'inicio');
                $datos = is_array($conversacion['datos_temporales'] ?? null) ? $conversacion['datos_temporales'] : [];
                $tipoMensaje = (string) ($mensaje['type'] ?? '');

                registrarBitacora('Procesando mensaje', [
                    'telefono' => $telefono,
                    'mensaje_id' => $mensajeId,
                    'tipo' => $tipoMensaje,
                    'estado_actual' => $estado,
                ]);

                if ($tipoMensaje === 'text') {
                    $texto = limpiarTexto((string) ($mensaje['text']['body'] ?? ''));
                    $this->procesarTexto($telefono, $texto, $estado, $datos, $conversacion);
                } elseif ($tipoMensaje === 'image') {
                    $this->procesarImagen($telefono, $mensaje, $estado, $datos, $conversacion);
                } else {
                    $this->whatsapp->enviarTexto($telefono, "Por ahora solo puedo procesar texto e imágenes.\n\nEscribe *hola* para iniciar o continuar tu cotización.");
                }

                $this->marcarMensajeProcesado($mensajeId, $telefono, $tipoMensaje);
                flock($bloqueo, LOCK_UN);
                fclose($bloqueo);
            } catch (Throwable $e) {
                registrarBitacora('Error procesando mensaje', [
                    'telefono' => $telefono,
                    'mensaje_id' => $mensajeId,
                    'error' => $e->getMessage(),
                ]);

                if (is_resource($bloqueo)) {
                    flock($bloqueo, LOCK_UN);
                    fclose($bloqueo);
                }
            }
        }
    }

    private function procesarTexto(string $telefono, string $texto, string $estado, array $datos, ?array $conversacion): void
    {
        $textoNormalizado = mb_strtolower($texto);
        if (in_array($textoNormalizado, ['hola', 'menu', 'menú', 'inicio', 'empezar', 'reiniciar'], true)) {
            $this->enviarMenuPrincipal($telefono);
            $this->repositorio->crearOActualizarConversacion($telefono, 'seleccion_tipo', []);
            return;
        }

        switch ($estado) {
            case 'inicio':
                $this->enviarMenuPrincipal($telefono);
                $this->repositorio->crearOActualizarConversacion($telefono, 'seleccion_tipo', []);
                return;

            case 'seleccion_tipo':
                $this->manejarSeleccionTipo($telefono, $texto);
                return;

            case 'seleccion_opcion':
                $this->manejarSeleccionOpcion($telefono, $texto, $conversacion);
                return;

            case 'nombre_completo':
                if (!validarNombreCompleto($texto) || textoPareceBasura($texto)) {
                    $this->whatsapp->enviarTexto($telefono, 'Necesito tu *nombre completo real* (nombre y apellidos), sin apodos ni texto de prueba.');
                    return;
                }
                $datos['nombre_completo'] = limpiarTexto($texto);
                $this->actualizarEstadoConversacion($telefono, 'edad', $datos, $conversacion);
                $this->whatsapp->enviarTexto($telefono, 'Perfecto. Ahora escribe tu *edad* con números.\n\nSolo aceptamos personas entre 18 y 85 años.');
                return;

            case 'edad':
                if (!validarEdad($texto)) {
                    $this->whatsapp->enviarTexto($telefono, 'La edad debe ser un número entre *18 y 85*. Inténtalo nuevamente.');
                    return;
                }
                $datos['edad'] = (int) $texto;
                $this->actualizarEstadoConversacion($telefono, 'correo', $datos, $conversacion);
                $this->whatsapp->enviarTexto($telefono, 'Escribe tu *correo electrónico* para enviarte la cotización.');
                return;

            case 'correo':
                if (!validarCorreo($texto)) {
                    $this->whatsapp->enviarTexto($telefono, 'Ese correo no parece válido. Ejemplo correcto: *nombre@correo.com*');
                    return;
                }
                $datos['correo'] = mb_strtolower($texto);
                $this->actualizarEstadoConversacion($telefono, 'ciudad', $datos, $conversacion);
                $this->whatsapp->enviarTexto($telefono, 'Indica tu *ciudad o municipio*.');
                return;

            case 'ciudad':
                if (!validarCiudad($texto) || textoPareceBasura($texto)) {
                    $this->whatsapp->enviarTexto($telefono, 'Escribe una ciudad válida, por ejemplo: *Saltillo* o *Monterrey*.');
                    return;
                }
                $datos['ciudad'] = limpiarTexto($texto);
                $this->actualizarEstadoConversacion($telefono, 'codigo_postal', $datos, $conversacion);
                $this->whatsapp->enviarTexto($telefono, 'Ahora escribe tu *código postal* de 5 dígitos.');
                return;

            case 'codigo_postal':
                if (!validarCodigoPostal($texto)) {
                    $this->whatsapp->enviarTexto($telefono, 'El código postal debe tener *5 dígitos*.');
                    return;
                }
                $datos['codigo_postal'] = $texto;
                $siguiente = $this->obtenerSiguienteCampoEspecifico((int) ($conversacion['tipo_seguro_id'] ?? 0), []);
                if ($siguiente === null) {
                    $this->finalizarDatosGenerales($telefono, $datos, $conversacion);
                    return;
                }
                $this->actualizarEstadoConversacion($telefono, (string) $siguiente['estado'], $datos, $conversacion);
                $this->whatsapp->enviarTexto($telefono, (string) $siguiente['pregunta']);
                return;

            default:
                if (str_starts_with($estado, 'extra_')) {
                    $this->manejarCamposEspecificos($telefono, $texto, $estado, $datos, $conversacion);
                    return;
                }

                if ($estado === 'esperando_ine_frente') {
                    $this->whatsapp->enviarTexto($telefono, 'Para continuar necesito la *foto frontal de tu INE*. Envíala como imagen clara y completa.');
                    return;
                }

                if ($estado === 'esperando_ine_reverso') {
                    $this->whatsapp->enviarTexto($telefono, 'Ahora envía la *foto del reverso de tu INE* para completar la validación.');
                    return;
                }

                $this->enviarMenuPrincipal($telefono);
                $this->repositorio->crearOActualizarConversacion($telefono, 'seleccion_tipo', []);
                return;
        }
    }

    private function manejarSeleccionTipo(string $telefono, string $texto): void
    {
        $tipos = $this->repositorio->obtenerTiposSeguroActivos();
        $indice = (int) preg_replace('/\D+/', '', $texto);

        if ($indice < 1 || $indice > count($tipos)) {
            $this->whatsapp->enviarTexto($telefono, "Selecciona una opción válida del menú escribiendo solo el número.\n\n" . $this->formatearTiposSeguro($tipos));
            return;
        }

        $tipo = $tipos[$indice - 1];
        $opciones = $this->repositorio->obtenerOpcionesPorTipo((int) $tipo['id']);

        $mensaje = "Elegiste *{$tipo['nombre']}*.\n\nOpciones disponibles:\n";
        foreach ($opciones as $posicion => $opcion) {
            $numero = $posicion + 1;
            $mensaje .= "{$numero}. {$opcion['nombre']} - {$opcion['descripcion']}\n";
        }
        $mensaje .= "\nResponde con el número de la opción que deseas cotizar.";

        $this->repositorio->crearOActualizarConversacion($telefono, 'seleccion_opcion', [], (int) $tipo['id'], null);
        $this->whatsapp->enviarTexto($telefono, $mensaje);
    }

    private function manejarSeleccionOpcion(string $telefono, string $texto, ?array $conversacion): void
    {
        $tipoSeguroId = (int) ($conversacion['tipo_seguro_id'] ?? 0);
        $opciones = $this->repositorio->obtenerOpcionesPorTipo($tipoSeguroId);
        $indice = (int) preg_replace('/\D+/', '', $texto);

        if ($indice < 1 || $indice > count($opciones)) {
            $this->whatsapp->enviarTexto($telefono, 'Selecciona una opción válida escribiendo solo el número del plan o cobertura.');
            return;
        }

        $opcion = $opciones[$indice - 1];
        $this->repositorio->crearOActualizarConversacion($telefono, 'nombre_completo', [], $tipoSeguroId, (int) $opcion['id']);
        $this->whatsapp->enviarTexto($telefono, "Excelente. Has seleccionado *{$opcion['nombre']}*.\n\nAhora compárteme tu *nombre completo* tal como aparece en tu identificación oficial.");
    }

    private function manejarCamposEspecificos(string $telefono, string $texto, string $estado, array $datos, ?array $conversacion): void
    {
        $tipoSeguroId = (int) ($conversacion['tipo_seguro_id'] ?? 0);
        $campos = $this->obtenerCamposEspecificos($tipoSeguroId);

        foreach ($campos as $indice => $campo) {
            if ($campo['estado'] !== $estado) {
                continue;
            }

            if (!(($campo['validador'])($texto)) || textoPareceBasura($texto)) {
                $this->whatsapp->enviarTexto($telefono, $campo['mensaje_error']);
                return;
            }

            $datos[$campo['llave']] = limpiarTexto($texto);
            $restantes = array_slice($campos, $indice + 1);
            $siguiente = $this->obtenerSiguienteCampoEspecifico($tipoSeguroId, $restantes);

            registrarBitacora('Campo especifico procesado', [
                'telefono' => $telefono,
                'estado_actual' => $estado,
                'llave_guardada' => $campo['llave'],
                'valor' => $datos[$campo['llave']],
                'siguiente_estado' => $siguiente['estado'] ?? 'finalizar',
            ]);

            if ($siguiente === null) {
                $this->finalizarDatosGenerales($telefono, $datos, $conversacion);
                return;
            }

            $this->repositorio->crearOActualizarConversacion($telefono, $siguiente['estado'], $datos, $tipoSeguroId, (int) ($conversacion['opcion_seguro_id'] ?? 0));
            $this->whatsapp->enviarTexto($telefono, $siguiente['pregunta']);
            return;
        }

        $this->whatsapp->enviarTexto($telefono, 'Escribe *hola* para volver a empezar el proceso.');
    }

    private function finalizarDatosGenerales(string $telefono, array $datos, ?array $conversacion): void
    {
        $tipoSeguroId = (int) ($conversacion['tipo_seguro_id'] ?? 0);
        $opcionSeguroId = (int) ($conversacion['opcion_seguro_id'] ?? 0);

        if ($tipoSeguroId <= 0 || $opcionSeguroId <= 0) {
            registrarBitacora('No se pudo finalizar por falta de tipo u opcion', [
                'telefono' => $telefono,
                'conversacion' => $conversacion,
            ]);
            $this->enviarMenuPrincipal($telefono);
            $this->repositorio->crearOActualizarConversacion($telefono, 'seleccion_tipo', []);
            return;
        }

        $solicitudId = $this->repositorio->guardarSolicitud([
            'telefono' => $telefono,
            'nombre_completo' => (string) ($datos['nombre_completo'] ?? ''),
            'edad' => (int) ($datos['edad'] ?? 0),
            'correo' => (string) ($datos['correo'] ?? ''),
            'ciudad' => (string) ($datos['ciudad'] ?? ''),
            'codigo_postal' => (string) ($datos['codigo_postal'] ?? ''),
            'tipo_seguro_id' => $tipoSeguroId,
            'opcion_seguro_id' => $opcionSeguroId,
            'datos_adicionales' => $this->filtrarDatosAdicionales($datos),
            'validacion_ine_completa' => 0,
        ]);

        $datos['solicitud_id'] = $solicitudId;
        $this->repositorio->crearOActualizarConversacion($telefono, 'esperando_ine_frente', $datos, $tipoSeguroId, $opcionSeguroId);

        $tipo = $this->repositorio->obtenerTipoSeguroPorId($tipoSeguroId);
        $opcion = $this->repositorio->obtenerOpcionPorId($opcionSeguroId);

        $mensaje = "Gracias. Ya registré tu solicitud para *{$tipo['nombre']}* - *{$opcion['nombre']}*.\n\n";
        $mensaje .= "Para corroborar la información, envía ahora una *foto clara del frente de tu INE*.\n";
        $mensaje .= "Después te pediré la parte trasera.\n\n";
        $mensaje .= "Importante: la imagen debe verse completa, sin recortes, sin reflejos y con buena iluminación.";

        registrarBitacora('Solicitud creada', [
            'telefono' => $telefono,
            'solicitud_id' => $solicitudId,
            'tipo_seguro_id' => $tipoSeguroId,
            'opcion_seguro_id' => $opcionSeguroId,
        ]);

        $this->whatsapp->enviarTexto($telefono, $mensaje);
    }

    private function procesarImagen(string $telefono, array $mensaje, string $estado, array $datos, ?array $conversacion): void
    {
        if (!in_array($estado, ['esperando_ine_frente', 'esperando_ine_reverso'], true)) {
            $this->whatsapp->enviarTexto($telefono, 'Todavía no corresponde enviar imágenes. Primero completa tu cotización escribiendo *hola* para iniciar.');
            return;
        }

        $mediaId = (string) ($mensaje['image']['id'] ?? '');
        if ($mediaId === '') {
            $this->whatsapp->enviarTexto($telefono, 'No pude leer la imagen. Intenta enviarla de nuevo.');
            return;
        }

        if (empty($datos['solicitud_id'])) {
            registrarBitacora('Imagen recibida sin solicitud asociada', [
                'telefono' => $telefono,
                'estado' => $estado,
                'datos' => $datos,
            ]);
            $this->whatsapp->enviarTexto($telefono, 'No encontré una solicitud activa para asociar tu INE. Escribe *hola* para iniciar de nuevo.');
            $this->repositorio->reiniciarConversacion($telefono);
            return;
        }

        $archivo = $this->whatsapp->descargarMedia($mediaId);
        if ($archivo === null) {
            $this->whatsapp->enviarTexto($telefono, 'No pude descargar la imagen desde WhatsApp. Intenta reenviarla en unos segundos.');
            return;
        }

        $mime = (string) ($archivo['mime_type'] ?? 'image/jpeg');
        if (!str_starts_with($mime, 'image/')) {
            $this->whatsapp->enviarTexto($telefono, 'El archivo enviado no es una imagen válida. Envía una foto de tu INE.');
            return;
        }

        $extension = match ($mime) {
            'image/png' => 'png',
            'image/webp' => 'webp',
            default => 'jpg',
        };

        $lado = $estado === 'esperando_ine_frente' ? 'frente' : 'reverso';
        $carpeta = dirname(__DIR__) . '/almacen/ine_' . $lado;
        if (!is_dir($carpeta)) {
            mkdir($carpeta, 0775, true);
        }

        $nombreArchivo = $telefono . '_' . $datos['solicitud_id'] . '_' . $lado . '_' . date('Ymd_His') . '.' . $extension;
        $rutaAbsoluta = $carpeta . '/' . $nombreArchivo;
        file_put_contents($rutaAbsoluta, $archivo['contenido']);

        $rutaRelativa = 'almacen/ine_' . $lado . '/' . $nombreArchivo;
        $this->repositorio->actualizarSolicitudConINE((int) $datos['solicitud_id'], $lado, $rutaRelativa, $mime, $mediaId);

        if ($lado === 'frente') {
            $this->repositorio->crearOActualizarConversacion($telefono, 'esperando_ine_reverso', $datos, (int) ($conversacion['tipo_seguro_id'] ?? 0), (int) ($conversacion['opcion_seguro_id'] ?? 0));
            $this->whatsapp->enviarTexto($telefono, 'Recibí correctamente el *frente de tu INE*.\n\nAhora envía la *foto del reverso* para completar la revisión.');
            return;
        }

        $this->repositorio->reiniciarConversacion($telefono);
        $this->whatsapp->enviarTexto($telefono, "Gracias. Tu solicitud quedó registrada y enviada al equipo de seguros para revisión.\n\nEn breve se pondrán en contacto contigo por WhatsApp o correo electrónico.\n\nSi deseas hacer otra cotización, escribe *hola*.");
    }

    private function extraerMensajes(array $entrada): array
    {
        $mensajes = [];
        foreach (($entrada['entry'] ?? []) as $entry) {
            foreach (($entry['changes'] ?? []) as $change) {
                foreach (($change['value']['messages'] ?? []) as $mensaje) {
                    $mensajes[] = $mensaje;
                }
            }
        }
        return $mensajes;
    }

    private function enviarMenuPrincipal(string $telefono): void
    {
        $tipos = $this->repositorio->obtenerTiposSeguroActivos();
        $mensaje = "¡Hola! Soy el asistente de cotizaciones de seguros.\n\n";
        $mensaje .= "Puedo ayudarte a registrar tu solicitud. Selecciona el tipo de seguro que deseas cotizar:\n\n";
        $mensaje .= $this->formatearTiposSeguro($tipos);
        $mensaje .= "\nEscribe solo el número de la opción.\n\n";
        $mensaje .= "Para evitar registros falsos, validamos los datos y solicitamos foto de tu INE por ambos lados al final del proceso.";

        $this->whatsapp->enviarTexto($telefono, $mensaje);
    }

    private function formatearTiposSeguro(array $tipos): string
    {
        $texto = '';
        foreach ($tipos as $indice => $tipo) {
            $numero = $indice + 1;
            $texto .= "{$numero}. {$tipo['nombre']} - {$tipo['descripcion']}\n";
        }
        return $texto;
    }

    private function obtenerCamposEspecificos(int $tipoSeguroId): array
    {
        return match ($tipoSeguroId) {
            1 => [
                [
                    'estado' => 'extra_marca_vehiculo',
                    'llave' => 'marca_vehiculo',
                    'pregunta' => 'Escribe la *marca del vehículo* que deseas asegurar. Ejemplo: Nissan, Chevrolet, Kia.',
                    'mensaje_error' => 'Escribe una marca de vehículo válida. No uses números solos ni texto de prueba.',
                    'validador' => fn(string $valor): bool => validarCiudad($valor),
                ],
                [
                    'estado' => 'extra_modelo_vehiculo',
                    'llave' => 'modelo_vehiculo',
                    'pregunta' => 'Ahora escribe el *modelo del vehículo*. Ejemplo: Versa, Aveo, Rio.',
                    'mensaje_error' => 'Escribe un modelo válido del vehículo.',
                    'validador' => fn(string $valor): bool => mb_strlen(limpiarTexto($valor)) >= 2 && mb_strlen(limpiarTexto($valor)) <= 40,
                ],
                [
                    'estado' => 'extra_anio_vehiculo',
                    'llave' => 'anio_vehiculo',
                    'pregunta' => 'Indica el *año del vehículo* con 4 dígitos. Ejemplo: 2022.',
                    'mensaje_error' => 'El año del vehículo debe estar entre 1995 y el próximo año.',
                    'validador' => fn(string $valor): bool => validarAnioVehiculo($valor),
                ],
            ],
            2 => [
                [
                    'estado' => 'extra_beneficiario',
                    'llave' => 'beneficiario_principal',
                    'pregunta' => 'Escribe el *nombre completo del beneficiario principal*.',
                    'mensaje_error' => 'Necesito el nombre completo real del beneficiario principal.',
                    'validador' => fn(string $valor): bool => validarNombreCompleto($valor),
                ],
            ],
            3 => [
                [
                    'estado' => 'extra_cantidad_asegurados',
                    'llave' => 'cantidad_asegurados',
                    'pregunta' => '¿Cuántas personas deseas asegurar? Escribe solo un número del 1 al 15.',
                    'mensaje_error' => 'La cantidad de asegurados debe ser un número entre 1 y 15.',
                    'validador' => fn(string $valor): bool => validarCantidadAsegurados($valor),
                ],
            ],
            4 => [
                [
                    'estado' => 'extra_valor_vivienda',
                    'llave' => 'valor_estimado_vivienda',
                    'pregunta' => 'Escribe el *valor estimado de la vivienda* en números. Ejemplo: 1450000',
                    'mensaje_error' => 'Escribe un valor aproximado válido, solo con números.',
                    'validador' => fn(string $valor): bool => validarValorMonetario($valor),
                ],
            ],
            default => [],
        };
    }

    private function obtenerSiguienteCampoEspecifico(int $tipoSeguroId, array $campos): ?array
    {
        $campos = $campos ?: $this->obtenerCamposEspecificos($tipoSeguroId);
        return $campos[0] ?? null;
    }

    private function filtrarDatosAdicionales(array $datos): array
    {
        $llavesGenerales = ['nombre_completo', 'edad', 'correo', 'ciudad', 'codigo_postal', 'solicitud_id'];
        $resultado = [];
        foreach ($datos as $clave => $valor) {
            if (!in_array($clave, $llavesGenerales, true)) {
                $resultado[$clave] = $valor;
            }
        }
        return $resultado;
    }

    private function actualizarEstadoConversacion(string $telefono, string $estado, array $datos, ?array $conversacion): void
    {
        $this->repositorio->crearOActualizarConversacion(
            $telefono,
            $estado,
            $datos,
            (int) ($conversacion['tipo_seguro_id'] ?? 0) ?: null,
            (int) ($conversacion['opcion_seguro_id'] ?? 0) ?: null,
        );

        registrarBitacora('Estado de conversacion actualizado', [
            'telefono' => $telefono,
            'nuevo_estado' => $estado,
            'tipo_seguro_id' => (int) ($conversacion['tipo_seguro_id'] ?? 0),
            'opcion_seguro_id' => (int) ($conversacion['opcion_seguro_id'] ?? 0),
        ]);
    }

    private function abrirBloqueoTelefono(string $telefono)
    {
        $carpeta = dirname(__DIR__) . '/almacen/temp/bloqueos';
        if (!is_dir($carpeta)) {
            mkdir($carpeta, 0775, true);
        }

        $ruta = $carpeta . '/' . preg_replace('/\D+/', '', $telefono) . '.lock';
        return fopen($ruta, 'c+');
    }

    private function mensajeYaProcesado(string $mensajeId): bool
    {
        $carpeta = dirname(__DIR__) . '/almacen/temp/procesados';
        $ruta = $carpeta . '/' . sha1($mensajeId) . '.ok';
        return is_file($ruta);
    }

    private function marcarMensajeProcesado(string $mensajeId, string $telefono, string $tipoMensaje): void
    {
        $carpeta = dirname(__DIR__) . '/almacen/temp/procesados';
        if (!is_dir($carpeta)) {
            mkdir($carpeta, 0775, true);
        }

        $ruta = $carpeta . '/' . sha1($mensajeId) . '.ok';
        $contenido = json_encode([
            'mensaje_id' => $mensajeId,
            'telefono' => $telefono,
            'tipo' => $tipoMensaje,
            'fecha' => date('Y-m-d H:i:s'),
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        file_put_contents($ruta, $contenido);
    }
}
