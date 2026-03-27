<?php

declare(strict_types=1);

require_once __DIR__ . '/../configuracion/conexion.php';
require_once __DIR__ . '/../ayudas/utilidades.php';

class ServicioWhatsApp
{
    private string $tokenAcceso;
    private string $idNumeroTelefono;
    private string $versionApi;

    public function __construct()
    {
        $this->tokenAcceso = (string) entorno('WHATSAPP_TOKEN_ACCESO', '');
        $this->idNumeroTelefono = (string) entorno('WHATSAPP_ID_NUMERO_TELEFONO', '');
        $this->versionApi = (string) entorno('WHATSAPP_VERSION_API', 'v23.0');
    }

    public function enviarTexto(string $telefono, string $mensaje): array
    {
        return $this->hacerPeticion('/messages', [
            'messaging_product' => 'whatsapp',
            'to' => $telefono,
            'type' => 'text',
            'text' => [
                'preview_url' => false,
                'body' => $mensaje,
            ],
        ]);
    }

    public function descargarMedia(string $mediaId): ?array
    {
        if ($mediaId === '') {
            return null;
        }

        $urlInfo = $this->construirUrl("/{$mediaId}");
        $info = $this->hacerPeticionDirecta('GET', $urlInfo);

        if (!isset($info['url'])) {
            registrarBitacora('No se pudo obtener URL de media', ['media_id' => $mediaId, 'respuesta' => $info]);
            return null;
        }

        $archivo = $this->hacerDescargaArchivo((string) $info['url']);
        if (!$archivo) {
            return null;
        }

        return [
            'contenido' => $archivo['contenido'],
            'mime_type' => $info['mime_type'] ?? $archivo['mime_type'] ?? 'application/octet-stream',
            'sha256' => $info['sha256'] ?? null,
            'id' => $mediaId,
        ];
    }

    private function hacerPeticion(string $ruta, array $cuerpo): array
    {
        $url = $this->construirUrl('/' . $this->idNumeroTelefono . $ruta);
        return $this->hacerPeticionDirecta('POST', $url, $cuerpo);
    }

    private function construirUrl(string $ruta): string
    {
        return 'https://graph.facebook.com/' . $this->versionApi . $ruta;
    }

    private function hacerPeticionDirecta(string $metodo, string $url, ?array $cuerpo = null): array
    {
        $ch = curl_init($url);
        $encabezados = [
            'Authorization: Bearer ' . $this->tokenAcceso,
            'Content-Type: application/json',
        ];

        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST => $metodo,
            CURLOPT_HTTPHEADER => $encabezados,
            CURLOPT_TIMEOUT => 30,
        ]);

        if ($cuerpo !== null) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($cuerpo, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        }

        $respuesta = curl_exec($ch);
        $error = curl_error($ch);
        $codigo = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($respuesta === false) {
            registrarBitacora('Error al enviar petición a WhatsApp', ['error' => $error, 'url' => $url]);
            return ['error' => $error, 'codigo_http' => $codigo];
        }

        $datos = json_decode($respuesta, true);
        if (!is_array($datos)) {
            $datos = ['respuesta_cruda' => $respuesta];
        }

        if ($codigo >= 400) {
            registrarBitacora('Respuesta de error de WhatsApp', ['url' => $url, 'codigo_http' => $codigo, 'respuesta' => $datos]);
        }

        return $datos;
    }

    private function hacerDescargaArchivo(string $url): ?array
    {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 60,
            CURLOPT_HTTPHEADER => ['Authorization: Bearer ' . $this->tokenAcceso],
            CURLOPT_HEADER => true,
        ]);

        $respuesta = curl_exec($ch);
        $error = curl_error($ch);
        $codigo = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $tamanoEncabezado = (int) curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        $mime = (string) curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
        curl_close($ch);

        if ($respuesta === false || $codigo >= 400) {
            registrarBitacora('No se pudo descargar archivo de WhatsApp', ['error' => $error, 'codigo_http' => $codigo, 'url' => $url]);
            return null;
        }

        $contenido = substr($respuesta, $tamanoEncabezado);
        return [
            'contenido' => $contenido,
            'mime_type' => $mime,
        ];
    }
}
