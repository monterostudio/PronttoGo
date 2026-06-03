-- ============================================================
--  PronttoGo — Esquema de Base de Datos (Supabase / PostgreSQL)
--  Producto de Montero Studio  ·  © 2026 Todos los derechos reservados
--  Plataforma: Supabase (PostgreSQL)
-- ============================================================


-- ────────────────────────────────────────────────────────────
-- TABLA 1: configuracion
-- Registro único (id = 1) con los datos del comercio.
-- ────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS public.configuracion (
    id                  BIGINT          PRIMARY KEY GENERATED ALWAYS AS IDENTITY,
    nombre              TEXT            NOT NULL DEFAULT 'Mi Tienda',
    telefono_whatsapp   TEXT            NOT NULL DEFAULT '',
    logo_url            TEXT            NULL,
    tasa_dolar          NUMERIC(12, 2)  NOT NULL DEFAULT 1.00,
    tasa_tipo           TEXT            NOT NULL DEFAULT 'manual'
                                        CHECK (tasa_tipo IN ('manual', 'bcv')),
    admin_user          TEXT            NOT NULL DEFAULT 'admin',
    admin_password      TEXT            NOT NULL DEFAULT 'admin123',
    tipo_negocio        TEXT            NOT NULL DEFAULT 'gastronomia'
                                        CHECK (tipo_negocio IN ('gastronomia', 'comida_rapida', 'minimarket', 'farmacia', 'boutique', 'ferreteria_repuestos', 'belleza_estetica', 'otros')),
    moneda_simbolo      TEXT            NOT NULL DEFAULT '$',
    moneda_nombre       TEXT            NOT NULL DEFAULT 'USD',
    costo_delivery      NUMERIC(12, 2)  NOT NULL DEFAULT 0.00,
    direccion           TEXT            NOT NULL DEFAULT '',
    horario             TEXT            NOT NULL DEFAULT '',
    color_primario      TEXT            NOT NULL DEFAULT '#4F46E5',
    plantilla_whatsapp  TEXT            NULL,
    created_at          TIMESTAMPTZ     NOT NULL DEFAULT NOW(),
    updated_at          TIMESTAMPTZ     NOT NULL DEFAULT NOW()
);

COMMENT ON TABLE  public.configuracion                  IS 'Configuración global del comercio. Solo debe existir un registro (id = 1).';
COMMENT ON COLUMN public.configuracion.nombre           IS 'Nombre visible del comercio en el catálogo público.';
COMMENT ON COLUMN public.configuracion.telefono_whatsapp IS 'Número de WhatsApp completo con código de país (ej: 584121234567).';
COMMENT ON COLUMN public.configuracion.logo_url         IS 'URL pública de la imagen del logotipo del comercio. NULL muestra el logo de PronttoGo.';
COMMENT ON COLUMN public.configuracion.tasa_dolar       IS 'Tasa de cambio activa: cuántas unidades locales equivalen a 1 USD.';
COMMENT ON COLUMN public.configuracion.tasa_tipo        IS 'Fuente de la tasa: manual (fija definida por comercio), bcv (Venezuela).';
COMMENT ON COLUMN public.configuracion.admin_user       IS 'Usuario para acceder al panel de administración.';
COMMENT ON COLUMN public.configuracion.admin_password   IS 'Contraseña en texto plano o hash bcrypt del administrador.';
COMMENT ON COLUMN public.configuracion.tipo_negocio     IS 'Nicho o rubro del comercio local (gastronomia, boutique, etc).';
COMMENT ON COLUMN public.configuracion.moneda_simbolo   IS 'Símbolo de la moneda de cobro local (ej. $, Bs, COP).';
COMMENT ON COLUMN public.configuracion.moneda_nombre    IS 'Nombre o código de la moneda local (ej. USD, VES, COP).';
COMMENT ON COLUMN public.configuracion.costo_delivery   IS 'Tarifa fija o costo base del delivery a cobrar al cliente.';
COMMENT ON COLUMN public.configuracion.direccion        IS 'Dirección física del establecimiento comercial.';
COMMENT ON COLUMN public.configuracion.horario          IS 'Horario de atención al público en formato de texto.';

-- Registro inicial por defecto
INSERT INTO public.configuracion (nombre, telefono_whatsapp, tasa_dolar, tasa_tipo, admin_user, admin_password, tipo_negocio, moneda_simbolo, moneda_nombre, costo_delivery, direccion, horario)
VALUES ('PronttoGo', '584121234567', 1.00, 'manual', 'admin', 'admin123', 'gastronomia', '$', 'USD', 0.00, '', '')
ON CONFLICT DO NOTHING;


-- ────────────────────────────────────────────────────────────
-- TABLA 2: categorias
-- Grupos que organizan los productos del catálogo.
-- ────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS public.categorias (
    id                  BIGINT          PRIMARY KEY GENERATED ALWAYS AS IDENTITY,
    nombre_categoria    TEXT            NOT NULL,
    orden_visual        INTEGER         NOT NULL DEFAULT 1,
    created_at          TIMESTAMPTZ     NOT NULL DEFAULT NOW()
);

COMMENT ON TABLE  public.categorias                         IS 'Grupos o secciones del catálogo (ej: Comidas, Bebidas, Postres).';
COMMENT ON COLUMN public.categorias.nombre_categoria        IS 'Nombre visible de la categoría en el menú público.';
COMMENT ON COLUMN public.categorias.orden_visual            IS 'Número de orden de aparición en el catálogo. Menor número = aparece primero.';

CREATE INDEX IF NOT EXISTS idx_categorias_orden ON public.categorias (orden_visual ASC);


-- ────────────────────────────────────────────────────────────
-- TABLA 3: productos
-- Artículos del catálogo vinculados a una categoría.
-- ────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS public.productos (
    id                  BIGINT          PRIMARY KEY GENERATED ALWAYS AS IDENTITY,
    categoria_id        BIGINT          NOT NULL REFERENCES public.categorias (id) ON DELETE CASCADE,
    nombre              TEXT            NOT NULL,
    descripcion         TEXT            NULL,
    precio              NUMERIC(10, 2)  NOT NULL CHECK (precio > 0),
    imagen_url          TEXT            NULL,
    disponible          BOOLEAN         NOT NULL DEFAULT TRUE,
    stock               INTEGER         NULL DEFAULT NULL CHECK (stock >= 0 OR stock IS NULL),
    created_at          TIMESTAMPTZ     NOT NULL DEFAULT NOW(),
    updated_at          TIMESTAMPTZ     NOT NULL DEFAULT NOW()
);

COMMENT ON TABLE  public.productos                  IS 'Artículos del catálogo digital. Cada producto pertenece a una categoría.';
COMMENT ON COLUMN public.productos.categoria_id     IS 'FK hacia la tabla categorias. Al borrar la categoría se eliminan sus productos en cascada.';
COMMENT ON COLUMN public.productos.nombre           IS 'Nombre del producto visible en el catálogo y en el mensaje de WhatsApp.';
COMMENT ON COLUMN public.productos.descripcion      IS 'Descripción opcional: ingredientes, tamaño, variantes, etc.';
COMMENT ON COLUMN public.productos.precio           IS 'Precio en USD. Se convierte a moneda local usando la tasa activa de configuracion.';
COMMENT ON COLUMN public.productos.imagen_url       IS 'URL pública de la imagen del producto. NULL muestra la tarjeta sin foto.';
COMMENT ON COLUMN public.productos.disponible       IS 'Si es FALSE el producto no aparece en el catálogo público.';

CREATE INDEX IF NOT EXISTS idx_productos_categoria  ON public.productos (categoria_id);
CREATE INDEX IF NOT EXISTS idx_productos_disponible ON public.productos (disponible);


-- ────────────────────────────────────────────────────────────
-- POLÍTICAS DE SEGURIDAD (Row Level Security — Supabase)
-- ────────────────────────────────────────────────────────────

-- Activar RLS en todas las tablas
ALTER TABLE public.configuracion  ENABLE ROW LEVEL SECURITY;
ALTER TABLE public.categorias     ENABLE ROW LEVEL SECURITY;
ALTER TABLE public.productos      ENABLE ROW LEVEL SECURITY;

-- Lectura pública (anon): necesaria para renderizar el catálogo sin autenticación
DROP POLICY IF EXISTS "lectura_publica_configuracion" ON public.configuracion;
CREATE POLICY "lectura_publica_configuracion" ON public.configuracion
    FOR SELECT TO anon USING (true);

DROP POLICY IF EXISTS "lectura_publica_categorias" ON public.categorias;
CREATE POLICY "lectura_publica_categorias" ON public.categorias
    FOR SELECT TO anon USING (true);

DROP POLICY IF EXISTS "lectura_publica_productos" ON public.productos;
CREATE POLICY "lectura_publica_productos" ON public.productos
    FOR SELECT TO anon USING (true);

-- Escritura restringida al service_role (usado por el backend PHP en admin.php)
DROP POLICY IF EXISTS "escritura_service_configuracion" ON public.configuracion;
CREATE POLICY "escritura_service_configuracion" ON public.configuracion
    FOR ALL TO service_role USING (true) WITH CHECK (true);

DROP POLICY IF EXISTS "escritura_service_categorias" ON public.categorias;
CREATE POLICY "escritura_service_categorias" ON public.categorias
    FOR ALL TO service_role USING (true) WITH CHECK (true);

DROP POLICY IF EXISTS "escritura_service_productos" ON public.productos;
CREATE POLICY "escritura_service_productos" ON public.productos
    FOR ALL TO service_role USING (true) WITH CHECK (true);


-- ────────────────────────────────────────────────────────────
-- TABLA 4: categorias_predeterminadas
-- Categorías sugeridas según el tipo de negocio.
-- ────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS public.categorias_predeterminadas (
    id                  BIGINT          PRIMARY KEY GENERATED ALWAYS AS IDENTITY,
    tipo_negocio        TEXT            NOT NULL,
    nombre              TEXT            NOT NULL,
    orden_visual        INTEGER         NOT NULL DEFAULT 10
);

ALTER TABLE public.categorias_predeterminadas ENABLE ROW LEVEL SECURITY;

DROP POLICY IF EXISTS "lectura_publica_predeterminadas" ON public.categorias_predeterminadas;
CREATE POLICY "lectura_publica_predeterminadas" ON public.categorias_predeterminadas
    FOR SELECT TO anon USING (true);

DROP POLICY IF EXISTS "escritura_service_predeterminadas" ON public.categorias_predeterminadas;
CREATE POLICY "escritura_service_predeterminadas" ON public.categorias_predeterminadas
    FOR ALL TO service_role USING (true) WITH CHECK (true);

-- Insertar categorías por defecto para cada rubro
INSERT INTO public.categorias_predeterminadas (tipo_negocio, nombre, orden_visual) VALUES
('gastronomia', 'Entradas', 10),
('gastronomia', 'Platos Principales', 20),
('gastronomia', 'Postres', 30),
('gastronomia', 'Bebidas', 40),

('comida_rapida', 'Hamburguesas', 10),
('comida_rapida', 'Perros Calientes', 20),
('comida_rapida', 'Pizzas', 30),
('comida_rapida', 'Bebidas y Combos', 40),

('minimarket', 'Víveres y Alimentos', 10),
('minimarket', 'Charcutería y Lácteos', 20),
('minimarket', 'Bebidas y Licores', 30),
('minimarket', 'Limpieza y Hogar', 40),

('farmacia', 'Medicamentos', 10),
('farmacia', 'Cuidado Personal', 20),
('farmacia', 'Bienestar y Suplementos', 30),
('farmacia', 'Bebés y Maternidad', 40),

('boutique', 'Damas', 10),
('boutique', 'Caballeros', 20),
('boutique', 'Niños', 30),
('boutique', 'Calzado', 40),
('boutique', 'Accesorios', 50),

('ferreteria_repuestos', 'Herramientas', 10),
('ferreteria_repuestos', 'Electricidad', 20),
('ferreteria_repuestos', 'Plomería', 30),
('ferreteria_repuestos', 'Repuestos y Tornillos', 40),

('belleza_estetica', 'Cuidado Capilar', 10),
('belleza_estetica', 'Maquillaje', 20),
('belleza_estetica', 'Cuidado de la Piel', 30),
('belleza_estetica', 'Perfumería', 40),

('otros', 'Productos Generales', 10),
('otros', 'Ofertas Especiales', 20),
('otros', 'Nuevos Ingresos', 30)
ON CONFLICT DO NOTHING;


-- ============================================================
--  MIGRACIÓN: Si ya tienes la base de datos activa,
--  ejecuta estos comandos en tu SQL Editor de Supabase:
-- ============================================================
-- ALTER TABLE public.configuracion DROP CONSTRAINT IF EXISTS configuracion_tipo_negocio_check;
-- ALTER TABLE public.configuracion ADD CONSTRAINT configuracion_tipo_negocio_check CHECK (tipo_negocio IN ('gastronomia', 'comida_rapida', 'minimarket', 'farmacia', 'boutique', 'ferreteria_repuestos', 'belleza_estetica', 'otros'));
-- ALTER TABLE public.configuracion ADD COLUMN IF NOT EXISTS color_primario TEXT NOT NULL DEFAULT '#4F46E5';
-- ALTER TABLE public.configuracion ADD COLUMN IF NOT EXISTS logo_url TEXT NULL;
-- ALTER TABLE public.configuracion ADD COLUMN IF NOT EXISTS moneda_simbolo TEXT NOT NULL DEFAULT '$';
-- ALTER TABLE public.configuracion ADD COLUMN IF NOT EXISTS moneda_nombre TEXT NOT NULL DEFAULT 'USD';
-- ALTER TABLE public.configuracion ADD COLUMN IF NOT EXISTS costo_delivery NUMERIC(12, 2) NOT NULL DEFAULT 0.00;
-- ALTER TABLE public.configuracion ADD COLUMN IF NOT EXISTS direccion TEXT NOT NULL DEFAULT '';
-- ALTER TABLE public.configuracion ADD COLUMN IF NOT EXISTS horario TEXT NOT NULL DEFAULT '';
-- ALTER TABLE public.configuracion ADD COLUMN IF NOT EXISTS plantilla_whatsapp TEXT NULL;
--
-- ALTER TABLE public.productos ADD COLUMN IF NOT EXISTS stock INTEGER NULL DEFAULT NULL;
-- ALTER TABLE public.productos DROP CONSTRAINT IF EXISTS productos_stock_check;
-- ALTER TABLE public.productos ADD CONSTRAINT productos_stock_check CHECK (stock >= 0 OR stock IS NULL);
--
-- CREATE TABLE IF NOT EXISTS public.categorias_predeterminadas (
--     id                  BIGINT          PRIMARY KEY GENERATED ALWAYS AS IDENTITY,
--     tipo_negocio        TEXT            NOT NULL,
--     nombre              TEXT            NOT NULL,
--     orden_visual        INTEGER         NOT NULL DEFAULT 10
-- );
-- ALTER TABLE public.categorias_predeterminadas ENABLE ROW LEVEL SECURITY;
-- CREATE POLICY "lectura_publica_predeterminadas" ON public.categorias_predeterminadas FOR SELECT TO anon USING (true);
-- CREATE POLICY "escritura_service_predeterminadas" ON public.categorias_predeterminadas FOR ALL TO service_role USING (true) WITH CHECK (true);
--
-- INSERT INTO public.categorias_predeterminadas (tipo_negocio, nombre, orden_visual) VALUES
-- ('gastronomia', 'Entradas', 10),
-- ('gastronomia', 'Platos Principales', 20),
-- ('gastronomia', 'Postres', 30),
-- ('gastronomia', 'Bebidas', 40),
-- ('comida_rapida', 'Hamburguesas', 10),
-- ('comida_rapida', 'Perros Calientes', 20),
-- ('comida_rapida', 'Pizzas', 30),
-- ('comida_rapida', 'Bebidas y Combos', 40),
-- ('minimarket', 'Víveres y Alimentos', 10),
-- ('minimarket', 'Charcutería y Lácteos', 20),
-- ('minimarket', 'Bebidas y Licores', 30),
-- ('minimarket', 'Limpieza y Hogar', 40),
-- ('farmacia', 'Medicamentos', 10),
-- ('farmacia', 'Cuidado Personal', 20),
-- ('farmacia', 'Bienestar y Suplementos', 30),
-- ('farmacia', 'Bebés y Maternidad', 40),
-- ('boutique', 'Damas', 10),
-- ('boutique', 'Caballeros', 20),
-- ('boutique', 'Niños', 30),
-- ('boutique', 'Calzado', 40),
-- ('boutique', 'Accesorios', 50),
-- ('ferreteria_repuestos', 'Herramientas', 10),
-- ('ferreteria_repuestos', 'Electricidad', 20),
-- ('ferreteria_repuestos', 'Plomería', 30),
-- ('ferreteria_repuestos', 'Repuestos y Tornillos', 40),
-- ('belleza_estetica', 'Cuidado Capilar', 10),
-- ('belleza_estetica', 'Maquillaje', 20),
-- ('belleza_estetica', 'Cuidado de la Piel', 30),
-- ('belleza_estetica', 'Perfumería', 40),
-- ('otros', 'Productos Generales', 10),
-- ('otros', 'Ofertas Especiales', 20),
-- ('otros', 'Nuevos Ingresos', 30)
-- ON CONFLICT DO NOTHING;

-- ============================================================
--  Fin del esquema · PronttoGo · Montero Studio © 2026
-- ============================================================
