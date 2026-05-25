<?php
/**
 * PronttoGo - Panel de Administración Dedicado (Single-Store)
 * Administra la configuración, categorías y productos de una única tienda.
 */

require_once __DIR__ . '/config.php';

$error = '';
$success = '';

// 1. PROCESAR ACCIÓN DE LOGIN (Sin requerir sesión iniciada)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'login') {
    $password = $_POST['password'] ?? '';
    
    if ($password === ADMIN_PASSWORD) {
        $_SESSION['admin_logged_in'] = true;
        redirect('admin.php');
    } else {
        $error = 'Contraseña de administrador incorrecta.';
    }
}

// Cerrar sesión
if (isset($_GET['action']) && $_GET['action'] === 'logout') {
    $_SESSION = [];
    session_destroy();
    redirect('admin.php');
}

// --- VERIFICAR AUTENTICACIÓN ---
$is_logged_in = is_admin_logged_in();

if (!$is_logged_in):
    // RENDERIZAR PANTALLA DE ACCESO POR CONTRASEÑA
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Acceso Administrativo - PronttoGo</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; }
    </style>
</head>
<body class="bg-[#F8FAFC] text-[#0F172A] min-h-screen flex items-center justify-center p-4">
    <div class="w-full max-w-md bg-white rounded-2xl shadow-xl border border-slate-100 overflow-hidden">
        <!-- Encabezado con degradado Emerald a Cyan -->
        <div class="bg-gradient-to-r from-[#10B981] to-[#06B6D4] p-8 text-white text-center">
            <h1 class="text-3xl font-extrabold tracking-tight">PronttoGo</h1>
            <p class="text-emerald-50 mt-2 font-medium">Panel de Control del Comercio</p>
        </div>

        <div class="p-6 space-y-6">
            <?php if (!empty($error)): ?>
                <div class="p-3 bg-red-50 border-l-4 border-red-500 text-red-700 text-sm rounded-r-md">
                    <?= h($error) ?>
                </div>
            <?php endif; ?>

            <form action="admin.php" method="POST" class="space-y-4">
                <input type="hidden" name="action" value="login">
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 mb-1">Contraseña del Administrador</label>
                    <input type="password" name="password" required placeholder="••••••••" 
                           class="w-full px-4 py-2.5 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-[#10B981] focus:border-transparent transition-all">
                    <p class="text-[10px] text-slate-400 mt-1">Por defecto es <code>admin123</code> (puedes cambiarla en las variables de entorno de Vercel).</p>
                </div>
                <button type="submit" class="w-full py-3 bg-gradient-to-r from-[#10B981] to-[#06B6D4] hover:opacity-90 text-white font-bold text-sm rounded-xl shadow-md transition-all">
                    Ingresar al Panel
                </button>
            </form>
        </div>
    </div>
</body>
</html>
<?php
    exit;
endif;

// --- PROCESAMIENTO DE ACCIONES CON SESIÓN ACTIVA (CRUD) ---
$active_tab = '#profile';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    // Validar CSRF
    if (!verify_csrf_token($_POST['csrf_token'] ?? null)) {
        $error = 'Validación CSRF fallida. Inténtalo de nuevo.';
    } else {
        $action = $_POST['action'];
        
        // 1. ACTUALIZAR CONFIGURACIÓN DE LA TIENDA
        if ($action === 'update_profile') {
            $nombre = trim($_POST['nombre'] ?? '');
            $whatsapp = preg_replace('/[^0-9]/', '', $_POST['telefono_whatsapp'] ?? '');
            
            if (empty($nombre) || empty($whatsapp)) {
                $error = 'El nombre del comercio y el teléfono de WhatsApp son obligatorios.';
            } else {
                $response = supabase_request('PATCH', 'configuracion?id=eq.1', [
                    'nombre' => $nombre,
                    'telefono_whatsapp' => $whatsapp
                ]);
                if ($response['success']) {
                    $success = 'Datos del local actualizados con éxito.';
                } else {
                    $error = 'Error al actualizar el perfil en la base de datos.';
                }
            }
            $active_tab = '#profile';
        }
        
        // 2. CREAR CATEGORÍA
        if ($action === 'add_category') {
            $nombre_categoria = trim($_POST['nombre_categoria'] ?? '');
            $orden_visual = intval($_POST['orden_visual'] ?? 0);
            
            if (empty($nombre_categoria)) {
                $error = 'El nombre de la categoría es obligatorio.';
            } else {
                $response = supabase_request('POST', 'categorias', [
                    'nombre_categoria' => $nombre_categoria,
                    'orden_visual' => $orden_visual
                ]);
                if ($response['success']) {
                    $success = 'Categoría agregada correctamente.';
                } else {
                    $error = 'Error al guardar la categoría.';
                }
            }
            $active_tab = '#categories';
        }
        
        // 3. ELIMINAR CATEGORÍA
        if ($action === 'delete_category') {
            $categoria_id = $_POST['categoria_id'] ?? '';
            if (!empty($categoria_id)) {
                $response = supabase_request('DELETE', 'categorias?id=eq.' . rawurlencode($categoria_id));
                if ($response['success']) {
                    $success = 'Categoría eliminada con éxito (junto con sus productos).';
                } else {
                    $error = 'Error al eliminar la categoría.';
                }
            }
            $active_tab = '#categories';
        }
        
        // 4. GUARDAR PRODUCTO (CREAR / EDITAR)
        if ($action === 'save_product') {
            $producto_id = $_POST['producto_id'] ?? '';
            $categoria_id = $_POST['categoria_id'] ?? '';
            $nombre = trim($_POST['nombre'] ?? '');
            $descripcion = trim($_POST['descripcion'] ?? '');
            $precio = floatval($_POST['precio'] ?? 0.00);
            $imagen_url = trim($_POST['imagen_url'] ?? '');
            $disponible = isset($_POST['disponible']) && $_POST['disponible'] == '1';
            
            if (empty($categoria_id) || empty($nombre) || $precio <= 0) {
                $error = 'Nombre, Categoría y Precio (mayor a 0) son obligatorios.';
            } else {
                $productData = [
                    'categoria_id' => intval($categoria_id),
                    'nombre' => $nombre,
                    'descripcion' => $descripcion,
                    'precio' => $precio,
                    'imagen_url' => !empty($imagen_url) ? $imagen_url : null,
                    'disponible' => $disponible
                ];
                
                if (!empty($producto_id)) {
                    // Editar
                    $response = supabase_request('PATCH', 'productos?id=eq.' . rawurlencode($producto_id), $productData);
                    if ($response['success']) {
                        $success = 'Producto actualizado correctamente.';
                    } else {
                        $error = 'Error al actualizar el producto.';
                    }
                } else {
                    // Crear
                    $response = supabase_request('POST', 'productos', $productData);
                    if ($response['success']) {
                        $success = 'Producto registrado correctamente.';
                    } else {
                        $error = 'Error al guardar el producto.';
                    }
                }
            }
            $active_tab = '#products';
        }
        
        // 5. ELIMINAR PRODUCTO
        if ($action === 'delete_product') {
            $producto_id = $_POST['producto_id'] ?? '';
            if (!empty($producto_id)) {
                $response = supabase_request('DELETE', 'productos?id=eq.' . rawurlencode($producto_id));
                if ($response['success']) {
                    $success = 'Producto eliminado con éxito.';
                } else {
                    $error = 'Error al eliminar el producto.';
                }
            }
            $active_tab = '#products';
        }
        
        // 6. TOGGLE DISPONIBILIDAD RÁPIDO
        if ($action === 'toggle_disponible') {
            $producto_id = $_POST['producto_id'] ?? '';
            $disponible = isset($_POST['disponible']) && $_POST['disponible'] == '1';
            
            if (!empty($producto_id)) {
                $response = supabase_request('PATCH', 'productos?id=eq.' . rawurlencode($producto_id), [
                    'disponible' => $disponible
                ]);
                if ($response['success']) {
                    $success = 'Disponibilidad del producto actualizada.';
                } else {
                    $error = 'Error al cambiar la disponibilidad.';
                }
            }
            $active_tab = '#products';
        }
    }
}

// --- OBTENCIÓN DE DATOS GENERALES ---
// Cargar configuración única
$resConfig = supabase_request('GET', 'configuracion?id=eq.1');
$config = $resConfig['success'] && !empty($resConfig['data']) ? $resConfig['data'][0] : ['nombre' => 'Mi Tienda', 'telefono_whatsapp' => ''];

// Cargar categorías ordenadas
$resCategorias = supabase_request('GET', 'categorias?order=orden_visual.asc');
$categorias = $resCategorias['success'] ? $resCategorias['data'] : [];

// Cargar todos los productos
$resProductos = supabase_request('GET', 'productos?order=id.asc');
$productos = $resProductos['success'] ? $resProductos['data'] : [];

// Agrupar categorías en un array asociativo por ID
$categoriasMap = [];
foreach ($categorias as $cat) {
    $categoriasMap[$cat['id']] = $cat['nombre_categoria'];
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - <?= h($config['nombre']) ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;850&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; }
        .tab-content { display: none; }
        .tab-content.active { display: block; }
    </style>
</head>
<body class="bg-[#F8FAFC] text-[#0F172A] min-h-screen flex flex-col">

    <!-- Header -->
    <header class="bg-white border-b border-slate-100 sticky top-0 z-40 shadow-sm">
        <div class="max-w-6xl mx-auto px-4 py-4 flex items-center justify-between">
            <div>
                <a href="/" target="_blank" class="flex items-center space-x-1.5 text-[#0F172A] hover:opacity-80 transition-all">
                    <span class="font-extrabold text-xl tracking-tight bg-gradient-to-r from-[#10B981] to-[#06B6D4] bg-clip-text text-transparent">PronttoGo</span>
                    <span class="text-xs font-semibold px-2 py-0.5 bg-emerald-50 text-[#10B981] rounded-full">Ver Tienda ↗</span>
                </a>
                <p class="text-xs text-slate-400 font-medium">Local: <span class="text-slate-650 font-bold"><?= h($config['nombre']) ?></span></p>
            </div>
            
            <a href="admin.php?action=logout" class="text-xs font-bold text-slate-500 hover:text-red-600 border border-slate-200 rounded-xl px-4 py-2 hover:bg-red-50 transition-all">
                Cerrar Sesión
            </a>
        </div>
    </header>

    <!-- Contenido Principal -->
    <main class="flex-1 max-w-6xl w-full mx-auto p-4 md:py-8 space-y-6">
        
        <!-- Mensajes de Estado -->
        <?php if (!empty($error)): ?>
            <div id="alert-error" class="p-4 bg-red-50 border-l-4 border-red-500 text-red-700 text-sm rounded-r-xl flex justify-between items-center shadow-sm">
                <span><?= h($error) ?></span>
                <button onclick="document.getElementById('alert-error').remove()" class="text-red-500 hover:text-red-800 font-bold">×</button>
            </div>
        <?php endif; ?>

        <?php if (!empty($success)): ?>
            <div id="alert-success" class="p-4 bg-emerald-50 border-l-4 border-[#10B981] text-emerald-800 text-sm rounded-r-xl flex justify-between items-center shadow-sm">
                <span><?= h($success) ?></span>
                <button onclick="document.getElementById('alert-success').remove()" class="text-emerald-500 hover:text-emerald-850 font-bold">×</button>
            </div>
        <?php endif; ?>

        <!-- Tabs de Navegación -->
        <div class="bg-white p-2 rounded-2xl border border-slate-100 flex shadow-sm">
            <button onclick="switchTab('#profile')" id="tab-btn-profile" class="tab-btn flex-1 py-2.5 text-center font-bold text-xs md:text-sm rounded-xl transition-all">
                Datos del Local
            </button>
            <button onclick="switchTab('#categories')" id="tab-btn-categories" class="tab-btn flex-1 py-2.5 text-center font-bold text-xs md:text-sm rounded-xl transition-all">
                Categorías
            </button>
            <button onclick="switchTab('#products')" id="tab-btn-products" class="tab-btn flex-1 py-2.5 text-center font-bold text-xs md:text-sm rounded-xl transition-all">
                Productos
            </button>
        </div>

        <!-- ================= TAB: DATOS DEL LOCAL ================= -->
        <section id="profile" class="tab-content bg-white p-6 rounded-2xl border border-slate-100 shadow-sm space-y-6">
            <div class="border-b border-slate-50 pb-4">
                <h2 class="text-xl font-extrabold tracking-tight">Datos del Local</h2>
                <p class="text-xs text-slate-400">Configuración básica que verán tus clientes.</p>
            </div>
            
            <form action="admin.php" method="POST" class="space-y-4 max-w-xl">
                <?= csrf_input() ?>
                <input type="hidden" name="action" value="update_profile">
                
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 mb-1">Nombre del Comercio</label>
                    <input type="text" name="nombre" value="<?= h($config['nombre']) ?>" required
                           class="w-full px-4 py-2.5 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-[#10B981] focus:border-transparent transition-all">
                </div>
                
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 mb-1">WhatsApp para Pedidos (Código de país incluido)</label>
                    <input type="text" name="telefono_whatsapp" value="<?= h($config['telefono_whatsapp']) ?>" required
                           class="w-full px-4 py-2.5 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-[#10B981] focus:border-transparent transition-all">
                    <p class="text-[11px] text-slate-400 mt-1">Ingresa solo números. Ej: 584121234567 para Venezuela (sin el signo + ni ceros iniciales).</p>
                </div>

                <button type="submit" class="px-6 py-2.5 bg-gradient-to-r from-[#10B981] to-[#06B6D4] hover:opacity-90 text-white font-bold text-xs rounded-xl shadow-md transition-all">
                    Guardar Cambios
                </button>
            </form>
        </section>

        <!-- ================= TAB: CATEGORÍAS ================= -->
        <section id="categories" class="tab-content space-y-6">
            <div class="grid md:grid-cols-3 gap-6">
                <!-- Crear Categoría -->
                <div class="bg-white p-6 rounded-2xl border border-slate-100 shadow-sm h-fit space-y-4">
                    <div class="border-b border-slate-50 pb-2">
                        <h2 class="text-lg font-extrabold">Nueva Categoría</h2>
                        <p class="text-[11px] text-slate-400">Organiza tus productos.</p>
                    </div>
                    <form action="admin.php" method="POST" class="space-y-4">
                        <?= csrf_input() ?>
                        <input type="hidden" name="action" value="add_category">
                        <div>
                            <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 mb-1">Nombre de la Categoría</label>
                            <input type="text" name="nombre_categoria" required placeholder="ej: Comidas, Bebidas, Dulces" 
                                   class="w-full px-4 py-2.5 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-[#10B981] focus:border-transparent transition-all">
                        </div>
                        <div>
                            <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 mb-1">Orden de Visualización</label>
                            <input type="number" name="orden_visual" value="0" required 
                                   class="w-full px-4 py-2.5 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-[#10B981] focus:border-transparent transition-all">
                        </div>
                        <button type="submit" class="w-full py-2.5 bg-gradient-to-r from-[#10B981] to-[#06B6D4] hover:opacity-90 text-white font-bold text-xs rounded-xl shadow-md transition-all">
                            Crear Categoría
                        </button>
                    </form>
                </div>

                <!-- Lista de Categorías -->
                <div class="bg-white p-6 rounded-2xl border border-slate-100 shadow-sm md:col-span-2 space-y-4">
                    <div class="border-b border-slate-50 pb-2">
                        <h2 class="text-lg font-extrabold">Categorías Registradas</h2>
                        <p class="text-[11px] text-slate-400">Categorías y su orden en el menú.</p>
                    </div>

                    <?php if (empty($categorias)): ?>
                        <p class="text-sm text-slate-400 py-6 text-center">No has registrado categorías aún.</p>
                    <?php else: ?>
                        <div class="overflow-x-auto">
                            <table class="w-full text-left text-sm border-collapse">
                                <thead>
                                    <tr class="border-b border-slate-100 text-slate-400 text-xs font-bold uppercase">
                                        <th class="py-3 px-2">Orden</th>
                                        <th class="py-3 px-2">Nombre</th>
                                        <th class="py-3 px-2 text-right">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($categorias as $cat): ?>
                                        <tr class="border-b border-slate-50 hover:bg-slate-50 transition-colors">
                                            <td class="py-3.5 px-2 font-bold text-[#10B981]">#<?= h($cat['orden_visual']) ?></td>
                                            <td class="py-3.5 px-2 font-semibold"><?= h($cat['nombre_categoria']) ?></td>
                                            <td class="py-3.5 px-2 text-right">
                                                <form action="admin.php" method="POST" onsubmit="return confirm('¿Seguro que deseas eliminar esta categoría? Se eliminarán todos los productos asociados.')" class="inline">
                                                    <?= csrf_input() ?>
                                                    <input type="hidden" name="action" value="delete_category">
                                                    <input type="hidden" name="categoria_id" value="<?= h($cat['id']) ?>">
                                                    <button type="submit" class="text-xs font-bold text-red-500 hover:text-red-700 transition-colors">
                                                        Eliminar
                                                    </button>
                                                </form>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </section>

        <!-- ================= TAB: PRODUCTOS ================= -->
        <section id="products" class="tab-content space-y-6">
            <div class="grid md:grid-cols-3 gap-6">
                <!-- Crear / Editar Producto -->
                <div class="bg-white p-6 rounded-2xl border border-slate-100 shadow-sm h-fit space-y-4">
                    <div class="border-b border-slate-50 pb-2">
                        <h2 id="product-form-title" class="text-lg font-extrabold">Nuevo Producto</h2>
                        <p class="text-[11px] text-slate-400">Registra o actualiza los artículos de tu catálogo.</p>
                    </div>
                    <form id="form-product" action="admin.php" method="POST" class="space-y-4">
                        <?= csrf_input() ?>
                        <input type="hidden" name="action" value="save_product">
                        <input type="hidden" name="producto_id" id="prod-id" value="">
                        
                        <div>
                            <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 mb-1">Nombre</label>
                            <input type="text" name="nombre" id="prod-nombre" required placeholder="ej: Hamburguesa Doble" 
                                   class="w-full px-4 py-2.5 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-[#10B981] focus:border-transparent transition-all">
                        </div>

                        <div>
                            <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 mb-1">Categoría</label>
                            <select name="categoria_id" id="prod-cat" required 
                                    class="w-full px-4 py-2.5 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-[#10B981] focus:border-transparent bg-white transition-all">
                                <option value="">Selecciona una categoría</option>
                                <?php foreach ($categorias as $cat): ?>
                                    <option value="<?= h($cat['id']) ?>"><?= h($cat['nombre_categoria']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div>
                            <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 mb-1">Descripción</label>
                            <textarea name="descripcion" id="prod-desc" rows="2" placeholder="Detalles, ingredientes, tamaño..." 
                                      class="w-full px-4 py-2.5 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-[#10B981] focus:border-transparent transition-all"></textarea>
                        </div>

                        <div>
                            <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 mb-1">Precio ($)</label>
                            <input type="number" step="0.01" name="precio" id="prod-precio" required placeholder="0.00" 
                                   class="w-full px-4 py-2.5 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-[#10B981] focus:border-transparent transition-all">
                        </div>

                        <div>
                            <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 mb-1">URL de la Imagen</label>
                            <input type="url" name="imagen_url" id="prod-img" placeholder="https://ejemplo.com/imagen.jpg" 
                                   class="w-full px-4 py-2.5 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-[#10B981] focus:border-transparent transition-all">
                        </div>

                        <div>
                            <label class="flex items-center space-x-2 text-sm font-semibold cursor-pointer">
                                <input type="checkbox" name="disponible" id="prod-disp" value="1" checked class="rounded text-[#10B981] focus:ring-[#10B981] w-4 h-4">
                                <span>Disponible para la venta</span>
                            </label>
                        </div>

                        <div class="flex gap-2">
                            <button type="button" onclick="resetProductForm()" id="prod-btn-cancel" class="flex-1 py-2.5 border border-slate-200 text-slate-600 hover:bg-slate-50 font-bold text-xs rounded-xl transition-all hidden">
                                Cancelar
                            </button>
                            <button type="submit" class="flex-1 py-2.5 bg-gradient-to-r from-[#10B981] to-[#06B6D4] hover:opacity-90 text-white font-bold text-xs rounded-xl shadow-md transition-all">
                                Guardar
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Lista de Productos -->
                <div class="bg-white p-6 rounded-2xl border border-slate-100 shadow-sm md:col-span-2 space-y-4">
                    <div class="border-b border-slate-50 pb-2">
                        <h2 class="text-lg font-extrabold">Productos Registrados</h2>
                        <p class="text-[11px] text-slate-400">Administra disponibilidad y detalles.</p>
                    </div>

                    <?php if (empty($productos)): ?>
                        <p class="text-sm text-slate-400 py-6 text-center">No has registrado productos aún.</p>
                    <?php else: ?>
                        <div class="overflow-x-auto">
                            <table class="w-full text-left text-sm border-collapse">
                                <thead>
                                    <tr class="border-b border-slate-100 text-slate-400 text-xs font-bold uppercase">
                                        <th class="py-3 px-2">Producto</th>
                                        <th class="py-3 px-2">Categoría</th>
                                        <th class="py-3 px-2">Precio</th>
                                        <th class="py-3 px-2 text-center">Disponible</th>
                                        <th class="py-3 px-2 text-right">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($productos as $prod): ?>
                                        <tr class="border-b border-slate-50 hover:bg-slate-50 transition-colors">
                                            <td class="py-3 px-2">
                                                <div class="flex items-center space-x-3">
                                                    <?php if (!empty($prod['imagen_url'])): ?>
                                                        <img src="<?= h($prod['imagen_url']) ?>" alt="<?= h($prod['nombre']) ?>" class="w-10 h-10 object-cover rounded-lg bg-slate-100">
                                                    <?php else: ?>
                                                        <div class="w-10 h-10 rounded-lg bg-slate-100 flex items-center justify-center text-slate-400 text-xs font-bold">🍔</div>
                                                    <?php endif; ?>
                                                    <div>
                                                        <h4 class="font-bold text-slate-800"><?= h($prod['nombre']) ?></h4>
                                                        <p class="text-[10px] text-slate-400 line-clamp-1 max-w-[200px]"><?= h($prod['descripcion']) ?></p>
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="py-3 px-2 text-xs font-semibold text-slate-500">
                                                <?= h($categoriasMap[$prod['categoria_id']] ?? 'Sin Categoría') ?>
                                            </td>
                                            <td class="py-3 px-2 font-extrabold text-slate-800">$<?= number_format($prod['precio'], 2) ?></td>
                                            <td class="py-3 px-2 text-center">
                                                <!-- Formulario rápido disponible/agotado -->
                                                <form action="admin.php" method="POST" class="inline-block">
                                                    <?= csrf_input() ?>
                                                    <input type="hidden" name="action" value="toggle_disponible">
                                                    <input type="hidden" name="producto_id" value="<?= h($prod['id']) ?>">
                                                    <input type="hidden" name="disponible" value="<?= $prod['disponible'] ? '0' : '1' ?>">
                                                    <button type="submit" class="relative inline-flex items-center h-6 rounded-full w-11 transition-colors focus:outline-none <?= $prod['disponible'] ? 'bg-[#10B981]' : 'bg-slate-200' ?>">
                                                        <span class="inline-block w-4 h-4 transform bg-white rounded-full transition-transform <?= $prod['disponible'] ? 'translate-x-6' : 'translate-x-1' ?>"></span>
                                                    </button>
                                                </form>
                                            </td>
                                            <td class="py-3 px-2 text-right">
                                                <div class="flex justify-end space-x-3">
                                                    <!-- Editar -->
                                                    <button type="button" onclick='loadProductForEdit(<?= json_encode([
                                                        'id' => $prod['id'],
                                                        'nombre' => $prod['nombre'],
                                                        'categoria_id' => $prod['categoria_id'],
                                                        'descripcion' => $prod['descripcion'] ?? '',
                                                        'precio' => $prod['precio'],
                                                        'imagen_url' => $prod['imagen_url'] ?? '',
                                                        'disponible' => $prod['disponible'] ? 1 : 0
                                                    ]) ?>)' class="text-xs font-bold text-[#06B6D4] hover:text-[#0891b2] transition-colors">
                                                        Editar
                                                    </button>
                                                    
                                                    <!-- Eliminar -->
                                                    <form action="admin.php" method="POST" onsubmit="return confirm('¿Seguro que deseas eliminar este producto?')" class="inline">
                                                        <?= csrf_input() ?>
                                                        <input type="hidden" name="action" value="delete_product">
                                                        <input type="hidden" name="producto_id" value="<?= h($prod['id']) ?>">
                                                        <button type="submit" class="text-xs font-bold text-red-500 hover:text-red-700 transition-colors">
                                                            Eliminar
                                                        </button>
                                                    </form>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </section>

    </main>

    <!-- Footer -->
    <footer class="bg-white border-t border-slate-100 py-6 text-center text-xs text-slate-400 font-medium mt-auto">
        &copy; 2026 PronttoGo. Todos los derechos reservados.
    </footer>

    <!-- Scripts -->
    <script>
        // Control de Tabs
        function switchTab(hash) {
            window.location.hash = hash;
            
            document.querySelectorAll('.tab-content').forEach(tab => {
                tab.classList.remove('active');
            });
            
            document.querySelectorAll('.tab-btn').forEach(btn => {
                btn.className = "tab-btn flex-1 py-2.5 text-center font-bold text-xs md:text-sm rounded-xl text-slate-400 hover:text-slate-650 transition-all";
            });
            
            const targetTab = document.querySelector(hash);
            if (targetTab) {
                targetTab.classList.add('active');
            }
            
            const btnId = 'tab-btn-' + hash.replace('#', '');
            const targetBtn = document.getElementById(btnId);
            if (targetBtn) {
                targetBtn.className = "tab-btn flex-1 py-2.5 text-center font-bold text-xs md:text-sm rounded-xl bg-gradient-to-r from-[#10B981] to-[#06B6D4] text-white shadow-md transition-all";
            }
        }

        // Cargar producto en formulario
        function loadProductForEdit(product) {
            document.getElementById('product-form-title').textContent = 'Editar Producto';
            document.getElementById('prod-id').value = product.id;
            document.getElementById('prod-nombre').value = product.nombre;
            document.getElementById('prod-cat').value = product.categoria_id;
            document.getElementById('prod-desc').value = product.descripcion;
            document.getElementById('prod-precio').value = parseFloat(product.precio).toFixed(2);
            document.getElementById('prod-img').value = product.imagen_url;
            document.getElementById('prod-disp').checked = product.disponible === 1;
            
            document.getElementById('prod-btn-cancel').classList.remove('hidden');
            document.getElementById('form-product').scrollIntoView({ behavior: 'smooth' });
        }

        // Limpiar formulario
        function resetProductForm() {
            document.getElementById('product-form-title').textContent = 'Nuevo Producto';
            document.getElementById('prod-id').value = '';
            document.getElementById('form-product').reset();
            document.getElementById('prod-btn-cancel').classList.add('hidden');
        }

        // Inicialización
        window.addEventListener('DOMContentLoaded', () => {
            let defaultTab = '<?= $active_tab ?? '#profile' ?>';
            if (window.location.hash) {
                defaultTab = window.location.hash;
            }
            switchTab(defaultTab);
        });
    </script>
</body>
</html>
