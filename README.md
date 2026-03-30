# Chatbot de Seguros por WhatsApp

Repositorio: https://github.com/Enriquelm210/chatbot

Este proyecto implementa un chatbot desarrollado en **PHP + MySQL** que se integra con la **WhatsApp Cloud API** para automatizar la atención de clientes y la recolección de datos para cotización de seguros.

El sistema guía al usuario mediante un flujo conversacional estructurado, validando información y almacenando solicitudes para su posterior revisión en un panel administrativo.

---

## 🚀 Funcionalidades principales

- Menú interactivo por tipo de seguro.
- Selección de planes o coberturas.
- Validación de datos del usuario:
  - Nombre completo
  - Edad
  - Correo electrónico
  - Ciudad o municipio
  - Código postal
- Captura de datos específicos según el seguro.
- Solicitud de INE (frente y reverso).
- Descarga y almacenamiento local de imágenes.
- Panel administrativo para gestión de solicitudes.
- Bitácora para debugging del sistema y webhook.

---

## 🛡️ Tipos de seguro incluidos

1. **Seguro de Auto**
   - Cobertura Básica
   - Cobertura Limitada
   - Cobertura Amplia

2. **Seguro de Vida**
   - Plan Individual
   - Plan Familiar
   - Plan con Ahorro

3. **Seguro de Gastos Médicos**
   - Plan Básico
   - Plan Plus
   - Plan Premium

4. **Seguro de Hogar**
   - Protección Esencial
   - Protección Completa

---

## 🏗️ Arquitectura del sistema

El sistema actualmente utiliza una arquitectura **monolítica**, donde todos los componentes están integrados en una sola aplicación.

### Componentes principales

- Webhook (entrada desde WhatsApp)
- Manejador del chatbot (lógica de flujo)
- Servicio de WhatsApp (envío de mensajes)
- Repositorio de datos
- Bitácora de eventos
- Panel administrativo

### Evolución futura

El sistema está diseñado para poder migrar a **microservicios**, separando:

- Servicio de mensajes
- Servicio de usuarios
- Servicio de cotizaciones
- Servicio de autenticación

---

## 🧱 Estructura del proyecto

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
│   ├── webhook_whatsapp.php
│   └── otros archivos del panel
├── sql/
│   └── base_de_datos.sql
├── .env.ejemplo
└── README.md

---

## ⚙️ Requisitos

- PHP 8.1 o superior
- MySQL o MariaDB
- XAMPP o entorno local equivalente
- Extensión pdo_mysql
- Extensión curl
- Cuenta en Meta Developers con WhatsApp Cloud API

---

## 🛠️ Instalación

### 1. Clonar repositorio

git clone https://github.com/Enriquelm210/chatbot.git
cd chatbot

---

### 2. Configurar archivo .env

APP_NOMBRE=Chatbot de Seguros
APP_URL=http://localhost/chatbot/publico

DB_HOST=localhost
DB_NOMBRE=chatbot_seguros
DB_USUARIO=root
DB_CONTRASENA=

WHATSAPP_TOKEN_VERIFICACION=tu_token_de_verificacion
WHATSAPP_TOKEN_ACCESO=tu_token_de_whatsapp
WHATSAPP_ID_NUMERO_TELEFONO=tu_phone_number_id
WHATSAPP_VERSION_API=v25.0

ADMIN_USUARIO=admin
ADMIN_CONTRASENA=*******

---

### 3. Base de datos

Importar archivo:
sql/base_de_datos.sql

---

### 4. Ubicación del proyecto

C:\xampp\htdocs\chatbot

---

### 5. Ejecutar servidor

Iniciar Apache y MySQL en XAMPP

Abrir:
http://localhost/chatbot/publico

---

## 🌐 Configuración de WhatsApp Cloud API

Webhook URL:
https://TU_URL_NGROK/chatbot/publico/webhook_whatsapp.php

Token de verificación:
tu_token_de_verificacion

---

## 🔌 Uso de ngrok

ngrok http 80

---

## 🔄 Flujo del chatbot

1. Usuario envía "hola"
2. Se muestra menú de seguros
3. Usuario selecciona opción
4. Se solicitan datos
5. Se validan entradas
6. Se solicitan imágenes de INE
7. Se guarda la solicitud
8. Se visualiza en panel administrativo

---

## 🧪 Validaciones implementadas

- Nombre completo real
- Edad válida
- Correo con formato correcto
- Código postal válido
- Validación por tipo de seguro
- Detección de respuestas falsas o spam

---

## ⚠️ Seguridad

Se recomienda:
- Uso de HTTPS
- Protección del panel administrativo
- Manejo seguro de datos personales
- Implementación de aviso de privacidad

---

## 📌 Estado actual

✔ Sistema funcional  
✔ Integración con WhatsApp activa  
✔ Webhook configurado correctamente  
✔ Flujo conversacional completo  

---

## 🔮 Mejoras futuras

- Migración a microservicios
- Integración con inteligencia artificial
- Panel más avanzado
- Base de datos optimizada
- Manejo de sesiones más robusto

---

## 👨‍💻 Autor

Proyecto académico  
Ingeniería en Desarrollo y Gestión de Software
Seguros Informaticos UTC
