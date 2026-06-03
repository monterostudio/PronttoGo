<div align="center">

<img src="api/logo.svg" alt="PronttoGo" width="280" />

### Catálogo Digital Inteligente — Single-Store
**Diseñado y desarrollado por [Montero Studio](https://github.com/monterostudio)**

[![GitHub](https://img.shields.io/badge/GitHub-monterostudio%2FPronttoGo-181717?style=flat-square&logo=github)](https://github.com/monterostudio/PronttoGo)
[![Estado](https://img.shields.io/badge/estado-producción-10B981?style=flat-square)](https://pronttogo.vercel.app)
[![Plataforma](https://img.shields.io/badge/plataforma-Vercel-black?style=flat-square)](https://vercel.com)
[![Base de datos](https://img.shields.io/badge/BD-Supabase-3ECF8E?style=flat-square)](https://supabase.com)
[![Licencia](https://img.shields.io/badge/licencia-Propietaria-red?style=flat-square)](LICENSE)

</div>

---

## ¿Qué es PronttoGo?

**PronttoGo** es un catálogo digital para comercios independientes (**Single-Store**). Permite al dueño de un negocio publicar sus productos, organizarlos por categorías y recibir pedidos directamente por **WhatsApp** — sin intermediarios, sin comisiones y sin necesidad de instalar ninguna aplicación.

El cliente navega el catálogo, agrega productos al carrito, y con un solo clic envía su pedido completo al número de WhatsApp del comercio.

---

## Características principales

| Función | Descripción |
|---|---|
| 🛒 **Carrito digital** | Acumulación de productos con contador de artículos y total en tiempo real |
| 💬 **Pedido por WhatsApp** | Mensaje formateado y listo para recibir en el negocio |
| 💱 **Tasa de cambio** | Soporte para moneda local (Bolívares) con tasa fija o automática (BCV) |
| 🔐 **Panel de administración** | CRUD completo de productos y categorías, protegido con login, CSRF y bloqueo por fuerza bruta |
| 🖼️ **Imágenes por URL** | Las fotos de productos se cargan desde cualquier URL pública |
| 📱 **100% responsivo** | Diseño optimizado para móviles, tablets y escritorio |
| ⚡ **Sin instalación** | El cliente no instala nada — todo funciona desde el navegador |

---

## Tecnologías utilizadas

| Capa | Tecnología |
|---|---|
| **Backend** | PHP 8.x (serverless en Vercel) |
| **Base de datos** | Supabase (PostgreSQL) vía REST API |
| **Frontend** | HTML5 · CSS · JavaScript vanilla · Tailwind CSS (CDN) |
| **Tipografía** | Plus Jakarta Sans (Google Fonts) |
| **Despliegue** | Vercel (`vercel.json` configurado) |

---

## Estructura del proyecto

```
PronttoGo/
├── api/
│   ├── index.php        ← Catálogo público (tienda)
│   ├── admin.php        ← Panel de administración
│   ├── legal.php        ← Página de términos y privacidad
│   ├── config.php       ← Configuración, cliente Supabase y utilidades
│   └── logo.svg         ← Logotipo oficial de PronttoGo
├── schema.sql           ← Esquema completo de la base de datos
├── vercel.json          ← Configuración de rutas y runtime PHP en Vercel
├── LICENSE              ← Licencia propietaria de Montero Studio
└── README.md            ← Este archivo
```

---

## Base de datos (Supabase)

El esquema completo se encuentra en [`schema.sql`](schema.sql). Las tres tablas que utiliza el sistema son:

### `configuracion`
Registro único (`id = 1`) con los datos del comercio.

| Columna | Tipo | Descripción |
|---|---|---|
| `id` | BIGINT PK | Identificador (siempre 1) |
| `nombre` | TEXT | Nombre visible del comercio |
| `telefono_whatsapp` | TEXT | Número con código de país (ej: `584121234567`) |
| `logo_url` | TEXT | URL del logotipo (NULL = usa el logo de PronttoGo) |
| `tasa_dolar` | NUMERIC | Tasa de cambio activa (1 USD = X moneda local) |
| `tasa_tipo` | TEXT | `manual` · `bcv` |
| `admin_user` | TEXT | Usuario del panel de administración |
| `admin_password` | TEXT | Contraseña (texto plano o hash bcrypt) |

### `categorias`
Grupos que organizan los productos.

| Columna | Tipo | Descripción |
|---|---|---|
| `id` | BIGINT PK | Identificador único |
| `nombre_categoria` | TEXT | Nombre de la sección (ej: Comidas, Bebidas) |
| `orden_visual` | INTEGER | Orden de aparición en el menú |

### `productos`
Artículos del catálogo vinculados a una categoría.

| Columna | Tipo | Descripción |
|---|---|---|
| `id` | BIGINT PK | Identificador único |
| `categoria_id` | BIGINT FK | Referencia a `categorias.id` (cascada al borrar) |
| `nombre` | TEXT | Nombre del producto |
| `descripcion` | TEXT | Descripción opcional |
| `precio` | NUMERIC | Precio en USD |
| `imagen_url` | TEXT | URL de la foto (NULL = tarjeta sin imagen) |
| `disponible` | BOOLEAN | Si es `false`, no aparece en el catálogo público |

---

## Despliegue en Vercel

1. Haz fork o clona este repositorio en tu cuenta de GitHub.
2. Importa el proyecto en [vercel.com](https://vercel.com/new).
3. Configura las variables de entorno:

| Variable | Descripción |
|---|---|
| `SUPABASE_URL` | URL de tu proyecto Supabase (ej: `https://xxxx.supabase.co`) |
| `SUPABASE_KEY` | Clave `service_role` de Supabase |
| `ADMIN_USER` | Usuario administrador inicial |
| `ADMIN_PASSWORD` | Contraseña administrador inicial |

4. Ejecuta `schema.sql` en el editor SQL de Supabase para crear las tablas.
5. Despliega. El catálogo estará disponible en tu dominio de Vercel.

> **Nota:** Las variables de entorno tienen prioridad. Si no se configuran, el sistema usa los valores por defecto definidos en `api/config.php` para facilitar pruebas locales.

---

## Desarrollo local (Laragon / XAMPP)

1. Clona el repositorio dentro del directorio `www` de tu servidor local.
2. Crea un archivo `config.local.php` en la raíz con tus credenciales:

```php
<?php
return [
    'SUPABASE_URL'    => 'https://tu-proyecto.supabase.co',
    'SUPABASE_KEY'    => 'tu-service-role-key',
    'ADMIN_USER'      => 'admin',
    'ADMIN_PASSWORD'  => 'tu-clave-segura',
];
```

3. Accede desde `http://localhost/PronttoGo/api/index.php` o configura un virtual host.

---

## Licencia

Este software es propiedad exclusiva de **Montero Studio**.  
Su uso, copia o distribución sin autorización expresa está prohibida.  
Consulta el archivo [LICENSE](LICENSE) para más detalles.

---

<div align="center">

**PronttoGo** es una marca de **Montero Studio**  
© 2026 — Todos los derechos reservados

[🔗 Repositorio](https://github.com/monterostudio/PronttoGo) · [🌐 Producción](https://pronttogo.vercel.app)

</div>
