-- ============================================================
--  PronttoGo — Datos de Demostración
--  Ejecutar en el SQL Editor de Supabase
--  IMPORTANTE: Reemplaza las URLs de imagen si deseas otras fotos.
-- ============================================================

-- ────────────────────────────────────────────────────────────
-- PASO 1: Configuración base del comercio (demo: Restaurante)
-- ────────────────────────────────────────────────────────────
UPDATE public.configuracion
SET 
    nombre              = 'La Cocina de Juan',
    telefono_whatsapp   = '584141234567',
    tipo_negocio        = 'gastronomia',
    moneda_simbolo      = 'Bs.',
    moneda_nombre       = 'VES',
    tasa_dolar          = 40.00,
    tasa_tipo           = 'manual',
    costo_delivery      = 3.00,
    direccion           = 'Av. Las Américas, Edificio Centro, Local 4',
    horario             = 'Lunes a Sábado: 11:00am - 9:00pm',
    color_primario      = '#E11D48'
WHERE id = 1;


-- ────────────────────────────────────────────────────────────
-- PASO 2: Categorías del menú
-- ────────────────────────────────────────────────────────────
INSERT INTO public.categorias (nombre_categoria, orden_visual) VALUES
    ('🥗 Entradas',         10),
    ('🍝 Platos Principales',20),
    ('🍰 Postres',          30),
    ('🥤 Bebidas',          40),
    ('🌟 Especiales del Día',5)
ON CONFLICT DO NOTHING;


-- ────────────────────────────────────────────────────────────
-- PASO 3: Productos de demostración con stock variado
-- (Ajusta categoria_id si es necesario luego de insertar las categorías)
-- ────────────────────────────────────────────────────────────

-- Obtener los IDs dinámicamente usando subqueries
INSERT INTO public.productos (categoria_id, nombre, descripcion, precio, imagen_url, disponible, stock)
VALUES

-- 🌟 ESPECIALES DEL DÍA
(
    (SELECT id FROM public.categorias WHERE nombre_categoria = '🌟 Especiales del Día' LIMIT 1),
    'Combo del Chef',
    'Plato del día con proteína a elección, arroz, ensalada y bebida incluida. Porción generosa y receta tradicional.',
    9.99,
    'https://images.unsplash.com/photo-1504674900247-0877df9cc836?w=400&q=80',
    true,
    NULL  -- Sin límite de stock
),
(
    (SELECT id FROM public.categorias WHERE nombre_categoria = '🌟 Especiales del Día' LIMIT 1),
    'Parrillada Familiar',
    'Para 4 personas: pollo, carne y chorizos a la parrilla con yuca frita, ensalada y salsas. ¡La favorita!',
    34.99,
    'https://images.unsplash.com/photo-1544025162-d76694265947?w=400&q=80',
    true,
    3  -- ¡Stock crítico para demostración!
),

-- 🥗 ENTRADAS
(
    (SELECT id FROM public.categorias WHERE nombre_categoria = '🥗 Entradas' LIMIT 1),
    'Tequeños Artesanales',
    'Porción de 8 tequeños crujientes de queso blanco artesanal. Acompañados con guasacaca casera.',
    4.50,
    'https://images.unsplash.com/photo-1601050690597-df0568f70950?w=400&q=80',
    true,
    NULL
),
(
    (SELECT id FROM public.categorias WHERE nombre_categoria = '🥗 Entradas' LIMIT 1),
    'Ensalada César Clásica',
    'Lechuga romana fresca, croutones artesanales, queso parmesano rallado y aderezo César de la casa.',
    5.99,
    'https://images.unsplash.com/photo-1546793665-c74683f339c1?w=400&q=80',
    true,
    NULL
),
(
    (SELECT id FROM public.categorias WHERE nombre_categoria = '🥗 Entradas' LIMIT 1),
    'Sopa del Día',
    'Preparada con ingredientes frescos del mercado. Servida caliente con pan tostado. Consulta la sopa del día.',
    3.50,
    'https://images.unsplash.com/photo-1547592166-23ac45744acd?w=400&q=80',
    true,
    NULL
),

-- 🍝 PLATOS PRINCIPALES
(
    (SELECT id FROM public.categorias WHERE nombre_categoria = '🍝 Platos Principales' LIMIT 1),
    'Pabellón Criollo',
    'El plato bandera venezolano: carne mechada, caraotas negras, arroz blanco y tajadas de plátano maduro.',
    8.50,
    'https://images.unsplash.com/photo-1626645738196-c2a7c87a8f58?w=400&q=80',
    true,
    NULL
),
(
    (SELECT id FROM public.categorias WHERE nombre_categoria = '🍝 Platos Principales' LIMIT 1),
    'Pollo a la Plancha',
    'Pechuga de pollo marinada a la plancha. Servida con arroz integral, vegetales salteados y ensalada verde.',
    9.50,
    'https://images.unsplash.com/photo-1598515214211-89d3c73ae83b?w=400&q=80',
    true,
    NULL
),
(
    (SELECT id FROM public.categorias WHERE nombre_categoria = '🍝 Platos Principales' LIMIT 1),
    'Pasta Boloñesa Premium',
    'Espaguetis al dente con salsa boloñesa de carne de res y cerdo, tomate natural y hierbas italianas. Gratinada al horno.',
    10.99,
    'https://images.unsplash.com/photo-1551183053-bf91798d047e?w=400&q=80',
    true,
    NULL
),
(
    (SELECT id FROM public.categorias WHERE nombre_categoria = '🍝 Platos Principales' LIMIT 1),
    'Lomo de Res al Vino',
    'Lomo de res en su punto perfecto, bañado en salsa de vino tinto reducida. Papas hasselback y ensalada.',
    16.99,
    'https://images.unsplash.com/photo-1558030006-450675393462?w=400&q=80',
    true,
    5  -- Stock crítico para demostración
),
(
    (SELECT id FROM public.categorias WHERE nombre_categoria = '🍝 Platos Principales' LIMIT 1),
    'Filete de Pescado al Limón',
    'Filete fresco de día en salsa de mantequilla y limón. Servido con puré de papa y vegetales al vapor.',
    12.50,
    'https://images.unsplash.com/photo-1467003909585-2f8a72700288?w=400&q=80',
    true,
    NULL
),

-- 🍰 POSTRES
(
    (SELECT id FROM public.categorias WHERE nombre_categoria = '🍰 Postres' LIMIT 1),
    'Quesillo de la Abuela',
    'Flan de huevo esponjoso hecho con la receta original de la abuela. Bañado en caramelo oscuro.',
    3.99,
    'https://images.unsplash.com/photo-1488477181946-6428a0291777?w=400&q=80',
    true,
    NULL
),
(
    (SELECT id FROM public.categorias WHERE nombre_categoria = '🍰 Postres' LIMIT 1),
    'Torta de Chocolate',
    'Porción de torta húmeda de chocolate belga con ganache y fresas frescas. Sin gluten disponible.',
    4.50,
    'https://images.unsplash.com/photo-1578985545062-69928b1d9587?w=400&q=80',
    true,
    NULL
),
(
    (SELECT id FROM public.categorias WHERE nombre_categoria = '🍰 Postres' LIMIT 1),
    'Helado Artesanal 3 Bolas',
    'Selección de 3 sabores de helado artesanal: vainilla, chocolate y fresa. Con toppings a elección.',
    3.50,
    'https://images.unsplash.com/photo-1563805042-7684c019e1cb?w=400&q=80',
    true,
    0  -- Agotado para demostración
),

-- 🥤 BEBIDAS
(
    (SELECT id FROM public.categorias WHERE nombre_categoria = '🥤 Bebidas' LIMIT 1),
    'Jugo Natural del Día',
    'Jugos frescos preparados al momento: parchita, guanábana, melón, tamarindo. Pregunta la disponibilidad.',
    2.50,
    'https://images.unsplash.com/photo-1622597467836-f3285f2131b8?w=400&q=80',
    true,
    NULL
),
(
    (SELECT id FROM public.categorias WHERE nombre_categoria = '🥤 Bebidas' LIMIT 1),
    'Refresco / Agua Mineral',
    'Selección de refrescos nacionales e importados en lata o botella. Agua mineral fría.',
    1.50,
    'https://images.unsplash.com/photo-1437418747212-8d9709afab22?w=400&q=80',
    true,
    NULL
),
(
    (SELECT id FROM public.categorias WHERE nombre_categoria = '🥤 Bebidas' LIMIT 1),
    'Café Espresso / Guayoyo',
    'Café venezolano de altura: espresso doble, guayoyo largo o cortado. Azúcar o edulcorante a elección.',
    1.99,
    'https://images.unsplash.com/photo-1509042239860-f550ce710b93?w=400&q=80',
    true,
    NULL
),
(
    (SELECT id FROM public.categorias WHERE nombre_categoria = '🥤 Bebidas' LIMIT 1),
    'Limonada Fría Premium',
    'Limonada artesanal con menta fresca, jengibre y hielo picado. Dulzura natural. Tamaño grande 500ml.',
    2.99,
    'https://images.unsplash.com/photo-1513558161293-cdaf765ed2fd?w=400&q=80',
    true,
    NULL
);


-- ============================================================
--  ✅ VERIFICAR LOS DATOS INSERTADOS
-- ============================================================
-- SELECT c.nombre_categoria, count(p.id) as total_productos 
-- FROM public.categorias c 
-- LEFT JOIN public.productos p ON p.categoria_id = c.id 
-- GROUP BY c.nombre_categoria 
-- ORDER BY c.orden_visual;

-- ============================================================
--  SCRIPT DE LIMPIEZA (Ejecutar SOLO si deseas borrar todos 
--  los datos de demostración y empezar de cero)
-- ============================================================
-- DELETE FROM public.productos;
-- DELETE FROM public.categorias;

-- ============================================================
--  Fin del script de demostración · PronttoGo · Montero Studio © 2026
-- ============================================================
