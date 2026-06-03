<?php
$es_admin = false;
/**
 * PronttoGo - Catálogo Público Responsivo (Single-Store)
 * Refactorizado a arquitectura MVC simplificada
 */

require_once __DIR__ . '/../core/config.php';
require_once __DIR__ . '/../core/db.php';
require_once __DIR__ . '/../core/auth.php';

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
$tipo_negocio = $config['tipo_negocio'] ?? 'gastronomia';
$tasa_dolar = floatval($config['tasa_dolar'] ?? 1.00);
$tasa_tipo = $config['tasa_tipo'] ?? 'manual';
$moneda_local_nombre = !empty($config['moneda_nombre']) ? $config['moneda_nombre'] : 'Bs.';
$moneda_local_simbolo = !empty($config['moneda_simbolo']) ? $config['moneda_simbolo'] : 'Bs.';
$costo_delivery = floatval($config['costo_delivery'] ?? 0.00);
$direccion_local = !empty($config['direccion']) ? $config['direccion'] : '';
$horario_local = !empty($config['horario']) ? $config['horario'] : '';

// 2. CONSULTAR CATEGORÍAS (Ordenadas)
$resCategorias = supabase_request('GET', 'categorias?order=orden_visual.asc');
$categorias = $resCategorias['success'] ? $resCategorias['data'] : [];
if (!empty($categorias)) {
    foreach ($categorias as &$c) {
        $c['nombre'] = $c['nombre_categoria'] ?? $c['nombre'] ?? 'Sin Categoría';
    }
    unset($c);
}

// 3. CONSULTAR PRODUCTOS DISPONIBLES
$resProductos = supabase_request('GET', 'productos?disponible=eq.true&order=id.asc');
$productos = $resProductos['success'] ? $resProductos['data'] : [];
if (!empty($productos)) {
    foreach ($productos as &$p) {
        $p['nombre'] = $p['nombre'] ?? $p['nombre_producto'] ?? 'Sin Nombre';
        $p['precio_usd'] = $p['precio_usd'] ?? $p['precio'] ?? 0;
    }
    unset($p);
}

// Agrupar productos por categoría
$productosPorCategoria = [];
foreach ($productos as $prod) {
    $productosPorCategoria[$prod['categoria_id']][] = $prod;
}

// Determinar si hay algún error de conexión o base de datos (visible solo en entorno local)
$dbError = null;
if ($isLocalhost) {
    if (!$resCategorias['success']) {
        $dbError = 'Error de Categorías: ' . ($resCategorias['error'] ?? $resCategorias['raw'] ?? 'Error de conexión.');
    } elseif (!$resProductos['success']) {
        $dbError = 'Error de Productos: ' . ($resProductos['error'] ?? $resProductos['raw'] ?? 'Error de conexión.');
    }
}

// --- VISTAS ---
require_once __DIR__ . '/../includes/header.php';
?>

    <!-- Full Hero Section (Presentación de Ancho Completo Premium en Blanco) -->
    <div class="relative w-full bg-gradient-to-br from-[var(--hero-bg-from)] via-[var(--hero-bg-via)] to-[var(--hero-bg-to)] text-slate-800 overflow-hidden border-b border-slate-200/80 shadow-sm">
        <div class="absolute -right-10 top-0 w-96 h-96 pointer-events-none" style="background: radial-gradient(circle, var(--hero-glow) 0%, transparent 70%);"></div>
        <div class="absolute -left-10 bottom-0 w-96 h-96 pointer-events-none" style="background: radial-gradient(circle, rgba(42,53,67,0.03) 0%, transparent 70%);"></div>
        <div class="absolute left-1/2 top-1/2 -translate-x-1/2 -translate-y-1/2 w-[600px] h-40 pointer-events-none" style="background: radial-gradient(circle, var(--hero-glow) 0%, transparent 70%);"></div>
        
        <div class="max-w-6xl mx-auto px-4 sm:px-6 pt-12 pb-10 md:pt-16 md:pb-12 flex flex-col items-center text-center space-y-4 relative z-10">
            <?= render_logo('hero', $config) ?>

            <?php
            $hero_titulo = !empty($config['hero_titulo']) ? $config['hero_titulo'] : 'Tu catálogo digital, siempre disponible';
            $hero_subtitulo = !empty($config['hero_subtitulo']) ? $config['hero_subtitulo'] : 'Explora nuestros productos, arma tu pedido y envíalo directo por WhatsApp en segundos.';
            ?>

            <div class="space-y-2 max-w-2xl pt-1">
                <h1 class="text-xl md:text-3xl font-extrabold text-[#2A3543] tracking-tight leading-snug">
                    <?= h($hero_titulo) ?>
                </h1>
                <p class="text-sm text-slate-500 leading-relaxed font-medium">
                    <?= h($hero_subtitulo) ?>
                </p>
            </div>
        </div>
    </div>
    <!-- Contenedor del Catálogo (Optimizado en PHP y Vanilla JS) -->
    <main class="max-w-6xl w-full mx-auto px-4 sm:px-6 py-8 flex-1 pb-24 md:pb-12">
        <div class="w-full space-y-6">

            <?php if (empty($productos)): ?>
                <?php
                $placeholderIcon = 'bi-shop';
                if ($tipo_negocio === 'boutique') $placeholderIcon = 'bi-handbag';
                elseif ($tipo_negocio === 'ferreteria_repuestos') $placeholderIcon = 'bi-tools';
                elseif ($tipo_negocio === 'belleza_estetica') $placeholderIcon = 'bi-scissors';
                elseif ($tipo_negocio === 'otros') $placeholderIcon = 'bi-bag';
                ?>
                <!-- Catálogo Vacío -->
                <div class="text-center py-20 max-w-sm mx-auto space-y-3">
                    <div class="w-12 h-12 bg-slate-50 text-slate-400 rounded-full flex items-center justify-center mx-auto text-xl">
                        <i class="bi <?= $placeholderIcon ?>"></i>
                    </div>
                    <h3 class="font-bold text-slate-800 text-sm">El catálogo está vacío</h3>
                    <p class="text-slate-400 text-xs max-w-xs mx-auto leading-relaxed">
                        Aún no se han añadido productos. Inicia sesión en el panel para comenzar a cargar tu catálogo.
                    </p>
                    <div class="pt-2">
                        <a href="/admin" class="inline-flex items-center gap-1.5 px-4 py-2 border border-slate-200 text-slate-600 hover:bg-slate-50 rounded-xl font-bold text-xs transition-all shadow-sm">
                            Ir al Panel <i class="bi bi-gear-fill"></i>
                        </a>
                    </div>
                </div>
            <?php else: ?>
                <!-- Buscador de Productos -->
                <div class="relative w-full shadow-sm rounded-2xl bg-white border border-slate-100 p-2 flex items-center space-x-2.5 transition-all focus-within:ring-2 focus-within:ring-primary/30 focus-within:border-primary">
                    <div class="pl-3.5 text-slate-400">
                        <i class="bi bi-search"></i>
                    </div>
                    <input 
                        type="text" 
                        id="catalog-search"
                        placeholder="Buscar productos..." 
                        class="w-full bg-transparent border-0 outline-none text-slate-800 text-sm placeholder-slate-400 pr-4 py-1.5"
                        autocomplete="off"
                    />
                    <button 
                        id="clear-search" 
                        class="pr-3 text-slate-400 hover:text-slate-600 font-bold text-sm transition-colors hidden"
                    >
                        <i class="bi bi-x-lg text-xs"></i>
                    </button>
                </div>

                <!-- Categorías Deslizables -->
                <?php if (!empty($categorias)): ?>
                    <div class="-mx-4 sm:-mx-6 border-y border-slate-100 bg-white sticky top-16 z-20 shadow-sm">
                        <nav class="flex overflow-x-auto no-scrollbar scroll-smooth" style="padding: 10px 16px; gap: 8px; scroll-snap-type: x mandatory;">
                            <?php 
                            // Encontrar la primera categoría activa con productos para marcarla como activa por defecto
                            $active_cat_id = null;
                            foreach ($categorias as $cat) {
                                $hasProducts = false;
                                foreach ($productos as $p) {
                                    if (strval($p['categoria_id']) === strval($cat['id'])) {
                                        $hasProducts = true;
                                        break;
                                    }
                                }
                                if ($hasProducts) {
                                    $active_cat_id = $cat['id'];
                                    break;
                                }
                            }
                            
                            foreach ($categorias as $cat): 
                                $hasProducts = false;
                                foreach ($productos as $p) {
                                    if (strval($p['categoria_id']) === strval($cat['id'])) {
                                        $hasProducts = true;
                                        break;
                                    }
                                }
                                if (!$hasProducts) continue;
                                $isActive = strval($active_cat_id) === strval($cat['id']);
                                $btnClass = $isActive 
                                    ? 'bg-primary border-primary text-white shadow-sm' 
                                    : 'bg-slate-50 border-slate-100 text-slate-600 hover:bg-slate-100';
                            ?>
                                <button
                                    type="button"
                                    class="category-btn px-4 py-2 border rounded-xl font-bold text-xs whitespace-nowrap transition-all duration-200 active:scale-95 <?= $btnClass ?>"
                                    data-category-id="<?= $cat['id'] ?>"
                                    style="scroll-snap-align: start; flex-shrink: 0;"
                                >
                                    <?= h($cat['nombre']) ?>
                                </button>
                            <?php endforeach; ?>
                            <div style="flex-shrink: 0; width: 4px;"></div>
                        </nav>
                    </div>
                <?php endif; ?>

                <!-- Listado de Productos -->
                <div id="products-grid" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <?php 
                    foreach ($productos as $prod): 
                        $formattedPrice = number_format($prod['precio_usd'], 2);
                        $totalLocal = $prod['precio_usd'] * $tasa_dolar;
                        $formattedLocal = number_format($totalLocal, 2, ',', '.');
                        $isAgotado = $prod['stock'] !== null && intval($prod['stock']) <= 0;
                        $isStockCritico = $prod['stock'] !== null && intval($prod['stock']) > 0 && intval($prod['stock']) <= 5;
                        
                        $prod_id = intval($prod['id']);
                        $prod_nombre = $prod['nombre'];
                        $prod_precio = floatval($prod['precio_usd']);
                        $prod_stock = $prod['stock'] !== null ? intval($prod['stock']) : 'null';
                    ?>
                        <div 
                            class="product-card bg-white p-5 md:p-6 rounded-2xl border border-slate-100 shadow-sm transition-[box-shadow,border-color] duration-300 flex items-stretch justify-between gap-4 relative group cursor-pointer hover:shadow-md hover:border-slate-200 <?= $isAgotado ? 'opacity-65 cursor-not-allowed' : '' ?>"
                            data-category-id="<?= $prod['categoria_id'] ?>"
                            data-name="<?= h(strtolower($prod['nombre'])) ?>"
                            data-description="<?= h(strtolower($prod['descripcion'] ?? '')) ?>"
                            onclick="handleProductClick(event, <?= $prod_id ?>, <?= htmlspecialchars(json_encode($prod_nombre), ENT_QUOTES, 'UTF-8') ?>, <?= $prod_precio ?>, <?= $prod_stock ?>, <?= $isAgotado ? 'true' : 'false' ?>)"
                        >
                            <div class="flex-1 flex flex-col justify-between min-w-0 py-0.5">
                                <div class="space-y-1">
                                    <h3 class="product-title font-extrabold text-slate-900 text-sm md:text-base leading-snug transition-colors <?= !$isAgotado ? 'group-hover:text-primary' : '' ?>">
                                        <?= h($prod['nombre']) ?>
                                    </h3>
                                    <?php if (!empty($prod['descripcion'])): ?>
                                        <p class="text-xs text-slate-500 line-clamp-2 md:line-clamp-3 leading-relaxed">
                                            <?= h($prod['descripcion']) ?>
                                        </p>
                                    <?php endif; ?>
                                </div>
                                <div>
                                    <span class="block font-black text-sm md:text-base text-slate-900 mt-2">
                                        $<?= $formattedPrice ?>
                                    </span>
                                    <?php if ($tasa_dolar > 1): ?>
                                        <span class="block text-xs font-bold text-slate-500 mt-0.5">
                                            <?= $moneda_local_simbolo ?> <?= $formattedLocal ?> <?= $moneda_local_nombre ?>
                                        </span>
                                    <?php endif; ?>
                                    <?php if ($isAgotado): ?>
                                        <span class="inline-flex items-center gap-1 text-[10px] font-bold text-red-500 mt-1.5 bg-red-50 px-2 py-0.5 rounded-md">
                                            <i class="bi bi-x-circle-fill"></i> Agotado
                                        </span>
                                    <?php endif; ?>
                                    <?php if ($isStockCritico): ?>
                                        <span class="inline-flex items-center gap-1 text-[10px] font-bold text-amber-600 mt-1.5 bg-amber-50 px-2 py-0.5 rounded-md">
                                            <i class="bi bi-exclamation-triangle-fill"></i> ¡Solo quedan <?= $prod['stock'] ?>!
                                        </span>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <div class="flex flex-col items-center justify-between shrink-0 gap-3 w-16 sm:w-20 md:w-24">
                                <?php if (!empty($prod['imagen_url'])): ?>
                                    <img 
                                        src="<?= h($prod['imagen_url']) ?>" 
                                        alt="<?= h($prod['nombre']) ?>" 
                                        class="w-16 h-16 sm:w-20 sm:h-20 md:w-24 md:h-24 object-cover rounded-xl bg-slate-50 border border-slate-100 shadow-sm group-hover:scale-[1.02] transition-transform duration-300"
                                    />
                                <?php else: ?>
                                    <div class="w-16 sm:w-20 md:w-24 h-16 sm:h-20 md:h-24 rounded-xl bg-slate-50 border border-slate-100 flex items-center justify-center text-slate-350">
                                        <i class="bi bi-image text-xl"></i>
                                    </div>
                                <?php endif; ?>
                                <button 
                                    type="button"
                                    class="w-full font-bold text-center text-[10px] md:text-xs py-1.5 rounded-full shadow-md transition-all active:scale-95 whitespace-nowrap <?= $isAgotado ? 'bg-slate-200 text-slate-400 cursor-not-allowed shadow-none' : 'bg-primary hover:bg-primary-hover text-white' ?>"
                                    <?= $isAgotado ? 'disabled' : '' ?>
                                    onclick="event.stopPropagation(); handleAddClick(<?= $prod_id ?>, <?= htmlspecialchars(json_encode($prod_nombre), ENT_QUOTES, 'UTF-8') ?>, <?= $prod_precio ?>, <?= $prod_stock ?>, <?= $isAgotado ? 'true' : 'false' ?>, event)"
                                >
                                    <?= $isAgotado ? 'Agotado' : '+ Agregar' ?>
                                </button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <!-- Búsqueda sin Resultados -->
                <div id="empty-state" class="text-center py-16 space-y-3 hidden">
                    <div class="w-12 h-12 bg-slate-50 text-slate-400 rounded-full flex items-center justify-center mx-auto text-xl">
                        <i class="bi bi-search text-slate-350 text-xl"></i>
                    </div>
                    <h3 class="font-bold text-slate-800 text-sm">No se encontraron productos</h3>
                    <p class="text-slate-400 text-xs max-w-xs mx-auto leading-relaxed">
                        Intenta con otra palabra clave o explora las categorías del catálogo.
                    </p>
                </div>
            <?php endif; ?>
        </div>
    </main>

    <!-- Script de control de catálogo (Vanilla JS, súper ligero) -->
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            let activeCategoryId = null;
            let searchQuery = '';

            // Obtener categoría activa inicial de PHP
            const initialActiveBtn = document.querySelector('.category-btn.bg-primary');
            if (initialActiveBtn) {
                activeCategoryId = initialActiveBtn.getAttribute('data-category-id');
            }

            function filterCatalog() {
                const cards = document.querySelectorAll('#products-grid .product-card');
                let visibleCount = 0;

                cards.forEach(card => {
                    const catId = card.getAttribute('data-category-id');
                    const name = card.getAttribute('data-name');
                    const desc = card.getAttribute('data-description');

                    const matchesCategory = !activeCategoryId || String(catId) === String(activeCategoryId);
                    const matchesSearch = !searchQuery || name.includes(searchQuery) || desc.includes(searchQuery);

                    if (matchesCategory && matchesSearch) {
                        card.classList.remove('hidden');
                        card.classList.add('flex');
                        visibleCount++;
                    } else {
                        card.classList.add('hidden');
                        card.classList.remove('flex');
                    }
                });

                const emptyState = document.getElementById('empty-state');
                if (emptyState) {
                    if (visibleCount === 0 && cards.length > 0) {
                        emptyState.classList.remove('hidden');
                    } else {
                        emptyState.classList.add('hidden');
                    }
                }
            }

            // Manejadores de eventos para los botones de categorías
            const buttons = document.querySelectorAll('.category-btn');
            buttons.forEach(btn => {
                btn.addEventListener('click', () => {
                    buttons.forEach(b => {
                        b.className = 'category-btn px-4 py-2 border rounded-xl font-bold text-xs whitespace-nowrap transition-all duration-200 active:scale-95 bg-slate-50 border-slate-100 text-slate-600 hover:bg-slate-100';
                    });
                    btn.className = 'category-btn px-4 py-2 border rounded-xl font-bold text-xs whitespace-nowrap transition-all duration-200 active:scale-95 bg-primary border-primary text-white shadow-sm';
                    activeCategoryId = btn.getAttribute('data-category-id');
                    filterCatalog();
                });
            });

            // Manejo del Buscador
            const searchInput = document.getElementById('catalog-search');
            const clearBtn = document.getElementById('clear-search');

            if (searchInput) {
                searchInput.addEventListener('input', (e) => {
                    searchQuery = e.target.value.toLowerCase().trim();
                    if (clearBtn) {
                        if (searchQuery) {
                            clearBtn.classList.remove('hidden');
                        } else {
                            clearBtn.classList.add('hidden');
                        }
                    }
                    filterCatalog();
                });
            }

            if (clearBtn) {
                clearBtn.addEventListener('click', () => {
                    if (searchInput) {
                        searchInput.value = '';
                    }
                    searchQuery = '';
                    clearBtn.classList.add('hidden');
                    filterCatalog();
                });
            }

            // Inicializar filtros al cargar
            filterCatalog();
        });

        // Controladores globales de clics de productos
        function handleProductClick(event, id, nombre, precio, stock, isAgotado) {
            if (isAgotado) return;
            // Solo agregar si el clic no viene directamente del botón (para evitar doble llamada)
            if (!event.target.closest('button')) {
                handleAddClick(id, nombre, precio, stock, isAgotado, event);
            }
        }

        function handleAddClick(id, nombre, precio, stock, isAgotado, event) {
            if (isAgotado) return;
            if (window.addToCart) {
                window.addToCart({ id, nombre, precio, stock }, event);
            }
        }
    </script>

<?php
require_once __DIR__ . '/../includes/footer.php';
?>
