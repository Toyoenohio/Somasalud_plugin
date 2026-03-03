# WC Booking Wizard Pro (Chile Edition)

Plugin de WordPress para WooCommerce que proporciona un asistente de reservas paso a paso con integración para Chile.

## 🚀 Características

- ✅ **Asistente de Reservas Paso a Paso**: Interfaz intuitiva para reservar servicios
- ✅ **Validación de RUT Chileno**: Valida automáticamente el RUT de los clientes
- ✅ **Integración con WC Bookings**: Compatible con WooCommerce Bookings
- ✅ **Integración con Transbank**: Pagos con Webpay Plus
- ✅ **Shortcode Personalizado**: Fácil de insertar en cualquier página
- ✅ **AJAX Dinámico**: Experiencia de usuario fluida sin recargas

## 📋 Requisitos

- WordPress 5.0 o superior
- WooCommerce 4.0 o superior
- WooCommerce Bookings (opcional, recomendado)
- PHP 7.4 o superior

## 🛠️ Instalación

1. Sube la carpeta del plugin a `/wp-content/plugins/`
2. Activa el plugin desde el menú "Plugins" en WordPress
3. Inserta el shortcode `[booking_wizard]` en cualquier página o entrada

## 🔧 Configuración

### Shortcode

```
[booking_wizard]
```

O con parámetros opcionales:

```
[booking_wizard steps="5" theme="light"]
```

### Estructura de Archivos

```
somasalud/
├── assets/
│   ├── css/
│   │   └── style.css
│   └── js/
│       └── app.js
├── includes/
│   ├── class-wcbw-ajax.php
│   └── class-wcbw-shortcode.php
├── index.php
└── wc-booking-wizard-pro.php
```

## 🎨 Personalización

### CSS Personalizado

El plugin incluye estilos base en `assets/css/style.css`. Para personalizar:

1. Copia el archivo a tu tema hijo
2. Modifica según tus necesidades
3. O usa el personalizador de WordPress para CSS adicional

### JavaScript

La lógica del wizard está en `assets/js/app.js`. Puedes extenderla con hooks personalizados.

## 💳 Integración con Transbank

El plugin soporta pagos con Webpay Plus de Transbank. Para configurar:

1. Ve a WooCommerce → Configuración → Pagos
2. Habilita Transbank Webpay Plus
3. Configura tu código de comercio y clave secreta

## 🇨🇱 Validación de RUT

El plugin incluye validación automática del RUT chileno (con dígito verificador) en el formulario de reservas.

## 🐛 Soporte

Para reportar bugs o solicitar características:

- GitHub Issues: https://github.com/Toyoenohio/Somasalud_plugin/issues
- Email: soporte@ejemplo.com

## 📄 Licencia

GPL v3 o posterior

## 👨‍💻 Desarrollador

**Senior Dev Team**

## 🔄 Changelog

### Versión 2.1.0
- ✅ Integración con Transbank
- ✅ Validación de RUT chileno
- ✅ Mejoras en la interfaz del wizard
- ✅ Optimización de consultas AJAX

### Versión 2.0.0
- ✅ Primera versión estable
- ✅ Shortcode funcional
- ✅ Integración con WC Bookings

---

**Hecho con ❤️ para Chile**
