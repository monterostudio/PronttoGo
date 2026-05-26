<?php
/**
 * PronttoGo - Catálogo Público Responsivo (Single-Store)
 * Renderiza el menú digital adaptado a pantallas móviles y de escritorio.
 */

require_once __DIR__ . '/config.php';

// 1. OBTENER CONFIGURACIÓN DEL LOCAL (Fila única id = 1)
$response = supabase_request('GET', 'configuracion?id=eq.1');

if ($response['success'] && !empty($response['data'])) {
    $config = $response['data'][0];
} else {
    $config = [
        'nombre' => 'PronttoGo',
        'telefono_whatsapp' => '584121234567'
    ];
}

// Configuración de Tasa de Cambio y Moneda Local
$tasa_dolar = floatval($config['tasa_dolar'] ?? 1.00);
$tasa_tipo = $config['tasa_tipo'] ?? 'manual';
$moneda_local_nombre = ($tasa_tipo === 'trm') ? 'COP' : 'Bs.';

// 2. CONSULTAR CATEGORÍAS (Ordenadas)
$resCategorias = supabase_request('GET', 'categorias?order=orden_visual.asc');
$categorias = $resCategorias['success'] ? $resCategorias['data'] : [];

// 3. CONSULTAR PRODUCTOS DISPONIBLES
$resProductos = supabase_request('GET', 'productos?disponible=eq.true&order=id.asc');
$productos = $resProductos['success'] ? $resProductos['data'] : [];

// Agrupar productos por categoría
$productosPorCategoria = [];
foreach ($productos as $prod) {
    $productosPorCategoria[$prod['categoria_id']][] = $prod;
}

// Determinar si hay algún error de conexión o base de datos (visible solo en entorno local)
$dbError = null;
$isLocalhost = in_array($_SERVER['REMOTE_ADDR'] ?? '', ['127.0.0.1', '::1'])
    || (isset($_SERVER['HTTP_HOST']) && preg_match('/(localhost|127\.0\.0\.1|\.local|\.test)$/i', $_SERVER['HTTP_HOST']));

if ($isLocalhost) {
    if (!$resCategorias['success']) {
        $dbError = 'Error de Categorías: ' . ($resCategorias['error'] ?? $resCategorias['raw'] ?? 'Error de conexión.');
    } elseif (!$resProductos['success']) {
        $dbError = 'Error de Productos: ' . ($resProductos['error'] ?? $resProductos['raw'] ?? 'Error de conexión.');
    }
}
?>
<!DOCTYPE html>
<html lang="es" class="scroll-smooth overflow-x-hidden">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/svg+xml" href="/assets/favicon.svg">
    <link rel="icon" type="image/png" sizes="32x32" href="/assets/favicon.svg">
    <link rel="shortcut icon" href="/assets/favicon.svg">
    <link rel="apple-touch-icon" href="/assets/favicon.svg">
    <meta name="theme-color" content="#00CFBD">
    <title><?= h(!empty($config['nombre']) && $config['nombre'] !== 'Mi Tienda' ? $config['nombre'] : 'PronttoGo') ?></title>
    <script>
        // Evitar advertencia del CDN de Tailwind en la consola
        const _warn = console.warn;
        console.warn = (...args) => {
            if (args[0] && typeof args[0] === 'string' && args[0].includes('cdn.tailwindcss.com')) return;
            _warn(...args);
        };

        // Forzar tema claro eliminando cualquier rastro de configuración oscura
        localStorage.removeItem('theme');
        document.documentElement.classList.remove('dark');
    </script>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        // Desactivar el modo oscuro a nivel de Tailwind CDN (forzar modo clase sin clase activa)
        tailwind.config = {
            darkMode: 'class'
        };
    </script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; }
        .no-scrollbar::-webkit-scrollbar { display: none; }
        .no-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }
        
        /* Estilos personalizados para los pills de categorías */
        .mobile-category-pill {
            transition: all 0.2s ease-in-out;
        }
        .mobile-category-pill:hover {
            background-color: #F1F5F9 !important; /* bg-slate-100 */
            color: #1E293B !important;            /* text-slate-800 */
            border-color: #E2E8F0 !important;      /* border-slate-200 */
        }
        .mobile-category-pill.active {
            background-color: #E6FBF9 !important;  /* soft cyan bg */
            color: #00CFBD !important;             /* cyan text */
            border-color: #B2EFE9 !important;      /* soft cyan border */
        }
        .mobile-category-pill.active:hover {
            background-color: #D2F7F2 !important;  /* slightly darker soft cyan bg */
            color: #00B5A5 !important;             /* slightly darker cyan text */
            border-color: #9EEAE0 !important;
        }
    </style>
</head>
<body class="bg-[#F8FAFC] text-[#0F172A] min-h-screen flex flex-col overflow-x-hidden">

    <?php if ($dbError): ?>
        <!-- Barra de depuración en local para avisar errores de conexión de Supabase -->
        <div class="bg-red-600 text-white text-xs font-bold px-4 py-3 text-center shadow-md relative z-50">
            ⚠️ <strong>Error de Base de Datos (Local):</strong> <?= h($dbError) ?> | URL configurada: <code class="bg-red-700 px-1.5 py-0.5 rounded"><?= h(SUPABASE_URL) ?></code>
        </div>
    <?php endif; ?>

    <!-- Header -->
    <header class="h-16 bg-white/95 backdrop-blur-md border-b border-slate-100 sticky top-0 z-30 shadow-sm flex items-center">
        <div class="max-w-6xl w-full mx-auto px-4 sm:px-6 flex items-center justify-between">
            <div class="flex items-center space-x-2.5 min-w-0">
                <?php if (!empty($config['logo_url'])): ?>
                    <img src="<?= h($config['logo_url']) ?>" alt="<?= h($config['nombre']) ?>" class="h-8 w-auto object-contain rounded-lg shrink-0">
                    <span class="font-extrabold text-lg tracking-tight bg-gradient-to-r from-[#00CFBD] to-[#2A3543] bg-clip-text text-transparent truncate max-w-[140px] sm:max-w-none block"><?= h($config['nombre']) ?></span>
                <?php else: ?>
                    <?php if (strtolower($config['nombre'] ?? 'pronttogo') === 'pronttogo' || ($config['nombre'] ?? 'Mi Tienda') === 'Mi Tienda'): ?>
                        <?= get_logo_svg('h-8 w-auto shrink-0') ?>
                    <?php else: ?>
                        <span class="font-extrabold text-lg tracking-tight bg-gradient-to-r from-[#00CFBD] to-[#2A3543] bg-clip-text text-transparent truncate max-w-[140px] sm:max-w-none block"><?= h($config['nombre']) ?></span>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
            <a href="admin.php" class="text-xs font-bold text-slate-600 hover:text-slate-900 border border-slate-200 hover:border-slate-350 rounded-xl px-4 py-2 transition-all bg-white shadow-sm shrink-0">
                Iniciar Sesión
            </a>
        </div>
    </header>

    <!-- Full Hero Section (Presentación de Ancho Completo Premium en Blanco) -->
    <div class="relative w-full bg-gradient-to-br from-[#F0FDFB] via-[#E2F8F5] to-[#F0FDFB] text-slate-800 overflow-hidden border-b border-[#00CFBD]/15">
        <!-- Luces decorativas de fondo -->
        <div class="absolute -right-10 top-0 w-96 h-96 bg-[#00CFBD]/5 rounded-full blur-3xl pointer-events-none"></div>
        <div class="absolute -left-10 bottom-0 w-96 h-96 bg-[#2A3543]/4 rounded-full blur-3xl pointer-events-none"></div>
        <div class="absolute left-1/2 top-1/2 -translate-x-1/2 -translate-y-1/2 w-[600px] h-40 bg-[#00CFBD]/3 rounded-full blur-3xl pointer-events-none"></div>
        
        <!-- Contenido centrado -->
        <div class="max-w-6xl mx-auto px-4 sm:px-6 pt-12 pb-10 md:pt-16 md:pb-12 flex flex-col items-center text-center space-y-4 relative z-10">

            <!-- Logo o imagen personalizada -->
            <?php if (!empty($config['logo_url'])): ?>
                <img src="<?= h($config['logo_url']) ?>" alt="<?= h($config['nombre']) ?>" class="h-20 w-auto object-contain rounded-2xl shadow-md bg-white p-2.5 border border-slate-100">
            <?php elseif (strtolower($config['nombre'] ?? 'pronttogo') === 'pronttogo' || ($config['nombre'] ?? 'Mi Tienda') === 'Mi Tienda'): ?>
                <!-- Mostrar solo el logo SVG sin repetir el nombre en texto -->
                <div class="rounded-2xl shadow-md bg-white px-6 py-4 inline-flex items-center justify-center border border-slate-100 hover:scale-[1.02] transition-transform duration-300">
                    <?= get_logo_svg('h-12 w-auto') ?>
                </div>
            <?php else: ?>
                <!-- Nombre del comercio personalizado -->
                <span class="text-4xl md:text-5xl font-extrabold tracking-tight bg-gradient-to-r from-[#00CFBD] to-[#2A3543] bg-clip-text text-transparent">
                    <?= h($config['nombre']) ?>
                </span>
            <?php endif; ?>

            <!-- Tagline principal -->
            <div class="space-y-1.5 max-w-lg pt-1">
                <p class="text-xl md:text-2xl font-extrabold text-[#2A3543] tracking-tight leading-snug">
                    Tu menú digital,
                    <span class="bg-gradient-to-r from-[#00CFBD] to-[#00B5A5] bg-clip-text text-transparent">siempre disponible</span>
                </p>
                <p class="text-sm text-slate-500 leading-relaxed font-medium">
                    Explora nuestras especialidades, arma tu pedido y envíalo directo por WhatsApp en segundos.
                </p>
            </div>
        </div>
    </div>

    <!-- Contenedor del Catálogo (Sin Sidebar, Ancho Completo) -->
    <main class="max-w-6xl w-full mx-auto px-4 sm:px-6 py-8 flex-1 pb-24 md:pb-12">
        <div class="w-full space-y-6">
            <!-- Buscador de Productos -->
            <div class="relative w-full shadow-sm rounded-2xl bg-white border border-slate-100 p-2 flex items-center space-x-2.5 transition-all focus-within:ring-2 focus-within:ring-[#00CFBD]/30 focus-within:border-[#00CFBD]">
                <div class="pl-3.5 text-slate-400">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                    </svg>
                </div>
                <input type="text" id="search-input" placeholder="Buscar especialidades..." class="w-full bg-transparent border-0 outline-none text-slate-800 text-sm placeholder-slate-400 pr-4 py-1.5" autocomplete="off" />
                <button id="search-clear-btn" class="hidden pr-3 text-slate-400 hover:text-slate-600 font-bold text-sm">✕</button>
            </div>

            <!-- Categorías Deslizables (Sticky) - Unificado para Escritorio y Móvil -->
            <?php if (!empty($categorias)): ?>
                <div class="-mx-4 sm:-mx-6 px-4 sm:px-6 py-3 border-y border-slate-100 bg-white sticky top-16 z-20 shadow-sm">
                    <nav class="flex space-x-2.5 overflow-x-auto no-scrollbar scroll-smooth">
                        <?php foreach ($categorias as $cat): 
                            if (empty($productosPorCategoria[$cat['id']])) continue;
                        ?>
                            <a href="#cat-<?= h($cat['id']) ?>" 
                               class="mobile-category-pill px-4 py-2 bg-slate-50 border border-slate-100 text-slate-600 rounded-xl font-bold text-xs whitespace-nowrap">
                                <?= h($cat['nombre_categoria']) ?>
                            </a>
                        <?php endforeach; ?>
                    </nav>
                </div>
            <?php endif; ?>

            <?php if (empty($productos)): ?>
                <!-- Catálogo Vacío (Simple y Minimalista) -->
                <div class="text-center py-20 max-w-sm mx-auto space-y-3">
                    <div class="w-12 h-12 bg-slate-50 text-slate-400 rounded-full flex items-center justify-center mx-auto text-xl">
                        🍔
                    </div>
                    <h3 class="font-bold text-slate-800 text-sm">El catálogo está vacío</h3>
                    <p class="text-slate-400 text-xs max-w-xs mx-auto leading-relaxed">
                        Aún no se han añadido productos. Inicia sesión en el panel para comenzar a cargar tu catálogo.
                    </p>
                    <div class="pt-2">
                        <a href="admin.php" class="inline-flex items-center gap-1 px-4 py-2 border border-slate-200 text-slate-600 hover:bg-slate-50 rounded-xl font-bold text-xs transition-all">
                            Ir al Panel ↗
                        </a>
                    </div>
                </div>
            <?php else: ?>
                <!-- Mensaje de Búsqueda sin Resultados -->
                <div id="search-no-results" class="hidden text-center py-16 space-y-3">
                    <div class="w-12 h-12 bg-slate-50 text-slate-400 rounded-full flex items-center justify-center mx-auto text-xl">
                        🔍
                    </div>
                    <h3 class="font-bold text-slate-800 text-sm">No se encontraron productos</h3>
                    <p class="text-slate-400 text-xs max-w-xs mx-auto leading-relaxed">
                        Intenta con otra palabra clave o explora las categorías del menú.
                    </p>
                </div>

                <?php foreach ($categorias as $cat): 
                    $items = $productosPorCategoria[$cat['id']] ?? [];
                    if (empty($items)) continue;
                ?>
                    <section id="cat-<?= h($cat['id']) ?>" class="scroll-mt-28 space-y-4">
                        <div class="flex items-center space-x-3">
                            <h2 class="text-base md:text-lg font-extrabold tracking-tight text-slate-850"><?= h($cat['nombre_categoria']) ?></h2>
                            <div class="h-0.5 flex-1 bg-gradient-to-r from-[#00CFBD] to-[#2A3543] opacity-20 rounded"></div>
                        </div>

                        <!-- Grid de Productos (1 en móvil, 2 en tablet, 3 en pantallas grandes) -->
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                            <?php foreach ($items as $prod): ?>
                                <?php if (!empty($prod['imagen_url'])): ?>
                                    <!-- Tarjeta con Imagen -->
                                    <div onclick='openProductModal(<?= json_encode($prod) ?>)' class="cursor-pointer bg-white p-5 md:p-6 rounded-2xl border border-slate-100 shadow-sm hover:shadow-md hover:border-slate-200 transition-all duration-300 flex items-stretch justify-between gap-4 relative group">
                                        <div class="flex-1 flex flex-col justify-between min-w-0 py-0.5">
                                            <div class="space-y-1">
                                                <h3 class="font-extrabold text-slate-900 text-sm md:text-base leading-snug group-hover:text-[#00CFBD] transition-colors"><?= h($prod['nombre']) ?></h3>
                                                <?php if (!empty($prod['descripcion'])): ?>
                                                    <p class="text-xs text-slate-500 line-clamp-2 md:line-clamp-3 leading-relaxed"><?= h($prod['descripcion']) ?></p>
                                                <?php endif; ?>
                                            </div>
                                            <div>
                                                <span class="block font-black text-sm md:text-base text-slate-900 mt-2">$<?= number_format($prod['precio'], 2) ?></span>
                                                <?php if ($tasa_dolar > 1): ?>
                                                    <span class="block text-xs font-bold text-slate-500 mt-0.5"><?= $moneda_local_nombre ?> <?= number_format($prod['precio'] * $tasa_dolar, $tasa_tipo === 'trm' ? 0 : 2, ',', '.') ?></span>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                        <div class="flex flex-col items-center justify-between shrink-0 gap-3 w-16 sm:w-20 md:w-24">
                                            <img src="<?= h($prod['imagen_url']) ?>" alt="<?= h($prod['nombre']) ?>" class="w-16 h-16 sm:w-20 sm:h-20 md:w-24 md:h-24 object-cover rounded-xl bg-slate-50 border border-slate-100 shadow-sm group-hover:scale-[1.02] transition-transform duration-300">
                                            <button onclick='event.stopPropagation(); addToCart(<?= json_encode([
                                                'id' => $prod['id'],
                                                'nombre' => $prod['nombre'],
                                                'precio' => floatval($prod['precio'])
                                            ]) ?>)' class="w-full bg-[#00CFBD] hover:bg-[#00B5A5] text-white font-bold text-center text-[10px] md:text-xs py-1.5 rounded-full shadow-md transition-all active:scale-95 whitespace-nowrap">
                                                + Agregar
                                            </button>
                                        </div>
                                    </div>
                                <?php else: ?>
                                    <!-- Tarjeta sin Imagen -->
                                    <div onclick='openProductModal(<?= json_encode($prod) ?>)' class="cursor-pointer bg-white p-5 md:p-6 rounded-2xl border border-slate-100 shadow-sm hover:shadow-md hover:border-slate-200 transition-all duration-300 flex flex-col justify-between min-h-[120px] group">
                                        <div class="space-y-1">
                                            <h3 class="font-extrabold text-slate-900 text-sm md:text-base leading-snug group-hover:text-[#00CFBD] transition-colors"><?= h($prod['nombre']) ?></h3>
                                            <?php if (!empty($prod['descripcion'])): ?>
                                                <p class="text-xs text-slate-500 line-clamp-2 md:line-clamp-3 leading-relaxed"><?= h($prod['descripcion']) ?></p>
                                            <?php endif; ?>
                                        </div>
                                        <div class="flex items-center justify-between mt-4 pt-2 border-t border-slate-50">
                                            <div>
                                                <span class="font-black text-sm md:text-base text-slate-900 block">$<?= number_format($prod['precio'], 2) ?></span>
                                                <?php if ($tasa_dolar > 1): ?>
                                                    <span class="text-xs font-bold text-slate-500 block mt-0.5"><?= $moneda_local_nombre ?> <?= number_format($prod['precio'] * $tasa_dolar, $tasa_tipo === 'trm' ? 0 : 2, ',', '.') ?></span>
                                                <?php endif; ?>
                                            </div>
                                            <button onclick='event.stopPropagation(); addToCart(<?= json_encode([
                                                'id' => $prod['id'],
                                                'nombre' => $prod['nombre'],
                                                'precio' => floatval($prod['precio'])
                                            ]) ?>)' class="bg-[#00CFBD] hover:bg-[#00B5A5] text-white font-bold text-[10px] md:text-xs py-1.5 px-4.5 rounded-full shadow-md transition-all active:scale-95 whitespace-nowrap">
                                                + Agregar
                                            </button>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </div>
                    </section>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </main>

    <!-- Footer Bar -->
    <footer class="bg-white border-t border-slate-100 py-5 mt-auto">
        <div class="max-w-6xl w-full mx-auto px-4 sm:px-6 flex flex-col sm:flex-row items-center justify-between gap-3 text-xs font-semibold text-slate-500">
            <div class="flex items-center gap-4">
                <span>&copy; 2026 <?= h(!empty($config['nombre']) && $config['nombre'] !== 'Mi Tienda' ? $config['nombre'] : 'PronttoGo') ?></span>
                <a href="/legal" class="text-slate-400 hover:text-[#00CFBD] transition-colors">Términos y Privacidad</a>
            </div>
            <a href="admin.php" class="text-[10px] uppercase font-bold text-slate-400 hover:text-slate-600 transition-colors flex items-center gap-1">
                <span>Powered by</span>
                <span class="text-[#00CFBD] font-extrabold">Montero Studio</span>
            </a>
        </div>
    </footer>

    <!-- Carrito Flotante (JS) -->
    <div id="floating-cart" class="fixed bottom-0 left-0 right-0 p-4 bg-transparent max-w-md mx-auto z-40 hidden">
        <button onclick="toggleCartDrawer(true)" class="w-full py-4 px-6 bg-gradient-to-r from-[#00CFBD] to-[#00B5A5] hover:opacity-95 text-white font-bold text-sm rounded-2xl shadow-xl flex justify-between items-center transition-all active:scale-98">
            <div class="flex items-center space-x-2">
                <span>🛒</span>
                <span id="cart-count">0 artículos</span>
            </div>
            <div class="text-right">
                <span id="cart-total" class="block font-black text-sm md:text-base">$0.00</span>
                <?php if ($tasa_dolar > 1): ?>
                    <span id="cart-total-local" class="block text-[10px] opacity-90 font-bold font-mono"></span>
                <?php endif; ?>
            </div>
        </button>
    </div>

    <!-- Drawer del Carrito -->
    <div id="cart-drawer" class="fixed inset-0 z-50 hidden transition-opacity duration-300">
        <div onclick="toggleCartDrawer(false)" class="absolute inset-0 bg-slate-955/40 backdrop-blur-sm"></div>
        
        <!-- Panel Desplizable -->
        <div class="absolute bottom-0 left-0 right-0 max-h-[85vh] bg-white rounded-t-3xl shadow-2xl border-t border-slate-100 flex flex-col max-w-md mx-auto overflow-hidden">
            <div class="px-6 py-5 border-b border-slate-50 flex items-center justify-between">
                <div>
                    <h3 class="font-extrabold text-lg text-slate-800">Mi Pedido</h3>
                    <p class="text-xs text-slate-400">Verifica los artículos seleccionados</p>
                </div>
                <div class="flex items-center space-x-3">
                    <button onclick="clearCart()" class="text-xs font-bold text-red-500 hover:text-red-750 transition-colors">
                        Vaciar
                    </button>
                    <button onclick="toggleCartDrawer(false)" class="w-8 h-8 rounded-full bg-slate-50 hover:bg-slate-100 flex items-center justify-center font-bold text-slate-500">
                        ✕
                    </button>
                </div>
            </div>

            <div id="cart-items" class="p-6 overflow-y-auto space-y-1 divide-y divide-slate-50 flex-1">
                <!-- Se rellena por JS de forma segura -->
            </div>

            <div class="p-6 border-t border-slate-50 space-y-4 bg-slate-50/50">
                <div class="flex justify-between items-center font-extrabold text-slate-800">
                    <span>Total a pagar</span>
                    <div class="text-right">
                        <span id="drawer-total" class="text-xl block text-slate-800">$0.00</span>
                        <?php if ($tasa_dolar > 1): ?>
                            <span id="drawer-total-local" class="text-sm font-bold text-slate-500 block mt-0.5 font-mono"></span>
                        <?php endif; ?>
                    </div>
                </div>
                <button onclick="checkoutOrder()" class="w-full py-4 px-6 bg-gradient-to-r from-[#00CFBD] to-[#00B5A5] hover:opacity-95 text-white font-bold text-sm rounded-xl shadow-lg transition-all flex justify-between items-center active:scale-98">
                    <span>Enviar Pedido por WhatsApp</span>
                    <span>→</span>
                </button>
            </div>
        </div>
    </div>

    <!-- Modal de Detalle de Producto -->
    <div id="product-modal" class="fixed inset-0 z-50 hidden transition-opacity duration-300">
        <!-- Backdrop -->
        <div onclick="closeProductModal()" class="absolute inset-0 bg-slate-900/40 backdrop-blur-sm"></div>
        
        <!-- Contenedor del Modal -->
        <div class="absolute bottom-0 md:bottom-auto md:top-1/2 md:left-1/2 md:-translate-x-1/2 md:-translate-y-1/2 w-full md:max-w-lg bg-white rounded-t-3xl md:rounded-3xl shadow-2xl border-t md:border border-slate-100 flex flex-col overflow-hidden max-h-[90vh] transition-all duration-300">
            <!-- Header Modal -->
            <div class="px-6 py-4 border-b border-slate-50 flex items-center justify-between sticky top-0 bg-white z-10">
                <h3 class="font-extrabold text-base text-slate-800">Detalle del Producto</h3>
                <button onclick="closeProductModal()" class="w-8 h-8 rounded-full bg-slate-50 hover:bg-slate-100 flex items-center justify-center font-bold text-slate-500">
                    ✕
                </button>
            </div>

            <!-- Cuerpo Modal (Scrollable) -->
            <div class="p-6 overflow-y-auto space-y-5">
                <!-- Imagen -->
                <div id="modal-img-container" class="w-full h-48 md:h-56 rounded-2xl bg-slate-50 overflow-hidden hidden border border-slate-100">
                    <img id="modal-img" src="" alt="" class="w-full h-full object-cover">
                </div>

                <div class="space-y-1">
                    <h2 id="modal-title" class="font-black text-slate-900 text-lg md:text-xl leading-snug"></h2>
                    <p id="modal-desc" class="text-xs md:text-sm text-slate-500 leading-relaxed"></p>
                </div>

                <div class="flex items-center justify-between py-3 border-y border-slate-50">
                    <span class="text-xs font-bold text-slate-400 uppercase tracking-wider">Precio</span>
                    <div class="text-right">
                        <span id="modal-price" class="text-lg md:text-xl font-black text-slate-900"></span>
                        <span id="modal-price-local" class="block text-xs font-bold text-slate-500 mt-0.5"></span>
                    </div>
                </div>

                <!-- Campo de notas personalizadas -->
                <div class="space-y-2">
                    <label for="modal-notes" class="block text-xs font-bold text-slate-500 uppercase tracking-wider">Notas / Instrucciones adicionales</label>
                    <textarea id="modal-notes" placeholder="Ej. sin cebolla, salsa aparte, bien cocido..." class="w-full p-3.5 bg-slate-50 border border-slate-150 rounded-xl text-xs md:text-sm text-slate-800 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-[#00CFBD]/30 focus:border-[#00CFBD] resize-none h-20" maxlength="150"></textarea>
                </div>
            </div>

            <!-- Footer Modal (Controles y Botón de compra) -->
            <div class="p-6 border-t border-slate-50 bg-slate-50/50 flex flex-col sm:flex-row gap-4 sticky bottom-0 z-10">
                <!-- Selector de Cantidad -->
                <div class="flex items-center justify-center sm:justify-start space-x-3.5 bg-slate-100 px-4 py-2.5 rounded-xl shrink-0">
                    <button onclick="updateModalQuantity(-1)" class="text-slate-600 hover:text-slate-900 font-black text-base transition-colors w-6 h-6 flex items-center justify-center">−</button>
                    <span id="modal-qty" class="font-black text-sm w-6 text-center text-slate-800">1</span>
                    <button onclick="updateModalQuantity(1)" class="text-slate-600 hover:text-slate-900 font-black text-base transition-colors w-6 h-6 flex items-center justify-center">+</button>
                </div>

                <!-- Botón de compra -->
                <button id="modal-add-btn" class="flex-1 py-3 px-6 bg-gradient-to-r from-[#00CFBD] to-[#00B5A5] hover:opacity-95 text-white font-black text-sm rounded-xl shadow-lg transition-all flex justify-between items-center active:scale-98">
                    <span>Agregar al pedido</span>
                    <span id="modal-subtotal" class="font-black"></span>
                </button>
            </div>
        </div>
    </div>></span>
                </button>
            </div>
        </div>
    </div>

    <!-- Script del Carrito (Vanilla JS) -->
    <script>
        const whatsappNumber = <?= json_encode($config['telefono_whatsapp']) ?>;
        const cartKey = 'cart_pronttogo';

        let isScrolling = false;
        let scrollTimeout;

        // Variables del Modal de Detalle
        let currentModalProduct = null;
        let currentModalQuantity = 1;

        // Abrir Modal de Producto
        function openProductModal(product) {
            currentModalProduct = product;
            currentModalQuantity = 1;
            
            const modal = document.getElementById('product-modal');
            const modalImgContainer = document.getElementById('modal-img-container');
            const modalImg = document.getElementById('modal-img');
            const modalTitle = document.getElementById('modal-title');
            const modalDesc = document.getElementById('modal-desc');
            const modalPrice = document.getElementById('modal-price');
            const modalPriceLocal = document.getElementById('modal-price-local');
            const modalNotes = document.getElementById('modal-notes');
            
            // Limpiar notas previas
            modalNotes.value = '';
            
            // Llenar datos básicos
            modalTitle.textContent = product.nombre;
            modalDesc.textContent = product.descripcion || 'Sin descripción disponible.';
            modalPrice.textContent = `$${parseFloat(product.precio).toFixed(2)}`;
            
            // Cálculo de moneda local
            const tasaDolar = parseFloat(<?= json_encode($tasa_dolar) ?>);
            const monedaNombre = <?= json_encode($moneda_local_nombre) ?>;
            const tasaTipo = <?= json_encode($tasa_tipo) ?>;
            if (tasaDolar > 1) {
                const priceLocal = parseFloat(product.precio) * tasaDolar;
                const formattedLocal = tasaTipo === 'trm' 
                    ? priceLocal.toLocaleString('es-CO', { minimumFractionDigits: 0, maximumFractionDigits: 0 })
                    : priceLocal.toLocaleString('es-VE', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                modalPriceLocal.textContent = `${monedaNombre} ${formattedLocal}`;
                modalPriceLocal.classList.remove('hidden');
            } else {
                modalPriceLocal.classList.add('hidden');
            }

            // Imagen
            if (product.imagen_url) {
                modalImg.src = product.imagen_url;
                modalImg.alt = product.nombre;
                modalImgContainer.classList.remove('hidden');
            } else {
                modalImgContainer.classList.add('hidden');
            }

            updateModalUI();

            // Mostrar modal
            modal.classList.remove('hidden');
            setTimeout(() => {
                modal.classList.add('opacity-100');
            }, 10);
        }

        // Cerrar Modal de Producto
        function closeProductModal() {
            const modal = document.getElementById('product-modal');
            modal.classList.remove('opacity-100');
            setTimeout(() => {
                modal.classList.add('hidden');
            }, 300);
        }

        // Modificar cantidad en modal
        function updateModalQuantity(change) {
            currentModalQuantity += change;
            if (currentModalQuantity < 1) {
                currentModalQuantity = 1;
            }
            updateModalUI();
        }

        // Actualizar UI del modal (Subtotal)
        function updateModalUI() {
            document.getElementById('modal-qty').textContent = currentModalQuantity;
            const price = parseFloat(currentModalProduct.precio);
            const subtotal = price * currentModalQuantity;
            document.getElementById('modal-subtotal').textContent = `$${subtotal.toFixed(2)}`;
        }

        // Animación volar al carrito
        function triggerFlyAnimation(startElement) {
            const floatingCart = document.getElementById('floating-cart');
            if (!floatingCart) return;

            // Asegurarnos de que el carrito no esté oculto para obtener su posición
            const wasHidden = floatingCart.classList.contains('hidden');
            if (wasHidden) {
                floatingCart.classList.remove('hidden');
            }

            const rect = startElement.getBoundingClientRect();
            const cartRect = floatingCart.getBoundingClientRect();

            if (wasHidden) {
                floatingCart.classList.add('hidden');
            }

            // Crear la partícula
            const particle = document.createElement('div');
            particle.className = 'fixed z-50 w-6 h-6 bg-[#00CFBD] rounded-full pointer-events-none transition-all duration-750 ease-in-out flex items-center justify-center text-white text-[10px] font-black shadow-lg';
            particle.textContent = '+1';
            particle.style.left = `${rect.left + rect.width / 2 - 12}px`;
            particle.style.top = `${rect.top + rect.height / 2 - 12}px`;
            document.body.appendChild(particle);

            // Animar hacia el carrito flotante
            setTimeout(() => {
                particle.style.transform = 'scale(0.3)';
                particle.style.opacity = '0.5';
                particle.style.left = `${cartRect.left + cartRect.width / 2 - 12}px`;
                particle.style.top = `${cartRect.top + cartRect.height / 2 - 12}px`;
            }, 30);

            // Eliminar y aplicar bounce
            setTimeout(() => {
                particle.remove();
                floatingCart.classList.add('scale-105', 'rotate-3');
                setTimeout(() => {
                    floatingCart.classList.remove('scale-105', 'rotate-3');
                }, 150);
            }, 720);
        }

        // Asociar evento del botón de agregar en modal
        window.addEventListener('DOMContentLoaded', () => {
            const modalAddBtn = document.getElementById('modal-add-btn');
            if (modalAddBtn) {
                modalAddBtn.addEventListener('click', function(e) {
                    if (!currentModalProduct) return;
                    const notes = document.getElementById('modal-notes').value.trim();
                    addToCartWithDetails(currentModalProduct, currentModalQuantity, notes);
                    triggerFlyAnimation(e.target);
                    closeProductModal();
                });
            }
        });

        function handleCategoryLinkClick(e) {
            isScrolling = true;
            clearTimeout(scrollTimeout);
            
            const href = this.getAttribute('href');
            if (href && href.startsWith('#')) {
                const id = href.substring(1);
                setActiveCategory(id);
            }
            
            scrollTimeout = setTimeout(() => {
                isScrolling = false;
            }, 800);
        }

        // ScrollSpy para Categorías
        window.addEventListener('DOMContentLoaded', () => {
            const observerOptions = {
                root: null,
                rootMargin: '-10% 0px -75% 0px',
                threshold: 0
            };

            const observer = new IntersectionObserver((entries) => {
                if (isScrolling) return;
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        const id = entry.target.id;
                        setActiveCategory(id);
                    }
                });
            }, observerOptions);

            document.querySelectorAll('section[id^="cat-"]').forEach(section => {
                observer.observe(section);
            });

            // Registrar eventos de clic para detener temporalmente el ScrollSpy y fijar el activo inmediatamente
            document.querySelectorAll('.mobile-category-pill').forEach(link => {
                link.addEventListener('click', handleCategoryLinkClick);
            });
        });

        function setActiveCategory(id) {
            // Activar pill correspondiente en la barra de categorías unificada
            document.querySelectorAll('.mobile-category-pill').forEach(pill => {
                if (pill.getAttribute('href') === `#${id}`) {
                    pill.classList.add('active');
                    
                    // Centrar el elemento en el scroll horizontal de forma suave
                    const container = pill.parentElement;
                    if (container) {
                        const containerRect = container.getBoundingClientRect();
                        const pillRect = pill.getBoundingClientRect();
                        const scrollLeft = container.scrollLeft;
                        const targetScrollLeft = scrollLeft + (pillRect.left - containerRect.left) - (containerRect.width / 2) + (pillRect.width / 2);
                        container.scrollTo({
                            left: targetScrollLeft,
                            behavior: 'smooth'
                        });
                    }
                } else {
                    pill.classList.remove('active');
                }
            });
        }

        function getCart() {
            try {
                const cartData = localStorage.getItem(cartKey);
                return cartData ? JSON.parse(cartData) : [];
            } catch (e) {
                console.error(e);
                return [];
            }
        }

        function saveCart(cart) {
            try {
                localStorage.setItem(cartKey, JSON.stringify(cart));
                updateCartUI();
            } catch (e) {
                console.error(e);
            }
        }

        function addToCart(product, event) {
            const evt = event || window.event;
            addToCartWithDetails(product, 1, '');
            if (evt && evt.target) {
                triggerFlyAnimation(evt.target);
            }
        }

        // Agregar al carrito con detalles (notas y cantidad)
        function addToCartWithDetails(product, quantity, notes) {
            let cart = getCart();
            const existingItem = cart.find(item => item.id === product.id && (item.notes || '') === (notes || ''));
            
            if (existingItem) {
                existingItem.quantity += quantity;
            } else {
                cart.push({
                    id: product.id,
                    nombre: product.nombre,
                    precio: parseFloat(product.precio),
                    quantity: quantity,
                    notes: notes || ''
                });
            }
            saveCart(cart);
            
            const bar = document.getElementById('floating-cart');
            bar.classList.add('scale-105');
            setTimeout(() => bar.classList.remove('scale-105'), 150);
        }

        function updateQuantity(productId, change, notes = '') {
            let cart = getCart();
            const item = cart.find(item => item.id === productId && (item.notes || '') === (notes || ''));
            if (!item) return;

            item.quantity += change;
            if (item.quantity <= 0) {
                cart = cart.filter(item => !(item.id === productId && (item.notes || '') === (notes || '')));
            }
            saveCart(cart);
            
            if (cart.length === 0) {
                toggleCartDrawer(false);
            }
        }

        function clearCart() {
            if (confirm('¿Seguro que deseas vaciar tu carrito de compras?')) {
                saveCart([]);
                toggleCartDrawer(false);
            }
        }

        function toggleCartDrawer(show) {
            const drawer = document.getElementById('cart-drawer');
            if (show) {
                drawer.classList.remove('hidden');
                setTimeout(() => drawer.classList.add('opacity-100'), 10);
            } else {
                drawer.classList.remove('opacity-100');
                setTimeout(() => drawer.classList.add('hidden'), 300);
            }
        }

        function updateCartUI() {
            const cart = getCart();
            const floatingCart = document.getElementById('floating-cart');
            const cartCount = document.getElementById('cart-count');
            const cartTotal = document.getElementById('cart-total');
            const drawerTotal = document.getElementById('drawer-total');
            const cartItemsContainer = document.getElementById('cart-items');

            if (cart.length === 0) {
                floatingCart.classList.add('hidden');
                return;
            }

            let totalItems = 0;
            let totalPrice = 0;
            cart.forEach(item => {
                totalItems += item.quantity;
                totalPrice += item.precio * item.quantity;
            });

            cartCount.textContent = `${totalItems} ${totalItems === 1 ? 'artículo' : 'artículos'}`;
            cartTotal.textContent = `$${totalPrice.toFixed(2)}`;
            drawerTotal.textContent = `$${totalPrice.toFixed(2)}`;

            // Tasa de cambio local dinámica
            const tasaDolar = parseFloat(<?= json_encode($tasa_dolar) ?>);
            const monedaNombre = <?= json_encode($moneda_local_nombre) ?>;
            const tasaTipo = <?= json_encode($tasa_tipo) ?>;
            if (tasaDolar > 1) {
                const totalLocal = totalPrice * tasaDolar;
                const formattedLocal = tasaTipo === 'trm' 
                    ? totalLocal.toLocaleString('es-CO', { minimumFractionDigits: 0, maximumFractionDigits: 0 })
                    : totalLocal.toLocaleString('es-VE', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                
                const cartTotalLocal = document.getElementById('cart-total-local');
                const drawerTotalLocal = document.getElementById('drawer-total-local');
                if (cartTotalLocal) cartTotalLocal.textContent = `${monedaNombre} ${formattedLocal}`;
                if (drawerTotalLocal) drawerTotalLocal.textContent = `${monedaNombre} ${formattedLocal}`;
            }

            // Limpiar de forma segura
            cartItemsContainer.replaceChildren();

            // Construir DOM seguro
            cart.forEach(item => {
                const itemEl = document.createElement('div');
                itemEl.className = "flex justify-between items-center py-4 border-b border-slate-100 first:pt-2 last:border-b-0";

                const infoEl = document.createElement('div');
                
                const nameEl = document.createElement('h4');
                nameEl.className = "font-bold text-sm text-slate-800";
                nameEl.textContent = item.nombre;

                infoEl.appendChild(nameEl);

                // Notas / Instrucciones del producto
                if (item.notes) {
                    const notesEl = document.createElement('p');
                    notesEl.className = "text-[10px] font-semibold text-[#00CFBD] italic mt-0.5 max-w-[180px] truncate";
                    notesEl.textContent = `Nota: ${item.notes}`;
                    notesEl.title = item.notes;
                    infoEl.appendChild(notesEl);
                }

                const priceEl = document.createElement('p');
                priceEl.className = "text-xs font-semibold text-slate-400 mt-0.5";
                priceEl.textContent = `$${item.precio.toFixed(2)} c/u`;
                infoEl.appendChild(priceEl);

                const controlsEl = document.createElement('div');
                controlsEl.className = "flex items-center space-x-2.5";

                const btnMinus = document.createElement('button');
                btnMinus.className = "w-7 h-7 rounded-xl bg-slate-50 hover:bg-slate-100 flex items-center justify-center font-bold text-sm text-slate-600 transition-colors";
                btnMinus.textContent = "−";
                btnMinus.onclick = () => updateQuantity(item.id, -1, item.notes);

                const qtyEl = document.createElement('span');
                qtyEl.className = "text-sm font-extrabold w-4 text-center text-slate-800";
                qtyEl.textContent = item.quantity;

                const btnPlus = document.createElement('button');
                btnPlus.className = "w-7 h-7 rounded-xl bg-slate-50 hover:bg-slate-100 flex items-center justify-center font-bold text-sm text-slate-600 transition-colors";
                btnPlus.textContent = "+";
                btnPlus.onclick = () => updateQuantity(item.id, 1, item.notes);

                const btnRemove = document.createElement('button');
                btnRemove.className = "w-7 h-7 rounded-xl bg-red-50 hover:bg-red-100 flex items-center justify-center text-red-500 hover:text-red-750 transition-colors ml-1.5 font-bold text-xs";
                btnRemove.textContent = "✕";
                btnRemove.onclick = () => {
                    let cart = getCart().filter(i => !(i.id === item.id && (i.notes || '') === (item.notes || '')));
                    saveCart(cart);
                    if (cart.length === 0) {
                        toggleCartDrawer(false);
                    }
                };

                controlsEl.appendChild(btnMinus);
                controlsEl.appendChild(qtyEl);
                controlsEl.appendChild(btnPlus);
                controlsEl.appendChild(btnRemove);

                itemEl.appendChild(infoEl);
                itemEl.appendChild(controlsEl);
                cartItemsContainer.appendChild(itemEl);
            });

            floatingCart.classList.remove('hidden');
        }

        function checkoutOrder() {
            const cart = getCart();
            if (cart.length === 0) return;

            let totalPrice = 0;
            let itemsText = "";

            cart.forEach(item => {
                totalPrice += item.precio * item.quantity;
                const notesStr = item.notes ? ` (${item.notes})` : '';
                itemsText += `${item.quantity}x ${item.nombre}${notesStr} ($${item.precio.toFixed(2)} c/u)\n`;
            });

            const tasaDolar = parseFloat(<?= json_encode($tasa_dolar) ?>);
            const monedaNombre = <?= json_encode($moneda_local_nombre) ?>;
            const tasaTipo = <?= json_encode($tasa_tipo) ?>;
            let localTotalText = "";
            if (tasaDolar > 1) {
                const totalLocal = totalPrice * tasaDolar;
                const formattedLocal = tasaTipo === 'trm' 
                    ? totalLocal.toLocaleString('es-CO', { minimumFractionDigits: 0, maximumFractionDigits: 0 })
                    : totalLocal.toLocaleString('es-VE', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                localTotalText = `*Total en ${monedaNombre}: ${monedaNombre} ${formattedLocal}* (tasa: ${tasaDolar.toFixed(2)})\n`;
            }

            // Formato exacto solicitado
            const message = `*Pedido de PronttoGo* 🛒\n` +
                            `--------------------------\n` +
                            `${itemsText}` +
                            `--------------------------\n` +
                            `*Total a pagar: $${totalPrice.toFixed(2)}*\n` +
                            localTotalText;

            const encodedText = encodeURIComponent(message);
            const waUrl = `https://wa.me/${whatsappNumber}?text=${encodedText}`;

            window.open(waUrl, '_blank');
        }

        window.addEventListener('DOMContentLoaded', () => {
            updateCartUI();
        });
    </script>
</body>
</html>
