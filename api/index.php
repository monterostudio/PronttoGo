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
<html lang="es" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= h(!empty($config['nombre']) && $config['nombre'] !== 'Mi Tienda' ? $config['nombre'] : 'PronttoGo') ?> - Menú Digital</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; }
        .no-scrollbar::-webkit-scrollbar { display: none; }
        .no-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }
    </style>
</head>
<body class="bg-[#F8FAFC] text-[#0F172A] min-h-screen flex flex-col">

    <?php if ($dbError): ?>
        <!-- Barra de depuración en local para avisar errores de conexión de Supabase -->
        <div class="bg-red-600 text-white text-xs font-bold px-4 py-3 text-center shadow-md relative z-50">
            ⚠️ <strong>Error de Base de Datos (Local):</strong> <?= h($dbError) ?> | URL configurada: <code class="bg-red-700 px-1.5 py-0.5 rounded"><?= h(SUPABASE_URL) ?></code>
        </div>
    <?php endif; ?>

    <!-- Header -->
    <header class="h-16 bg-white/95 backdrop-blur-md border-b border-slate-100 sticky top-0 z-30 shadow-sm flex items-center">
        <div class="max-w-6xl w-full mx-auto px-4 sm:px-6 flex items-center justify-between">
            <span class="font-extrabold text-lg tracking-tight bg-gradient-to-r from-[#10B981] to-[#06B6D4] bg-clip-text text-transparent">PronttoGo</span>
            <a href="admin.php" class="text-xs font-bold text-slate-600 hover:text-slate-900 border border-slate-200 hover:border-slate-350 rounded-xl px-4 py-2 transition-all bg-white shadow-sm">
                Iniciar Sesión
            </a>
        </div>
    </header>

    <!-- Full Hero Section (Presentación de Ancho Completo Premium) -->
    <div class="relative w-full bg-gradient-to-r from-slate-900 via-slate-955 to-slate-900 text-white overflow-hidden border-b border-slate-800">
        <!-- Luces decorativas de fondo -->
        <div class="absolute right-1/4 top-0 w-80 h-80 bg-emerald-500/10 rounded-full blur-3xl"></div>
        <div class="absolute left-1/4 bottom-0 w-80 h-80 bg-cyan-500/10 rounded-full blur-3xl"></div>
        
        <!-- Contenido centrado y alíneado a la grilla principal -->
        <div class="max-w-6xl mx-auto px-4 sm:px-6 py-16 md:py-24 flex flex-col items-center text-center space-y-4 relative z-10">
            <span class="inline-flex items-center gap-1.5 px-3.5 py-1 bg-emerald-500/10 border border-emerald-500/20 text-emerald-400 rounded-full text-[10px] md:text-xs font-bold tracking-wide uppercase">
                ⚡ Pedidos por WhatsApp
            </span>
            <h1 class="text-3xl md:text-5xl font-extrabold tracking-tight text-white max-w-2xl leading-tight">
                <?= h(!empty($config['nombre']) && $config['nombre'] !== 'Mi Tienda' ? $config['nombre'] : 'PronttoGo') ?>
            </h1>
            <p class="text-xs md:text-base text-slate-400 max-w-xl leading-relaxed font-medium">
                Menú digital de especialidades. Agrega productos al carrito y envía tu pedido por WhatsApp de forma directa y rápida.
            </p>
        </div>
    </div>

    <!-- Contenedor del Catálogo y Sidebar (Grid Responsivo) -->
    <main class="max-w-6xl w-full mx-auto px-4 sm:px-6 py-8 flex flex-col md:flex-row gap-8 flex-1 pb-24 md:pb-12">
        
        <!-- Sidebar de Categorías (Solo Visible en Escritorio) -->
        <?php if (!empty($categorias)): ?>
            <aside class="hidden md:block w-56 flex-shrink-0 sticky top-24 h-fit space-y-2 pr-4">
                <h3 class="font-bold text-[10px] uppercase tracking-wider text-slate-400 mb-3 px-2">Categorías</h3>
                <nav class="space-y-1">
                    <?php foreach ($categorias as $cat): 
                        if (empty($productosPorCategoria[$cat['id']])) continue;
                    ?>
                        <a href="#cat-<?= h($cat['id']) ?>" 
                           class="block px-3.5 py-2.5 text-slate-600 hover:text-[#10B981] hover:bg-emerald-50/20 rounded-xl font-bold text-xs transition-all border border-transparent hover:border-emerald-50">
                            <?= h($cat['nombre_categoria']) ?>
                        </a>
                    <?php endforeach; ?>
                </nav>
            </aside>
        <?php endif; ?>

        <!-- Columna de Contenido principal -->
        <div class="flex-1 space-y-8 min-w-0">
            <!-- Categorías Deslizables (Sticky) - Solo Visible en Móvil -->
            <?php if (!empty($categorias)): ?>
                <div class="md:hidden -mx-4 sm:-mx-6 px-4 sm:px-6 py-2.5 border-y border-slate-100 bg-white sticky top-16 z-20 shadow-sm">
                    <nav class="flex space-x-2 overflow-x-auto no-scrollbar scroll-smooth">
                        <?php foreach ($categorias as $cat): 
                            if (empty($productosPorCategoria[$cat['id']])) continue;
                        ?>
                            <a href="#cat-<?= h($cat['id']) ?>" 
                               class="mobile-category-pill px-4 py-1.5 bg-slate-50 border border-slate-100 text-slate-600 hover:bg-slate-100 rounded-full font-bold text-xs whitespace-nowrap transition-all">
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
                <?php foreach ($categorias as $cat): 
                    $items = $productosPorCategoria[$cat['id']] ?? [];
                    if (empty($items)) continue;
                ?>
                    <section id="cat-<?= h($cat['id']) ?>" class="scroll-mt-28 space-y-4">
                        <div class="flex items-center space-x-3">
                            <h2 class="text-base md:text-lg font-extrabold tracking-tight text-slate-850"><?= h($cat['nombre_categoria']) ?></h2>
                            <div class="h-0.5 flex-1 bg-gradient-to-r from-[#10B981] to-[#06B6D4] opacity-20 rounded"></div>
                        </div>

                        <!-- Grid de Productos (1 en móvil/tablet, 2 en pantallas más grandes) -->
                        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                            <?php foreach ($items as $prod): ?>
                                <?php if (!empty($prod['imagen_url'])): ?>
                                    <!-- Tarjeta con Imagen -->
                                    <div class="bg-white p-4.5 rounded-2xl border border-slate-100 shadow-sm hover:shadow-md hover:border-slate-200 transition-all duration-300 flex items-stretch justify-between gap-4 relative group">
                                        <div class="flex-1 flex flex-col justify-between min-w-0 py-0.5">
                                            <div class="space-y-1">
                                                <h3 class="font-extrabold text-slate-900 text-sm md:text-base leading-snug group-hover:text-[#10B981] transition-colors"><?= h($prod['nombre']) ?></h3>
                                                <?php if (!empty($prod['descripcion'])): ?>
                                                    <p class="text-xs text-slate-455 line-clamp-2 md:line-clamp-3 leading-relaxed"><?= h($prod['descripcion']) ?></p>
                                                <?php endif; ?>
                                            </div>
                                            <span class="block font-black text-sm md:text-base text-slate-900 mt-2">$<?= number_format($prod['precio'], 2) ?></span>
                                        </div>
                                        <div class="flex flex-col items-center justify-between shrink-0 gap-3 w-20 md:w-24">
                                            <img src="<?= h($prod['imagen_url']) ?>" alt="<?= h($prod['nombre']) ?>" class="w-20 h-20 md:w-24 md:h-24 object-cover rounded-xl bg-slate-50 border border-slate-100 shadow-sm group-hover:scale-[1.02] transition-transform duration-300">
                                            <button onclick='addToCart(<?= json_encode([
                                                'id' => $prod['id'],
                                                'nombre' => $prod['nombre'],
                                                'precio' => floatval($prod['precio'])
                                            ]) ?>)' class="w-full bg-emerald-500 hover:bg-emerald-600 text-white font-bold text-center text-[10px] md:text-xs py-1.5 rounded-full shadow-md transition-all active:scale-95 whitespace-nowrap">
                                                + Agregar
                                            </button>
                                        </div>
                                    </div>
                                <?php else: ?>
                                    <!-- Tarjeta sin Imagen -->
                                    <div class="bg-white p-4.5 rounded-2xl border border-slate-100 shadow-sm hover:shadow-md hover:border-slate-200 transition-all duration-300 flex flex-col justify-between min-h-[120px] group">
                                        <div class="space-y-1">
                                            <h3 class="font-extrabold text-slate-900 text-sm md:text-base leading-snug group-hover:text-[#10B981] transition-colors"><?= h($prod['nombre']) ?></h3>
                                            <?php if (!empty($prod['descripcion'])): ?>
                                                <p class="text-xs text-slate-455 line-clamp-2 md:line-clamp-3 leading-relaxed"><?= h($prod['descripcion']) ?></p>
                                            <?php endif; ?>
                                        </div>
                                        <div class="flex items-center justify-between mt-4 pt-2 border-t border-slate-50">
                                            <span class="font-black text-sm md:text-base text-slate-900">$<?= number_format($prod['precio'], 2) ?></span>
                                            <button onclick='addToCart(<?= json_encode([
                                                'id' => $prod['id'],
                                                'nombre' => $prod['nombre'],
                                                'precio' => floatval($prod['precio'])
                                            ]) ?>)' class="bg-emerald-500 hover:bg-emerald-600 text-white font-bold text-[10px] md:text-xs py-1.5 px-4.5 rounded-full shadow-md transition-all active:scale-95 whitespace-nowrap">
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
        <div class="max-w-6xl w-full mx-auto px-4 sm:px-6 flex items-center justify-between text-xs font-semibold text-slate-500">
            <span>&copy; 2026 <?= h(!empty($config['nombre']) && $config['nombre'] !== 'Mi Tienda' ? $config['nombre'] : 'PronttoGo') ?></span>
            <a href="admin.php" class="text-[10px] uppercase font-bold text-slate-400 hover:text-slate-655 transition-colors flex items-center gap-1">
                <span>Powered by</span>
                <span class="bg-gradient-to-r from-[#10B981] to-[#06B6D4] bg-clip-text text-transparent font-extrabold">PronttoGo</span>
            </a>
        </div>
    </footer>

    <!-- Carrito Flotante (JS) -->
    <div id="floating-cart" class="fixed bottom-0 left-0 right-0 p-4 bg-transparent max-w-md mx-auto z-40 hidden">
        <button onclick="toggleCartDrawer(true)" class="w-full py-4 px-6 bg-gradient-to-r from-[#10B981] to-[#06B6D4] hover:opacity-95 text-white font-bold text-sm rounded-2xl shadow-xl flex justify-between items-center transition-all active:scale-98">
            <div class="flex items-center space-x-2">
                <span>🛒</span>
                <span id="cart-count">0 artículos</span>
            </div>
            <span id="cart-total">$0.00</span>
        </button>
    </div>

    <!-- Drawer del Carrito -->
    <div id="cart-drawer" class="fixed inset-0 z-50 hidden transition-opacity duration-300">
        <div onclick="toggleCartDrawer(false)" class="absolute inset-0 bg-slate-900/40 backdrop-blur-md"></div>
        
        <!-- Panel Desplizable -->
        <div class="absolute bottom-0 left-0 right-0 max-h-[85vh] bg-white rounded-t-3xl shadow-2xl border-t border-slate-100 flex flex-col max-w-md mx-auto overflow-hidden">
            <div class="px-6 py-5 border-b border-slate-50 flex items-center justify-between">
                <div>
                    <h3 class="font-extrabold text-lg text-slate-800">Mi Pedido</h3>
                    <p class="text-xs text-slate-400">Verifica los artículos seleccionados</p>
                </div>
                <button onclick="toggleCartDrawer(false)" class="w-8 h-8 rounded-full bg-slate-50 hover:bg-slate-100 flex items-center justify-center font-bold text-slate-500">
                    ✕
                </button>
            </div>

            <div id="cart-items" class="p-6 overflow-y-auto space-y-1 divide-y divide-slate-50 flex-1">
                <!-- Se rellena por JS de forma segura -->
            </div>

            <div class="p-6 border-t border-slate-50 space-y-4 bg-slate-50/50">
                <div class="flex justify-between items-center font-extrabold text-slate-955">
                    <span>Total a pagar</span>
                    <span id="drawer-total" class="text-xl">$0.00</span>
                </div>
                
                <button onclick="checkoutOrder()" class="w-full py-4 bg-gradient-to-r from-[#10B981] to-[#06B6D4] hover:opacity-95 text-white font-bold text-sm rounded-2xl shadow-lg transition-all flex justify-center items-center space-x-2 active:scale-98">
                    <span>Enviar Pedido por WhatsApp</span>
                    <span>💬</span>
                </button>
            </div>
        </div>
    </div>

    <!-- Script del Carrito (Vanilla JS) -->
    <script>
        const whatsappNumber = <?= json_encode($config['telefono_whatsapp']) ?>;
        const cartKey = 'cart_pronttogo';

        // ScrollSpy para Categorías
        window.addEventListener('DOMContentLoaded', () => {
            const observerOptions = {
                root: null,
                rootMargin: '-10% 0px -75% 0px',
                threshold: 0
            };

            const observer = new IntersectionObserver((entries) => {
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
        });

        function setActiveCategory(id) {
            // 1. Sidebar en Escritorio
            document.querySelectorAll('aside nav a').forEach(link => {
                if (link.getAttribute('href') === `#${id}`) {
                    link.classList.add('bg-emerald-50/70', 'text-[#10B981]', 'font-bold');
                    link.classList.remove('text-slate-605');
                } else {
                    link.classList.remove('bg-emerald-50/70', 'text-[#10B981]', 'font-bold');
                    link.classList.add('text-slate-605');
                }
            });

            // 2. Swiper en Móvil
            document.querySelectorAll('.mobile-category-pill').forEach(pill => {
                if (pill.getAttribute('href') === `#${id}`) {
                    pill.classList.add('bg-slate-900', 'text-white', 'border-slate-900');
                    pill.classList.remove('bg-slate-50', 'text-slate-600', 'border-slate-100');
                    
                    // Centrar el elemento en el scroll del swiper móvil
                    pill.scrollIntoView({ behavior: 'smooth', block: 'nearest', inline: 'center' });
                } else {
                    pill.classList.remove('bg-slate-900', 'text-white', 'border-slate-900');
                    pill.classList.add('bg-slate-50', 'text-slate-600', 'border-slate-100');
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

        function addToCart(product) {
            let cart = getCart();
            const existingItem = cart.find(item => item.id === product.id);
            
            if (existingItem) {
                existingItem.quantity += 1;
            } else {
                cart.push({
                    id: product.id,
                    nombre: product.nombre,
                    precio: parseFloat(product.precio),
                    quantity: 1
                });
            }
            saveCart(cart);
            
            const bar = document.getElementById('floating-cart');
            bar.classList.add('scale-105');
            setTimeout(() => bar.classList.remove('scale-105'), 150);
        }

        function updateQuantity(productId, change) {
            let cart = getCart();
            const item = cart.find(item => item.id === productId);
            if (!item) return;

            item.quantity += change;
            if (item.quantity <= 0) {
                cart = cart.filter(item => item.id !== productId);
            }
            saveCart(cart);
            
            if (cart.length === 0) {
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

                const priceEl = document.createElement('p');
                priceEl.className = "text-xs font-semibold text-slate-400 mt-0.5";
                priceEl.textContent = `$${item.precio.toFixed(2)} c/u`;

                infoEl.appendChild(nameEl);
                infoEl.appendChild(priceEl);

                const controlsEl = document.createElement('div');
                controlsEl.className = "flex items-center space-x-2.5";

                const btnMinus = document.createElement('button');
                btnMinus.className = "w-7 h-7 rounded-xl bg-slate-50 hover:bg-slate-100 flex items-center justify-center font-bold text-sm text-slate-655 transition-colors";
                btnMinus.textContent = "−";
                btnMinus.onclick = () => updateQuantity(item.id, -1);

                const qtyEl = document.createElement('span');
                qtyEl.className = "text-sm font-extrabold w-4 text-center text-slate-800";
                qtyEl.textContent = item.quantity;

                const btnPlus = document.createElement('button');
                btnPlus.className = "w-7 h-7 rounded-xl bg-slate-50 hover:bg-slate-100 flex items-center justify-center font-bold text-sm text-slate-655 transition-colors";
                btnPlus.textContent = "+";
                btnPlus.onclick = () => updateQuantity(item.id, 1);

                controlsEl.appendChild(btnMinus);
                controlsEl.appendChild(qtyEl);
                controlsEl.appendChild(btnPlus);

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
                itemsText += `${item.quantity}x ${item.nombre} ($${item.precio.toFixed(2)} c/u)\n`;
            });

            // Formato exacto solicitado
            const message = `*Pedido de PronttoGo* 🛒\n` +
                            `--------------------------\n` +
                            `${itemsText}` +
                            `--------------------------\n` +
                            `*Total a pagar: $${totalPrice.toFixed(2)}*`;

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
