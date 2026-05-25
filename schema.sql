-- PRONTTO GO - ESTRUCTURA DE BASE DE DATOS DEDICADA (SINGLE-STORE)

-- 1. Tabla 'configuracion' (Garantiza una única fila de configuración general del local)
CREATE TABLE IF NOT EXISTS configuracion (
    id INTEGER PRIMARY KEY DEFAULT 1,
    nombre VARCHAR(100) NOT NULL,
    telefono_whatsapp VARCHAR(30) NOT NULL,
    CONSTRAINT chk_single_row CHECK (id = 1)
);

-- Inicializar la configuración por defecto
INSERT INTO configuracion (id, nombre, telefono_whatsapp)
VALUES (1, 'Mi Tienda', '584121234567')
ON CONFLICT (id) DO NOTHING;

-- 2. Tabla 'categorias'
CREATE TABLE IF NOT EXISTS categorias (
    id BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    nombre_categoria VARCHAR(100) NOT NULL,
    orden_visual INTEGER DEFAULT 0 NOT NULL,
    created_at TIMESTAMP WITH TIME ZONE DEFAULT timezone('utc'::text, now()) NOT NULL
);

-- 3. Tabla 'productos'
CREATE TABLE IF NOT EXISTS productos (
    id BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    categoria_id BIGINT NOT NULL REFERENCES categorias(id) ON DELETE CASCADE,
    nombre VARCHAR(150) NOT NULL,
    descripcion TEXT,
    precio NUMERIC(10, 2) NOT NULL DEFAULT 0.00,
    imagen_url TEXT,
    disponible BOOLEAN DEFAULT true NOT NULL,
    created_at TIMESTAMP WITH TIME ZONE DEFAULT timezone('utc'::text, now()) NOT NULL
);

-- Crear índices de velocidad
CREATE INDEX IF NOT EXISTS idx_productos_categoria_id ON productos(categoria_id);
