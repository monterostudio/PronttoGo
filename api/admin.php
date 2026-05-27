<?php
/**
 * PronttoGo - Panel de Administración Dedicado (Single-Store)
 * Administra la configuración, categorías y productos de una única tienda.
 */

require_once __DIR__ . '/config.php';

// Cargar configuración de la base de datos (se usa para autenticación y UI)
$resConfig = supabase_request('GET', 'configuracion?id=eq.1');
$config = $resConfig['success'] && !empty($resConfig['data']) ? $resConfig['data'][0] : [];

// Resolver credenciales administrativas (base de datos o fallback a config.php)
$dbAdminUser = !empty($config['admin_user']) ? $config['admin_user'] : ADMIN_USER;
$dbAdminPassword = !empty($config['admin_password']) ? $config['admin_password'] : ADMIN_PASSWORD;

$error = '';
$success = '';

// 1. PROCESAR ACCIÓN DE LOGIN (Sin requerir sesión iniciada)
if (empty($_SESSION['login_attempts'])) {
    $_SESSION['login_attempts'] = 0;
}
if (empty($_SESSION['login_lock_until'])) {
    $_SESSION['login_lock_until'] = 0;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'login') {
    // A. Honeypot check
    if (!empty($_POST['website_url'])) {
        sleep(5);
        die('Acceso denegado (Bot detectado).');
    }

    // B. Bloqueo temporal por fuerza bruta
    if ($_SESSION['login_lock_until'] > time()) {
        $error = 'Demasiados intentos fallidos. Inténtalo de nuevo en 15 minutos.';
    } else {
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';
        $captcha_ans = intval($_POST['captcha_answer'] ?? 0);
        $expected_ans = ($_SESSION['login_captcha_a'] ?? 0) + ($_SESSION['login_captcha_b'] ?? 0);

        // C. Validar Captcha
        if ($captcha_ans !== $expected_ans) {
            $error = 'Respuesta de verificación de seguridad incorrecta.';
            $_SESSION['login_attempts']++;
            if ($_SESSION['login_attempts'] >= 5) {
                $_SESSION['login_lock_until'] = time() + 900;
            }
            $_SESSION['login_captcha_a'] = rand(1, 9);
            $_SESSION['login_captcha_b'] = rand(1, 9);
            sleep(1);
        } else {
            // D. Validar credenciales (con fallback texto plano para migración)
            $login_ok = false;
            if ($username === $dbAdminUser) {
                if (password_verify($password, $dbAdminPassword)) {
                    $login_ok = true;
                } elseif ($password === $dbAdminPassword) {
                    $login_ok = true;
                }
            }

            if ($login_ok) {
                $_SESSION['admin_logged_in'] = true;
                $_SESSION['login_attempts'] = 0;
                $_SESSION['login_lock_until'] = 0;
                unset($_SESSION['login_captcha_a']);
                unset($_SESSION['login_captcha_b']);
                redirect('/admin');
            } else {
                $error = 'Usuario o contraseña incorrectos.';
                $_SESSION['login_attempts']++;
                if ($_SESSION['login_attempts'] >= 5) {
                    $_SESSION['login_lock_until'] = time() + 900;
                }
                $_SESSION['login_captcha_a'] = rand(1, 9);
                $_SESSION['login_captcha_b'] = rand(1, 9);
                sleep(2);
            }
        }
    }
}

// Cerrar sesión
if (isset($_GET['action']) && $_GET['action'] === 'logout') {
    $_SESSION = [];
    session_destroy();
    redirect('/admin');
}

// --- VERIFICAR AUTENTICACIÓN ---
$is_logged_in = is_admin_logged_in();

if (!$is_logged_in):
    if (empty($_SESSION['login_captcha_a']) || empty($_SESSION['login_captcha_b'])) {
        $_SESSION['login_captcha_a'] = rand(1, 9);
        $_SESSION['login_captcha_b'] = rand(1, 9);
    }
    // RENDERIZAR PANTALLA DE ACCESO POR CONTRASEÑA
?>
<!DOCTYPE html>
<html lang="es" class="overflow-x-hidden">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/svg+xml" href="/assets/favicon.svg">
    <link rel="icon" type="image/png" sizes="32x32" href="/assets/favicon.svg">
    <link rel="shortcut icon" href="/assets/favicon.svg">
    <link rel="apple-touch-icon" href="/assets/favicon.svg">
    <meta name="theme-color" content="#00CFBD">
    <title>Acceso — Panel PronttoGo</title>
    <script>
        const _warn = console.warn;
        console.warn = (...args) => {
            if (args[0] && typeof args[0] === 'string' && args[0].includes('cdn.tailwindcss.com')) return;
            _warn(...args);
        };
    </script>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; }

        /* Fondo claro */
        .login-bg {
            background: linear-gradient(135deg, #F8FAFC 0%, #F1F5F9 50%, #E2E8F0 100%);
            position: relative;
            overflow: hidden;
        }
        .login-bg::before {
            content: '';
            position: absolute;
            top: -30%;
            right: -10%;
            width: 600px;
            height: 600px;
            background: radial-gradient(circle, rgba(0,207,189,0.06) 0%, transparent 70%);
            border-radius: 50%;
            animation: pulse-glow 6s ease-in-out infinite alternate;
        }
        .login-bg::after {
            content: '';
            position: absolute;
            bottom: -20%;
            left: -10%;
            width: 500px;
            height: 500px;
            background: radial-gradient(circle, rgba(42,53,67,0.04) 0%, transparent 70%);
            border-radius: 50%;
            animation: pulse-glow 8s ease-in-out infinite alternate-reverse;
        }
        @keyframes pulse-glow {
            0% { opacity: 0.6; transform: scale(1); }
            100% { opacity: 1; transform: scale(1.08); }
        }

        /* Tarjeta con entrada animada */
        .login-card {
            animation: card-in 0.45s cubic-bezier(0.16, 1, 0.3, 1) both;
        }
        @keyframes card-in {
            from { opacity: 0; transform: translateY(24px) scale(0.97); }
            to   { opacity: 1; transform: translateY(0) scale(1); }
        }

        /* Input con foco mejorado */
        .input-field {
            transition: border-color 0.2s, box-shadow 0.2s;
        }
        .input-field:focus {
            border-color: #00CFBD;
            box-shadow: 0 0 0 3px rgba(0,207,189,0.12);
            outline: none;
        }

        /* Botón con efecto shine */
        .btn-primary {
            background: linear-gradient(135deg, #00CFBD 0%, #00B5A5 100%);
            position: relative;
            overflow: hidden;
            transition: opacity 0.2s, transform 0.15s;
        }
        .btn-primary::after {
            content: '';
            position: absolute;
            top: 0; left: -100%;
            width: 60%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.15), transparent);
            transform: skewX(-20deg);
            transition: left 0.5s;
        }
        .btn-primary:hover::after { left: 150%; }
        .btn-primary:hover { opacity: 0.92; }
        .btn-primary:active { transform: scale(0.98); }
    </style>
</head>
<body class="login-bg min-h-screen flex items-center justify-center p-4 overflow-x-hidden">

    <div class="login-card w-full max-w-md relative z-10">
        <!-- Tarjeta principal -->
        <div class="bg-white border border-slate-100 rounded-3xl shadow-2xl overflow-hidden">

            <!-- Encabezado -->
            <div class="bg-gradient-to-br from-[#00CFBD] to-[#2A3543] p-8 text-center relative overflow-hidden">
                <!-- Shimmer decorativo -->
                <div class="absolute inset-0 bg-gradient-to-r from-transparent via-white/5 to-transparent -skew-x-12"></div>
                <div class="relative z-10">
                    <!-- Logo en cuadro blanco -->
                    <div class="inline-flex items-center justify-center bg-white rounded-2xl px-5 py-3 mb-4 shadow-lg ring-2 ring-white/10">
                        <?= get_logo_svg('h-9 w-auto') ?>
                    </div>
                    <p class="text-white/80 text-sm font-semibold tracking-wide">Panel de Control del Comercio</p>
                    <p class="text-white/50 text-xs mt-0.5">Ingresa tus credenciales para continuar</p>
                </div>
            </div>

            <!-- Cuerpo del formulario -->
            <div class="p-5 sm:p-7 space-y-5 bg-white">
                <?php if (!empty($error)): ?>
                    <div class="flex items-start gap-3 p-3.5 bg-red-50 border border-red-200 text-red-700 text-sm rounded-xl">
                        <span class="text-base mt-0.5">&#9888;</span>
                        <span><?= h($error) ?></span>
                    </div>
                <?php endif; ?>

                <?php
                $is_locked = isset($_SESSION['login_lock_until']) && $_SESSION['login_lock_until'] > time();
                $lock_time_left = $is_locked ? ceil(($_SESSION['login_lock_until'] - time()) / 60) : 0;
                ?>

                <form action="admin.php" method="POST" class="space-y-4">
                    <input type="hidden" name="action" value="login">
                    
                    <!-- Honeypot (campo invisible para bots) -->
                    <div style="display:none;">
                        <label>No rellenar</label>
                        <input type="text" name="website_url" autocomplete="off" tabindex="-1">
                    </div>

                    <!-- Usuario -->
                    <div>
                        <label for="login-user" class="block text-xs font-bold uppercase tracking-wider text-slate-500 mb-1.5">Usuario</label>
                        <div class="relative">
                            <span class="absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 text-sm">👤</span>
                            <input id="login-user" type="text" name="username" required placeholder="ej: admin" <?= $is_locked ? 'disabled' : '' ?>
                                   class="input-field w-full pl-10 pr-4 py-2.5 border border-slate-200 rounded-xl text-sm bg-slate-50/50 text-slate-900 placeholder-slate-400">
                        </div>
                    </div>

                    <!-- Contraseña -->
                    <div>
                        <label for="login-pass" class="block text-xs font-bold uppercase tracking-wider text-slate-500 mb-1.5">Contraseña</label>
                        <div class="relative">
                            <span class="absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 text-sm">🔒</span>
                            <input id="login-pass" type="password" name="password" required placeholder="&bull;&bull;&bull;&bull;&bull;&bull;&bull;&bull;" <?= $is_locked ? 'disabled' : '' ?>
                                   class="input-field w-full pl-10 pr-4 py-2.5 border border-slate-200 rounded-xl text-sm bg-slate-50/50 text-slate-900">
                        </div>
                        <p class="text-[10px] text-slate-400 mt-1.5">Por defecto: usuario <code class="bg-slate-100 px-1 rounded">admin</code> y clave <code class="bg-slate-100 px-1 rounded">admin123</code>.</p>
                    </div>

                    <!-- Captcha -->
                    <div>
                        <label for="login-captcha" class="block text-xs font-bold uppercase tracking-wider text-slate-500 mb-1.5">
                            Verificación: ¿Cuánto es <?= $_SESSION['login_captcha_a'] ?> + <?= $_SESSION['login_captcha_b'] ?>?
                        </label>
                        <div class="relative">
                            <span class="absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 text-sm">🧩</span>
                            <input id="login-captcha" type="number" name="captcha_answer" required placeholder="Tu respuesta" <?= $is_locked ? 'disabled' : '' ?>
                                   class="input-field w-full pl-10 pr-4 py-2.5 border border-slate-200 rounded-xl text-sm bg-slate-50/50 text-slate-900 [appearance:textfield] [&::-webkit-inner-spin-button]:appearance-none">
                        </div>
                    </div>

                    <!-- Botón de acceso -->
                    <button type="submit" <?= $is_locked ? 'disabled' : '' ?>
                            class="btn-primary w-full py-3.5 text-white font-bold text-sm rounded-xl shadow-lg disabled:opacity-50 disabled:cursor-not-allowed">
                        <?= $is_locked
                            ? "🔒 Bloqueado por {$lock_time_left} min"
                            : "⚡ Ingresar al Panel" ?>
                    </button>
                </form>

                <!-- Link volver al menú -->
                <div class="text-center pt-1">
                    <a href="/" class="text-xs text-slate-400 hover:text-[#00CFBD] transition-colors font-medium">
                        &larr; Volver al catálogo digital
                    </a>
                </div>
            </div>
        </div>

        <!-- Powered by -->
        <p class="text-center text-[10px] text-slate-400 mt-5 font-medium">
            Powered by <span class="text-[#00CFBD] font-bold">Montero Studio</span>
        </p>
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
            $codigo_pais = preg_replace('/[^0-9]/', '', $_POST['codigo_pais'] ?? '');
            $telefono_local = preg_replace('/[^0-9]/', '', $_POST['telefono_local'] ?? '');
            $whatsapp = $codigo_pais . $telefono_local;
            
            $logo_url = trim($_POST['logo_url'] ?? '');
            $tasa_tipo = trim($_POST['tasa_tipo'] ?? 'manual');
            $tasa_dolar = floatval($_POST['tasa_dolar'] ?? 1.00);
            
            $new_admin_user = trim($_POST['admin_user'] ?? '');
            $new_admin_password = $_POST['admin_password'] ?? '';

            // Tasa automática inteligente si no es manual y el valor enviado es por defecto
            if ($tasa_tipo !== 'manual' && $tasa_dolar <= 1.00) {
                $fetched_rate = fetch_automatic_rate($tasa_tipo);
                if ($fetched_rate !== null) {
                    $tasa_dolar = $fetched_rate;
                } else {
                    $error = 'No se pudo consultar la tasa automática de internet. Se conservó el valor anterior.';
                }
            }

            if (empty($nombre) || empty($whatsapp)) {
                $error = 'El nombre del comercio y el teléfono de WhatsApp son obligatorios.';
            } else {
                $updateData = [
                    'nombre' => $nombre,
                    'telefono_whatsapp' => $whatsapp,
                    'logo_url' => !empty($logo_url) ? $logo_url : null,
                    'tasa_dolar' => $tasa_dolar,
                    'tasa_tipo' => $tasa_tipo
                ];

                if (!empty($new_admin_user)) {
                    $updateData['admin_user'] = $new_admin_user;
                }
                
                if (!empty($new_admin_password)) {
                    $updateData['admin_password'] = password_hash($new_admin_password, PASSWORD_DEFAULT);
                }

                $response = supabase_request('PATCH', 'configuracion?id=eq.1', $updateData);
                
                if ($response['success']) {
                    $success = 'Perfil comercial actualizado con éxito.';
                    // Actualizar el estado local
                    $resConfig = supabase_request('GET', 'configuracion?id=eq.1');
                    if ($resConfig['success'] && !empty($resConfig['data'])) {
                        $config = $resConfig['data'][0];
                        // Actualizar credenciales en la sesión/variables de esta ejecución
                        $dbAdminUser = !empty($config['admin_user']) ? $config['admin_user'] : ADMIN_USER;
                        $dbAdminPassword = !empty($config['admin_password']) ? $config['admin_password'] : ADMIN_PASSWORD;
                    }
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
// Inicializar configuración por defecto si la base de datos falló por completo
if (empty($config)) {
    $config = ['nombre' => 'PronttoGo', 'telefono_whatsapp' => ''];
}

// Cargar categorías ordenadas
$resCategorias = supabase_request('GET', 'categorias?order=orden_visual.asc');
$categorias = $resCategorias['success'] ? $resCategorias['data'] : [];

// Calcular siguiente orden de categoría inteligente
$next_cat_order = 1;
if (!empty($categorias)) {
    $max_order = 0;
    foreach ($categorias as $cat) {
        if ($cat['orden_visual'] > $max_order) {
            $max_order = $cat['orden_visual'];
        }
    }
    $next_cat_order = $max_order + 1;
}
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
    <link rel="icon" type="image/svg+xml" href="/assets/favicon.svg">
    <link rel="icon" type="image/png" sizes="32x32" href="/assets/favicon.svg">
    <link rel="shortcut icon" href="/assets/favicon.svg">
    <link rel="apple-touch-icon" href="/assets/favicon.svg">
    <meta name="theme-color" content="#00CFBD">
    <script>
        // Evitar advertencia del CDN de Tailwind en la consola
        const _warn = console.warn;
        console.warn = (...args) => {
            if (args[0] && typeof args[0] === 'string' && args[0].includes('cdn.tailwindcss.com')) return;
            _warn(...args);
        };
    </script>
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
        <div class="max-w-6xl mx-auto px-4 py-3 sm:py-4 flex items-center justify-between gap-2 min-w-0">
            <div class="min-w-0 flex items-center">
                    <?php if (strtolower($config['nombre'] ?? 'pronttogo') === 'pronttogo' || ($config['nombre'] ?? 'Mi Tienda') === 'Mi Tienda'): ?>
                        <?= get_logo_svg('h-8 w-auto shrink-0') ?>
                    <?php else: ?>
                    <span class="font-extrabold text-lg sm:text-xl tracking-tight bg-gradient-to-r from-[#00CFBD] to-[#2A3543] bg-clip-text text-transparent truncate max-w-[140px] sm:max-w-none block"><?= h($config['nombre'] ?? 'PronttoGo') ?></span>
                <?php endif; ?>
            </div>
            
            <div class="flex items-center gap-1.5 sm:gap-3 shrink-0">
                <a href="/" target="_blank" class="text-[10px] sm:text-xs font-bold text-[#00CFBD] hover:text-white border border-[#00CFBD]/20 hover:bg-[#00CFBD] hover:border-transparent rounded-xl px-2.5 sm:px-4 py-1.5 sm:py-2 transition-all bg-white shadow-sm flex items-center gap-1 whitespace-nowrap">
                    Ver Tienda ↗
                </a>
                <a href="admin.php?action=logout" class="text-[10px] sm:text-xs font-bold text-slate-500 hover:text-red-600 border border-slate-200 rounded-xl px-2.5 sm:px-4 py-1.5 sm:py-2 hover:bg-red-50 transition-all shadow-sm whitespace-nowrap">
                    Cerrar Sesión
                </a>
            </div>
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
            <div id="alert-success" class="p-4 bg-cyan-50 border-l-4 border-[#00CFBD] text-cyan-800 text-sm rounded-r-xl flex justify-between items-center shadow-sm">
                <span><?= h($success) ?></span>
                <button onclick="document.getElementById('alert-success').remove()" class="text-cyan-500 hover:text-cyan-850 font-bold">×</button>
            </div>
        <?php endif; ?>

        <!-- Tabs de Navegación -->
        <div class="bg-white p-2 rounded-2xl border border-slate-100 flex shadow-sm">
            <button onclick="switchTab('#profile')" id="tab-btn-profile" class="tab-btn flex-1 py-2.5 text-center font-bold text-xs md:text-sm rounded-xl transition-all">
                Perfil
            </button>
            <button onclick="switchTab('#categories')" id="tab-btn-categories" class="tab-btn flex-1 py-2.5 text-center font-bold text-xs md:text-sm rounded-xl transition-all">
                Categorías
            </button>
            <button onclick="switchTab('#products')" id="tab-btn-products" class="tab-btn flex-1 py-2.5 text-center font-bold text-xs md:text-sm rounded-xl transition-all">
                Productos
            </button>
        </div>

        <!-- ================= TAB: PERFIL COMERCIAL ================= -->
        <section id="profile" class="tab-content bg-white p-6 rounded-2xl border border-slate-100 shadow-sm space-y-6">
            <div class="border-b border-slate-50 pb-4">
                <h2 class="text-xl font-extrabold tracking-tight">Perfil</h2>
                <p class="text-xs text-slate-400">Ajustes principales del comercio y contacto de WhatsApp que se mostrarán en el catálogo digital.</p>
            </div>
            
            <form action="admin.php" method="POST" class="space-y-4 max-w-xl">
                <?= csrf_input() ?>
                <input type="hidden" name="action" value="update_profile">
                
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 mb-1">Nombre del Comercio</label>
                    <input type="text" name="nombre" value="<?= h($config['nombre']) ?>" required
                           class="w-full px-4 py-2.5 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-[#00CFBD] focus:border-transparent transition-all">
                </div>

                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 mb-1">URL del Logo del Comercio</label>
                    <input type="url" name="logo_url" value="<?= h($config['logo_url'] ?? '') ?>" placeholder="https://ejemplo.com/logo.png"
                           class="w-full px-4 py-2.5 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-[#00CFBD] focus:border-transparent transition-all">
                    <p class="text-[10px] text-slate-400 mt-1">Ingresa el enlace de la imagen del logotipo. Si lo dejas vacío, se mostrará el nombre en texto.</p>
                </div>
                
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 mb-1">WhatsApp para Pedidos</label>
                    <div class="flex gap-2 w-full">
                        <select name="codigo_pais" required
                                class="w-[125px] sm:w-[155px] shrink-0 pl-3 pr-6 sm:pl-3.5 sm:pr-8 py-2.5 border border-slate-200 rounded-xl text-xs sm:text-sm focus:outline-none focus:ring-2 focus:ring-[#00CFBD] focus:border-transparent bg-white transition-all">
                            <?php
                            $phone_split = split_whatsapp_number($config['telefono_whatsapp'] ?? '');
                            $selected_code = $phone_split['code'];
                            $local_number = $phone_split['local'];
                            
                            $prefixes = [
                                '58'  => 'Venezuela (+58)',
                                '57'  => 'Colombia (+57)'
                            ];
                            foreach ($prefixes as $code => $label):
                                $selected = ($selected_code == $code) ? 'selected' : '';
                            ?>
                                <option value="<?= h($code) ?>" <?= $selected ?>><?= h($label) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <input type="tel" name="telefono_local" value="<?= h($local_number) ?>" required placeholder="Ej: 4121234567"
                               class="flex-1 min-w-0 px-4 py-2.5 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-[#00CFBD] focus:border-transparent transition-all">
                    </div>
                    <p class="text-[11px] text-slate-400 mt-1">Selecciona el código de tu país e ingresa el número telefónico local sin el signo + ni ceros al inicio.</p>
                </div>

                <!-- Tasa de Cambio Inteligente -->
                <div class="border border-slate-100 bg-slate-50/50 p-4 rounded-xl space-y-4">
                    <h4 class="text-xs font-bold uppercase tracking-wider text-slate-600">Tasa de Cambio a Moneda Local</h4>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 mb-1">Tipo de Tasa</label>
                            <select name="tasa_tipo" id="tasa_tipo" onchange="handleTasaTipoChange()" required
                                    class="w-full px-4 py-2.5 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-[#00CFBD] focus:border-transparent bg-white transition-all">
                                <option value="manual" <?= ($config['tasa_tipo'] ?? 'manual') === 'manual' ? 'selected' : '' ?>>Tasa Fija / Personalizada</option>
                                <option value="bcv" <?= ($config['tasa_tipo'] ?? '') === 'bcv' ? 'selected' : '' ?>>Automático: Banco Central de Venezuela (BCV)</option>
                                <option value="trm" <?= ($config['tasa_tipo'] ?? '') === 'trm' ? 'selected' : '' ?>>Automático: TRM Colombia (Pesos)</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 mb-1">Valor de la Tasa ($1 USD = X)</label>
                            <div class="relative">
                                <input type="number" step="0.01" name="tasa_dolar" id="tasa_dolar_input" value="<?= number_format(floatval($config['tasa_dolar'] ?? 1.00), 2, '.', '') ?>" required
                                       class="w-full px-4 py-2.5 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-[#00CFBD] focus:border-transparent transition-all">
                                <div id="tasa_loading" class="absolute right-3 top-3.5 hidden">
                                    <svg class="animate-spin h-4 w-4 text-[#00CFBD]" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                </div>
                            </div>
                            <p id="tasa_note" class="text-[11px] text-slate-400 mt-1">Introduce el valor de cambio manualmente.</p>
                        </div>
                    </div>
                </div>

                <!-- Credenciales Administrativas -->
                <div class="border border-slate-100 bg-slate-50/50 p-4 rounded-xl space-y-4">
                    <h4 class="text-xs font-bold uppercase tracking-wider text-slate-600">Credenciales de Acceso</h4>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 mb-1">Usuario Administrativo</label>
                            <input type="text" name="admin_user" value="<?= h($dbAdminUser) ?>" required placeholder="ej: admin"
                                   class="w-full px-4 py-2.5 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-[#00CFBD] focus:border-transparent bg-white transition-all">
                        </div>
                        <div>
                            <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 mb-1">Nueva Contraseña</label>
                            <input type="password" name="admin_password" placeholder="Escribe para cambiar la clave"
                                   class="w-full px-4 py-2.5 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-[#00CFBD] focus:border-transparent bg-white transition-all">
                            <p class="text-[10px] text-slate-400 mt-1">Dejar vacío para conservar la contraseña actual.</p>
                        </div>
                    </div>
                </div>

                <button type="submit" class="px-6 py-2.5 bg-gradient-to-r from-[#00CFBD] to-[#00B5A5] hover:opacity-90 text-white font-bold text-xs rounded-xl shadow-md transition-all">
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
                             <input type="text" name="nombre_categoria" required placeholder="ej: Repuestos, Joyas, Tortas, Helados" 
                                   class="w-full px-4 py-2.5 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-[#00CFBD] focus:border-transparent transition-all">
                        </div>
                        <div>
                            <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 mb-1">Orden de Visualización</label>
                            <input type="number" name="orden_visual" value="<?= $next_cat_order ?>" required 
                                   class="w-full px-4 py-2.5 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-[#00CFBD] focus:border-transparent transition-all">
                        </div>
                        <button type="submit" class="w-full py-2.5 bg-gradient-to-r from-[#00CFBD] to-[#00B5A5] hover:opacity-90 text-white font-bold text-xs rounded-xl shadow-md transition-all">
                            Crear Categoría
                        </button>
                    </form>
                </div>

                <!-- Lista de Categorías -->
                <div class="bg-white p-6 rounded-2xl border border-slate-100 shadow-sm md:col-span-2 space-y-4 min-w-0">
                    <div class="border-b border-slate-50 pb-2">
                        <h2 class="text-lg font-extrabold">Categorías Registradas</h2>
                        <p class="text-[11px] text-slate-400">Categorías y su orden en el catálogo.</p>
                    </div>

                    <?php if (empty($categorias)): ?>
                        <p class="text-sm text-slate-400 py-6 text-center">No has registrado categorías aún.</p>
                    <?php else: ?>
                        <!-- Vista de Tarjetas para Móvil -->
                        <div class="space-y-3 lg:hidden">
                            <?php foreach ($categorias as $cat): ?>
                                <div class="bg-white border border-slate-100 rounded-xl p-4 flex items-center justify-between shadow-sm">
                                    <div>
                                        <span class="text-xs font-bold text-[#00CFBD] bg-cyan-50 px-2.5 py-1 rounded-lg">Orden #<?= h($cat['orden_visual']) ?></span>
                                        <h4 class="font-bold text-slate-800 mt-2"><?= h($cat['nombre_categoria']) ?></h4>
                                    </div>
                                    <div>
                                        <form action="admin.php" method="POST" onsubmit="return confirm('¿Seguro que deseas eliminar esta categoría? Se eliminarán todos los productos asociados.')" class="inline">
                                            <?= csrf_input() ?>
                                            <input type="hidden" name="action" value="delete_category">
                                            <input type="hidden" name="categoria_id" value="<?= h($cat['id']) ?>">
                                            <button type="submit" class="text-xs font-bold text-red-500 hover:text-red-755 bg-red-50 hover:bg-red-100 px-3.5 py-2 rounded-xl transition-colors">
                                                Eliminar
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <!-- Vista de Tabla para Escritorio -->
                        <div class="hidden lg:block overflow-x-auto">
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
                                            <td class="py-3.5 px-2 font-bold text-[#00CFBD]">#<?= h($cat['orden_visual']) ?></td>
                                            <td class="py-3.5 px-2 font-semibold"><?= h($cat['nombre_categoria']) ?></td>
                                            <td class="py-3.5 px-2 text-right">
                                                <form action="admin.php" method="POST" onsubmit="return confirm('¿Seguro que deseas eliminar esta categoría? Se eliminarán todos los productos asociados.')" class="inline">
                                                    <?= csrf_input() ?>
                                                    <input type="hidden" name="action" value="delete_category">
                                                    <input type="hidden" name="categoria_id" value="<?= h($cat['id']) ?>">
                                                    <button type="submit" class="text-xs font-bold text-red-500 hover:text-red-750 transition-colors">
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
                             <input type="text" name="nombre" id="prod-nombre" required placeholder="ej: Alternador Toyota, Anillo de Plata" 
                                   class="w-full px-4 py-2.5 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-[#00CFBD] focus:border-transparent transition-all">
                        </div>

                        <div>
                            <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 mb-1">Categoría</label>
                            <select name="categoria_id" id="prod-cat" required 
                                    class="w-full px-4 py-2.5 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-[#00CFBD] focus:border-transparent bg-white transition-all">
                                <option value="">Selecciona una categoría</option>
                                <?php foreach ($categorias as $cat): ?>
                                    <option value="<?= h($cat['id']) ?>"><?= h($cat['nombre_categoria']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div>
                            <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 mb-1">Descripción</label>
                             <textarea name="descripcion" id="prod-desc" rows="2" placeholder="Detalles, especificaciones, modelo, talla, materiales..." 
                                      class="w-full px-4 py-2.5 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-[#00CFBD] focus:border-transparent transition-all"></textarea>
                        </div>

                        <div>
                            <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 mb-1">Precio ($)</label>
                            <input type="number" step="0.01" name="precio" id="prod-precio" required placeholder="0.00" 
                                   class="w-full px-4 py-2.5 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-[#00CFBD] focus:border-transparent transition-all">
                        </div>

                        <div>
                            <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 mb-1">URL de la Imagen</label>
                            <input type="url" name="imagen_url" id="prod-img" placeholder="https://ejemplo.com/imagen.jpg" 
                                   class="w-full px-4 py-2.5 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-[#00CFBD] focus:border-transparent transition-all">
                        </div>

                        <div>
                            <label class="flex items-center space-x-2 text-sm font-semibold cursor-pointer">
                                <input type="checkbox" name="disponible" id="prod-disp" value="1" checked class="rounded text-[#00CFBD] focus:ring-[#00CFBD] w-4 h-4">
                                <span>Disponible para la venta</span>
                            </label>
                        </div>

                        <div class="flex gap-2">
                            <button type="button" onclick="resetProductForm()" id="prod-btn-cancel" class="flex-1 py-2.5 border border-slate-200 text-slate-600 hover:bg-slate-50 font-bold text-xs rounded-xl transition-all hidden">
                                Cancelar
                            </button>
                            <button type="submit" class="flex-1 py-2.5 bg-gradient-to-r from-[#00CFBD] to-[#00B5A5] hover:opacity-90 text-white font-bold text-xs rounded-xl shadow-md transition-all">
                                Guardar
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Lista de Productos -->
                <div class="bg-white p-6 rounded-2xl border border-slate-100 shadow-sm md:col-span-2 space-y-4 min-w-0">
                    <div class="border-b border-slate-50 pb-2">
                        <h2 class="text-lg font-extrabold">Productos Registrados</h2>
                        <p class="text-[11px] text-slate-400">Administra disponibilidad y detalles.</p>
                    </div>

                    <?php if (empty($productos)): ?>
                        <p class="text-sm text-slate-400 py-6 text-center">No has registrado productos aún.</p>
                    <?php else: ?>
                        <!-- Vista de Tarjetas para Móvil -->
                        <div class="space-y-3 lg:hidden">
                            <?php foreach ($productos as $prod): ?>
                                <div class="bg-white border border-slate-100 rounded-xl p-4 space-y-3 shadow-sm">
                                    <div class="flex items-center space-x-3">
                                        <?php if (!empty($prod['imagen_url'])): ?>
                                            <img src="<?= h($prod['imagen_url']) ?>" alt="<?= h($prod['nombre']) ?>" class="w-12 h-12 object-cover rounded-lg bg-slate-100">
                                        <?php else: ?>
                                            <div class="w-12 h-12 rounded-lg bg-slate-100 flex items-center justify-center text-slate-400 text-lg font-bold">🍔</div>
                                        <?php endif; ?>
                                        <div class="flex-1 min-w-0">
                                            <h4 class="font-bold text-slate-800 truncate"><?= h($prod['nombre']) ?></h4>
                                            <p class="text-xs text-slate-500 font-semibold mt-0.5"><?= h($categoriasMap[$prod['categoria_id']] ?? 'Sin Categoría') ?></p>
                                        </div>
                                        <div class="text-right">
                                            <span class="font-extrabold text-slate-800 text-sm">$<?= number_format($prod['precio'], 2) ?></span>
                                        </div>
                                    </div>
                                    
                                    <?php if (!empty($prod['descripcion'])): ?>
                                        <p class="text-[11px] text-slate-550 leading-relaxed bg-slate-55/50 p-2 rounded-lg"><?= h($prod['descripcion']) ?></p>
                                    <?php endif; ?>
                                    
                                    <div class="flex flex-wrap items-center justify-between gap-y-2 pt-2.5 border-t border-slate-100">
                                        <div class="flex items-center space-x-2 shrink-0">
                                            <span class="text-xs font-semibold text-slate-500">Disponible:</span>
                                            <form action="admin.php" method="POST" class="inline-block">
                                                <?= csrf_input() ?>
                                                <input type="hidden" name="action" value="toggle_disponible">
                                                <input type="hidden" name="producto_id" value="<?= h($prod['id']) ?>">
                                                <input type="hidden" name="disponible" value="<?= $prod['disponible'] ? '0' : '1' ?>">
                                                <button type="submit" class="relative inline-flex items-center h-6 rounded-full w-11 transition-colors focus:outline-none <?= $prod['disponible'] ? 'bg-[#00CFBD]' : 'bg-slate-200' ?>">
                                                    <span class="inline-block w-4 h-4 transform bg-white rounded-full transition-transform <?= $prod['disponible'] ? 'translate-x-6' : 'translate-x-1' ?>"></span>
                                                </button>
                                            </form>
                                        </div>
                                        <div class="flex items-center space-x-2 shrink-0">
                                            <button type="button" onclick='loadProductForEdit(<?= json_encode([
                                                'id' => $prod['id'],
                                                'nombre' => $prod['nombre'],
                                                'categoria_id' => $prod['categoria_id'],
                                                'descripcion' => $prod['descripcion'] ?? '',
                                                'precio' => $prod['precio'],
                                                'imagen_url' => $prod['imagen_url'] ?? '',
                                                'disponible' => $prod['disponible'] ? 1 : 0
                                            ]) ?>)' class="text-xs font-bold text-[#00CFBD] bg-cyan-50 hover:bg-cyan-100 px-3.5 py-2 rounded-xl transition-colors">
                                                Editar
                                            </button>
                                            <form action="admin.php" method="POST" onsubmit="return confirm('¿Seguro que deseas eliminar este producto?')" class="inline">
                                                <?= csrf_input() ?>
                                                <input type="hidden" name="action" value="delete_product">
                                                <input type="hidden" name="producto_id" value="<?= h($prod['id']) ?>">
                                                <button type="submit" class="text-xs font-bold text-red-500 bg-red-50 hover:bg-red-100 px-3.5 py-2 rounded-xl transition-colors">
                                                    Eliminar
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <!-- Vista de Tabla para Escritorio -->
                        <div class="hidden lg:block overflow-x-auto">
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
                                                    <button type="submit" class="relative inline-flex items-center h-6 rounded-full w-11 transition-colors focus:outline-none <?= $prod['disponible'] ? 'bg-[#00CFBD]' : 'bg-slate-200' ?>">
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
                                                    ]) ?>)' class="text-xs font-bold text-[#00CFBD] hover:text-[#00B5A5] transition-colors">
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

    <footer class="bg-white border-t border-slate-100 py-6 text-center text-xs text-slate-400 font-medium mt-auto flex flex-col sm:flex-row items-center justify-center gap-2 sm:gap-4">
        <span>&copy; 2026 <?= h(!empty($config['nombre']) && $config['nombre'] !== 'Mi Tienda' ? $config['nombre'] : 'PronttoGo') ?>. Desarrollado por Montero Studio.</span>
        <span class="hidden sm:inline text-slate-200">|</span>
        <a href="/legal" class="text-slate-400 hover:text-[#00CFBD] transition-colors">Términos y Privacidad</a>
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
                btn.className = "tab-btn flex-1 py-2.5 text-center font-bold text-xs md:text-sm rounded-xl text-slate-400 hover:text-slate-655 transition-all";
            });
            
            const targetTab = document.querySelector(hash);
            if (targetTab) {
                targetTab.classList.add('active');
            }
            
            const btnId = 'tab-btn-' + hash.replace('#', '');
            const targetBtn = document.getElementById(btnId);
            if (targetBtn) {
                targetBtn.className = "tab-btn flex-1 py-2.5 text-center font-bold text-xs md:text-sm rounded-xl bg-gradient-to-r from-[#00CFBD] to-[#00B5A5] text-white shadow-md transition-all";
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

        async function fetchRateFromClient(type) {
            const loading = document.getElementById('tasa_loading');
            const input = document.getElementById('tasa_dolar_input');
            if (loading) loading.classList.remove('hidden');
            try {
                const res = await fetch('https://open.er-api.com/v6/latest/USD');
                if (!res.ok) throw new Error();
                const data = await res.json();
                if (type === 'bcv' && data.rates && data.rates.VES) {
                    input.value = parseFloat(data.rates.VES).toFixed(2);
                } else if (type === 'trm' && data.rates && data.rates.COP) {
                    input.value = Math.round(data.rates.COP);
                }
            } catch (err) {
                console.error('Error fetching rate:', err);
            } finally {
                if (loading) loading.classList.add('hidden');
            }
        }

        function handleTasaTipoChange(isInitial = false) {
            const type = document.getElementById('tasa_tipo').value;
            const input = document.getElementById('tasa_dolar_input');
            const note = document.getElementById('tasa_note');
            
            if (type === 'manual') {
                input.removeAttribute('readonly');
                input.classList.remove('bg-slate-50', 'text-slate-450');
                note.textContent = 'Introduce el valor de cambio manualmente.';
            } else {
                input.removeAttribute('readonly');
                input.classList.remove('bg-slate-50', 'text-slate-450');
                note.textContent = 'Tasa oficial. Puedes ajustarla manualmente o dejar la sugerida.';
                if (!isInitial) {
                    fetchRateFromClient(type);
                }
            }
        }

        // Inicialización
        window.addEventListener('DOMContentLoaded', () => {
            let defaultTab = '<?= $active_tab ?? '#profile' ?>';
            if (window.location.hash) {
                defaultTab = window.location.hash;
            }
            switchTab(defaultTab);
            
            if (document.getElementById('tasa_tipo')) {
                handleTasaTipoChange(true);
            }
        });
    </script>
</body>
</html>
