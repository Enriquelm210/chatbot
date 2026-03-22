# Chatbot de Seguros por WhatsApp

Repositorio: https://github.com/Enriquelm210/chatbot

Este proyecto implementa un chatbot en **PHP + MySQL** para recibir solicitudes de cotización de seguros por WhatsApp. El flujo está pensado para evitar registros falsos, validar datos básicos, pedir foto de la **INE por ambos lados** y mostrar todo en un panel para el personal encargado de seguros.

## Funcionalidades principales

- Menú de cotización por tipo de seguro.
- Opciones y planes por cada tipo de seguro.
- Validación de nombre, edad, correo, ciudad y código postal.
- Campos específicos según el seguro elegido.
- Solicitud de INE frente y reverso para corroborar información.
- Descarga y guardado local de imágenes enviadas por WhatsApp Cloud API.
- Panel administrativo en español para revisar solicitudes, filtros, datos e imágenes.
- Bitácora simple para revisar errores o payloads del webhook.

## Tipos de seguro incluidos

1. Seguro de Auto
   - Cobertura Básica
   - Cobertura Limitada
   - Cobertura Amplia
2. Seguro de Vida
   - Plan Individual
   - Plan Familiar
   - Plan con Ahorro
3. Seguro de Gastos Médicos
   - Plan Básico
   - Plan Plus
   - Plan Premium
4. Seguro de Hogar
   - Protección Esencial
   - Protección Completa

## Estructura del proyecto

```text
chatbot/
├── almacen/
│   ├── ine_frente/
│   ├── ine_reverso/
│   └── temp/
├── ayudas/
│   ├── autenticacion.php
│   ├── utilidades.php
│   └── validaciones.php
├── configuracion/
│   ├── conexion.php
│   └── variables.php
├── modulos/
│   ├── manejador_chatbot.php
│   ├── repositorio_datos.php
│   └── servicio_whatsapp.php
├── publico/
│   ├── administracion.php
│   ├── cerrar_sesion.php
│   ├── detalle_solicitud.php
│   ├── index.php
│   ├── iniciar_sesion.php
│   ├── ver_archivo.php
│   └── webhook_whatsapp.php
├── sql/
│   └── base_de_datos.sql
├── .env.ejemplo
├── .gitignore
└── README.md
```

## Requisitos

- PHP 8.1 o superior
- Extensión `pdo_mysql`
- Extensión `curl`
- MySQL o MariaDB
- XAMPP, Laragon o un entorno local similar
- Cuenta de Meta Developers con **WhatsApp Cloud API** activa

## Paso 1. Clonar el repositorio

```bash
git clone https://github.com/Enriquelm210/chatbot.git
cd chatbot
```

## Paso 2. Crear el archivo `.env`

Duplica el archivo de ejemplo y renómbralo como `.env`.

```bash
cp .env.ejemplo .env
```

Si estás en Windows y no te funciona `cp`, crea manualmente un archivo `.env` y copia este contenido base:

```ini
APP_NOMBRE=Chatbot de Seguros por WhatsApp
APP_URL=http://localhost/chatbot/publico
APP_ZONA_HORARIA=America/Mexico_City

DB_HOST=localhost
DB_PUERTO=3306
DB_NOMBRE=chatbot_seguros
DB_USUARIO=root
DB_CONTRASENA=

WHATSAPP_TOKEN_VERIFICACION=mi_token_de_verificacion
WHATSAPP_TOKEN_ACCESO=pega_aqui_tu_token_de_whatsapp_cloud
WHATSAPP_ID_NUMERO_TELEFONO=123456789012345
WHATSAPP_VERSION_API=v23.0

ADMIN_USUARIO=admin
ADMIN_CONTRASENA=Seguros2026*
```

## Paso 3. Crear la base de datos en XAMPP

1. Abre XAMPP.
2. Inicia **Apache** y **MySQL**.
3. Entra a `http://localhost/phpmyadmin`.
4. Ve a **Importar**.
5. Selecciona el archivo `sql/base_de_datos.sql`.
6. Ejecuta la importación.

Eso creará la base `chatbot_seguros`, las tablas y los catálogos de tipos de seguro y opciones.

## Paso 4. Colocar el proyecto en `htdocs`

Copia la carpeta `chatbot` dentro de:

```text
C:\xampp\htdocs\
```

La ruta final debe quedar así:

```text
C:\xampp\htdocs\chatbot
```

## Paso 5. Probar en local

Abre en el navegador:

```text
http://localhost/chatbot/publico
```

## Paso 6. Entrar al panel administrativo

Abre:

```text
http://localhost/chatbot/publico/iniciar_sesion.php
```

Usuario y contraseña por defecto del `.env`:

- Usuario: `admin`
- Contraseña: `Seguros2026*`

Cámbialos antes de usarlo en un entorno real.

## Paso 7. Configurar WhatsApp Cloud API

Dentro de Meta Developers debes configurar:

- **Token de acceso permanente** o temporal
- **Phone Number ID** del número que usará el bot
- **Webhook URL**
- **Webhook Verify Token**

### Valores que debes copiar al `.env`

- `WHATSAPP_TOKEN_ACCESO`
- `WHATSAPP_ID_NUMERO_TELEFONO`
- `WHATSAPP_TOKEN_VERIFICACION`

### URL del webhook

Si usas XAMPP local, la ruta del webhook será:

```text
http://localhost/chatbot/publico/webhook_whatsapp.php
```

Para probar con Meta, normalmente necesitarás exponer tu servidor local con una herramienta como **ngrok** o subir el proyecto a un hosting.

Ejemplo con URL pública:

```text
https://tu-dominio-o-ngrok/chatbot/publico/webhook_whatsapp.php
```

## Cómo funciona el chatbot

1. El usuario escribe `hola`.
2. El bot muestra tipos de seguro.
3. El usuario elige el tipo.
4. El bot muestra planes o coberturas disponibles.
5. El usuario selecciona una opción.
6. El bot solicita datos generales:
   - nombre completo
   - edad
   - correo
   - ciudad
   - código postal
7. El bot pide datos adicionales según el tipo de seguro:
   - Auto: marca, modelo, año
   - Vida: beneficiario principal
   - Gastos médicos: cantidad de asegurados
   - Hogar: valor estimado de la vivienda
8. El bot pide foto del frente de la INE.
9. El bot pide foto del reverso de la INE.
10. La solicitud queda disponible en el panel administrativo.

## Validaciones incluidas

El sistema aplica validaciones para evitar datos falsos o que alguien solo juegue con el bot:

- Nombre completo real, con al menos nombre y apellidos.
- Edad entre 18 y 85 años.
- Correo con formato válido.
- Ciudad con texto real.
- Código postal de 5 dígitos.
- Validación específica por tipo de seguro.
- Detección básica de texto de prueba como `asdf`, `qwerty`, `12345`, `xxx`, etc.
- La solicitud no se considera completa hasta recibir las dos imágenes de INE.

## Seguridad recomendada

Este proyecto guarda información sensible. Antes de usarlo en producción deberías:

- Cambiar credenciales del panel.
- Proteger la carpeta del proyecto con HTTPS.
- Restringir el acceso al panel administrativo.
- Cifrar archivos sensibles si vas a manejar datos reales.
- Agregar aviso de privacidad.
- Definir política de conservación y eliminación de INE.
- Implementar control de auditoría y roles si habrá varios agentes.

## Prueba rápida del webhook

Puedes verificar que el archivo responde con el token de validación cuando Meta intente validar el webhook.

## Comandos Git para subir cambios

```bash
git add .
git commit -m "feat: chatbot de seguros por WhatsApp en español"
git push origin main
```

## Notas finales

- El proyecto está completamente en español, incluyendo nombres de archivos y mensajes del chatbot.
- No incluye dependencias externas para que sea más fácil levantarlo en XAMPP.
- Si vas a usar un número real de WhatsApp, recuerda que necesitas configurarlo desde Meta Developers.
