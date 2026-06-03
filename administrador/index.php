<?php
$es_admin = true;
/**
 * PronttoGo - Panel de Administración
 * Refactorizado (MVC)
 */

require_once __DIR__ . '/../core/config.php';
require_once __DIR__ . '/../core/auth.php';
require_once __DIR__ . '/../core/db.php';

$error = '';
$success = isset($_GET['success']) ? 'Operación realizada con éxito.' : '';

function fetch_bcv_rate() {
    $ctx = stream_context_create(['http' => ['timeout' => 3]]);
    $res = @file_get_contents('https://s3.amazonaws.com/dolartoday/data.json', false, $ctx);
    if ($res) {
        $data = json_decode($res, true);
        if (isset($data['USD']['bcv'])) {
            return floatval($data['USD']['bcv']);
        }
    }
    $res = @file_get_contents('https://open.er-api.com/v6/latest/USD', false, $ctx);
    if ($res) {
        $data = json_decode($res, true);
        if (isset($data['rates']['VES'])) {
            return floatval($data['rates']['VES']);
        }
    }
    return 0;
}



// Manejo de peticiones POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        die("Error CSRF: Token inválido o sesión expirada.");
    }
    
    if ($action === 'login') {
        $user = $_POST['username'] ?? '';
        $pass = $_POST['password'] ?? '';
        if ($user === ADMIN_USER && $pass === ADMIN_PASSWORD) {
            $_SESSION['admin_logged_in'] = true;
            redirect('admin.php');
        } else {
            $error = "Usuario o contraseña incorrectos.";
        }
    }
    
    if (is_admin_logged_in()) {
        if ($action === 'logout') {
            $_SESSION['admin_logged_in'] = false;
            session_destroy();
            redirect('admin.php');
        }
        
        if ($action === 'update_config') {
            $tasa_tipo = $_POST['tasa_tipo'] ?? 'manual';
            $tasa_dolar = floatval($_POST['tasa_dolar'] ?? 1.00);
            
            if ($tasa_tipo === 'bcv') {
                $bcv = fetch_bcv_rate();
                if ($bcv > 0) $tasa_dolar = $bcv;
            }

            $tipo_negocio = $_POST['tipo_negocio'] ?? 'gastronomia';
            
            // OBTENER CONFIGURACIÓN ACTUAL ANTES DE ACTUALIZAR
            $resConfigActual = supabase_request('GET', 'configuracion?id=eq.1');
            $current_tipo_negocio = ($resConfigActual['success'] && !empty($resConfigActual['data'])) ? ($resConfigActual['data'][0]['tipo_negocio'] ?? 'gastronomia') : 'gastronomia';

            $data = [
                'nombre' => $_POST['nombre'] ?? '',
                'telefono_whatsapp' => preg_replace('/[^0-9]/', '', $_POST['telefono_whatsapp'] ?? ''),
                'logo_url' => empty($_POST['logo_url']) ? null : $_POST['logo_url'],
                'color_primario' => $_POST['color_primario'] ?? '#4F46E5',
                'tasa_dolar' => $tasa_dolar,
                'tasa_tipo' => $tasa_tipo,
                'moneda_nombre' => $_POST['moneda_nombre'] ?? 'USD',
                'moneda_simbolo' => $_POST['moneda_simbolo'] ?? '$',
                'costo_delivery' => floatval($_POST['costo_delivery'] ?? 0.00),
                'direccion' => $_POST['direccion'] ?? '',
                'horario' => $_POST['horario'] ?? ''
            ];
            supabase_request('PATCH', 'configuracion?id=eq.1', $data);

            // LÓGICA INTELIGENTE DE CATÁLOGOS AL CAMBIAR TIPO DE NEGOCIO O SI ESTÁ VACÍO
            $resCat = supabase_request('GET', 'categorias');
            $current_cats = $resCat['success'] ? $resCat['data'] : [];
            
            $categorias_defecto = [
                'gastronomia' => ['Entradas', 'Platos Principales', 'Postres', 'Bebidas'],
                'comida_rapida' => ['Hamburguesas', 'Perros Calientes', 'Pizzas', 'Bebidas y Combos'],
                'minimarket' => ['Víveres y Alimentos', 'Charcutería y Lácteos', 'Bebidas y Licores', 'Limpieza y Hogar'],
                'farmacia' => ['Medicamentos', 'Cuidado Personal', 'Bienestar y Suplementos', 'Bebés y Maternidad'],
                'boutique' => ['Damas', 'Caballeros', 'Niños', 'Calzado', 'Accesorios'],
                'ferreteria_repuestos' => ['Herramientas', 'Electricidad', 'Plomería', 'Repuestos y Tornillos'],
                'belleza_estetica' => ['Cuidado Capilar', 'Maquillaje', 'Cuidado de la Piel', 'Perfumería'],
                'otros' => ['Productos Generales', 'Ofertas Especiales', 'Nuevos Ingresos']
            ];

            $es_vacio = empty($current_cats);
            $debe_cargar = $es_vacio;
            
            if (!$es_vacio && $tipo_negocio !== $current_tipo_negocio) {
                // Verificar si las categorías actuales coinciden exactamente con las predeterminadas del negocio anterior
                $old_defaults = [];
                $resPredOld = supabase_request('GET', 'categorias_predeterminadas?tipo_negocio=eq.' . urlencode($current_tipo_negocio) . '&order=orden_visual.asc');
                if ($resPredOld['success'] && !empty($resPredOld['data'])) {
                    foreach ($resPredOld['data'] as $p) {
                        $old_defaults[] = $p['nombre'];
                    }
                }
                if (empty($old_defaults)) {
                    $old_defaults = $categorias_defecto[$current_tipo_negocio] ?? [];
                }

                if (count($current_cats) === count($old_defaults)) {
                    $current_names = array_map(function($c) { return $c['nombre_categoria'] ?? $c['nombre'] ?? ''; }, $current_cats);
                    $son_iguales = true;
                    foreach ($old_defaults as $od) {
                        if (!in_array($od, $current_names)) {
                            $son_iguales = false;
                            break;
                        }
                    }
                    if ($son_iguales) {
                        $debe_cargar = true;
                        // Borrar las categorías antiguas al cargar un nuevo catálogo inteligente
                        foreach ($current_cats as $cat) {
                            supabase_request('DELETE', 'categorias?id=eq.' . $cat['id']);
                        }
                    }
                }
            }

            if ($debe_cargar) {
                $new_cats = [];
                $resPred = supabase_request('GET', 'categorias_predeterminadas?tipo_negocio=eq.' . urlencode($tipo_negocio) . '&order=orden_visual.asc');
                if ($resPred['success'] && !empty($resPred['data'])) {
                    foreach ($resPred['data'] as $p) {
                        $new_cats[] = $p['nombre'];
                    }
                }
                
                if (empty($new_cats)) {
                    $new_cats = $categorias_defecto[$tipo_negocio] ?? $categorias_defecto['gastronomia'];
                }

                foreach ($new_cats as $idx => $catName) {
                    $catData = [
                        'nombre' => $catName,
                        'nombre_categoria' => $catName,
                        'orden_visual' => ($idx + 1) * 10
                    ];
                    supabase_request('POST', 'categorias', $catData);
                }
            }
            redirect('admin.php?tab=config&success=1');
        }

        if ($action === 'load_default_categories') {
            // Cargar configuración actual para saber el tipo de negocio
            $resConfig = supabase_request('GET', 'configuracion?id=eq.1');
            $config_temp = ($resConfig['success'] && !empty($resConfig['data'])) ? $resConfig['data'][0] : [];
            $tipo_negocio = $config_temp['tipo_negocio'] ?? 'gastronomia';
            
            // Intentar obtener desde la base de datos (tabla categorias_predeterminadas)
            $resPred = supabase_request('GET', 'categorias_predeterminadas?tipo_negocio=eq.' . urlencode($tipo_negocio) . '&order=orden_visual.asc');
            
            $cats = [];
            if ($resPred['success'] && !empty($resPred['data'])) {
                foreach ($resPred['data'] as $p) {
                    $cats[] = $p['nombre'];
                }
            }
            
            // Fallback por si acaso no han corrido la migración de la tabla predeterminadas
            if (empty($cats)) {
                $categorias_defecto = [
                    'gastronomia' => ['Entradas', 'Platos Principales', 'Postres', 'Bebidas'],
                    'comida_rapida' => ['Hamburguesas', 'Perros Calientes', 'Pizzas', 'Bebidas y Combos'],
                    'minimarket' => ['Víveres y Alimentos', 'Charcutería y Lácteos', 'Bebidas y Licores', 'Limpieza y Hogar'],
                    'farmacia' => ['Medicamentos', 'Cuidado Personal', 'Bienestar y Suplementos', 'Bebés y Maternidad'],
                    'boutique' => ['Damas', 'Caballeros', 'Niños', 'Calzado', 'Accesorios'],
                    'ferreteria_repuestos' => ['Herramientas', 'Electricidad', 'Plomería', 'Repuestos y Tornillos'],
                    'belleza_estetica' => ['Cuidado Capilar', 'Maquillaje', 'Cuidado de la Piel', 'Perfumería'],
                    'otros' => ['Productos Generales', 'Ofertas Especiales', 'Nuevos Ingresos']
                ];
                $cats = $categorias_defecto[$tipo_negocio] ?? $categorias_defecto['gastronomia'];
            }
            
            // Verificar que no existan categorías previas para evitar duplicidad accidental
            $resCatCheck = supabase_request('GET', 'categorias?limit=1');
            if ($resCatCheck['success'] && empty($resCatCheck['data'])) {
                foreach ($cats as $idx => $catName) {
                    $catData = [
                        'nombre' => $catName,
                        'nombre_categoria' => $catName, // Compatibilidad doble de nombre de columna
                        'orden_visual' => ($idx + 1) * 10
                    ];
                    supabase_request('POST', 'categorias', $catData);
                }
            }
            redirect('admin.php?tab=categorias&success=1');
        }
        
        if ($action === 'create_category') {
            $data = ['nombre' => $_POST['nombre'] ?? '', 'orden_visual' => intval($_POST['orden_visual'] ?? 0)];
            supabase_request('POST', 'categorias', $data);
            redirect('admin.php?tab=categorias&success=1');
        }
        
        if ($action === 'update_category') {
            $id = intval($_POST['id']);
            $data = ['nombre' => $_POST['nombre'] ?? '', 'orden_visual' => intval($_POST['orden_visual'] ?? 0)];
            supabase_request('PATCH', 'categorias?id=eq.' . $id, $data);
            redirect('admin.php?tab=categorias&success=1');
        }
        
        if ($action === 'delete_category') {
            $id = intval($_POST['id']);
            supabase_request('DELETE', 'categorias?id=eq.' . $id);
            redirect('admin.php?tab=categorias&success=1');
        }
        
        if ($action === 'create_product') {
            $stock = (isset($_POST['stock']) && $_POST['stock'] !== '') ? intval($_POST['stock']) : null;
            if ($stock !== null && $stock < 0) {
                die("Error: El stock no puede ser un número negativo.");
            }
            $data = [
                'nombre' => $_POST['nombre'] ?? '',
                'descripcion' => $_POST['descripcion'] ?? '',
                'precio' => floatval($_POST['precio_usd'] ?? 0),
                'precio_usd' => floatval($_POST['precio_usd'] ?? 0), // Compatibilidad doble de columna
                'categoria_id' => intval($_POST['categoria_id'] ?? 0),
                'disponible' => isset($_POST['disponible']) ? true : false,
                'imagen_url' => empty($_POST['imagen_url']) ? null : $_POST['imagen_url'],
                'stock' => $stock
            ];
            supabase_request('POST', 'productos', $data);
            redirect('admin.php?tab=productos&success=1');
        }
        
        if ($action === 'update_product') {
            $id = intval($_POST['id']);
            $stock = (isset($_POST['stock']) && $_POST['stock'] !== '') ? intval($_POST['stock']) : null;
            if ($stock !== null && $stock < 0) {
                die("Error: El stock no puede ser un número negativo.");
            }
            $data = [
                'nombre' => $_POST['nombre'] ?? '',
                'descripcion' => $_POST['descripcion'] ?? '',
                'precio' => floatval($_POST['precio_usd'] ?? 0),
                'precio_usd' => floatval($_POST['precio_usd'] ?? 0), // Compatibilidad doble de columna
                'categoria_id' => intval($_POST['categoria_id'] ?? 0),
                'disponible' => isset($_POST['disponible']) ? true : false,
                'imagen_url' => empty($_POST['imagen_url']) ? null : $_POST['imagen_url'],
                'stock' => $stock
            ];
            supabase_request('PATCH', 'productos?id=eq.' . $id, $data);
            redirect('admin.php?tab=productos&success=1');
        }
        
        if ($action === 'delete_product') {
            $id = intval($_POST['id']);
            supabase_request('DELETE', 'productos?id=eq.' . $id);
            redirect('admin.php?tab=productos&success=1');
        }
    }
}

// Cargar datos
$config = []; $categorias = []; $productos = [];
$currentTab = $_GET['tab'] ?? 'config';

if (is_admin_logged_in()) {
    $resConfig = supabase_request('GET', 'configuracion?id=eq.1');
    $config = ($resConfig['success'] && !empty($resConfig['data'])) ? $resConfig['data'][0] : [];
    
    $resCat = supabase_request('GET', 'categorias?order=orden_visual.asc');
    $categorias = $resCat['success'] ? $resCat['data'] : [];
    if (!empty($categorias)) {
        foreach ($categorias as &$c) {
            $c['nombre'] = $c['nombre_categoria'] ?? $c['nombre'] ?? 'Sin Categoría';
        }
        unset($c);
    }
    
    $resProd = supabase_request('GET', 'productos?order=categoria_id.asc,id.asc');
    $productos = $resProd['success'] ? $resProd['data'] : [];
    if (!empty($productos)) {
        foreach ($productos as &$p) {
            $p['nombre'] = $p['nombre'] ?? $p['nombre_producto'] ?? 'Sin Nombre';
            $p['precio_usd'] = $p['precio_usd'] ?? $p['precio'] ?? 0;
        }
        unset($p);
    }
}

// Cargar categorías sugeridas para el datalist si el admin está logueado
$categorias_sugeridas = [];
if (is_admin_logged_in() && !empty($config)) {
    $resPred = supabase_request('GET', 'categorias_predeterminadas?tipo_negocio=eq.' . urlencode($config['tipo_negocio'] ?? 'gastronomia') . '&order=orden_visual.asc');
    if ($resPred['success'] && !empty($resPred['data'])) {
        foreach ($resPred['data'] as $p) {
            $existe = false;
            foreach ($categorias as $c) {
                if (strtolower($c['nombre']) === strtolower($p['nombre'])) {
                    $existe = true; break;
                }
            }
            if (!$existe) {
                $categorias_sugeridas[] = $p['nombre'];
            }
        }
    }
}

$csrfField = csrf_input();

// Incluir Cabecera HTML
require_once __DIR__ . '/../includes/header.php';

if (!is_admin_logged_in()): ?>
    <!-- LOGIN -->
    <div class="w-full h-full flex items-center justify-center bg-gradient-to-br from-indigo-50 to-slate-100 p-4 relative overflow-hidden">
        <div class="absolute -top-32 -left-32 w-96 h-96 bg-indigo-200 rounded-full mix-blend-multiply filter blur-3xl opacity-70 animate-blob"></div>
        <div class="absolute top-1/2 -right-32 w-96 h-96 bg-emerald-200 rounded-full mix-blend-multiply filter blur-3xl opacity-70 animate-blob animation-delay-2000"></div>
        <div class="glass-panel p-8 md:p-12 rounded-3xl shadow-xl w-full max-w-md relative z-10 border border-white">
            <div class="text-center mb-8">
                <div class="max-w-[200px] sm:max-w-[240px] mx-auto mb-4 drop-shadow-sm">
                    <?= get_logo_svg('w-full h-auto block') ?>
                </div>
                <p class="text-slate-500 mt-2">Panel de Administración</p>
            </div>
            <?php if ($error): ?>
                <div class="bg-red-50 text-red-600 p-4 rounded-xl mb-6 text-sm flex items-center border border-red-100">
                    <i class="bi bi-exclamation-triangle-fill mr-2"></i>
                    <?= h($error) ?>
                </div>
            <?php endif; ?>
            <form method="POST" action="admin.php" class="space-y-5">
                <?= $csrfField ?>
                <input type="hidden" name="action" value="login">
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-2">Usuario</label>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
                            <i class="bi bi-person text-slate-400 text-lg"></i>
                        </span>
                        <input type="text" name="username" required class="w-full pl-10 pr-4 py-3 bg-white border border-slate-200 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all outline-none" placeholder="Tu usuario">
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-2">Contraseña</label>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
                            <i class="bi bi-lock text-slate-400 text-lg"></i>
                        </span>
                        <input type="password" name="password" required class="w-full pl-10 pr-4 py-3 bg-white border border-slate-200 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all outline-none" placeholder="••••••••">
                    </div>
                </div>
                <button type="submit" class="w-full bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-3 px-4 rounded-xl transition-all shadow-md hover:shadow-lg transform hover:-translate-y-0.5 mt-2">
                    Ingresar <i class="bi bi-arrow-right ml-1"></i>
                </button>
            </form>
        </div>
    </div>
<?php else: ?>
    <!-- DASHBOARD -->
    <div x-data="{ currentTab: '<?= h($currentTab) ?>', sidebarOpen: false }" class="flex w-full h-full bg-slate-50">
        <?php require_once __DIR__ . '/../includes/sidebar.php'; ?>

        <main class="flex-1 flex flex-col h-full overflow-hidden">
            <header class="h-16 bg-white border-b border-slate-200 flex items-center justify-between px-4 md:hidden shrink-0 relative z-50">
                <div class="flex items-center gap-2">
                    <?= get_logo_svg('h-8 w-auto') ?>
                </div>
                <button @click="sidebarOpen = true" class="text-slate-500 hover:text-slate-800 text-2xl">
                    <i class="bi bi-list"></i>
                </button>
            </header>

            <div class="flex-1 overflow-y-auto p-4 md:p-8">
                <div class="max-w-5xl mx-auto">
                    
                    <?php if ($success): ?>
                        <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 3000)" class="mb-6 bg-emerald-50 text-emerald-700 p-4 rounded-xl flex items-center border border-emerald-100 shadow-sm transition-all duration-500">
                            <i class="bi bi-check-circle-fill mr-3 text-lg"></i>
                            <span class="font-medium"><?= h($success) ?></span>
                        </div>
                    <?php endif; ?>

                    <div x-show="currentTab === 'config'" x-cloak class="space-y-6">
                        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                            <h2 class="text-2xl font-bold text-slate-800">Configuración del Local</h2>
                            <a href="../" target="_blank" class="bg-white border border-slate-200 text-slate-600 hover:text-indigo-600 hover:border-indigo-200 px-4 py-2 rounded-xl text-sm font-bold shadow-sm transition-colors flex items-center justify-center gap-2">
                                <i class="bi bi-eye"></i> Ver Catálogo
                            </a>
                        </div>
                        
                        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6">
                            <form method="POST" action="admin.php" class="space-y-6">
                                <?= $csrfField ?>
                                <input type="hidden" name="action" value="update_config">
                                
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <div>
                                        <label class="block text-sm font-semibold text-slate-700 mb-2">Nombre Comercial</label>
                                        <div class="relative">
                                            <span class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
                                                <i class="bi bi-shop text-slate-400 text-lg"></i>
                                            </span>
                                            <input type="text" name="nombre" value="<?= h($config['nombre'] ?? '') ?>" class="w-full pl-10 pr-4 py-2.5 border border-slate-200 rounded-xl focus:ring-2 focus:ring-indigo-500 outline-none" required>
                                        </div>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-semibold text-slate-700 mb-2">WhatsApp para Pedidos</label>
                                        <div class="relative">
                                            <span class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
                                                <i class="bi bi-whatsapp text-slate-400 text-lg"></i>
                                            </span>
                                            <input type="text" name="telefono_whatsapp" value="<?= h($config['telefono_whatsapp'] ?? '') ?>" class="w-full pl-10 pr-4 py-2.5 border border-slate-200 rounded-xl focus:ring-2 focus:ring-indigo-500 outline-none" required placeholder="Ej: 584121234567 (Código + Número sin + ni espacios)">
                                        </div>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-semibold text-slate-700 mb-2">URL del Logotipo (Imagen)</label>
                                        <div class="relative">
                                            <span class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
                                                <i class="bi bi-image text-slate-400 text-lg"></i>
                                            </span>
                                            <input type="url" name="logo_url" value="<?= h($config['logo_url'] ?? '') ?>" placeholder="https://ejemplo.com/logo.png (Vacío usa PronttoGo)" class="w-full pl-10 pr-4 py-2.5 border border-slate-200 rounded-xl focus:ring-2 focus:ring-indigo-500 outline-none">
                                        </div>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-semibold text-slate-700 mb-2">Color de Marca (Personalizado)</label>
                                        <div class="flex gap-2">
                                            <div class="relative flex-1">
                                                <span class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
                                                    <i class="bi bi-palette text-slate-400 text-lg"></i>
                                                </span>
                                                <input type="text" id="color_text" name="color_primario" value="<?= h($config['color_primario'] ?? '#4F46E5') ?>" placeholder="#4F46E5" class="w-full pl-10 pr-4 py-2.5 border border-slate-200 rounded-xl focus:ring-2 focus:ring-indigo-500 outline-none uppercase font-mono">
                                            </div>
                                            <input type="color" id="color_picker" value="<?= h($config['color_primario'] ?? '#4F46E5') ?>" class="w-12 h-11 p-0.5 border border-slate-200 rounded-xl cursor-pointer bg-white" oninput="document.getElementById('color_text').value = this.value.toUpperCase()">
                                        </div>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-semibold text-slate-700 mb-2">Tipo de Negocio</label>
                                        <div class="relative">
                                            <span class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
                                                <i class="bi bi-briefcase text-slate-400 text-lg"></i>
                                            </span>
                                            <select name="tipo_negocio" class="w-full pl-10 pr-10 py-2.5 border border-slate-200 rounded-xl focus:ring-2 focus:ring-indigo-500 outline-none appearance-none bg-white">
                                                <option value="gastronomia" <?= ($config['tipo_negocio'] ?? '') === 'gastronomia' ? 'selected' : '' ?>>Gastronomía / Restaurante</option>
                                                <option value="comida_rapida" <?= ($config['tipo_negocio'] ?? '') === 'comida_rapida' ? 'selected' : '' ?>>Comida Rápida / Callejera</option>
                                                <option value="minimarket" <?= ($config['tipo_negocio'] ?? '') === 'minimarket' ? 'selected' : '' ?>>Minimarket / Supermercado</option>
                                                <option value="farmacia" <?= ($config['tipo_negocio'] ?? '') === 'farmacia' ? 'selected' : '' ?>>Farmacia / Salud</option>
                                                <option value="boutique" <?= ($config['tipo_negocio'] ?? '') === 'boutique' ? 'selected' : '' ?>>Boutique / Tienda de Ropa</option>
                                                <option value="ferreteria_repuestos" <?= ($config['tipo_negocio'] ?? '') === 'ferreteria_repuestos' ? 'selected' : '' ?>>Ferretería y Repuestos</option>
                                                <option value="belleza_estetica" <?= ($config['tipo_negocio'] ?? '') === 'belleza_estetica' ? 'selected' : '' ?>>Belleza y Estética</option>
                                                <option value="otros" <?= ($config['tipo_negocio'] ?? '') === 'otros' ? 'selected' : '' ?>>Otros Negocios (General)</option>
                                            </select>
                                            <span class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none">
                                                <i class="bi bi-chevron-down text-slate-400"></i>
                                            </span>
                                        </div>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-semibold text-slate-700 mb-2">Costo Delivery (USD)</label>
                                        <div class="relative">
                                            <span class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
                                                <i class="bi bi-bicycle text-slate-400 text-lg"></i>
                                            </span>
                                            <input type="number" step="0.01" name="costo_delivery" value="<?= h($config['costo_delivery'] ?? '0') ?>" class="w-full pl-10 pr-4 py-2.5 border border-slate-200 rounded-xl focus:ring-2 focus:ring-indigo-500 outline-none" required>
                                        </div>
                                    </div>
                                    <div class="md:col-span-2">
                                        <hr class="my-2 border-slate-100">
                                    </div>
                                    <div>
                                        <label class="block text-sm font-semibold text-slate-700 mb-2">Modo de Tasa de Cambio</label>
                                        <div class="relative">
                                            <span class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
                                                <i class="bi bi-gear-wide-connected text-slate-400 text-lg"></i>
                                            </span>
                                            <select name="tasa_tipo" id="tasa_tipo" class="w-full pl-10 pr-10 py-2.5 border border-slate-200 rounded-xl focus:ring-2 focus:ring-indigo-500 outline-none bg-white appearance-none">
                                                <option value="manual" <?= ($config['tasa_tipo'] ?? 'manual') === 'manual' ? 'selected' : '' ?>>Fija (Definida por el comercio)</option>
                                                <option value="bcv" <?= ($config['tasa_tipo'] ?? '') === 'bcv' ? 'selected' : '' ?>>Oficial (Banco Central de Venezuela)</option>
                                            </select>
                                            <span class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none">
                                                <i class="bi bi-chevron-down text-slate-400"></i>
                                            </span>
                                        </div>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-semibold text-slate-700 mb-2">Valor de la Tasa (USD a Local)</label>
                                        <div class="relative flex gap-2">
                                            <div class="relative flex-1">
                                                <span class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
                                                    <i class="bi bi-currency-exchange text-slate-400 text-lg"></i>
                                                </span>
                                                <input type="number" step="0.01" name="tasa_dolar" id="tasa_dolar" value="<?= h($config['tasa_dolar'] ?? '1') ?>" class="w-full pl-10 pr-4 py-2.5 border border-slate-200 rounded-xl focus:ring-2 focus:ring-indigo-500 outline-none" required>
                                            </div>
                                            <button type="button" id="btn-fetch-tasa" class="bg-indigo-50 border border-indigo-200 text-indigo-600 hover:bg-indigo-100 px-3.5 rounded-xl transition-all flex items-center justify-center shrink-0" title="Consultar tasa ahora por internet">
                                                <i class="bi bi-arrow-clockwise text-lg"></i>
                                            </button>
                                        </div>
                                        <p id="tasa-status-text" class="text-[11px] mt-1 text-slate-400 font-semibold"></p>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-semibold text-slate-700 mb-2">Nombre Moneda Local</label>
                                        <div class="relative">
                                            <span class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
                                                <i class="bi bi-cash-stack text-slate-400 text-lg"></i>
                                            </span>
                                            <input type="text" name="moneda_nombre" id="moneda_nombre" value="<?= h($config['moneda_nombre'] ?? 'USD') ?>" placeholder="Ej. Bs., COP, USD" class="w-full pl-10 pr-4 py-2.5 border border-slate-200 rounded-xl focus:ring-2 focus:ring-indigo-500 outline-none" required>
                                        </div>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-semibold text-slate-700 mb-2">Símbolo Moneda Local</label>
                                        <div class="relative">
                                            <span class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
                                                <i class="bi bi-coin text-slate-400 text-lg"></i>
                                            </span>
                                            <input type="text" name="moneda_simbolo" id="moneda_simbolo" value="<?= h($config['moneda_simbolo'] ?? '$') ?>" placeholder="Ej. $, Bs., COP" class="w-full pl-10 pr-4 py-2.5 border border-slate-200 rounded-xl focus:ring-2 focus:ring-indigo-500 outline-none" required>
                                        </div>
                                    </div>
                                    <div class="md:col-span-2 grid grid-cols-1 md:grid-cols-2 gap-6">
                                        <div>
                                            <label class="block text-sm font-semibold text-slate-700 mb-2">Dirección del Local</label>
                                            <div class="relative">
                                                <span class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
                                                    <i class="bi bi-geo-alt text-slate-400 text-lg"></i>
                                                </span>
                                                <input type="text" name="direccion" value="<?= h($config['direccion'] ?? '') ?>" placeholder="Ej. Calle Falsa 123, Ciudad" class="w-full pl-10 pr-4 py-2.5 border border-slate-200 rounded-xl focus:ring-2 focus:ring-indigo-500 outline-none">
                                            </div>
                                        </div>
                                        <div>
                                            <label class="block text-sm font-semibold text-slate-700 mb-2">Horario de Atención</label>
                                            <div class="relative">
                                                <span class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
                                                    <i class="bi bi-clock text-slate-400 text-lg"></i>
                                                </span>
                                                <input type="text" name="horario" value="<?= h($config['horario'] ?? '') ?>" placeholder="Ej. Lun a Sáb: 9:00 AM - 8:00 PM" class="w-full pl-10 pr-4 py-2.5 border border-slate-200 rounded-xl focus:ring-2 focus:ring-indigo-500 outline-none">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="pt-4 text-center sm:text-right">
                                     <button type="submit" class="w-full sm:w-auto bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-2.5 px-6 rounded-xl transition-all shadow-md">
                                         Guardar Cambios <i class="bi bi-save ml-1"></i>
                                     </button>
                                 </div>
                            </form>
                        </div>
                    </div>

                    <div x-show="currentTab === 'categorias'" x-cloak class="space-y-6">
                        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                            <h2 class="text-2xl font-bold text-slate-800">Categorías</h2>
                            <button x-data @click="$dispatch('open-modal', 'modal-cat-new')" class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-xl text-sm font-bold shadow-md transition-colors flex items-center justify-center gap-2">
                                <i class="bi bi-plus-lg"></i> Nueva Categoría
                            </button>
                        </div>
                        <?php if(empty($categorias)): ?>
                            <div class="bg-white rounded-2xl border border-slate-200 p-8 text-center space-y-4 shadow-sm">
                                <div class="w-16 h-16 bg-indigo-50 text-indigo-600 rounded-full flex items-center justify-center mx-auto text-2xl">
                                    <i class="bi bi-folder-plus"></i>
                                </div>
                                <div class="max-w-md mx-auto space-y-1">
                                    <h3 class="text-lg font-bold text-slate-800">Crea tus Categorías</h3>
                                    <p class="text-sm text-slate-500">Organiza tus productos en secciones. Puedes agregarlas manualmente o precargar las sugeridas para tu tipo de negocio.</p>
                                </div>
                                <form method="POST" action="admin.php?tab=categorias" class="pt-2">
                                    <?= $csrfField ?>
                                    <input type="hidden" name="action" value="load_default_categories">
                                    <button type="submit" class="bg-indigo-50 hover:bg-indigo-100 text-indigo-600 border border-indigo-200 px-5 py-3 rounded-xl text-xs sm:text-sm font-bold transition-all shadow-sm inline-flex items-center justify-center gap-1.5 text-center mx-auto">
                                        <i class="bi bi-magic text-sm shrink-0"></i>
                                        <span>Precargar categorías de <span class="capitalize"><?= h(str_replace('_', ' ', $config['tipo_negocio'] ?? 'gastronomia')) ?></span></span>
                                    </button>
                                </form>
                            </div>
                        <?php else: ?>
                            <!-- Tabla para Escritorio -->
                            <div class="hidden sm:block bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
                                <table class="w-full text-left border-collapse">
                                    <thead>
                                        <tr class="bg-slate-50 text-slate-500 text-sm border-b border-slate-200">
                                            <th class="p-4 font-semibold">Orden</th>
                                            <th class="p-4 font-semibold">Nombre</th>
                                            <th class="p-4 font-semibold text-right">Acciones</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach($categorias as $cat): ?>
                                        <tr class="border-b border-slate-100 hover:bg-slate-50 transition-colors">
                                            <td class="p-4 text-slate-600"><?= h($cat['orden_visual']) ?></td>
                                            <td class="p-4 font-semibold text-slate-800"><?= h($cat['nombre']) ?></td>
                                            <td class="p-4 text-right space-x-2">
                                                <button x-data @click="$dispatch('open-edit-cat', { id: <?= $cat['id'] ?>, nombre: '<?= h(addslashes($cat['nombre'])) ?>', orden: <?= $cat['orden_visual'] ?> })" class="text-indigo-600 hover:text-indigo-800 bg-indigo-50 hover:bg-indigo-100 p-2 rounded-lg transition-colors"><i class="bi bi-pencil-fill"></i></button>
                                                <form method="POST" class="inline" onsubmit="return confirm('¿Seguro que deseas eliminar esta categoría?');">
                                                    <?= $csrfField ?><input type="hidden" name="action" value="delete_category"><input type="hidden" name="id" value="<?= $cat['id'] ?>">
                                                    <button type="submit" class="text-red-600 hover:text-red-800 bg-red-50 hover:bg-red-100 p-2 rounded-lg transition-colors"><i class="bi bi-trash-fill"></i></button>
                                                </form>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>

                            <!-- Tarjetas para Móvil -->
                            <div class="block sm:hidden space-y-4">
                                <?php foreach($categorias as $cat): ?>
                                <div class="bg-white rounded-2xl border border-slate-200 p-4 shadow-sm flex items-center justify-between gap-4">
                                    <div class="space-y-1">
                                        <div class="text-[10px] text-slate-400 font-bold uppercase tracking-wider">Orden: <?= h($cat['orden_visual']) ?></div>
                                        <div class="font-bold text-slate-800 text-base"><?= h($cat['nombre']) ?></div>
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <button x-data @click="$dispatch('open-edit-cat', { id: <?= $cat['id'] ?>, nombre: '<?= h(addslashes($cat['nombre'])) ?>', orden: <?= $cat['orden_visual'] ?> })" class="text-indigo-600 hover:text-indigo-800 bg-indigo-50 hover:bg-indigo-100 p-2.5 rounded-xl transition-colors"><i class="bi bi-pencil-fill"></i></button>
                                        <form method="POST" class="inline" onsubmit="return confirm('¿Seguro que deseas eliminar esta categoría?');">
                                            <?= $csrfField ?><input type="hidden" name="action" value="delete_category"><input type="hidden" name="id" value="<?= $cat['id'] ?>">
                                            <button type="submit" class="text-red-600 hover:text-red-800 bg-red-50 hover:bg-red-100 p-2.5 rounded-xl transition-colors"><i class="bi bi-trash-fill"></i></button>
                                        </form>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>

                    <div x-show="currentTab === 'productos'" x-cloak class="space-y-6">
                        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                            <h2 class="text-2xl font-bold text-slate-800">Productos</h2>
                            <button x-data @click="$dispatch('open-modal', 'modal-prod-new')" class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-xl text-sm font-bold shadow-md transition-colors flex items-center justify-center gap-2">
                                <i class="bi bi-plus-lg"></i> Nuevo Producto
                            </button>
                        </div>
                        <!-- Tabla para Escritorio -->
                        <div class="hidden sm:block bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
                            <div class="overflow-x-auto">
                                <table class="w-full text-left border-collapse min-w-[600px]">
                                    <thead>
                                        <tr class="bg-slate-50 text-slate-500 text-sm border-b border-slate-200">
                                            <th class="p-4 font-semibold">Producto</th>
                                            <th class="p-4 font-semibold">Categoría</th>
                                            <th class="p-4 font-semibold">Precio (USD)</th>
                                            <th class="p-4 font-semibold text-center">Stock</th>
                                            <th class="p-4 font-semibold text-center">Estado</th>
                                            <th class="p-4 font-semibold text-right">Acciones</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if(empty($productos)): ?>
                                            <tr><td colspan="6" class="p-6 text-center text-slate-500">No hay productos disponibles.</td></tr>
                                        <?php else: ?>
                                            <?php $catMap = []; foreach($categorias as $c) $catMap[$c['id']] = $c['nombre']; ?>
                                            <?php foreach($productos as $prod): ?>
                                            <tr class="border-b border-slate-100 hover:bg-slate-50 transition-colors">
                                                <td class="p-4">
                                                    <div class="flex items-center gap-3">
                                                        <?php if(!empty($prod['imagen_url'])): ?><img src="<?= h($prod['imagen_url']) ?>" alt="img" class="w-10 h-10 rounded-lg object-cover bg-slate-200"><?php else: ?><div class="w-10 h-10 rounded-lg bg-slate-100 flex items-center justify-center text-slate-400"><i class="bi bi-image"></i></div><?php endif; ?>
                                                        <div class="font-semibold text-slate-800"><?= h($prod['nombre']) ?></div>
                                                    </div>
                                                </td>
                                                <td class="p-4 text-slate-600 text-sm"><span class="bg-slate-100 text-slate-700 px-2 py-1 rounded-md"><?= h($catMap[$prod['categoria_id']] ?? 'Sin Categoría') ?></span></td>
                                                <td class="p-4 font-bold text-indigo-600">$<?= number_format($prod['precio_usd'], 2) ?></td>
                                                <td class="p-4 text-center">
                                                    <?php if($prod['stock'] === null): ?>
                                                        <span class="inline-flex items-center gap-1 bg-slate-100 text-slate-600 px-2 py-0.5 rounded-md text-xs font-bold border border-slate-200" title="Stock Ilimitado">
                                                            <i class="bi bi-infinity"></i> Ilimitado
                                                        </span>
                                                    <?php elseif($prod['stock'] <= 0): ?>
                                                        <span class="inline-flex items-center gap-1 bg-red-50 text-red-700 px-2 py-0.5 rounded-md text-xs font-bold border border-red-100">
                                                            Agotado
                                                        </span>
                                                    <?php elseif($prod['stock'] <= 5): ?>
                                                        <span class="inline-flex items-center gap-1 bg-amber-50 text-amber-700 px-2 py-0.5 rounded-md text-xs font-bold border border-amber-100" title="Stock Crítico: requiere reposición">
                                                            <i class="bi bi-exclamation-triangle-fill"></i> Bajo: <?= $prod['stock'] ?>
                                                        </span>
                                                    <?php else: ?>
                                                        <span class="inline-flex items-center gap-1 bg-emerald-50 text-emerald-700 px-2 py-0.5 rounded-md text-xs font-bold border border-emerald-100">
                                                            <?= $prod['stock'] ?> disp.
                                                        </span>
                                                    <?php endif; ?>
                                                </td>
                                                <td class="p-4 text-center">
                                                    <?php if($prod['disponible']): ?><span class="inline-flex items-center gap-1 bg-emerald-50 text-emerald-700 px-2 py-1 rounded-md text-xs font-bold border border-emerald-100"><i class="bi bi-check-circle-fill"></i> Activo</span><?php else: ?><span class="inline-flex items-center gap-1 bg-red-50 text-red-700 px-2 py-1 rounded-md text-xs font-bold border border-red-100"><i class="bi bi-x-circle-fill"></i> Inactivo</span><?php endif; ?>
                                                </td>
                                                <td class="p-4 text-right space-x-2">
                                                    <?php $jsonProd = json_encode(['id' => $prod['id'],'nombre' => $prod['nombre'],'descripcion' => $prod['descripcion'],'precio_usd' => $prod['precio_usd'],'categoria_id' => $prod['categoria_id'],'disponible' => $prod['disponible'],'imagen_url' => $prod['imagen_url'],'stock' => $prod['stock']]); ?>
                                                    <button x-data @click="$dispatch('open-edit-prod', <?= htmlspecialchars($jsonProd, ENT_QUOTES, 'UTF-8') ?>)" class="text-indigo-600 hover:text-indigo-800 bg-indigo-50 hover:bg-indigo-100 p-2 rounded-lg transition-colors"><i class="bi bi-pencil-fill"></i></button>
                                                    <form method="POST" class="inline" onsubmit="return confirm('¿Eliminar producto?');">
                                                        <?= $csrfField ?><input type="hidden" name="action" value="delete_product"><input type="hidden" name="id" value="<?= $prod['id'] ?>">
                                                        <button type="submit" class="text-red-600 hover:text-red-800 bg-red-50 hover:bg-red-100 p-2 rounded-lg transition-colors"><i class="bi bi-trash-fill"></i></button>
                                                    </form>
                                                </td>
                                            </tr>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <!-- Tarjetas para Móvil -->
                        <div class="block sm:hidden space-y-4">
                            <?php if(empty($productos)): ?>
                                <div class="bg-white rounded-2xl border border-slate-200 p-6 text-center text-slate-500">No hay productos disponibles.</div>
                            <?php else: ?>
                                <?php $catMap = []; foreach($categorias as $c) $catMap[$c['id']] = $c['nombre']; ?>
                                <?php foreach($productos as $prod): ?>
                                <div class="bg-white rounded-2xl border border-slate-200 p-4 shadow-sm space-y-3">
                                    <div class="flex items-center gap-3">
                                        <?php if(!empty($prod['imagen_url'])): ?>
                                            <img src="<?= h($prod['imagen_url']) ?>" alt="img" class="w-12 h-12 rounded-xl object-cover bg-slate-200 shrink-0">
                                        <?php else: ?>
                                            <div class="w-12 h-12 rounded-xl bg-slate-100 flex items-center justify-center text-slate-400 shrink-0"><i class="bi bi-image"></i></div>
                                        <?php endif; ?>
                                        <div class="min-w-0 flex-1">
                                            <div class="font-bold text-slate-800 text-sm truncate"><?= h($prod['nombre']) ?></div>
                                            <div class="text-xs text-slate-500 mt-0.5"><span class="bg-slate-100 text-slate-700 px-1.5 py-0.5 rounded-md font-semibold"><?= h($catMap[$prod['categoria_id']] ?? 'Sin Categoría') ?></span></div>
                                        </div>
                                    </div>
                                    
                                    <div class="grid grid-cols-3 gap-2 pt-1 border-t border-slate-50 items-center">
                                        <div class="space-y-0.5">
                                            <div class="text-[10px] text-slate-400 font-bold uppercase tracking-wider">Precio</div>
                                            <div class="font-extrabold text-indigo-600 text-base">$<?= number_format($prod['precio_usd'], 2) ?></div>
                                        </div>
                                        <div class="text-center space-y-0.5">
                                            <div class="text-[10px] text-slate-400 font-bold uppercase tracking-wider">Stock</div>
                                            <div>
                                                <?php if($prod['stock'] === null): ?>
                                                    <span class="inline-flex items-center gap-0.5 bg-slate-100 text-slate-600 px-1.5 py-0.5 rounded-md text-[10px] font-bold border border-slate-200"><i class="bi bi-infinity"></i> Ilimitado</span>
                                                <?php elseif($prod['stock'] <= 0): ?>
                                                    <span class="inline-flex items-center gap-0.5 bg-red-50 text-red-700 px-1.5 py-0.5 rounded-md text-[10px] font-bold border border-red-100">Agotado</span>
                                                <?php elseif($prod['stock'] <= 5): ?>
                                                    <span class="inline-flex items-center gap-0.5 bg-amber-50 text-amber-700 px-1.5 py-0.5 rounded-md text-[10px] font-bold border border-amber-100"><i class="bi bi-exclamation-triangle-fill"></i> <?= $prod['stock'] ?></span>
                                                <?php else: ?>
                                                    <span class="inline-flex items-center gap-0.5 bg-emerald-50 text-emerald-700 px-1.5 py-0.5 rounded-md text-[10px] font-bold border border-emerald-100"><?= $prod['stock'] ?> disp.</span>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                        <div class="text-right space-y-0.5">
                                            <div class="text-[10px] text-slate-400 font-bold uppercase tracking-wider">Estado</div>
                                            <div>
                                                <?php if($prod['disponible']): ?>
                                                    <span class="inline-flex items-center gap-1 bg-emerald-50 text-emerald-700 px-2 py-0.5 rounded-md text-xs font-bold border border-emerald-100"><i class="bi bi-check-circle-fill"></i> Activo</span>
                                                <?php else: ?>
                                                    <span class="inline-flex items-center gap-1 bg-red-50 text-red-700 px-2 py-0.5 rounded-md text-xs font-bold border border-red-100"><i class="bi bi-x-circle-fill"></i> Inactivo</span>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="flex items-center justify-end gap-2 pt-2 border-t border-slate-100">
                                        <?php $jsonProd = json_encode(['id' => $prod['id'],'nombre' => $prod['nombre'],'descripcion' => $prod['descripcion'],'precio_usd' => $prod['precio_usd'],'categoria_id' => $prod['categoria_id'],'disponible' => $prod['disponible'],'imagen_url' => $prod['imagen_url'],'stock' => $prod['stock']]); ?>
                                        <button x-data @click="$dispatch('open-edit-prod', <?= htmlspecialchars($jsonProd, ENT_QUOTES, 'UTF-8') ?>)" class="text-indigo-600 hover:text-indigo-800 bg-indigo-50 hover:bg-indigo-100 py-2 px-3 rounded-xl transition-colors text-xs font-bold flex items-center gap-1"><i class="bi bi-pencil-fill"></i> Editar</button>
                                        <form method="POST" class="inline" onsubmit="return confirm('¿Eliminar producto?');">
                                            <?= $csrfField ?><input type="hidden" name="action" value="delete_product"><input type="hidden" name="id" value="<?= $prod['id'] ?>">
                                            <button type="submit" class="text-red-600 hover:text-red-800 bg-red-50 hover:bg-red-100 py-2 px-3 rounded-xl transition-colors text-xs font-bold flex items-center gap-1"><i class="bi bi-trash-fill"></i> Eliminar</button>
                                        </form>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                    </div>
                </div>
            </div>
        </main>

        <!-- Modales -->
        <div x-data="{ open: false }" @open-modal.window="if ($event.detail === 'modal-cat-new') open = true" x-show="open" x-cloak class="fixed inset-0 z-[100] flex items-center justify-center">
            <div @click="open = false" class="absolute inset-0 bg-slate-900/40 backdrop-blur-sm"></div>
            <div class="bg-white rounded-2xl shadow-xl w-full max-w-md relative z-10 p-6 transform transition-all">
                <div class="flex justify-between items-center mb-4"><h3 class="text-xl font-bold">Nueva Categoría</h3><button @click="open = false" class="text-slate-400 hover:text-slate-600 text-xl"><i class="bi bi-x-lg"></i></button></div>
                <form method="POST" action="admin.php" class="space-y-4">
                    <?= $csrfField ?><input type="hidden" name="action" value="create_category">
                    <div>
                        <label class="block text-sm font-semibold mb-1">Nombre</label>
                        <input type="text" name="nombre" list="cat-sugeridas" class="w-full border border-slate-200 rounded-xl px-3 py-2 outline-none focus:ring-2 focus:ring-indigo-500" required placeholder="Ej. Promociones">
                        <p class="text-[10px] text-slate-500 mt-1">Puedes elegir una sugerida o escribir la tuya.</p>
                        <datalist id="cat-sugeridas">
                            <?php foreach($categorias_sugeridas as $sug): ?>
                                <option value="<?= h($sug) ?>"></option>
                            <?php endforeach; ?>
                        </datalist>
                    </div>
                    <div><label class="block text-sm font-semibold mb-1">Orden</label><input type="number" name="orden_visual" value="0" class="w-full border border-slate-200 rounded-xl px-3 py-2 outline-none focus:ring-2 focus:ring-indigo-500" required></div>
                    <button type="submit" class="w-full bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-2 rounded-xl">Guardar</button>
                </form>
            </div>
        </div>

        <div x-data="{ open: false, cat: {id:'', nombre:'', orden:0} }" @open-edit-cat.window="cat = $event.detail; open = true" x-show="open" x-cloak class="fixed inset-0 z-[100] flex items-center justify-center">
            <div @click="open = false" class="absolute inset-0 bg-slate-900/40 backdrop-blur-sm"></div>
            <div class="bg-white rounded-2xl shadow-xl w-full max-w-md relative z-10 p-6 transform transition-all">
                <div class="flex justify-between items-center mb-4"><h3 class="text-xl font-bold">Editar Categoría</h3><button @click="open = false" class="text-slate-400 hover:text-slate-600 text-xl"><i class="bi bi-x-lg"></i></button></div>
                <form method="POST" action="admin.php" class="space-y-4">
                    <?= $csrfField ?><input type="hidden" name="action" value="update_category"><input type="hidden" name="id" x-model="cat.id">
                    <div><label class="block text-sm font-semibold mb-1">Nombre</label><input type="text" name="nombre" x-model="cat.nombre" class="w-full border border-slate-200 rounded-xl px-3 py-2 outline-none focus:ring-2 focus:ring-indigo-500" required></div>
                    <div><label class="block text-sm font-semibold mb-1">Orden</label><input type="number" name="orden_visual" x-model="cat.orden" class="w-full border border-slate-200 rounded-xl px-3 py-2 outline-none focus:ring-2 focus:ring-indigo-500" required></div>
                    <button type="submit" class="w-full bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-2 rounded-xl">Actualizar</button>
                </form>
            </div>
        </div>

        <div x-data="{ open: false }" @open-modal.window="if ($event.detail === 'modal-prod-new') open = true" x-show="open" x-cloak class="fixed inset-0 z-[100] flex items-center justify-center p-4">
            <div @click="open = false" class="absolute inset-0 bg-slate-900/40 backdrop-blur-sm"></div>
            <div class="bg-white rounded-2xl shadow-xl w-full max-w-lg relative z-10 p-6 transform transition-all max-h-full overflow-y-auto">
                <div class="flex justify-between items-center mb-4"><h3 class="text-xl font-bold">Nuevo Producto</h3><button @click="open = false" class="text-slate-400 hover:text-slate-600 text-xl"><i class="bi bi-x-lg"></i></button></div>
                <form method="POST" action="admin.php" class="space-y-4">
                    <?= $csrfField ?><input type="hidden" name="action" value="create_product">
                    <div class="flex items-center gap-2 mb-4"><input type="checkbox" name="disponible" id="disp_new" value="1" checked class="w-4 h-4 text-indigo-600 rounded"><label for="disp_new" class="font-semibold text-sm">Producto Disponible</label></div>
                    <div class="grid grid-cols-2 gap-4">
                        <div class="col-span-2"><label class="block text-sm font-semibold mb-1">Nombre</label><input type="text" name="nombre" class="w-full border border-slate-200 rounded-xl px-3 py-2 outline-none focus:ring-2 focus:ring-indigo-500" required></div>
                        <div class="col-span-2"><label class="block text-sm font-semibold mb-1">Descripción</label><textarea name="descripcion" rows="2" class="w-full border border-slate-200 rounded-xl px-3 py-2 outline-none focus:ring-2 focus:ring-indigo-500"></textarea></div>
                        <div><label class="block text-sm font-semibold mb-1">Precio (USD)</label><input type="number" step="0.01" name="precio_usd" class="w-full border border-slate-200 rounded-xl px-3 py-2 outline-none focus:ring-2 focus:ring-indigo-500" required></div>
                        <div><label class="block text-sm font-semibold mb-1">Categoría</label><select name="categoria_id" class="w-full border border-slate-200 rounded-xl px-3 py-2 outline-none focus:ring-2 focus:ring-indigo-500 bg-white" required><?php foreach($categorias as $c): ?><option value="<?= $c['id'] ?>"><?= h($c['nombre']) ?></option><?php endforeach; ?></select></div>
                        <div><label class="block text-sm font-semibold mb-1">Stock Disponible</label><input type="number" name="stock" min="0" placeholder="Ilimitado" class="w-full border border-slate-200 rounded-xl px-3 py-2 outline-none focus:ring-2 focus:ring-indigo-500"></div>
                        <div><label class="block text-sm font-semibold mb-1">URL de Imagen</label><input type="url" name="imagen_url" class="w-full border border-slate-200 rounded-xl px-3 py-2 outline-none focus:ring-2 focus:ring-indigo-500" placeholder="https://..."></div>
                    </div>
                    <button type="submit" class="w-full mt-4 bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-2.5 rounded-xl">Guardar Producto</button>
                </form>
            </div>
        </div>

        <div x-data="{ open: false, prod: {id:'', nombre:'', descripcion:'', precio_usd:0, categoria_id:'', disponible:false, imagen_url:'', stock:''} }" @open-edit-prod.window="prod = $event.detail; open = true" x-show="open" x-cloak class="fixed inset-0 z-[100] flex items-center justify-center p-4">
            <div @click="open = false" class="absolute inset-0 bg-slate-900/40 backdrop-blur-sm"></div>
            <div class="bg-white rounded-2xl shadow-xl w-full max-w-lg relative z-10 p-6 transform transition-all max-h-full overflow-y-auto">
                <div class="flex justify-between items-center mb-4"><h3 class="text-xl font-bold">Editar Producto</h3><button @click="open = false" class="text-slate-400 hover:text-slate-600 text-xl"><i class="bi bi-x-lg"></i></button></div>
                <form method="POST" action="admin.php" class="space-y-4">
                    <?= $csrfField ?><input type="hidden" name="action" value="update_product"><input type="hidden" name="id" x-model="prod.id">
                    <div class="flex items-center gap-2 mb-4"><input type="checkbox" name="disponible" id="disp_edit" value="1" x-model="prod.disponible" class="w-4 h-4 text-indigo-600 rounded"><label for="disp_edit" class="font-semibold text-sm">Producto Disponible</label></div>
                    <div class="grid grid-cols-2 gap-4">
                        <div class="col-span-2"><label class="block text-sm font-semibold mb-1">Nombre</label><input type="text" name="nombre" x-model="prod.nombre" class="w-full border border-slate-200 rounded-xl px-3 py-2 outline-none focus:ring-2 focus:ring-indigo-500" required></div>
                        <div class="col-span-2"><label class="block text-sm font-semibold mb-1">Descripción</label><textarea name="descripcion" x-model="prod.descripcion" rows="2" class="w-full border border-slate-200 rounded-xl px-3 py-2 outline-none focus:ring-2 focus:ring-indigo-500"></textarea></div>
                        <div><label class="block text-sm font-semibold mb-1">Precio (USD)</label><input type="number" step="0.01" name="precio_usd" x-model="prod.precio_usd" class="w-full border border-slate-200 rounded-xl px-3 py-2 outline-none focus:ring-2 focus:ring-indigo-500" required></div>
                        <div><label class="block text-sm font-semibold mb-1">Categoría</label><select name="categoria_id" x-model="prod.categoria_id" class="w-full border border-slate-200 rounded-xl px-3 py-2 outline-none focus:ring-2 focus:ring-indigo-500 bg-white" required><?php foreach($categorias as $c): ?><option value="<?= $c['id'] ?>"><?= h($c['nombre']) ?></option><?php endforeach; ?></select></div>
                        <div><label class="block text-sm font-semibold mb-1">Stock Disponible</label><input type="number" name="stock" x-model="prod.stock" min="0" placeholder="Ilimitado" class="w-full border border-slate-200 rounded-xl px-3 py-2 outline-none focus:ring-2 focus:ring-indigo-500"></div>
                        <div><label class="block text-sm font-semibold mb-1">URL de Imagen</label><input type="url" name="imagen_url" x-model="prod.imagen_url" class="w-full border border-slate-200 rounded-xl px-3 py-2 outline-none focus:ring-2 focus:ring-indigo-500" placeholder="https://..."></div>
                    </div>
                    <button type="submit" class="w-full mt-4 bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-2.5 rounded-xl">Actualizar Producto</button>
                </form>
            </div>
        </div>
        <script>
            document.addEventListener('DOMContentLoaded', () => {
                const tasaTipoSelect = document.getElementById('tasa_tipo');
                const tasaDolarInput = document.getElementById('tasa_dolar');
                const monedaNombreInput = document.getElementById('moneda_nombre');
                const monedaSimboloInput = document.getElementById('moneda_simbolo');
                const btnFetchTasa = document.getElementById('btn-fetch-tasa');
                const statusText = document.getElementById('tasa-status-text');

                async function fetchExchangeRate(silent = false) {
                    const tipo = tasaTipoSelect.value;
                    if (tipo === 'manual') {
                        statusText.innerText = '';
                        tasaDolarInput.readOnly = false;
                        return;
                    }

                    if (!silent) {
                        statusText.innerText = 'Consultando tasa en tiempo real...';
                        statusText.className = 'text-[11px] mt-1 text-amber-500 font-semibold';
                    }

                    try {
                        const response = await fetch(`/api/tasa.php?tipo=${tipo}`);
                        if (!response.ok) throw new Error('Error al conectar con el servidor.');
                        const data = await response.json();
                        
                        if (data.success && data.rate > 0) {
                            tasaDolarInput.value = data.rate.toFixed(2);
                            if (tipo === 'bcv') {
                                monedaNombreInput.value = 'Bs.';
                                monedaSimboloInput.value = 'Bs.';
                                statusText.innerText = 'Tasa BCV cargada automáticamente por el servidor.';
                            } else if (tipo === 'trm') {
                                monedaNombreInput.value = 'COP';
                                monedaSimboloInput.value = '$';
                                statusText.innerText = 'Tasa TRM cargada automáticamente por el servidor.';
                            }
                            statusText.className = 'text-[11px] mt-1 text-emerald-600 font-semibold';
                        } else {
                            throw new Error(data.error || 'No se pudo obtener la tasa.');
                        }
                    } catch (err) {
                        console.error(err);
                        statusText.innerText = 'Error de conexión. Ingresa el valor manual.';
                        statusText.className = 'text-[11px] mt-1 text-red-500 font-semibold';
                    }
                }

                tasaTipoSelect.addEventListener('change', () => {
                    if (tasaTipoSelect.value !== 'manual') {
                        fetchExchangeRate();
                        tasaDolarInput.readOnly = true;
                    } else {
                        tasaDolarInput.readOnly = false;
                        statusText.innerText = '';
                    }
                });

                btnFetchTasa.addEventListener('click', () => {
                    fetchExchangeRate(false);
                });

                if (tasaTipoSelect.value !== 'manual') {
                    tasaDolarInput.readOnly = true;
                    fetchExchangeRate(true);
                }

                // Sincronizar selectores de color
                const colorText = document.getElementById('color_text');
                const colorPicker = document.getElementById('color_picker');
                if (colorText && colorPicker) {
                    colorText.addEventListener('input', function() {
                        let val = this.value;
                        if (val.match(/^#[0-9A-Fa-f]{6}$/)) {
                            colorPicker.value = val;
                        }
                    });
                }
            });
        </script>
    </div>
<?php endif; ?>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
