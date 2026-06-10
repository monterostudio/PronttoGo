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
                <div class="relative w-full shadow-sm rounded-xl bg-white border border-slate-200 p-2 flex items-center space-x-2.5 transition-all focus-within:ring-2 focus-within:ring-primary/20 focus-within:border-primary mb-6">
                    <div class="pl-3.5 text-slate-400">
                        <i class="bi bi-search"></i>
                    </div>
                    <input 
                        type="text" 
                        id="catalog-search"
                        placeholder="Buscar productos..." 
                        class="w-full bg-transparent border-0 outline-none text-slate-800 text-sm placeholder-slate-400 pr-4 py-2"
                        autocomplete="off"
                    />
                    <button 
                        id="clear-search" 
                        class="pr-3 text-slate-400 hover:text-slate-600 font-bold text-sm transition-colors hidden"
                    >
                        <i class="bi bi-x-lg text-xs"></i>
                    </button>
                </div>



                <!-- Listado de Productos -->
                <div id="products-grid" class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 xl:grid-cols-6 gap-3 sm:gap-4">
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
                            class="product-card bg-white rounded-2xl border border-slate-100 shadow-sm hover:shadow-md hover:border-slate-200 transition-all duration-300 overflow-hidden flex flex-col relative group <?= $isAgotado ? 'opacity-65 cursor-not-allowed' : '' ?>"
                            data-category-id="<?= $prod['categoria_id'] ?>"
                            data-product-id="<?= $prod_id ?>"
                            data-name="<?= h(strtolower($prod['nombre'])) ?>"
                            data-description="<?= h(strtolower($prod['descripcion'] ?? '')) ?>"
                            onclick="handleProductClick(event, <?= $prod_id ?>, <?= htmlspecialchars(json_encode($prod_nombre), ENT_QUOTES, 'UTF-8') ?>, <?= $prod_precio ?>, <?= $prod_stock ?>, <?= $isAgotado ? 'true' : 'false' ?>)"
                        >
                            <!-- Imagen Superior -->
                            <div class="w-full aspect-square bg-slate-50 relative overflow-hidden shrink-0">
                                <?php if (!empty($prod['imagen_url'])): ?>
                                    <img 
                                        src="<?= h($prod['imagen_url']) ?>" 
                                        alt="<?= h($prod['nombre']) ?>" 
                                        class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500"
                                        loading="lazy"
                                    />
                                <?php else: ?>
                                    <div class="w-full h-full flex items-center justify-center text-slate-300">
                                        <i class="bi bi-image text-3xl"></i>
                                    </div>
                                <?php endif; ?>
                                
                                <!-- Etiquetas Superpuestas -->
                                <div class="absolute top-2 left-2 flex flex-col gap-1">
                                    <?php if ($isAgotado): ?>
                                        <span class="bg-red-500/90 backdrop-blur-sm text-white text-[10px] font-bold px-2 py-1 rounded-lg shadow-sm">
                                            Agotado
                                        </span>
                                    <?php elseif ($isStockCritico): ?>
                                        <span class="bg-amber-500/90 backdrop-blur-sm text-white text-[10px] font-bold px-2 py-1 rounded-lg shadow-sm">
                                            ¡Quedan <?= $prod['stock'] ?>!
                                        </span>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <!-- Contenido Inferior -->
                            <div class="p-3 flex flex-col flex-1">
                                <div class="flex-1 space-y-1 mb-2">
                                    <h3 class="product-title font-extrabold text-slate-800 text-xs sm:text-sm leading-snug group-hover:text-primary transition-colors">
                                        <?= h($prod['nombre']) ?>
                                    </h3>
                                    <?php if (!empty($prod['descripcion'])): ?>
                                        <p class="text-[10px] sm:text-xs text-slate-500 line-clamp-2 leading-relaxed">
                                            <?= h($prod['descripcion']) ?>
                                        </p>
                                    <?php endif; ?>
                                </div>

                                <div class="flex items-end justify-between mt-auto">
                                    <div class="flex flex-col">
                                        <span class="font-black text-sm sm:text-base text-slate-900 leading-none">
                                            $<?= $formattedPrice ?>
                                        </span>
                                        <?php if ($tasa_dolar > 1): ?>
                                            <span class="text-[10px] font-bold text-slate-400 mt-1">
                                                <?= $moneda_local_simbolo ?> <?= $formattedLocal ?> <?= $moneda_local_nombre ?>
                                            </span>
                                        <?php endif; ?>
                                    </div>

                                    <!-- Controles de Agregar / Cantidad -->
                                    <div class="relative min-w-[90px] flex justify-end">
                                        <?php if (!$isAgotado): ?>
                                            <!-- Botón Agregar (Visible por defecto) -->
                                            <button 
                                                type="button"
                                                class="btn-add w-8 h-8 rounded-xl bg-primary hover:bg-primary-hover text-white flex items-center justify-center shadow-md shadow-primary/20 transition-all active:scale-95"
                                                onclick="event.stopPropagation(); handleAddClick(<?= $prod_id ?>, <?= htmlspecialchars(json_encode($prod_nombre), ENT_QUOTES, 'UTF-8') ?>, <?= $prod_precio ?>, <?= $prod_stock ?>, <?= $isAgotado ? 'true' : 'false' ?>, event)"
                                                title="Agregar al pedido"
                                            >
                                                <i class="bi bi-plus-lg text-sm font-bold"></i>
                                            </button>

                                            <!-- Controles de Cantidad (Ocultos por defecto) -->
                                            <div class="qty-controls hidden items-center justify-between bg-slate-100 rounded-xl p-1 w-24 border border-slate-200">
                                                <button type="button" class="w-7 h-7 flex items-center justify-center rounded-lg bg-white shadow-sm text-slate-600 hover:text-primary transition-colors" onclick="event.stopPropagation(); updateQuantity(<?= $prod_id ?>, -1, '')">
                                                    <i class="bi bi-dash font-bold"></i>
                                                </button>
                                                <span class="qty-value text-xs font-black text-slate-800 w-6 text-center">1</span>
                                                <button type="button" class="w-7 h-7 flex items-center justify-center rounded-lg bg-white shadow-sm text-slate-600 hover:text-primary transition-colors" onclick="event.stopPropagation(); updateQuantity(<?= $prod_id ?>, 1, '')">
                                                    <i class="bi bi-plus font-bold"></i>
                                                </button>
                                            </div>
                                        <?php else: ?>
                                            <div class="w-9 h-9 rounded-xl bg-slate-100 flex items-center justify-center text-slate-400 cursor-not-allowed">
                                                <i class="bi bi-slash-circle text-sm"></i>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
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
