<?php
/**
 * PronttoGo - CatÃ¡logo PÃºblico Responsivo (Single-Store)
 * Renderiza el menÃº digital adaptado a pantallas mÃ³viles y de escritorio.
 */

require_once __DIR__ . '/config.php';

// 1. OBTENER CONFIGURACIÃ“N DEL LOCAL (Fila Ãºnica id = 1)
$response = supabase_request('GET', 'configuracion?id=eq.1');

if ($response['success'] && !empty($response['data'])) {
    $config = $response['data'][0];
} else {
    $config = [
        'nombre' => 'PronttoGo',
        'telefono_whatsapp' => '584121234567'
    ];
}

// ConfiguraciÃ³n de Tasa de Cambio y Moneda Local con soporte para rubros y monedas dinÃ¡micas
$tipo_negocio = $config['tipo_negocio'] ?? 'gastronomia';
$tasa_dolar = floatval($config['tasa_dolar'] ?? 1.00);
$tasa_tipo = $config['tasa_tipo'] ?? 'manual';
$moneda_local_nombre = !empty($config['moneda_nombre']) ? $config['moneda_nombre'] : (($tasa_tipo === 'trm') ? 'COP' : 'Bs.');
$moneda_local_simbolo = !empty($config['moneda_simbolo']) ? $config['moneda_simbolo'] : (($tasa_tipo === 'trm') ? '$' : 'Bs.');
$costo_delivery = floatval($config['costo_delivery'] ?? 0.00);
$direccion_local = !empty($config['direccion']) ? $config['direccion'] : '';
$horario_local = !empty($config['horario']) ? $config['horario'] : '';

// 2. CONSULTAR CATEGORÃAS (Ordenadas)
$resCategorias = supabase_request('GET', 'categorias?order=orden_visual.asc');
$categorias = $resCategorias['success'] ? $resCategorias['data'] : [];

// 3. CONSULTAR PRODUCTOS DISPONIBLES
$resProductos = supabase_request('GET', 'productos?disponible=eq.true&order=id.asc');
$productos = $resProductos['success'] ? $resProductos['data'] : [];

// Agrupar productos por categorÃ­a
$productosPorCategoria = [];
foreach ($productos as $prod) {
    $productosPorCategoria[$prod['categoria_id']][] = $prod;
}

// Determinar si hay algÃºn error de conexiÃ³n o base de datos (visible solo en entorno local)
$dbError = null;
$isLocalhost = in_array($_SERVER['REMOTE_ADDR'] ?? '', ['127.0.0.1', '::1'])
    || (isset($_SERVER['HTTP_HOST']) && preg_match('/(localhost|127\.0\.0\.1|\.local|\.test)$/i', $_SERVER['HTTP_HOST']));

if ($isLocalhost) {
    if (!$resCategorias['success']) {
        $dbError = 'Error de CategorÃ­as: ' . ($resCategorias['error'] ?? $resCategorias['raw'] ?? 'Error de conexiÃ³n.');
    } elseif (!$resProductos['success']) {
        $dbError = 'Error de Productos: ' . ($resProductos['error'] ?? $resProductos['raw'] ?? 'Error de conexiÃ³n.');
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
    <?php
    // Mapeo de colores principales segÃºn el tipo de negocio
    $color_niche = [
        'gastronomia' => [
            'primary' => '#00CFBD',
            'primary-hover' => '#00B5A5',
            'soft' => '#E6FBF9',
            'border-soft' => '#B2EFE9',
            'hero-bg-from' => '#F0FDFB',
            'hero-bg-via' => '#E2F8F5',
            'hero-bg-to' => '#F0FDFB',
            'hero-glow' => 'rgba(0,207,189,0.05)'
        ],
        'boutique' => [
            'primary' => '#8B5CF6',
            'primary-hover' => '#7C3AED',
            'soft' => '#F5F3FF',
            'border-soft' => '#DDD6FE',
            'hero-bg-from' => '#FAF5FF',
            'hero-bg-via' => '#F3E8FF',
            'hero-bg-to' => '#FAF5FF',
            'hero-glow' => 'rgba(139,92,246,0.05)'
        ],
        'ferreteria_repuestos' => [
            'primary' => '#F59E0B',
            'primary-hover' => '#D97706',
            'soft' => '#FFFBEB',
            'border-soft' => '#FDE68A',
            'hero-bg-from' => '#FFFDF5',
            'hero-bg-via' => '#FEF3C7',
            'hero-bg-to' => '#FFFDF5',
            'hero-glow' => 'rgba(245,158,11,0.05)'
        ],
        'belleza_estetica' => [
            'primary' => '#EC4899',
            'primary-hover' => '#DB2777',
            'soft' => '#FDF2F8',
            'border-soft' => '#FBCFE8',
            'hero-bg-from' => '#FDF2F8',
            'hero-bg-via' => '#FCE7F3',
            'hero-bg-to' => '#FDF2F8',
            'hero-glow' => 'rgba(236,72,153,0.05)'
        ],
        'otros' => [
            'primary' => '#4F46E5',
            'primary-hover' => '#4338CA',
            'soft' => '#EEF2FF',
            'border-soft' => '#C7D2FE',
            'hero-bg-from' => '#EEF2FF',
            'hero-bg-via' => '#E0E7FF',
            'hero-bg-to' => '#EEF2FF',
            'hero-glow' => 'rgba(79,70,229,0.05)'
        ]
    ];
    $colors = $color_niche[$tipo_negocio] ?? $color_niche['gastronomia'];
    ?>
    <meta name="theme-color" content="<?= h($colors['primary']) ?>">
    <title><?= h(!empty($config['nombre']) && $config['nombre'] !== 'Mi Tienda' ? $config['nombre'] : 'PronttoGo') ?></title>
    <script>
        // Evitar advertencia del CDN de Tailwind en la consola
        const _warn = console.warn;
        console.warn = (...args) => {
            if (args[0] && typeof args[0] === 'string' && args[0].includes('cdn.tailwindcss.com')) return;
            _warn(...args);
        };

        // Forzar tema claro eliminando cualquier rastro de configuraciÃ³n oscura
        localStorage.removeItem('theme');
        document.documentElement.classList.remove('dark');
    </script>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        // Configurar tema de Tailwind dinÃ¡mico mediante variables CSS
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    colors: {
                        primary: 'var(--color-primary)',
                        'primary-hover': 'var(--color-primary-hover)',
                        soft: 'var(--color-soft)',
                        'border-soft': 'var(--color-border-soft)'
                    }
                }
            }
        };
    </script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --color-primary: <?= $colors['primary'] ?>;
            --color-primary-hover: <?= $colors['primary-hover'] ?>;
            --color-soft: <?= $colors['soft'] ?>;
            --color-border-soft: <?= $colors['border-soft'] ?>;
            --hero-bg-from: <?= $colors['hero-bg-from'] ?>;
            --hero-bg-via: <?= $colors['hero-bg-via'] ?>;
            --hero-bg-to: <?= $colors['hero-bg-to'] ?>;
            --hero-glow: <?= $colors['hero-glow'] ?>;
        }
        body { font-family: 'Plus Jakarta Sans', sans-serif; }
        .no-scrollbar::-webkit-scrollbar { display: none; }
        .no-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }
        
        /* Estilos personalizados para los pills de categorÃ­as */
        .mobile-category-pill {
            transition: all 0.2s ease-in-out;
        }
        .mobile-category-pill:hover {
            background-color: #F1F5F9 !important; /* bg-slate-100 */
            color: #1E293B !important;            /* text-slate-800 */
            border-color: #E2E8F0 !important;      /* border-slate-200 */
        }
        .mobile-category-pill.active {
            background-color: var(--color-soft) !important;  /* soft bg */
            color: var(--color-primary) !important;             /* primary text */
            border-color: var(--color-border-soft) !important;      /* soft border */
        }
        .mobile-category-pill.active:hover {
            background-color: var(--color-soft) !important;
            color: var(--color-primary-hover) !important;             /* slightly darker text */
            opacity: 0.9;
        }
    </style>
</head>
<body class="bg-[#F8FAFC] text-[#0F172A] min-h-screen flex flex-col overflow-x-hidden">

    <?php if ($dbError): ?>
        <!-- Barra de depuraciÃ³n en local para avisar errores de conexiÃ³n de Supabase -->
        <div class="bg-red-600 text-white text-xs font-bold px-4 py-3 text-center shadow-md relative z-50">
            âš ï¸ <strong>Error de Base de Datos (Local):</strong> <?= h($dbError) ?> | URL configurada: <code class="bg-red-700 px-1.5 py-0.5 rounded"><?= h(SUPABASE_URL) ?></code>
        </div>
    <?php endif; ?>
    <!-- Header -->
    <header class="h-16 bg-white/95 backdrop-blur-md border-b border-slate-100 sticky top-0 z-30 shadow-sm flex items-center">
        <div class="max-w-6xl w-full mx-auto px-4 sm:px-6 flex items-center justify-between">
            <div class="flex items-center space-x-2.5 min-w-0">
                <?php if (!empty($config['logo_url'])): ?>
                    <img src="<?= h($config['logo_url']) ?>" alt="<?= h($config['nombre']) ?>" class="h-8 w-auto object-contain rounded-lg shrink-0">
                    <span class="font-extrabold text-lg tracking-tight text-slate-800 truncate max-w-[140px] sm:max-w-none block"><?= h($config['nombre']) ?></span>
                <?php else: ?>
                    <?php if (strtolower($config['nombre'] ?? 'pronttogo') === 'pronttogo' || ($config['nombre'] ?? 'Mi Tienda') === 'Mi Tienda'): ?>
                        <?= get_logo_svg('h-8 w-auto shrink-0') ?>
                    <?php else: ?>
                        <span class="font-extrabold text-lg tracking-tight text-slate-800 truncate max-w-[140px] sm:max-w-none block"><?= h($config['nombre']) ?></span>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
            <a href="admin.php" class="text-xs font-bold text-slate-655 hover:text-slate-900 border border-slate-200 hover:border-slate-350 rounded-xl px-4 py-2 transition-all bg-white shadow-sm shrink-0">
                Iniciar SesiÃ³n
            </a>
        </div>
    </header>

    <!-- Full Hero Section (PresentaciÃ³n de Ancho Completo Premium en Blanco) -->
    <div class="relative w-full bg-gradient-to-br from-[var(--hero-bg-from)] via-[var(--hero-bg-via)] to-[var(--hero-bg-to)] text-slate-800 overflow-hidden border-b border-primary/15">
        <!-- Luces decorativas de fondo -->
        <div class="absolute -right-10 top-0 w-96 h-96 bg-primary/5 rounded-full blur-3xl pointer-events-none"></div>
        <div class="absolute -left-10 bottom-0 w-96 h-96 bg-[#2A3543]/4 rounded-full blur-3xl pointer-events-none"></div>
        <div class="absolute left-1/2 top-1/2 -translate-x-1/2 -translate-y-1/2 w-[600px] h-40 bg-primary/3 rounded-full blur-3xl pointer-events-none"></div>
        
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
                <span class="text-4xl md:text-5xl font-extrabold tracking-tight text-slate-800">
                    <?= h($config['nombre']) ?>
                </span>
            <?php endif; ?>



            <!-- Tagline principal -->
            <div class="space-y-1.5 max-w-lg pt-1">
                <p class="text-xl md:text-2xl font-extrabold text-[#2A3543] tracking-tight leading-snug">
                    Tu catÃ¡logo digital,
                    <span class="bg-primary bg-clip-text text-transparent">siempre disponible</span>
                </p>
                <p class="text-sm text-slate-500 leading-relaxed font-medium">
                    Explora nuestros productos, arma tu pedido y envÃ­alo directo por WhatsApp en segundos.
                </p>

                <!-- Datos del Local (DirecciÃ³n y Horarios) -->
                <?php if (!empty($direccion_local) || !empty($horario_local)): ?>
                    <div class="flex flex-wrap items-center justify-center gap-2 pt-3 text-[11px] text-slate-655 font-semibold max-w-lg mx-auto">
                        <?php if (!empty($direccion_local)): ?>
                            <div class="flex items-center space-x-1.5 bg-white/80 backdrop-blur-sm px-3.5 py-1.5 rounded-xl border border-slate-100/80 shadow-sm hover:shadow transition-shadow">
                                <span>ðŸ“</span>
                                <span class="truncate max-w-[220px] sm:max-w-xs" title="<?= h($direccion_local) ?>"><?= h($direccion_local) ?></span>
                            </div>
                        <?php endif; ?>
                        <?php if (!empty($horario_local)): ?>
                            <div class="flex items-center space-x-1.5 bg-white/80 backdrop-blur-sm px-3.5 py-1.5 rounded-xl border border-slate-100/80 shadow-sm hover:shadow transition-shadow">
                                <span>ðŸ•’</span>
                                <span><?= h($horario_local) ?></span>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Contenedor del CatÃ¡logo (Sin Sidebar, Ancho Completo) -->
    <main class="max-w-6xl w-full mx-auto px-4 sm:px-6 py-8 flex-1 pb-24 md:pb-12">
        <div class="w-full space-y-6">
            <!-- Buscador de Productos -->
            <div class="relative w-full shadow-sm rounded-2xl bg-white border border-slate-100 p-2 flex items-center space-x-2.5 transition-all focus-within:ring-2 focus-within:ring-primary/30 focus-within:border-primary">
                <div class="pl-3.5 text-slate-400">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                    </svg>
                </div>
                <input type="text" id="search-input" placeholder="Buscar productos..." class="w-full bg-transparent border-0 outline-none text-slate-800 text-sm placeholder-slate-400 pr-4 py-1.5" autocomplete="off" />
                <button id="search-clear-btn" class="hidden pr-3 text-slate-400 hover:text-slate-600 font-bold text-sm">âœ•</button>
            </div>

            <!-- CategorÃ­as Deslizables (Sticky) - Unificado para Escritorio y MÃ³vil -->
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
                <!-- CatÃ¡logo VacÃ­o (Simple y Minimalista) -->
                <div class="text-center py-20 max-w-sm mx-auto space-y-3">
                    <div class="w-12 h-12 bg-slate-50 text-slate-400 rounded-full flex items-center justify-center mx-auto text-xl">
                        <?php
                        $placeholder_emoji = 'ðŸ”';
                        if ($tipo_negocio === 'boutique') $placeholder_emoji = 'ðŸ‘•';
                        elseif ($tipo_negocio === 'ferreteria_repuestos') $placeholder_emoji = 'ðŸ”§';
                        elseif ($tipo_negocio === 'belleza_estetica') $placeholder_emoji = 'âœ‚ï¸';
                        elseif ($tipo_negocio === 'otros') $placeholder_emoji = 'ðŸ›ï¸';
                        echo $placeholder_emoji;
                        ?>
                    </div>
                    <h3 class="font-bold text-slate-800 text-sm">El catÃ¡logo estÃ¡ vacÃ­o</h3>
                    <p class="text-slate-400 text-xs max-w-xs mx-auto leading-relaxed">
                        AÃºn no se han aÃ±adido productos. Inicia sesiÃ³n en el panel para comenzar a cargar tu catÃ¡logo.
                    </p> cargar tu catÃ¡logo.
                    </p>
                    <div class="pt-2">
                        <a href="admin.php" class="inline-flex items-center gap-1 px-4 py-2 border border-slate-200 text-slate-600 hover:bg-slate-50 rounded-xl font-bold text-xs transition-all">
                            Ir al Panel â†—
                        </a>
                    </div>
                </div>
            <?php else: ?>
                <!-- Mensaje de BÃºsqueda sin Resultados -->
                <div id="search-no-results" class="hidden text-center py-16 space-y-3">
                    <div class="w-12 h-12 bg-slate-50 text-slate-400 rounded-full flex items-center justify-center mx-auto text-xl">
                        ðŸ”
                    </div>
                    <h3 class="font-bold text-slate-800 text-sm">No se encontraron productos</h3>
                    <p class="text-slate-400 text-xs max-w-xs mx-auto leading-relaxed">
                        Intenta con otra palabra clave o explora las categorÃ­as del catÃ¡logo.
                    </p>
                </div>

                <?php foreach ($categorias as $cat): 
                    $items = $productosPorCategoria[$cat['id']] ?? [];
                    if (empty($items)) continue;
                ?>
                    <section id="cat-<?= h($cat['id']) ?>" class="scroll-mt-28 space-y-4">
                        <div class="flex items-center space-x-3">
                            <h2 class="text-base md:text-lg font-extrabold tracking-tight text-slate-850"><?= h($cat['nombre_categoria']) ?></h2>
                            <div class="h-0.5 flex-1 bg-gradient-to-r from-primary to-[#2A3543] opacity-20 rounded"></div>
                        </div>

                        <!-- Grid de Productos (1 en mÃ³vil, 2 en tablet, 3 en pantallas grandes) -->
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                            <?php foreach ($items as $prod): ?>
                                <?php if (!empty($prod['imagen_url'])): ?>
                                    <!-- Tarjeta con Imagen -->
                                    <div onclick='addToCart(<?= json_encode([
                                        'id' => $prod['id'],
                                        'nombre' => $prod['nombre'],
                                        'precio' => floatval($prod['precio'])
                                    ]) ?>, event)' class="cursor-pointer bg-white p-5 md:p-6 rounded-2xl border border-slate-100 shadow-sm hover:shadow-md hover:border-slate-200 transition-all duration-300 flex items-stretch justify-between gap-4 relative group">
                                        <div class="flex-1 flex flex-col justify-between min-w-0 py-0.5">
                                            <div class="space-y-1">
                                                <h3 class="font-extrabold text-slate-900 text-sm md:text-base leading-snug group-hover:text-primary transition-colors"><?= h($prod['nombre']) ?></h3>
                                                <?php if (!empty($prod['descripcion'])): ?>
                                                    <p class="text-xs text-slate-500 line-clamp-2 md:line-clamp-3 leading-relaxed"><?= h($prod['descripcion']) ?></p>
                                                <?php endif; ?>
                                            </div>
                                            <div>
                                                <span class="block font-black text-sm md:text-base text-slate-900 mt-2">$<?= number_format($prod['precio'], 2) ?></span>
                                                <?php if ($tasa_dolar > 1): ?>
                                                    <span class="block text-xs font-bold text-slate-500 mt-0.5"><?= h($moneda_local_simbolo) ?> <?= number_format($prod['precio'] * $tasa_dolar, $tasa_tipo === 'trm' ? 0 : 2, ',', '.') ?> <?= h($moneda_local_nombre) ?></span>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                        <div class="flex flex-col items-center justify-between shrink-0 gap-3 w-16 sm:w-20 md:w-24">
                                            <img src="<?= h($prod['imagen_url']) ?>" alt="<?= h($prod['nombre']) ?>" class="w-16 h-16 sm:w-20 sm:h-20 md:w-24 md:h-24 object-cover rounded-xl bg-slate-50 border border-slate-100 shadow-sm group-hover:scale-[1.02] transition-transform duration-300">
                                            <button onclick='event.stopPropagation(); addToCart(<?= json_encode([
                                                'id' => $prod['id'],
                                                'nombre' => $prod['nombre'],
                                                'precio' => floatval($prod['precio'])
                                            ]) ?>)' class="w-full bg-primary hover:bg-primary-hover text-white font-bold text-center text-[10px] md:text-xs py-1.5 rounded-full shadow-md transition-all active:scale-95 whitespace-nowrap">
                                                + Agregar
                                            </button>
                                        </div>
                                    </div>
                                <?php else: ?>
                                    <!-- Tarjeta sin Imagen -->
                                    <div onclick='addToCart(<?= json_encode([
                                        'id' => $prod['id'],
                                        'nombre' => $prod['nombre'],
                                        'precio' => floatval($prod['precio'])
                                    ]) ?>, event)' class="cursor-pointer bg-white p-5 md:p-6 rounded-2xl border border-slate-100 shadow-sm hover:shadow-md hover:border-slate-200 transition-all duration-300 flex flex-col justify-between min-h-[120px] group">
                                        <div class="space-y-1">
                                            <h3 class="font-extrabold text-slate-900 text-sm md:text-base leading-snug group-hover:text-primary transition-colors"><?= h($prod['nombre']) ?></h3>
                                            <?php if (!empty($prod['descripcion'])): ?>
                                                <p class="text-xs text-slate-500 line-clamp-2 md:line-clamp-3 leading-relaxed"><?= h($prod['descripcion']) ?></p>
                                            <?php endif; ?>
                                        </div>
                                        <div class="flex items-center justify-between mt-4 pt-2 border-t border-slate-50">
                                            <div>
                                                <span class="font-black text-sm md:text-base text-slate-900 block">$<?= number_format($prod['precio'], 2) ?></span>
                                                <?php if ($tasa_dolar > 1): ?>
                                                    <span class="text-xs font-bold text-slate-500 block mt-0.5"><?= h($moneda_local_simbolo) ?> <?= number_format($prod['precio'] * $tasa_dolar, $tasa_tipo === 'trm' ? 0 : 2, ',', '.') ?> <?= h($moneda_local_nombre) ?></span>
                                                <?php endif; ?>
                                            </div>
                                            <button onclick='event.stopPropagation(); addToCart(<?= json_encode([
                                                'id' => $prod['id'],
                                                'nombre' => $prod['nombre'],
                                                'precio' => floatval($prod['precio'])
                                            ]) ?>)' class="bg-primary hover:bg-primary-hover text-white font-bold text-[10px] md:text-xs py-1.5 px-4.5 rounded-full shadow-md transition-all active:scale-95 whitespace-nowrap">
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
                <a href="/legal" class="text-slate-400 hover:text-primary transition-colors">TÃ©rminos y Privacidad</a>
            </div>
            <a href="admin.php" class="text-[10px] uppercase font-bold text-slate-400 hover:text-slate-600 transition-colors flex items-center gap-1">
                <span>Powered by</span>
                <span class="text-primary font-extrabold">Montero Studio</span>
            </a>
        </div>
    </footer>

    <!-- Carrito Flotante (JS) -->
    <div id="floating-cart" class="fixed bottom-0 left-0 right-0 p-4 bg-transparent max-w-md mx-auto z-40 hidden">
        <button onclick="toggleCartDrawer(true)" class="w-full py-4 px-6 bg-primary hover:opacity-95 text-white font-bold text-sm rounded-2xl shadow-xl flex justify-between items-center transition-all active:scale-98">
            <div class="flex items-center space-x-2">
                <span>ðŸ›’</span>
                <span id="cart-count">0 artÃ­culos</span>
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
                    <p class="text-xs text-slate-400">Verifica los artÃ­culos seleccionados</p>
                </div>
                <div class="flex items-center space-x-3">
                    <button onclick="clearCart()" class="text-xs font-bold text-red-500 hover:text-red-750 transition-colors">
                        Vaciar
                    </button>
                    <button onclick="toggleCartDrawer(false)" class="w-8 h-8 rounded-full bg-slate-50 hover:bg-slate-100 flex items-center justify-center font-bold text-slate-500">
                        âœ•
                    </button>
                </div>
            </div>

            <!-- Contenedor scrollable principal -->
            <div class="flex-1 overflow-y-auto p-5 sm:p-6 space-y-6">
                <!-- Listado de Productos -->
                <div id="cart-items" class="space-y-1 divide-y divide-slate-100">
                    <!-- Se rellena por JS de forma segura -->
                </div>

                <!-- Formulario de Datos del Cliente -->
                <div id="customer-data-form" class="border-t border-slate-100 pt-5 space-y-4">
                    <div class="border-b border-slate-50 pb-2">
                        <h4 class="font-extrabold text-sm text-slate-800">Datos del Cliente</h4>
                        <p class="text-[10px] text-slate-400">Completa esta informaciÃ³n para procesar tu pedido.</p>
                    </div>

                    <!-- Nombre -->
                    <div class="space-y-1.5">
                        <label for="cust-name" class="block text-[10px] font-bold uppercase tracking-wider text-slate-500">Tu Nombre</label>
                        <div class="relative">
                            <span class="absolute left-3 top-1/2 -translate-y-1/2 text-xs">ðŸ‘¤</span>
                            <input type="text" id="cust-name" placeholder="Ej. Carlos Mendoza" required
                                   class="w-full pl-8 pr-3 py-2 border border-slate-200 rounded-xl text-xs bg-slate-50/50 text-slate-900 placeholder-slate-400 focus:outline-none focus:ring-1 focus:ring-primary focus:border-primary transition-all">
                        </div>
                    </div>

                    <!-- Tipo de entrega -->
                    <div class="space-y-2">
                        <label class="block text-[10px] font-bold uppercase tracking-wider text-slate-500">Tipo de Entrega</label>
                        <div class="grid grid-cols-2 gap-2 bg-slate-100 p-1 rounded-xl">
                            <button type="button" id="delivery-type-delivery" onclick="setDeliveryType('delivery')" 
                                    class="py-1.5 text-[11px] font-bold rounded-lg transition-all bg-white text-slate-800 shadow-sm border border-slate-100">
                                ðŸ›µ Delivery
                            </button>
                            <button type="button" id="delivery-type-pickup" onclick="setDeliveryType('pickup')" 
                                    class="py-1.5 text-[11px] font-bold rounded-lg transition-all text-slate-500 hover:text-slate-800">
                                ðŸ›ï¸ Retiro
                            </button>
                        </div>
                        <p id="delivery-cost-note" class="text-[10px] text-amber-600 font-semibold flex items-center gap-1 mt-1 pl-1">
                            <?php if ($costo_delivery > 0): ?>
                                ðŸ›µ Costo de envÃ­o: $<?= number_format($costo_delivery, 2) ?>
                            <?php else: ?>
                                ðŸ›µ EnvÃ­o gratis o a acordar con el vendedor.
                            <?php endif; ?>
                        </p>
                    </div>

                    <!-- DirecciÃ³n -->
                    <div id="delivery-address-container" class="space-y-1.5 transition-all duration-300">
                        <label for="cust-address" class="block text-[10px] font-bold uppercase tracking-wider text-slate-500">DirecciÃ³n de Entrega</label>
                        <div class="relative">
                            <span class="absolute left-3 top-2.5 text-xs">ðŸ“</span>
                            <textarea id="cust-address" placeholder="Indica calle, edificio, nro de casa y puntos de referencia..." rows="2" required
                                      class="w-full pl-8 pr-3 py-2 border border-slate-200 rounded-xl text-xs bg-slate-50/50 text-slate-900 placeholder-slate-400 focus:outline-none focus:ring-1 focus:ring-primary focus:border-primary transition-all resize-none"></textarea>
                        </div>
                    </div>

                    <!-- MÃ©todo de pago -->
                    <div class="space-y-1.5">
                        <label for="cust-payment" class="block text-[10px] font-bold uppercase tracking-wider text-slate-500">MÃ©todo de Pago</label>
                        <div class="relative">
                            <span class="absolute left-3 top-1/2 -translate-y-1/2 text-xs">ðŸ’³</span>
                            <select id="cust-payment" 
                                    class="w-full pl-8 pr-3 py-2 border border-slate-200 rounded-xl text-xs bg-slate-50/50 text-slate-900 focus:outline-none focus:ring-1 focus:ring-primary focus:border-primary transition-all appearance-none bg-[url('data:image/svg+xml;charset=utf-8,%3Csvg%20xmlns%3D%22http%3A%2F%2Fwww.w3.org%2F2000%2Fsvg%22%20viewBox%3D%220%200%2020%2020%22%20fill%3D%22none%22%3E%3Cpath%20d%3D%22M7%209l3%203%203-3%22%20stroke%3D%22%236b7280%22%20stroke-width%3D%221.5%22%20stroke-linecap%3D%22round%22%20stroke-linejoin%3D%22round%22%2F%3E%3C%2Fsvg%3E')] bg-[length:1.25rem_1.25rem] bg-[right_0.5rem_center] bg-no-repeat pr-8">
                                <option value="Pago MÃ³vil">ðŸ’¸ Pago MÃ³vil (BolÃ­vares - VES)</option>
                                <option value="Efectivo Divisas">ðŸ’µ Efectivo Divisas (DÃ³lares - USD)</option>
                                <option value="Zelle">ðŸ‡ºðŸ‡¸ Zelle (DÃ³lares - USD)</option>
                                <option value="Efectivo Bs.">ðŸ‡»ðŸ‡ª Efectivo BolÃ­vares (VES)</option>
                                <option value="Tarjeta / Punto de Venta">ðŸ’³ Tarjeta / Punto de Venta</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>

            <div class="p-6 border-t border-slate-50 space-y-4 bg-slate-50/50">
                <!-- Desglose de Pedido -->
                <div class="space-y-1.5 text-xs text-slate-500 border-b border-slate-200/50 pb-3">
                    <div class="flex justify-between">
                        <span>Subtotal</span>
                        <span id="drawer-subtotal" class="font-bold text-slate-700">$0.00</span>
                    </div>
                    <div id="drawer-delivery-row" class="flex justify-between">
                        <span>Costo de EnvÃ­o</span>
                        <span id="drawer-delivery-cost" class="font-bold text-slate-700">$0.00</span>
                    </div>
                </div>

                <div class="flex justify-between items-center font-extrabold text-slate-800 pt-1">
                    <span>Total a pagar</span>
                    <div class="text-right">
                        <span id="drawer-total" class="text-xl block text-slate-850">$0.00</span>
                        <?php if ($tasa_dolar > 1): ?>
                            <span id="drawer-total-local" class="text-xs font-bold text-slate-500 block mt-0.5 font-mono"></span>
                        <?php endif; ?>
                    </div>
                </div>
                <button onclick="checkoutOrder()" class="w-full py-4 px-6 bg-primary hover:opacity-95 text-white font-bold text-sm rounded-xl shadow-lg transition-all flex justify-between items-center active:scale-98">
                    <span>Enviar Pedido por WhatsApp</span>
                    <span>â†’</span>
                </button>
            </div>
        </div>
    </div>



    <!-- Script del Carrito (Vanilla JS) -->
    <script>
        const whatsappNumber = <?= json_encode($config['telefono_whatsapp']) ?>;
        const cartKey = 'cart_pronttogo';
        const costoDelivery = parseFloat(<?= json_encode($costo_delivery) ?>);

        let isScrolling = false;
        let scrollTimeout;



        // AnimaciÃ³n volar al carrito
        function triggerFlyAnimation(startElement) {
            const floatingCart = document.getElementById('floating-cart');
            if (!floatingCart) return;

            // Asegurarnos de que el carrito no estÃ© oculto para obtener su posiciÃ³n
            const wasHidden = floatingCart.classList.contains('hidden');
            if (wasHidden) {
                floatingCart.classList.remove('hidden');
            }

            const rect = startElement.getBoundingClientRect();
            const cartRect = floatingCart.getBoundingClientRect();

            if (wasHidden) {
                floatingCart.classList.add('hidden');
            }

            // Crear la partÃ­cula
            const particle = document.createElement('div');
            particle.className = 'fixed z-50 w-6 h-6 bg-primary rounded-full pointer-events-none transition-all duration-750 ease-in-out flex items-center justify-center text-white text-[10px] font-black shadow-lg';
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

        // ScrollSpy para CategorÃ­as
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
            // Activar pill correspondiente en la barra de categorÃ­as unificada
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
            if (confirm('Â¿Seguro que deseas vaciar tu carrito de compras?')) {
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

            const subtotal = totalPrice;
            let deliveryFee = 0;
            if (currentDeliveryType === 'delivery') {
                deliveryFee = costoDelivery;
            }
            const grandTotal = subtotal + deliveryFee;

            cartCount.textContent = `${totalItems} ${totalItems === 1 ? 'artÃ­culo' : 'artÃ­culos'}`;
            cartTotal.textContent = `$${grandTotal.toFixed(2)}`;
            
            const subtotalEl = document.getElementById('drawer-subtotal');
            if (subtotalEl) subtotalEl.textContent = `$${subtotal.toFixed(2)}`;
            
            const deliveryCostEl = document.getElementById('drawer-delivery-cost');
            const deliveryRowEl = document.getElementById('drawer-delivery-row');
            if (deliveryCostEl) {
                if (deliveryFee > 0) {
                    deliveryCostEl.textContent = `$${deliveryFee.toFixed(2)}`;
                    if (deliveryRowEl) deliveryRowEl.classList.remove('hidden');
                } else {
                    deliveryCostEl.textContent = 'Gratis / Convenir';
                    if (costoDelivery > 0) {
                        if (deliveryRowEl) deliveryRowEl.classList.add('hidden');
                    } else {
                        deliveryCostEl.textContent = 'Gratis';
                        if (deliveryRowEl) deliveryRowEl.classList.remove('hidden');
                    }
                }
            }
            
            drawerTotal.textContent = `$${grandTotal.toFixed(2)}`;

            // Tasa de cambio local dinÃ¡mica
            const tasaDolar = parseFloat(<?= json_encode($tasa_dolar) ?>);
            const monedaNombre = <?= json_encode($moneda_local_nombre) ?>;
            const tasaTipo = <?= json_encode($tasa_tipo) ?>;
            if (tasaDolar > 1) {
                const totalLocal = grandTotal * tasaDolar;
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
                itemEl.className = "flex flex-col py-4 border-b border-slate-100 first:pt-2 last:border-b-0 gap-2";

                // Fila principal (Info y Controles)
                const mainRow = document.createElement('div');
                mainRow.className = "flex justify-between items-center w-full";

                const infoEl = document.createElement('div');
                
                const nameEl = document.createElement('h4');
                nameEl.className = "font-bold text-sm text-slate-800";
                nameEl.textContent = item.nombre;
                infoEl.appendChild(nameEl);

                const priceEl = document.createElement('p');
                priceEl.className = "text-xs font-semibold text-slate-400 mt-0.5";
                priceEl.textContent = `$${item.precio.toFixed(2)} c/u`;
                infoEl.appendChild(priceEl);

                const controlsEl = document.createElement('div');
                controlsEl.className = "flex items-center space-x-2.5";

                const btnMinus = document.createElement('button');
                btnMinus.className = "w-7 h-7 rounded-xl bg-slate-50 hover:bg-slate-100 flex items-center justify-center font-bold text-sm text-slate-600 transition-colors";
                btnMinus.textContent = "âˆ’";
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
                btnRemove.textContent = "âœ•";
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

                mainRow.appendChild(infoEl);
                mainRow.appendChild(controlsEl);
                itemEl.appendChild(mainRow);

                // Fila de notas del producto
                const notesRow = document.createElement('div');
                notesRow.className = "w-full";

                const notesInput = document.createElement('input');
                notesInput.type = "text";
                notesInput.placeholder = "âœï¸ Indica aquÃ­ la talla, color, modelo o detalles...";
                notesInput.value = item.notes || '';
                notesInput.className = "w-full px-3 py-1.5 bg-slate-50 border border-slate-200/60 rounded-xl text-[11px] text-slate-700 placeholder-slate-400 focus:outline-none focus:ring-1 focus:ring-primary/40 focus:border-primary transition-all";
                notesInput.onchange = (e) => {
                    updateItemNotes(item.id, item.notes, e.target.value.trim());
                };

                notesRow.appendChild(notesInput);
                itemEl.appendChild(notesRow);

                cartItemsContainer.appendChild(itemEl);
            });

            floatingCart.classList.remove('hidden');
        }

        let currentDeliveryType = 'delivery';

        function setDeliveryType(type) {
            currentDeliveryType = type;
            const btnDelivery = document.getElementById('delivery-type-delivery');
            const btnPickup = document.getElementById('delivery-type-pickup');
            const addressContainer = document.getElementById('delivery-address-container');
            const addressInput = document.getElementById('cust-address');
            const costNote = document.getElementById('delivery-cost-note');
            
            if (type === 'delivery') {
                btnDelivery.className = "py-1.5 text-[11px] font-bold rounded-lg transition-all bg-white text-slate-800 shadow-sm border border-slate-100";
                btnPickup.className = "py-1.5 text-[11px] font-bold rounded-lg transition-all text-slate-500 hover:text-slate-800";
                addressContainer.classList.remove('hidden');
                addressInput.setAttribute('required', 'true');
                if (costNote) costNote.classList.remove('hidden');
            } else {
                btnDelivery.className = "py-1.5 text-[11px] font-bold rounded-lg transition-all text-slate-500 hover:text-slate-800";
                btnPickup.className = "py-1.5 text-[11px] font-bold rounded-lg transition-all bg-white text-slate-800 shadow-sm border border-slate-100";
                addressContainer.classList.add('hidden');
                addressInput.removeAttribute('required');
                if (costNote) costNote.classList.add('hidden');
            }
            saveCustomerData();
            updateCartUI();
        }

        function loadCustomerData() {
            try {
                const name = localStorage.getItem('cust_name') || '';
                const address = localStorage.getItem('cust_address') || '';
                const payment = localStorage.getItem('cust_payment') || 'Pago MÃ³vil';
                const deliveryType = localStorage.getItem('cust_delivery_type') || 'delivery';

                const nameInput = document.getElementById('cust-name');
                const addressInput = document.getElementById('cust-address');
                const paymentInput = document.getElementById('cust-payment');

                if (nameInput) nameInput.value = name;
                if (addressInput) addressInput.value = address;
                if (paymentInput) paymentInput.value = payment;
                
                setDeliveryType(deliveryType);
            } catch (e) {
                console.error(e);
            }
        }

        function saveCustomerData() {
            try {
                const nameInput = document.getElementById('cust-name');
                const addressInput = document.getElementById('cust-address');
                const paymentInput = document.getElementById('cust-payment');

                const name = nameInput ? nameInput.value.trim() : '';
                const address = addressInput ? addressInput.value.trim() : '';
                const payment = paymentInput ? paymentInput.value : 'Pago MÃ³vil';
                
                localStorage.setItem('cust_name', name);
                localStorage.setItem('cust_address', address);
                localStorage.setItem('cust_payment', payment);
                localStorage.setItem('cust_delivery_type', currentDeliveryType);
            } catch (e) {
                console.error(e);
            }
        }

        function updateItemNotes(productId, oldNotes, newNotes) {
            let cart = getCart();
            const item = cart.find(item => item.id === productId && (item.notes || '') === (oldNotes || ''));
            if (item) {
                item.notes = newNotes;
                saveCart(cart);
            }
        }

        function checkoutOrder() {
            const cart = getCart();
            if (cart.length === 0) return;

            // Validar campos de cliente
            const clientName = document.getElementById('cust-name').value.trim();
            if (!clientName) {
                alert('Por favor, ingresa tu nombre para poder enviar el pedido.');
                document.getElementById('cust-name').focus();
                return;
            }

            const clientAddress = document.getElementById('cust-address').value.trim();
            if (currentDeliveryType === 'delivery' && !clientAddress) {
                alert('Por favor, ingresa tu direcciÃ³n para el delivery.');
                document.getElementById('cust-address').focus();
                return;
            }

            const clientPayment = document.getElementById('cust-payment').value;

            let totalPrice = 0;
            let itemsText = "";

            cart.forEach(item => {
                totalPrice += item.precio * item.quantity;
                const notesStr = item.notes ? ` (${item.notes})` : '';
                itemsText += `${item.quantity}x ${item.nombre}${notesStr} ($${item.precio.toFixed(2)} c/u)\n`;
            });

            const deliveryFee = currentDeliveryType === 'delivery' ? costoDelivery : 0;
            const grandTotal = totalPrice + deliveryFee;

            const tasaDolar = parseFloat(<?= json_encode($tasa_dolar) ?>);
            const monedaNombre = <?= json_encode($moneda_local_nombre) ?>;
            const tasaTipo = <?= json_encode($tasa_tipo) ?>;
            let localTotalText = "";
            if (tasaDolar > 1) {
                const totalLocal = grandTotal * tasaDolar;
                const formattedLocal = tasaTipo === 'trm' 
                    ? totalLocal.toLocaleString('es-CO', { minimumFractionDigits: 0, maximumFractionDigits: 0 })
                    : totalLocal.toLocaleString('es-VE', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                localTotalText = `*Total en ${monedaNombre}: ${monedaNombre} ${formattedLocal}* (tasa: ${tasaDolar.toFixed(2)})\n`;
            }

            let deliveryText = "";
            if (currentDeliveryType === 'delivery') {
                deliveryText = `ðŸ›µ *Despacho:* Delivery (${deliveryFee > 0 ? '$' + deliveryFee.toFixed(2) : 'Gratis / Convenir'})\nðŸ“ *DirecciÃ³n:* ${clientAddress}`;
            } else {
                deliveryText = `ðŸ› *Despacho:* Retiro en local`;
            }

            // Formato exacto solicitado con datos del cliente
            const message = `*Pedido de <?= h(!empty($config['nombre']) && $config['nombre'] !== 'Mi Tienda' ? $config['nombre'] : 'PronttoGo') ?>* ðŸ›’\n` +
                            `--------------------------\n` +
                            `ðŸ‘¤ *Cliente:* ${clientName}\n` +
                            `${deliveryText}\n` +
                            `ðŸ’³ *Pago:* ${clientPayment}\n` +
                            `--------------------------\n` +
                            `${itemsText}` +
                            `--------------------------\n` +
                            `*Subtotal:* $${totalPrice.toFixed(2)}\n` +
                            (deliveryFee > 0 ? `*EnvÃ­o:* $${deliveryFee.toFixed(2)}\n` : '') +
                            `*Total a pagar: $${grandTotal.toFixed(2)}*\n` +
                            localTotalText;

            const encodedText = encodeURIComponent(message);
            const waUrl = `https://wa.me/${whatsappNumber}?text=${encodedText}`;

            window.open(waUrl, '_blank');
        }

        window.addEventListener('DOMContentLoaded', () => {
            updateCartUI();
            loadCustomerData();

            // Guardar datos del cliente mientras escribe
            document.getElementById('cust-name').addEventListener('input', saveCustomerData);
            document.getElementById('cust-address').addEventListener('input', saveCustomerData);
            document.getElementById('cust-payment').addEventListener('change', saveCustomerData);
        });
    </script>
</body>
</html>

