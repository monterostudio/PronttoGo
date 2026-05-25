<?php
/**
 * PronttoGo - Catálogo Público Dinámico y Landing Page
 * Renderiza el catálogo según el slug y maneja el carrito interactivo.
 */

require_once __DIR__ . '/config.php';

// Capturar y limpiar el slug
$slug = isset($_GET['slug']) ? sanitize_slug($_GET['slug']) : '';

// 1. SI NO HAY SLUG, MOSTRAR LANDING PAGE PRINCIPAL
if (empty($slug)):
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PronttoGo - Crea tu Catálogo Digital en Segundos</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;750;800&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; }
    </style>
</head>
<body class="bg-[#F8FAFC] text-[#0F172A] min-h-screen flex flex-col justify-between">

    <!-- Header -->
    <header class="bg-white/80 backdrop-blur-md sticky top-0 z-50 border-b border-slate-100">
        <div class="max-w-5xl mx-auto px-6 py-4 flex justify-between items-center">
            <span class="font-extrabold text-xl tracking-tight bg-gradient-to-r from-[#10B981] to-[#06B6D4] bg-clip-text text-transparent">PronttoGo</span>
            <a href="/admin" class="text-xs font-bold bg-[#10B981] hover:bg-emerald-600 text-white px-5 py-2.5 rounded-xl transition-all shadow-md">
                Admin Panel
            </a>
        </div>
    </header>

    <!-- Hero Section -->
    <main class="flex-1 max-w-4xl mx-auto px-6 py-12 md:py-24 text-center flex flex-col items-center justify-center space-y-8">
        <div class="inline-flex items-center space-x-2 bg-emerald-50 text-[#10B981] text-xs font-bold px-4 py-1.5 rounded-full">
            <span>🚀 Lanzamiento Oficial V1.0</span>
        </div>
        <h1 class="text-4xl md:text-6xl font-extrabold tracking-tight leading-tight">
            Tus pedidos directos a <span class="bg-gradient-to-r from-[#10B981] to-[#06B6D4] bg-clip-text text-transparent">WhatsApp</span>
        </h1>
        <p class="text-slate-500 text-base md:text-lg max-w-xl">
            La plataforma ultra rápida y minimalista para comercios. Sube tus productos, comparte tu enlace y recibe pedidos organizados al instante.
        </p>
        <div class="flex flex-col sm:flex-row gap-4 w-full justify-center max-w-md pt-4">
            <a href="/admin#register" class="flex-1 py-3.5 bg-gradient-to-r from-[#10B981] to-[#06B6D4] text-white font-bold text-sm rounded-xl shadow-lg hover:opacity-95 transition-all text-center">
                Crear mi Catálogo Gratis
            </a>
            <a href="/admin" class="flex-1 py-3.5 bg-white border border-slate-200 text-slate-700 font-bold text-sm rounded-xl hover:bg-slate-50 transition-all text-center">
                Ingresar a mi Panel
            </a>
        </div>
    </main>

    <!-- Footer -->
    <footer class="bg-white border-t border-slate-100 py-6 text-center text-xs text-slate-400 font-medium">
        &copy; 2026 PronttoGo. Hecho para Vercel y Supabase.
    </footer>

</body>
</html>
<?php
    exit;
endif;

// 2. SI HAY SLUG, CONSULTAR COMERCIO EN SUPABASE
$response = supabase_request('GET', 'comercios?slug=eq.' . rawurlencode($slug));

if (!$response['success'] || empty($response['data']) || !$response['data'][0]['activo']) {
    // RENDERIZAR VISTA DE ERROR 404 - NEGOCIO NO REGISTRADO
    http_response_code(404);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Negocio no registrado - PronttoGo</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; }
    </style>
</head>
<body class="bg-[#F8FAFC] text-[#0F172A] min-h-screen flex flex-col items-center justify-center p-6 text-center">
    <div class="max-w-md w-full bg-white p-8 rounded-3xl border border-slate-100 shadow-xl space-y-6">
        <div class="w-16 h-16 bg-red-50 text-red-500 rounded-full flex items-center justify-center mx-auto text-3xl">
            🧐
        </div>
        <h1 class="text-2xl font-extrabold tracking-tight">Negocio no registrado</h1>
        <p class="text-slate-400 text-sm">
            El enlace que estás intentando visitar no pertenece a ningún comercio activo en nuestra plataforma.
        </p>
        <a href="/" class="block w-full py-3.5 bg-gradient-to-r from-[#10B981] to-[#06B6D4] text-white font-bold text-sm rounded-xl shadow-md hover:opacity-90 transition-all">
            Ir a PronttoGo
        </a>
    </div>
</body>
</html>
<?php
    exit;
}

// Comercio Válido, cargar datos
$comercio = $response['data'][0];
$comercio_id = $comercio['id'];

// Consultar Categorías (ordenadas)
$resCategorias = supabase_request('GET', 'categorias?comercio_id=eq.' . rawurlencode($comercio_id) . '&order=orden_visual.asc');
$categorias = $resCategorias['success'] ? $resCategorias['data'] : [];

// Consultar Productos Disponibles
$resProductos = supabase_request('GET', 'productos?comercio_id=eq.' . rawurlencode($comercio_id) . '&disponible=eq.true&order=id.asc');
$productos = $resProductos['success'] ? $resProductos['data'] : [];

// Agrupar productos por categoría en un mapa asociativo
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
    <title><?= h($comercio['nombre']) ?> - Menú Digital</title>
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

    <!-- Header del Comercio -->
    <header class="bg-white border-b border-slate-100 sticky top-0 z-30 shadow-sm">
        <div class="max-w-2xl mx-auto px-4 py-4 flex items-center justify-between">
            <div>
                <h1 class="font-extrabold text-xl tracking-tight text-slate-900"><?= h($comercio['nombre']) ?></h1>
                <p class="text-[10px] text-slate-400 font-semibold flex items-center mt-0.5">
                    <span class="inline-block w-2.5 h-2.5 rounded-full bg-[#10B981] mr-1.5 animate-pulse"></span>
                    Abierto para pedidos
                </p>
            </div>
            <div class="text-right">
                <span class="text-[10px] uppercase font-bold text-slate-400">Powered by</span>
                <span class="block text-xs font-extrabold bg-gradient-to-r from-[#10B981] to-[#06B6D4] bg-clip-text text-transparent">PronttoGo</span>
            </div>
        </div>
        
        <!-- Navegación por Categorías Deslizable (Excelente para móviles) -->
        <?php if (!empty($categorias)): ?>
            <div class="border-t border-slate-50 bg-white">
                <nav class="max-w-2xl mx-auto px-4 py-2.5 flex space-x-2 overflow-x-auto no-scrollbar scroll-smooth">
                    <?php foreach ($categorias as $cat): 
                        // Omitir si la categoría no tiene productos
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
            <div class="text-center py-16 space-y-4">
                <span class="text-4xl">🍔</span>
                <h3 class="font-bold text-slate-700">El catálogo está vacío</h3>
                <p class="text-slate-450 text-xs max-w-xs mx-auto">Vuelve más tarde o comunícate con el comercio para realizar tu consulta.</p>
            </div>
        <?php else: ?>
            <?php foreach ($categorias as $cat): 
                $items = $productosPorCategoria[$cat['id']] ?? [];
                if (empty($items)) continue; // Omitir categoría vacía
            ?>
                <section id="cat-<?= h($cat['id']) ?>" class="space-y-4 scroll-mt-28">
                    <!-- Título de sección con degradado sutil en línea inferior -->
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

    <!-- Carrito Flotante (JS) -->
    <div id="floating-cart" class="fixed bottom-0 left-0 right-0 p-4 bg-transparent max-w-2xl mx-auto z-45 hidden">
        <button onclick="toggleCartDrawer(true)" class="w-full py-4 px-6 bg-gradient-to-r from-[#10B981] to-[#06B6D4] hover:opacity-95 text-white font-bold text-sm rounded-2xl shadow-xl flex justify-between items-center transition-all animate-bounce">
            <div class="flex items-center space-x-2">
                <span>🛒</span>
                <span id="cart-count">0 artículos</span>
            </div>
            <span id="cart-total">$0.00</span>
        </button>
    </div>

    <!-- Drawer del Carrito (Modal Desplizable) -->
    <div id="cart-drawer" class="fixed inset-0 z-50 hidden transition-opacity duration-300">
        <!-- Fondo oscuro translúcido con blur (Glassmorphism) -->
        <div onclick="toggleCartDrawer(false)" class="absolute inset-0 bg-slate-900/40 backdrop-blur-md"></div>
        
        <!-- Panel Desplizable -->
        <div class="absolute bottom-0 left-0 right-0 max-h-[85vh] bg-white rounded-t-3xl shadow-2xl border-t border-slate-100 flex flex-col max-w-2xl mx-auto overflow-hidden">
            <!-- Encabezado del Drawer -->
            <div class="px-6 py-5 border-b border-slate-50 flex items-center justify-between">
                <div>
                    <h3 class="font-extrabold text-lg text-slate-800">Mi Pedido</h3>
                    <p class="text-xs text-slate-400">Verifica los artículos seleccionados</p>
                </div>
                <button onclick="toggleCartDrawer(false)" class="w-8 h-8 rounded-full bg-slate-50 hover:bg-slate-100 flex items-center justify-center font-bold text-slate-500">
                    ✕
                </button>
            </div>

            <!-- Listado de Productos en el Carrito (Rellenado por JS) -->
            <div id="cart-items" class="p-6 overflow-y-auto space-y-1 divide-y divide-slate-50 flex-1">
                <!-- Los elementos se agregan de forma segura mediante JS -->
            </div>

            <!-- Resumen y Acción de Envío -->
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

    <!-- Script del Carrito de Compras (Vanilla JS) -->
    <script>
        // Clave única por comercio para localStorage
        const shopSlug = <?= json_encode($comercio['slug']) ?>;
        const whatsappNumber = <?= json_encode($comercio['telefono_whatsapp']) ?>;
        const cartKey = `cart_${shopSlug}`;

        // Obtener carrito del localStorage
        function getCart() {
            try {
                const cartData = localStorage.getItem(cartKey);
                return cartData ? JSON.parse(cartData) : [];
            } catch (e) {
                console.error("Error al leer el carrito de localStorage:", e);
                return [];
            }
        }

        // Guardar carrito en localStorage
        function saveCart(cart) {
            try {
                localStorage.setItem(cartKey, JSON.stringify(cart));
                updateCartUI();
            } catch (e) {
                console.error("Error al guardar el carrito en localStorage:", e);
            }
        }

        // Agregar un producto al carrito
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
            
            // Efecto sutil visual al agregar
            const bar = document.getElementById('floating-cart');
            bar.classList.add('scale-105');
            setTimeout(() => bar.classList.remove('scale-105'), 150);
        }

        // Modificar cantidad (+1 o -1)
        function updateQuantity(productId, change) {
            let cart = getCart();
            const item = cart.find(item => item.id === productId);
            if (!item) return;

            item.quantity += change;
            if (item.quantity <= 0) {
                cart = cart.filter(item => item.id !== productId);
            }
            saveCart(cart);
            
            // Si el carrito queda vacío, cerrar el drawer
            if (cart.length === 0) {
                toggleCartDrawer(false);
            }
        }

        // Alternar visualización del drawer
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

        // Actualizar la interfaz del carrito flotante y el drawer de forma SECURA
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

            // Calcular totales
            let totalItems = 0;
            let totalPrice = 0;
            cart.forEach(item => {
                totalItems += item.quantity;
                totalPrice += item.precio * item.quantity;
            });

            // Actualizar textos básicos
            cartCount.textContent = `${totalItems} ${totalItems === 1 ? 'artículo' : 'artículos'}`;
            cartTotal.textContent = `$${totalPrice.toFixed(2)}`;
            drawerTotal.textContent = `$${totalPrice.toFixed(2)}`;

            // Limpiar listado de forma segura usando API DOM nativa limpia
            cartItemsContainer.replaceChildren();

            // Construir el DOM de cada elemento de forma segura contra XSS
            cart.forEach(item => {
                const itemEl = document.createElement('div');
                itemEl.className = "flex justify-between items-center py-4 border-b border-slate-100/55 first:pt-2 last:border-b-0";

                const infoEl = document.createElement('div');
                infoEl.className = "space-y-0.5";
                
                const nameEl = document.createElement('h4');
                nameEl.className = "font-bold text-sm text-slate-800";
                nameEl.textContent = item.nombre;

                const priceEl = document.createElement('p');
                priceEl.className = "text-xs font-semibold text-slate-400";
                priceEl.textContent = `$${item.precio.toFixed(2)} c/u`;

                infoEl.appendChild(nameEl);
                infoEl.appendChild(priceEl);

                // Controles de cantidad
                const controlsEl = document.createElement('div');
                controlsEl.className = "flex items-center space-x-2.5";

                const btnMinus = document.createElement('button');
                btnMinus.className = "w-7 h-7 rounded-xl bg-slate-50 hover:bg-slate-100 flex items-center justify-center font-bold text-sm text-slate-650 transition-colors";
                btnMinus.textContent = "−";
                btnMinus.onclick = () => updateQuantity(item.id, -1);

                const qtyEl = document.createElement('span');
                qtyEl.className = "text-sm font-extrabold w-4 text-center text-slate-850";
                qtyEl.textContent = item.quantity;

                const btnPlus = document.createElement('button');
                btnPlus.className = "w-7 h-7 rounded-xl bg-slate-50 hover:bg-slate-100 flex items-center justify-center font-bold text-sm text-slate-650 transition-colors";
                btnPlus.textContent = "+";
                btnPlus.onclick = () => updateQuantity(item.id, 1);

                controlsEl.appendChild(btnMinus);
                controlsEl.appendChild(qtyEl);
                controlsEl.appendChild(btnPlus);

                itemEl.appendChild(infoEl);
                itemEl.appendChild(controlsEl);
                cartItemsContainer.appendChild(itemEl);
            });

            // Mostrar el carrito flotante
            floatingCart.classList.remove('hidden');
        }

        // Checkout de Pedido: Abre WhatsApp con el formato exacto requerido
        function checkoutOrder() {
            const cart = getCart();
            if (cart.length === 0) return;

            let totalPrice = 0;
            let itemsText = "";

            cart.forEach(item => {
                const subtotal = item.precio * item.quantity;
                totalPrice += subtotal;
                
                // Formatear renglón de producto: [Cantidad]x [Nombre de Producto] ($[Precio] c/u)
                itemsText += `${item.quantity}x ${item.nombre} ($${item.precio.toFixed(2)} c/u)\n`;
            });

            // Construir el mensaje exacto solicitado
            const message = `*Pedido de PronttoGo* 🛒\n` +
                            `--------------------------\n` +
                            `${itemsText}` +
                            `--------------------------\n` +
                            `*Total a pagar: $${totalPrice.toFixed(2)}*`;

            // Codificar el texto del mensaje
            const encodedText = encodeURIComponent(message);
            const waUrl = `https://wa.me/${whatsappNumber}?text=${encodedText}`;

            // Abrir WhatsApp en pestaña nueva
            window.open(waUrl, '_blank');
        }

        // Inicializar interfaz al cargar
        window.addEventListener('DOMContentLoaded', () => {
            updateCartUI();
        });
    </script>
</body>
</html>
