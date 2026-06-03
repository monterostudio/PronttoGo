-- =========================================================================
--  PronttoGo — Script de Datos de Prueba Premium
--  Para probar el catálogo visual, tarjeta de info y modal de login.
--  Ejecutar directamente en el editor SQL de Supabase.
-- =========================================================================

-- 1. CONFIGURACIÓN DEL COMERCIO (id = 1)
-- Se limpia/actualiza el registro de configuración principal.
INSERT INTO public.configuracion (
    id, nombre, telefono_whatsapp, tasa_dolar, tasa_tipo, admin_user, admin_password, 
    tipo_negocio, moneda_simbolo, moneda_nombre, costo_delivery, direccion, horario, 
    color_primario, hero_titulo, hero_subtitulo, social_instagram, social_tiktok, 
    social_facebook, social_telegram, correo_electronico, delivery_moneda
)
OVERRIDING SYSTEM VALUE
VALUES (
    1, 
    'Bistró Express', 
    '584121234567', 
    36.50, 
    'manual', 
    'admin', 
    'admin123', 
    'gastronomia', 
    'Bs.', 
    'VES', 
    3.50, 
    'Av. Principal Las Mercedes, Centro Comercial Paseo, Nivel PB, Local 4. Caracas, Venezuela', 
    'Lunes a Sábado: 11:30 AM - 10:00 PM | Domingos: 12:00 PM - 9:00 PM', 
    '#4F46E5', 
    'El sabor gourmet en la comodidad de tu hogar', 
    'Elige entre nuestras deliciosas especialidades, arma tu pedido y recíbelo por delivery directo a tu puerta en minutos.',
    'https://instagram.com/bistro.express', 
    'https://tiktok.com/@bistro.express', 
    'https://facebook.com/bistro.express.oficial', 
    'https://t.me/bistro_express', 
    'contacto@bistroexpress.com', 
    'USD'
)
ON CONFLICT (id) DO UPDATE SET
    nombre = EXCLUDED.nombre,
    telefono_whatsapp = EXCLUDED.telefono_whatsapp,
    tasa_dolar = EXCLUDED.tasa_dolar,
    tasa_tipo = EXCLUDED.tasa_tipo,
    admin_user = EXCLUDED.admin_user,
    admin_password = EXCLUDED.admin_password,
    tipo_negocio = EXCLUDED.tipo_negocio,
    moneda_simbolo = EXCLUDED.moneda_simbolo,
    moneda_nombre = EXCLUDED.moneda_nombre,
    costo_delivery = EXCLUDED.costo_delivery,
    direccion = EXCLUDED.direccion,
    horario = EXCLUDED.horario,
    color_primario = EXCLUDED.color_primario,
    hero_titulo = EXCLUDED.hero_titulo,
    hero_subtitulo = EXCLUDED.hero_subtitulo,
    social_instagram = EXCLUDED.social_instagram,
    social_tiktok = EXCLUDED.social_tiktok,
    social_facebook = EXCLUDED.social_facebook,
    social_telegram = EXCLUDED.social_telegram,
    correo_electronico = EXCLUDED.correo_electronico,
    delivery_moneda = EXCLUDED.delivery_moneda;


-- 2. CATEGORÍAS DE PRUEBA
-- Insertamos 3 categorías principales para organizar el menú.
INSERT INTO public.categorias (id, nombre_categoria, orden_visual)
OVERRIDING SYSTEM VALUE
VALUES 
(1, 'Entradas & Compartir', 10),
(2, 'Especialidades Bistró', 20),
(3, 'Bebidas & Postres', 30)
ON CONFLICT (id) DO UPDATE 
SET nombre_categoria = EXCLUDED.nombre_categoria, orden_visual = EXCLUDED.orden_visual;


-- 3. PRODUCTOS DE PRUEBA
-- Limpiamos productos anteriores en estas categorías para evitar duplicaciones
DELETE FROM public.productos WHERE categoria_id IN (1, 2, 3);

-- Insertamos una selección de productos deliciosos con imágenes reales optimizadas
INSERT INTO public.productos (id, categoria_id, nombre, descripcion, precio, imagen_url, disponible, stock)
OVERRIDING SYSTEM VALUE
VALUES
-- Categoría 1: Entradas & Compartir
(101, 1, 'Tequeños Crujientes (6 und)', 'Tradicionales deditos de queso blanco envueltos en masa crujiente y frita, acompañados con salsa tártara artesanal.', 5.50, 'https://images.unsplash.com/photo-1541532713592-79a0317b6b77?auto=format&fit=crop&w=400&q=80', true, 50),
(102, 1, 'Papas Bravas Rústicas', 'Papas rústicas doradas y crujientes con alioli de ajo asado y salsa brava picante ahumada de la casa.', 4.50, 'https://images.unsplash.com/photo-1573080496219-bb080dd4f877?auto=format&fit=crop&w=400&q=80', true, 30),
(103, 1, 'Aros de Cebolla Crujientes', 'Aros de cebolla tierna tempurizados en panko dorado, servidos con aderezo Honey Mustard.', 3.90, NULL, true, 40),

-- Categoría 2: Especialidades Bistró
(201, 2, 'Hamburguesa Trufa & Bacon', '150g de carne angus selecta a la parrilla, queso cheddar fundido, tocineta ahumada crujiente, cebolla caramelizada y salsa tártara de trufa en pan brioche.', 9.50, 'https://images.unsplash.com/photo-1568901346375-23c9450c58cd?auto=format&fit=crop&w=400&q=80', true, 15),
(202, 2, 'Pizza Margherita Premium', 'Salsa de tomates italianos San Marzano, queso mozzarella fresco, hojas de albahaca y aceite de oliva extra virgen.', 8.00, 'https://images.unsplash.com/photo-1604382355076-af4b0eb60143?auto=format&fit=crop&w=400&q=80', true, 20),
(203, 2, 'Costillas BBQ Ahumadas', 'Tiernas costillas de cerdo tiernizadas al horno y bañadas en salsa barbacoa artesanal al ron, servidas con papas fritas.', 12.50, 'https://images.unsplash.com/photo-1544025162-d76694265947?auto=format&fit=crop&w=400&q=80', true, 8),
(204, 2, 'Fettuccine Alfredo (Agotado)', 'Fettuccine en salsa clásica de crema de leche y queso parmesano, acompañada de filete de pechuga de pollo.', 8.90, NULL, true, 0), -- Producto agotado de prueba

-- Categoría 3: Bebidas & Postres
(301, 3, 'Tarta Tres Leches', 'Bizcocho húmedo bañado en salsa tradicional de tres leches y decorado con merengue suizo flameado y canela.', 3.50, 'https://images.unsplash.com/photo-1563729784474-d77dbb933a9e?auto=format&fit=crop&w=400&q=80', true, 12),
(302, 3, 'Té Frío Durazno (1 Litro)', 'Té negro natural saborizado con pulpa de durazno maduro, servido frío con rodajas de limón fresco.', 2.50, 'https://images.unsplash.com/photo-1556679343-c7306c1976bc?auto=format&fit=crop&w=400&q=80', true, 100),
(303, 3, 'Refresco de Lata (Fresa)', 'Lata de refresco sabor a fresa frío de 355ml.', 1.50, NULL, true, 80)
ON CONFLICT (id) DO UPDATE SET
    categoria_id = EXCLUDED.categoria_id,
    nombre = EXCLUDED.nombre,
    descripcion = EXCLUDED.descripcion,
    precio = EXCLUDED.precio,
    imagen_url = EXCLUDED.imagen_url,
    disponible = EXCLUDED.disponible,
    stock = EXCLUDED.stock;

-- 4. REINICIAR SECUENCIAS
-- Ajusta el contador de los generadores automáticos de ID para evitar colisiones futuras.
SELECT setval(pg_get_serial_sequence('public.categorias', 'id'), COALESCE(MAX(id), 1)) FROM public.categorias;
SELECT setval(pg_get_serial_sequence('public.productos', 'id'), COALESCE(MAX(id), 1)) FROM public.productos;
