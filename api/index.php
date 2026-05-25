<?php
/**
 * PronttoGo - Catálogo Público (Single-Store)
 * Renderiza el menú digital del local y maneja el carrito interactivo.
 */

require_once __DIR__ . '/config.php';

// 1. OBTENER CONFIGURACIÓN DEL LOCAL (Fila única id = 1)
$response = supabase_request('GET', 'configuracion?id=eq.1');

if ($response['success'] && !empty($response['data'])) {
    $config = $response['data'][0];
} else {
    // Valores por defecto de contingencia si no se ha configurado la DB
    $config = [
        'nombre' => 'Mi Tienda',
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
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= h($config['nombre']) ?> - Menú Digital</title>
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
<body class="bg-[#F8FAFC] text-[#0F172A] min-h-screen flex flex-col pb-28">

    <!-- Header -->
    <header class="bg-white border-b border-slate-100 sticky top-0 z-30 shadow-sm">
        <div class="max-w-2xl mx-auto px-4 py-4 flex items-center justify-between">
            <div>
                <h1 class="font-extrabold text-xl tracking-tight text-slate-900">
                    <?= h(!empty($config['nombre']) && $config['nombre'] !== 'Mi Tienda' ? $config['nombre'] : 'PronttoGo') ?>
                </h1>
            </div>
            <div class="flex items-center space-x-3">
                <a href="admin.php" class="text-xs font-bold text-slate-650 hover:text-slate-900 border border-slate-200 hover:border-slate-350 rounded-xl px-4 py-2 transition-all bg-white shadow-sm">
                    Iniciar Sesión
                </a>
            </div>
        </div>
        
        <!-- Categorías Deslizables -->
        <?php if (!empty($categorias)): ?>
            <div class="border-t border-slate-50 bg-white">
                <nav class="max-w-2xl mx-auto px-4 py-2.5 flex space-x-2 overflow-x-auto no-scrollbar scroll-smooth">
                    <?php foreach ($categorias as $cat): 
                        if (empty($productosPorCategoria[$cat['id']])) continue;
                    ?>
                        <a href="#cat-<?= h($cat['id']) ?>" 
                           class="px-4 py-1.5 bg-slate-50 border border-slate-100 text-slate-650 hover:bg-slate-100 rounded-full font-bold text-xs whitespace-nowrap transition-all">
                            <?= h($cat['nombre_categoria']) ?>
                        </a>
                    <?php endforeach; ?>
                </nav>
            </div>
        <?php endif; ?>
    </header>

    <!-- Contenido del Menú -->
    <main class="max-w-2xl w-full mx-auto px-4 py-6 space-y-8 flex-1">
        <?php if (empty($productos)): ?>
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
                        <h2 class="text-lg font-extrabold tracking-tight text-slate-800"><?= h($cat['nombre_categoria']) ?></h2>
                        <div class="h-0.5 flex-1 bg-gradient-to-r from-[#10B981] to-[#06B6D4] opacity-20 rounded"></div>
                    </div>

                    <div class="grid gap-3">
                        <?php foreach ($items as $prod): ?>
                            <div class="bg-white p-3.5 rounded-2xl border border-slate-100 shadow-sm flex items-start justify-between gap-4 hover:border-slate-200 transition-colors">
                                <div class="flex-1 space-y-1.5">
                                    <h3 class="font-bold text-slate-900 text-sm md:text-base"><?= h($prod['nombre']) ?></h3>
                                    <?php if (!empty($prod['descripcion'])): ?>
                                        <p class="text-xs text-slate-400 line-clamp-2 leading-relaxed"><?= h($prod['descripcion']) ?></p>
                                    <?php endif; ?>
                                    <span class="block font-extrabold text-sm md:text-base text-slate-900">$<?= number_format($prod['precio'], 2) ?></span>
                                </div>
                                
                                <div class="flex flex-col items-center justify-between h-full min-h-[85px] gap-2.5">
                                    <?php if (!empty($prod['imagen_url'])): ?>
                                        <img src="<?= h($prod['imagen_url']) ?>" alt="<?= h($prod['nombre']) ?>" class="w-20 h-20 object-cover rounded-xl bg-slate-50">
                                    <?php endif; ?>
                                    
                                    <button onclick='addToCart(<?= json_encode([
                                        'id' => $prod['id'],
                                        'nombre' => $prod['nombre'],
                                        'precio' => floatval($prod['precio'])
                                    ]) ?>)' class="px-4 py-1.5 bg-gradient-to-r from-[#10B981] to-[#06B6D4] hover:opacity-90 text-white font-bold text-xs rounded-xl shadow-md transition-all whitespace-nowrap">
                                        Agregar
                                    </button>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </section>
            <?php endforeach; ?>
        <?php endif; ?>
    </main>

    <!-- Footer -->
    <footer class="text-center text-[10px] text-slate-450 py-8">
        Powered by <span class="font-extrabold bg-gradient-to-r from-[#10B981] to-[#06B6D4] bg-clip-text text-transparent">PronttoGo</span>
    </footer>

    <!-- Carrito Flotante -->
    <div id="floating-cart" class="fixed bottom-0 left-0 right-0 p-4 bg-transparent max-w-2xl mx-auto z-45 hidden">
        <button onclick="toggleCartDrawer(true)" class="w-full py-4 px-6 bg-gradient-to-r from-[#10B981] to-[#06B6D4] hover:opacity-95 text-white font-bold text-sm rounded-2xl shadow-xl flex justify-between items-center transition-all animate-bounce">
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
        
        <div class="absolute bottom-0 left-0 right-0 max-h-[85vh] bg-white rounded-t-3xl shadow-2xl border-t border-slate-100 flex flex-col max-w-2xl mx-auto overflow-hidden">
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
                <div class="flex justify-between items-center font-extrabold text-slate-950">
                    <span>Total a pagar</span>
                    <span id="drawer-total" class="text-xl">$0.00</span>
                </div>
                
                <button onclick="checkoutOrder()" class="w-full py-4 bg-gradient-to-r from-[#10B981] to-[#06B6D4] hover:opacity-95 text-white font-bold text-sm rounded-2xl shadow-lg transition-all flex justify-center items-center space-x-2">
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
                itemEl.className = "flex justify-between items-center py-4 border-b border-slate-100/55 first:pt-2 last:border-b-0";

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
                btnMinus.className = "w-7 h-7 rounded-xl bg-slate-50 hover:bg-slate-100 flex items-center justify-center font-bold text-sm text-slate-600 transition-colors";
                btnMinus.textContent = "−";
                btnMinus.onclick = () => updateQuantity(item.id, -1);

                const qtyEl = document.createElement('span');
                qtyEl.className = "text-sm font-extrabold w-4 text-center text-slate-850";
                qtyEl.textContent = item.quantity;

                const btnPlus = document.createElement('button');
                btnPlus.className = "w-7 h-7 rounded-xl bg-slate-50 hover:bg-slate-100 flex items-center justify-center font-bold text-sm text-slate-600 transition-colors";
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
