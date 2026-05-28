<?php
/**
 * PronttoGo - Panel de AdministraciÃ³n Dedicado (Single-Store)
 * Administra la configuraciÃ³n, categorÃ­as y productos de una Ãºnica tienda.
 */

require_once __DIR__ . '/config.php';

// Cargar configuraciÃ³n de la base de datos (se usa para autenticaciÃ³n y UI)
$resConfig = supabase_request('GET', 'configuracion?id=eq.1');
$config = $resConfig['success'] && !empty($resConfig['data']) ? $resConfig['data'][0] : [];

// Resolver credenciales administrativas (base de datos o fallback a config.php)
$dbAdminUser = !empty($config['admin_user']) ? $config['admin_user'] : ADMIN_USER;
$dbAdminPassword = !empty($config['admin_password']) ? $config['admin_password'] : ADMIN_PASSWORD;

$error = '';
$success = '';

// 1. PROCESAR ACCIÃ“N DE LOGIN (Sin requerir sesiÃ³n iniciada)
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
        $error = 'Demasiados intentos fallidos. IntÃ©ntalo de nuevo en 15 minutos.';
    } else {
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';
        $captcha_ans = intval($_POST['captcha_answer'] ?? 0);
        $expected_ans = ($_SESSION['login_captcha_a'] ?? 0) + ($_SESSION['login_captcha_b'] ?? 0);

        // C. Validar Captcha
        if ($captcha_ans !== $expected_ans) {
            $error = 'Respuesta de verificaciÃ³n de seguridad incorrecta.';
            $_SESSION['login_attempts']++;
            if ($_SESSION['login_attempts'] >= 5) {
                $_SESSION['login_lock_until'] = time() + 900;
            }
            $_SESSION['login_captcha_a'] = rand(1, 9);
            $_SESSION['login_captcha_b'] = rand(1, 9);
            sleep(1);
        } else {
            // D. Validar credenciales (con fallback texto plano para migraciÃ³n)
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
                $error = 'Usuario o contraseÃ±a incorrectos.';
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

// Cerrar sesiÃ³n
if (isset($_GET['action']) && $_GET['action'] === 'logout') {
    $_SESSION = [];
    session_destroy();
    redirect('/admin');
}

// --- VERIFICAR AUTENTICACIÃ“N ---
$is_logged_in = is_admin_logged_in();

if (!$is_logged_in):
    if (empty($_SESSION['login_captcha_a']) || empty($_SESSION['login_captcha_b'])) {
        $_SESSION['login_captcha_a'] = rand(1, 9);
        $_SESSION['login_captcha_b'] = rand(1, 9);
    }
    // RENDERIZAR PANTALLA DE ACCESO POR CONTRASEÃ‘A
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
    <title>Acceso â€” Panel PronttoGo</title>
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

        /* BotÃ³n con efecto shine */
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
            <div class="bg-slate-800 p-8 text-center relative overflow-hidden">
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
                            <span class="absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 text-sm">ðŸ‘¤</span>
                            <input id="login-user" type="text" name="username" required placeholder="ej: admin" <?= $is_locked ? 'disabled' : '' ?>
                                   class="input-field w-full pl-10 pr-4 py-2.5 border border-slate-200 rounded-xl text-sm bg-slate-50/50 text-slate-900 placeholder-slate-400">
                        </div>
                    </div>

                    <!-- ContraseÃ±a -->
                    <div>
                        <label for="login-pass" class="block text-xs font-bold uppercase tracking-wider text-slate-500 mb-1.5">ContraseÃ±a</label>
                        <div class="relative">
                            <span class="absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 text-sm">ðŸ”’</span>
                            <input id="login-pass" type="password" name="password" required placeholder="&bull;&bull;&bull;&bull;&bull;&bull;&bull;&bull;" <?= $is_locked ? 'disabled' : '' ?>
                                   class="input-field w-full pl-10 pr-4 py-2.5 border border-slate-200 rounded-xl text-sm bg-slate-50/50 text-slate-900">
                        </div>
                        <p class="text-[10px] text-slate-400 mt-1.5">Por defecto: usuario <code class="bg-slate-100 px-1 rounded">admin</code> y clave <code class="bg-slate-100 px-1 rounded">admin123</code>.</p>
                    </div>

                    <!-- Captcha -->
                    <div>
                        <label for="login-captcha" class="block text-xs font-bold uppercase tracking-wider text-slate-500 mb-1.5">
                            VerificaciÃ³n: Â¿CuÃ¡nto es <?= $_SESSION['login_captcha_a'] ?> + <?= $_SESSION['login_captcha_b'] ?>?
                        </label>
                        <div class="relative">
                            <span class="absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 text-sm">ðŸ§©</span>
                            <input id="login-captcha" type="number" name="captcha_answer" required placeholder="Tu respuesta" <?= $is_locked ? 'disabled' : '' ?>
                                   class="input-field w-full pl-10 pr-4 py-2.5 border border-slate-200 rounded-xl text-sm bg-slate-50/50 text-slate-900 [appearance:textfield] [&::-webkit-inner-spin-button]:appearance-none">
                        </div>
                    </div>

                    <!-- BotÃ³n de acceso -->
                    <button type="submit" <?= $is_locked ? 'disabled' : '' ?>
                            class="btn-primary w-full py-3.5 text-white font-bold text-sm rounded-xl shadow-lg disabled:opacity-50 disabled:cursor-not-allowed">
                        <?= $is_locked
                            ? "ðŸ”’ Bloqueado por {$lock_time_left} min"
                            : "âš¡ Ingresar al Panel" ?>
                    </button>
                </form>

                <!-- Link volver al menÃº -->
                <div class="text-center pt-1">
                    <a href="/" class="text-xs text-slate-400 hover:text-[#00CFBD] transition-colors font-medium">
                        &larr; Volver al catÃ¡logo digital
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

// --- PROCESAMIENTO DE ACCIONES CON SESIÃ“N ACTIVA (CRUD) ---
$active_tab = '#dashboard';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    // Validar CSRF
    if (!verify_csrf_token($_POST['csrf_token'] ?? null)) {
        $error = 'ValidaciÃ³n CSRF fallida. IntÃ©ntalo de nuevo.';
    } else {
        $action = $_POST['action'];
        
        // 1. ACTUALIZAR CONFIGURACIÃ“N DE LA TIENDA
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

            // Nuevos campos de negocio local
            $tipo_negocio = trim($_POST['tipo_negocio'] ?? 'gastronomia');
            if (!in_array($tipo_negocio, ['gastronomia', 'boutique', 'ferreteria_repuestos', 'belleza_estetica', 'otros'])) {
                $tipo_negocio = 'gastronomia';
            }
            $moneda_simbolo = trim($_POST['moneda_simbolo'] ?? '$');
            $moneda_nombre = trim($_POST['moneda_nombre'] ?? 'USD');
            $costo_delivery = floatval($_POST['costo_delivery'] ?? 0.00);
            $direccion = trim($_POST['direccion'] ?? '');
            $horario = trim($_POST['horario'] ?? '');

            // Tasa automÃ¡tica inteligente si no es manual y el valor enviado es por defecto
            if ($tasa_tipo !== 'manual' && $tasa_dolar <= 1.00) {
                $fetched_rate = fetch_automatic_rate($tasa_tipo);
                if ($fetched_rate !== null) {
                    $tasa_dolar = $fetched_rate;
                } else {
                    $error = 'No se pudo consultar la tasa automÃ¡tica de internet. Se conservÃ³ el valor anterior.';
                }
            }

            if (empty($nombre) || empty($whatsapp)) {
                $error = 'El nombre del comercio y el telÃ©fono de WhatsApp son obligatorios.';
            } else {
                $updateData = [
                    'nombre' => $nombre,
                    'telefono_whatsapp' => $whatsapp,
                    'logo_url' => !empty($logo_url) ? $logo_url : null,
                    'tasa_dolar' => $tasa_dolar,
                    'tasa_tipo' => $tasa_tipo,
                    'tipo_negocio' => $tipo_negocio,
                    'moneda_simbolo' => $moneda_simbolo,
                    'moneda_nombre' => $moneda_nombre,
                    'costo_delivery' => $costo_delivery,
                    'direccion' => $direccion,
                    'horario' => $horario
                ];

                if (!empty($new_admin_user)) {
                    $updateData['admin_user'] = $new_admin_user;
                }
                
                if (!empty($new_admin_password)) {
                    $updateData['admin_password'] = password_hash($new_admin_password, PASSWORD_DEFAULT);
                }

                $response = supabase_request('PATCH', 'configuracion?id=eq.1', $updateData);
                
                if ($response['success']) {
                    $success = 'Perfil comercial actualizado con Ã©xito.';
                    // Actualizar el estado local
                    $resConfig = supabase_request('GET', 'configuracion?id=eq.1');
                    if ($resConfig['success'] && !empty($resConfig['data'])) {
                        $config = $resConfig['data'][0];
                        // Actualizar credenciales en la sesiÃ³n/variables de esta ejecuciÃ³n
                        $dbAdminUser = !empty($config['admin_user']) ? $config['admin_user'] : ADMIN_USER;
                        $dbAdminPassword = !empty($config['admin_password']) ? $config['admin_password'] : ADMIN_PASSWORD;
                    }
                } else {
                    $error = 'Error al actualizar el perfil en la base de datos.';
                }
            }
            $active_tab = '#profile';
        }
        
        // 2. GUARDAR CATEGORÃA (CREAR / EDITAR)
        if ($action === 'save_category') {
            $categoria_id = trim($_POST['categoria_id'] ?? '');
            $nombre_categoria = trim($_POST['nombre_categoria'] ?? '');
            $orden_visual = intval($_POST['orden_visual'] ?? 0);
            
            if (empty($nombre_categoria)) {
                $error = 'El nombre de la categorÃ­a es obligatorio.';
            } else {
                $catData = [
                    'nombre_categoria' => $nombre_categoria,
                    'orden_visual' => $orden_visual
                ];
                
                if (!empty($categoria_id)) {
                    // Editar
                    $response = supabase_request('PATCH', 'categorias?id=eq.' . rawurlencode($categoria_id), $catData);
                    if ($response['success']) {
                        $success = 'CategorÃ­a actualizada correctamente.';
                    } else {
                        $error = 'Error al actualizar la categorÃ­a.';
                    }
                } else {
                    // Crear
                    $response = supabase_request('POST', 'categorias', $catData);
                    if ($response['success']) {
                        $success = 'CategorÃ­a agregada correctamente.';
                    } else {
                        $error = 'Error al guardar la categorÃ­a.';
                    }
                }
            }
            $active_tab = '#categories';
        }
        
        // 3. ELIMINAR CATEGORÃA
        if ($action === 'delete_category') {
            $categoria_id = $_POST['categoria_id'] ?? '';
            if (!empty($categoria_id)) {
                $response = supabase_request('DELETE', 'categorias?id=eq.' . rawurlencode($categoria_id));
                if ($response['success']) {
                    $success = 'CategorÃ­a eliminada con Ã©xito (junto con sus productos).';
                } else {
                    $error = 'Error al eliminar la categorÃ­a.';
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
                $error = 'Nombre, CategorÃ­a y Precio (mayor a 0) son obligatorios.';
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
                    $success = 'Producto eliminado con Ã©xito.';
                } else {
                    $error = 'Error al eliminar el producto.';
                }
            }
            $active_tab = '#products';
        }
        
        // 6. TOGGLE DISPONIBILIDAD RÃPIDO
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

// --- OBTENCIÃ“N DE DATOS GENERALES ---
// Inicializar configuraciÃ³n por defecto si la base de datos fallÃ³ por completo
if (empty($config)) {
    $config = ['nombre' => 'PronttoGo', 'telefono_whatsapp' => ''];
}

// Cargar categorÃ­as ordenadas
$resCategorias = supabase_request('GET', 'categorias?order=orden_visual.asc');
$categorias = $resCategorias['success'] ? $resCategorias['data'] : [];

// Calcular siguiente orden de categorÃ­a inteligente
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

// Agrupar categorÃ­as en un array asociativo por ID
$categoriasMap = [];
foreach ($categorias as $cat) {
    $categoriasMap[$cat['id']] = $cat['nombre_categoria'];
}

$total_categorias = count($categorias);
$total_productos = count($productos);
$productos_activos = 0;
$productos_inactivos = 0;
foreach ($productos as $prod) {
    if (!empty($prod['disponible']) && ($prod['disponible'] === true || $prod['disponible'] == 1 || $prod['disponible'] === '1' || $prod['disponible'] === 'true')) {
        $productos_activos++;
    } else {
        $productos_inactivos++;
    }
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
        <div class="max-w-6xl mx-auto px-4 py-3 sm:py-4 flex items-center justify-between gap-4 min-w-0">
            <div class="min-w-0 flex items-center">
                <?php if (strtolower($config['nombre'] ?? 'pronttogo') === 'pronttogo' || ($config['nombre'] ?? 'Mi Tienda') === 'Mi Tienda'): ?>
                    <?= get_logo_svg('h-8 w-auto shrink-0') ?>
                <?php else: ?>
                    <span class="font-extrabold text-base sm:text-xl tracking-tight text-slate-800 truncate max-w-[130px] sm:max-w-none block"><?= h($config['nombre'] ?? 'PronttoGo') ?></span>
                <?php endif; ?>
            </div>
            
            <div class="flex items-center gap-2 sm:gap-3 shrink-0">
                <a href="/" target="_blank" class="text-[10px] sm:text-xs font-bold text-[#00CFBD] hover:text-white border border-[#00CFBD]/20 hover:bg-[#00CFBD] hover:border-transparent rounded-xl px-2.5 sm:px-4 py-1.5 sm:py-2 transition-all bg-white shadow-sm flex items-center gap-1.5 whitespace-nowrap">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                    </svg>
                    <span class="hidden sm:inline">Ver Tienda</span>
                    <span class="sm:hidden">Tienda</span>
                    <span>â†—</span>
                </a>
                <a href="admin.php?action=logout" class="text-[10px] sm:text-xs font-bold text-slate-500 hover:text-red-600 border border-slate-200 rounded-xl px-2.5 sm:px-4 py-1.5 sm:py-2 hover:bg-red-50 transition-all shadow-sm whitespace-nowrap flex items-center gap-1.5">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                    </svg>
                    <span class="hidden sm:inline">Cerrar SesiÃ³n</span>
                    <span class="sm:hidden">Salir</span>
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
                <button onclick="document.getElementById('alert-error').remove()" class="text-red-500 hover:text-red-800 font-bold">Ã—</button>
            </div>
        <?php endif; ?>

        <?php if (!empty($success)): ?>
            <div id="alert-success" class="p-4 bg-cyan-50 border-l-4 border-[#00CFBD] text-cyan-800 text-sm rounded-r-xl flex justify-between items-center shadow-sm">
                <span><?= h($success) ?></span>
                <button onclick="document.getElementById('alert-success').remove()" class="text-cyan-500 hover:text-cyan-850 font-bold">Ã—</button>
            </div>
        <?php endif; ?>

        <!-- Tabs de NavegaciÃ³n -->
        <div class="bg-white p-1.5 rounded-2xl border border-slate-100 flex shadow-sm gap-1">
            <button onclick="switchTab('#dashboard')" id="tab-btn-dashboard" class="tab-btn flex-1 flex flex-col md:flex-row items-center justify-center gap-1 py-2 sm:py-2.5 text-center font-bold text-[10px] sm:text-xs md:text-sm rounded-xl transition-all">
                <svg class="w-4 h-4 md:w-5 md:h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v4a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v4a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z" />
                </svg>
                <span>Dashboard</span>
            </button>
            <button onclick="switchTab('#products')" id="tab-btn-products" class="tab-btn flex-1 flex flex-col md:flex-row items-center justify-center gap-1 py-2 sm:py-2.5 text-center font-bold text-[10px] sm:text-xs md:text-sm rounded-xl transition-all">
                <svg class="w-4 h-4 md:w-5 md:h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                </svg>
                <span>Productos</span>
            </button>
            <button onclick="switchTab('#categories')" id="tab-btn-categories" class="tab-btn flex-1 flex flex-col md:flex-row items-center justify-center gap-1 py-2 sm:py-2.5 text-center font-bold text-[10px] sm:text-xs md:text-sm rounded-xl transition-all">
                <svg class="w-4 h-4 md:w-5 md:h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z" />
                </svg>
                <span>CategorÃ­as</span>
            </button>
            <button onclick="switchTab('#profile')" id="tab-btn-profile" class="tab-btn flex-1 flex flex-col md:flex-row items-center justify-center gap-1 py-2 sm:py-2.5 text-center font-bold text-[10px] sm:text-xs md:text-sm rounded-xl transition-all">
                <svg class="w-4 h-4 md:w-5 md:h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                </svg>
                <span>Ajustes</span>
            </button>
        </div>

        <!-- ================= TAB: DASHBOARD ================= -->
        <section id="dashboard" class="tab-content space-y-6">
            <!-- EstadÃ­sticas -->
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <div class="bg-white p-5 rounded-2xl border border-slate-100 shadow-sm flex flex-col justify-between">
                    <span class="text-xs font-bold text-slate-400 uppercase tracking-wider">ðŸ“ CategorÃ­as</span>
                    <span class="text-3xl font-black text-slate-800 mt-2"><?= intval($total_categorias) ?></span>
                </div>
                <div class="bg-white p-5 rounded-2xl border border-slate-100 shadow-sm flex flex-col justify-between">
                    <span class="text-xs font-bold text-slate-400 uppercase tracking-wider">ðŸ“¦ Total Productos</span>
                    <span class="text-3xl font-black text-slate-800 mt-2"><?= intval($total_productos) ?></span>
                </div>
                <div class="bg-white p-5 rounded-2xl border border-slate-100 shadow-sm flex flex-col justify-between">
                    <span class="text-xs font-bold text-slate-400 uppercase tracking-wider">ðŸŸ¢ Disponibles</span>
                    <span class="text-3xl font-black text-emerald-600 mt-2"><?= intval($productos_activos) ?></span>
                </div>
                <div class="bg-white p-5 rounded-2xl border border-slate-100 shadow-sm flex flex-col justify-between">
                    <span class="text-xs font-bold text-slate-400 uppercase tracking-wider">ðŸ”´ Agotados / Inactivos</span>
                    <span class="text-3xl font-black text-red-500 mt-2"><?= intval($productos_inactivos) ?></span>
                </div>
            </div>

            <div class="grid md:grid-cols-3 gap-6">
                <!-- Compartir y QR -->
                <div class="bg-white p-6 rounded-2xl border border-slate-100 shadow-sm space-y-6 flex flex-col justify-between">
                    <div>
                        <h3 class="text-base font-extrabold tracking-tight text-slate-800">Comparte tu CatÃ¡logo</h3>
                        <p class="text-xs text-slate-400 mt-1">Haz que tus clientes escaneen el cÃ³digo QR o copia el enlace directo para enviarlo por redes sociales.</p>
                    </div>

                    <?php
                    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://';
                    $catalogUrl = $protocol . ($_SERVER['HTTP_HOST'] ?? 'localhost') . '/';
                    $qrCodeApiUrl = "https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=" . urlencode($catalogUrl);
                    ?>

                    <div class="flex flex-col items-center justify-center p-4 bg-slate-50/50 rounded-2xl border border-slate-100">
                        <img src="<?= h($qrCodeApiUrl) ?>" alt="QR Code" class="w-40 h-40 bg-white p-2 rounded-xl shadow-sm border border-slate-100">
                        <a href="/" target="_blank" class="text-xs font-extrabold text-[#00CFBD] mt-3 hover:underline truncate max-w-full">
                            <?= h($catalogUrl) ?>
                        </a>
                    </div>

                    <div class="space-y-2">
                        <button onclick="copyToClipboard('<?= h($catalogUrl) ?>')" class="w-full py-2.5 border border-[#00CFBD]/20 hover:border-transparent text-[#00CFBD] hover:bg-[#00CFBD] hover:text-white font-bold text-xs rounded-xl shadow-sm transition-all flex items-center justify-center gap-1 bg-white">
                            ðŸ“‹ Copiar Enlace
                        </button>
                        <a href="<?= h($qrCodeApiUrl) ?>" download="qr_catalogo.png" target="_blank" class="w-full py-2.5 bg-slate-50 hover:bg-slate-100 border border-slate-200 text-slate-600 font-bold text-xs rounded-xl shadow-sm transition-all flex items-center justify-center gap-1">
                            â¬‡ï¸ Descargar CÃ³digo QR
                        </a>
                    </div>
                </div>

                <!-- Resumen de OperaciÃ³n y Tipo de Negocio -->
                <div class="bg-white p-6 rounded-2xl border border-slate-100 shadow-sm md:col-span-2 space-y-6 flex flex-col justify-between">
                    <div>
                        <div class="flex items-center justify-between">
                            <h3 class="text-base font-extrabold tracking-tight text-slate-800">Resumen del Comercio</h3>
                            <span class="text-xs font-extrabold text-[#00CFBD] bg-cyan-50 px-2.5 py-1 rounded-xl">
                                <?php
                                $tipo = $config['tipo_negocio'] ?? 'gastronomia';
                                if ($tipo === 'boutique') echo 'ðŸ‘• Boutique';
                                elseif ($tipo === 'ferreteria_repuestos') echo 'ðŸ”§ Repuestos/FerreterÃ­a';
                                elseif ($tipo === 'belleza_estetica') echo 'âœ‚ï¸ Belleza/EstÃ©tica';
                                elseif ($tipo === 'otros') echo 'ðŸ›ï¸ Otro Negocio';
                                else echo 'ðŸ” GastronomÃ­a';
                                ?>
                            </span>
                        </div>
                        <p class="text-xs text-slate-400 mt-1">Detalles actuales de configuraciÃ³n pÃºblica de tu negocio.</p>
                    </div>

                    <div class="divide-y divide-slate-100 bg-slate-50/50 p-4 rounded-2xl border border-slate-100 space-y-3">
                        <div class="flex justify-between items-center py-1">
                            <span class="text-xs font-bold text-slate-400">WhatsApp de AtenciÃ³n</span>
                            <span class="text-xs font-bold text-slate-800"><?= h($config['telefono_whatsapp'] ?? 'No configurado') ?></span>
                        </div>
                        <div class="flex justify-between items-center pt-2 py-1">
                            <span class="text-xs font-bold text-slate-400">Moneda de Trabajo</span>
                            <span class="text-xs font-bold text-slate-800"><?= h($config['moneda_simbolo'] ?? '$') ?> (<?= h($config['moneda_nombre'] ?? 'USD') ?>)</span>
                        </div>
                        <div class="flex justify-between items-center pt-2 py-1">
                            <span class="text-xs font-bold text-slate-400">Tasa de Cambio ($1)</span>
                            <span class="text-xs font-bold text-slate-800"><?= number_format(floatval($config['tasa_dolar'] ?? 1.00), 2) ?> <?= h($config['moneda_nombre'] ?? 'USD') ?></span>
                        </div>
                        <div class="flex justify-between items-center pt-2 py-1">
                            <span class="text-xs font-bold text-slate-400">Costo de Delivery</span>
                            <span class="text-xs font-bold text-slate-850">
                                <?= floatval($config['costo_delivery'] ?? 0.00) > 0 ? '$' . number_format($config['costo_delivery'], 2) : 'Gratis / Convenir' ?>
                            </span>
                        </div>
                        <div class="flex justify-between items-center pt-2 py-1">
                            <span class="text-xs font-bold text-slate-400">Horario</span>
                            <span class="text-xs font-bold text-slate-800 truncate max-w-[200px]"><?= h(!empty($config['horario']) ? $config['horario'] : 'No definido') ?></span>
                        </div>
                    </div>

                    <div class="flex gap-2">
                        <button onclick="switchTab('#profile')" class="flex-1 py-2.5 bg-slate-800 hover:bg-slate-700 hover:opacity-90 text-white font-bold text-xs rounded-xl shadow-md transition-all flex items-center justify-center gap-1">
                            âš™ï¸ Editar ConfiguraciÃ³n
                        </button>
                    </div>
                </div>
            </div>


        </section>

        <!-- ================= TAB: PERFIL COMERCIAL ================= -->
        <section id="profile" class="tab-content bg-white p-6 rounded-2xl border border-slate-100 shadow-sm space-y-6">
            <div class="border-b border-slate-50 pb-4">
                <h2 class="text-xl font-extrabold tracking-tight">Ajustes del Sistema</h2>
                <p class="text-xs text-slate-400">Administra la configuraciÃ³n comercial, pasarela de WhatsApp, tasas de cambio y credenciales de acceso de tu catÃ¡logo.</p>
            </div>
            
            <form action="admin.php" method="POST" class="space-y-6">
                <?= csrf_input() ?>
                <input type="hidden" name="action" value="update_profile">
                
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <!-- COLUMNA IZQUIERDA: InformaciÃ³n del Comercio y OperaciÃ³n -->
                    <div class="space-y-6">
                        
                        <!-- Tarjeta: Datos del Comercio -->
                        <div class="bg-[#F8FAFC]/50 border border-slate-100 p-5 rounded-2xl space-y-4">
                            <h3 class="text-xs font-bold uppercase tracking-wider text-slate-600 border-b border-slate-200/50 pb-2 flex items-center gap-1.5">ðŸ“¦ Datos del Comercio</h3>
                            
                            <div>
                                <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 mb-1">Nombre del Comercio</label>
                                <input type="text" name="nombre" value="<?= h($config['nombre']) ?>" required
                                       class="w-full px-4 py-2.5 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-[#00CFBD] focus:border-transparent bg-white transition-all">
                            </div>

                            <div>
                                <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 mb-1">URL del Logo del Comercio</label>
                                <input type="url" name="logo_url" value="<?= h($config['logo_url'] ?? '') ?>" placeholder="https://ejemplo.com/logo.png"
                                       class="w-full px-4 py-2.5 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-[#00CFBD] focus:border-transparent bg-white transition-all">
                                <p class="text-[10px] text-slate-400 mt-1">Ingresa el enlace de la imagen del logotipo. Si lo dejas vacÃ­o, se mostrarÃ¡ el nombre en texto.</p>
                            </div>
                            
                            <div>
                                <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 mb-1">WhatsApp para Pedidos</label>
                                <div class="flex gap-2 w-full">
                                    <select name="codigo_pais" required
                                            class="w-[85px] sm:w-[120px] shrink-0 pl-2 pr-6 sm:pl-3 sm:pr-8 py-2.5 border border-slate-200 rounded-xl text-xs sm:text-sm focus:outline-none focus:ring-2 focus:ring-[#00CFBD] focus:border-transparent bg-white transition-all">
                                        <?php
                                        $phone_split = split_whatsapp_number($config['telefono_whatsapp'] ?? '');
                                        $selected_code = $phone_split['code'];
                                        $local_number = $phone_split['local'];
                                        
                                        $prefixes = [
                                            '58'  => 'ðŸ‡»ðŸ‡ª +58',
                                            '57'  => 'ðŸ‡¨ðŸ‡´ +57'
                                        ];
                                        foreach ($prefixes as $code => $label):
                                            $selected = ($selected_code == $code) ? 'selected' : '';
                                        ?>
                                            <option value="<?= h($code) ?>" <?= $selected ?>><?= h($label) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                    <input type="tel" name="telefono_local" value="<?= h($local_number) ?>" required placeholder="Ej: 4121234567"
                                           class="flex-1 min-w-0 px-4 py-2.5 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-[#00CFBD] focus:border-transparent bg-white transition-all">
                                </div>
                                <p class="text-[10px] text-slate-400 mt-1">Selecciona el cÃ³digo de tu paÃ­s e ingresa el nÃºmero local sin el signo + ni ceros al inicio.</p>
                            </div>

                            <div>
                                <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 mb-1">Tipo de negocio</label>
                                <select name="tipo_negocio" required
                                        class="w-full px-4 py-2.5 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-[#00CFBD] focus:border-transparent bg-white transition-all">
                                    <option value="gastronomia" <?= ($config['tipo_negocio'] ?? 'gastronomia') === 'gastronomia' ? 'selected' : '' ?>>ðŸ” GastronomÃ­a (Restaurantes, CafÃ©s, Comida)</option>
                                    <option value="boutique" <?= ($config['tipo_negocio'] ?? '') === 'boutique' ? 'selected' : '' ?>>ðŸ‘• Tienda de Ropa / Calzado / Boutique</option>
                                    <option value="ferreteria_repuestos" <?= ($config['tipo_negocio'] ?? '') === 'ferreteria_repuestos' ? 'selected' : '' ?>>ðŸ”§ Repuestos / FerreterÃ­a / Herramientas</option>
                                    <option value="belleza_estetica" <?= ($config['tipo_negocio'] ?? '') === 'belleza_estetica' ? 'selected' : '' ?>>âœ‚ï¸ EstÃ©tica / PeluquerÃ­a / Belleza</option>
                                    <option value="otros" <?= ($config['tipo_negocio'] ?? '') === 'otros' ? 'selected' : '' ?>>ðŸ›ï¸ Otros Negocios Locales / Servicios</option>
                                </select>
                                <p class="text-[10px] text-slate-400 mt-1">Esto cambia la apariencia visual, los iconos por defecto y adaptaciones temÃ¡ticas del catÃ¡logo.</p>
                            </div>
                        </div>

                        <!-- Tarjeta: OperaciÃ³n del Establecimiento -->
                        <div class="bg-[#F8FAFC]/50 border border-slate-100 p-5 rounded-2xl space-y-4">
                            <h3 class="text-xs font-bold uppercase tracking-wider text-slate-600 border-b border-slate-200/50 pb-2 flex items-center gap-1.5">ðŸ›µ OperaciÃ³n y Delivery</h3>
                            
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 mb-1">Costo de Delivery ($)</label>
                                    <input type="number" step="0.01" name="costo_delivery" value="<?= number_format(floatval($config['costo_delivery'] ?? 0.00), 2, '.', '') ?>" required
                                           class="w-full px-4 py-2.5 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-[#00CFBD] focus:border-transparent bg-white transition-all">
                                    <p class="text-[10px] text-slate-400 mt-1">0 = EnvÃ­o gratis o a acordar.</p>
                                </div>
                                <div>
                                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 mb-1">Horario de AtenciÃ³n</label>
                                    <input type="text" name="horario" value="<?= h($config['horario'] ?? '') ?>" placeholder="Ej: Lun a SÃ¡b: 8am - 6pm"
                                           class="w-full px-4 py-2.5 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-[#00CFBD] focus:border-transparent bg-white transition-all">
                                </div>
                            </div>
                            <div>
                                <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 mb-1">DirecciÃ³n del Local</label>
                                <input type="text" name="direccion" value="<?= h($config['direccion'] ?? '') ?>" placeholder="Ej: Av. Principal con Calle 4, Local 2"
                                       class="w-full px-4 py-2.5 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-[#00CFBD] focus:border-transparent bg-white transition-all">
                            </div>
                        </div>
                    </div>

                    <!-- COLUMNA DERECHA: Moneda, Cambio y Seguridad -->
                    <div class="space-y-6">
                        
                        <!-- Tarjeta: Moneda y Cambio -->
                        <div class="bg-[#F8FAFC]/50 border border-slate-100 p-5 rounded-2xl space-y-4">
                            <h3 class="text-xs font-bold uppercase tracking-wider text-slate-600 border-b border-slate-200/50 pb-2 flex items-center gap-1.5">ðŸ’µ Moneda y Tasa de Cambio</h3>
                            
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 mb-1">Tasa</label>
                                    <select name="tasa_tipo" id="tasa_tipo" onchange="handleTasaTipoChange()" required
                                            class="w-full px-4 py-2.5 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-[#00CFBD] focus:border-transparent bg-white transition-all">
                                        <option value="manual" <?= ($config['tasa_tipo'] ?? 'manual') === 'manual' ? 'selected' : '' ?>>Manual / Fija</option>
                                        <option value="bcv" <?= ($config['tasa_tipo'] ?? '') === 'bcv' ? 'selected' : '' ?>>AutomÃ¡tico: Banco Central de Venezuela (BCV)</option>
                                        <option value="trm" <?= ($config['tasa_tipo'] ?? '') === 'trm' ? 'selected' : '' ?>>AutomÃ¡tico: TRM Colombia (Pesos)</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 mb-1">Valor de la Tasa ($1 USD = X)</label>
                                    <div class="relative">
                                        <input type="number" step="0.01" name="tasa_dolar" id="tasa_dolar_input" value="<?= number_format(floatval($config['tasa_dolar'] ?? 1.00), 2, '.', '') ?>" required
                                               class="w-full px-4 py-2.5 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-[#00CFBD] focus:border-transparent bg-white transition-all">
                                        <div id="tasa_loading" class="absolute right-3 top-3.5 hidden">
                                            <svg class="animate-spin h-4 w-4 text-[#00CFBD]" fill="none" viewBox="0 0 24 24">
                                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                            </svg>
                                        </div>
                                    </div>
                                    <p id="tasa_note" class="text-[10px] text-slate-400 mt-1">Introduce el valor de cambio manualmente.</p>
                                </div>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 pt-2 border-t border-slate-100">
                                <div>
                                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 mb-1">SÃ­mbolo Moneda Local</label>
                                    <input type="text" name="moneda_simbolo" value="<?= h($config['moneda_simbolo'] ?? '$') ?>" placeholder="Ej: Bs. o COP$" required
                                           class="w-full px-4 py-2.5 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-[#00CFBD] focus:border-transparent bg-white transition-all">
                                </div>
                                <div>
                                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 mb-1">Nombre Moneda Local</label>
                                    <input type="text" name="moneda_nombre" value="<?= h($config['moneda_nombre'] ?? 'USD') ?>" placeholder="Ej: VES, COP, MXN" required
                                           class="w-full px-4 py-2.5 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-[#00CFBD] focus:border-transparent bg-white transition-all">
                                </div>
                            </div>
                        </div>

                        <!-- Tarjeta: Credenciales de Acceso -->
                        <div class="bg-[#F8FAFC]/50 border border-slate-100 p-5 rounded-2xl space-y-4">
                            <h3 class="text-xs font-bold uppercase tracking-wider text-slate-600 border-b border-slate-200/50 pb-2 flex items-center gap-1.5">ðŸ” Seguridad</h3>
                            
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 mb-1">Usuario Administrativo</label>
                                    <input type="text" name="admin_user" value="<?= h($dbAdminUser) ?>" required placeholder="ej: admin"
                                           class="w-full px-4 py-2.5 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-[#00CFBD] focus:border-transparent bg-white transition-all">
                                </div>
                                <div>
                                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 mb-1">Nueva ContraseÃ±a</label>
                                    <input type="password" name="admin_password" placeholder="Escribe para cambiar la clave"
                                           class="w-full px-4 py-2.5 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-[#00CFBD] focus:border-transparent bg-white transition-all">
                                    <p class="text-[10px] text-slate-400 mt-1">Dejar vacÃ­o para conservar la contraseÃ±a actual.</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="flex justify-end pt-4 border-t border-slate-100">
                    <button type="submit" class="w-full sm:w-auto px-8 py-3 bg-slate-800 hover:bg-slate-700 hover:opacity-90 text-white font-bold text-sm rounded-xl shadow-md transition-all">
                        ðŸ’¾ Guardar Ajustes
                    </button>
                </div>
            </form>
        </section>

        <!-- ================= TAB: CATEGORÃAS ================= -->
        <section id="categories" class="tab-content space-y-6">
            <div class="bg-white p-6 rounded-2xl border border-slate-100 shadow-sm space-y-4">
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 border-b border-slate-50 pb-4">
                    <div>
                        <h2 class="text-xl font-extrabold tracking-tight">CategorÃ­as Registradas</h2>
                        <p class="text-xs text-slate-400">Define el orden en el que se verÃ¡n las secciones en tu catÃ¡logo.</p>
                    </div>
                    <div>
                        <button onclick="openCategoryModal()" class="px-5 py-2.5 bg-slate-800 hover:bg-slate-700 hover:opacity-90 text-white font-bold text-xs rounded-xl shadow-md transition-all flex items-center gap-1.5 whitespace-nowrap">
                            âž• Nueva CategorÃ­a
                        </button>
                    </div>
                </div>

                <?php if (empty($categorias)): ?>
                    <p class="text-sm text-slate-400 py-10 text-center">No has registrado categorÃ­as aÃºn. Haz clic en "Nueva CategorÃ­a" para comenzar.</p>
                <?php else: ?>
                    <!-- Vista de Tarjetas para MÃ³vil -->
                    <div class="space-y-3 lg:hidden">
                        <?php foreach ($categorias as $cat): ?>
                            <div class="bg-white border border-slate-100 rounded-xl p-4 flex items-center justify-between shadow-sm">
                                <div>
                                    <span class="text-xs font-bold text-[#00CFBD] bg-cyan-50 px-2.5 py-1 rounded-lg">Orden #<?= h($cat['orden_visual']) ?></span>
                                    <h4 class="font-bold text-slate-800 mt-2"><?= h($cat['nombre_categoria']) ?></h4>
                                </div>
                                <div class="flex items-center gap-2">
                                    <button type="button" onclick="openCategoryModalForEdit(<?= h($cat['id']) ?>, '<?= h(addslashes($cat['nombre_categoria'])) ?>', <?= h($cat['orden_visual']) ?>)" class="text-xs font-bold text-[#00CFBD] bg-cyan-50 hover:bg-cyan-100 px-3 py-2 rounded-xl transition-colors">
                                        Editar
                                    </button>
                                    <button type="button" onclick="confirmDelete('delete_category', 'categoria_id', <?= h($cat['id']) ?>, 'Â¿Seguro que deseas eliminar esta categorÃ­a? Se eliminarÃ¡n todos los productos asociados.')" class="text-xs font-bold text-red-500 bg-red-50 hover:bg-red-100 px-3.5 py-2 rounded-xl transition-colors">
                                        Eliminar
                                    </button>
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
                                            <div class="flex justify-end space-x-3">
                                                <button type="button" onclick="openCategoryModalForEdit(<?= h($cat['id']) ?>, '<?= h(addslashes($cat['nombre_categoria'])) ?>', <?= h($cat['orden_visual']) ?>)" class="text-xs font-bold text-[#00CFBD] hover:text-[#00B5A5] transition-colors">
                                                    Editar
                                                </button>
                                                <span class="text-slate-200">|</span>
                                                <button type="button" onclick="confirmDelete('delete_category', 'categoria_id', <?= h($cat['id']) ?>, 'Â¿Seguro que deseas eliminar esta categorÃ­a? Se eliminarÃ¡n todos los productos asociados.')" class="text-xs font-bold text-red-500 hover:text-red-700 transition-colors">
                                                    Eliminar
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </section>

        <!-- ================= TAB: PRODUCTOS ================= -->
        <section id="products" class="tab-content space-y-6">
            <div class="bg-white p-6 rounded-2xl border border-slate-100 shadow-sm space-y-4">
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 border-b border-slate-50 pb-4">
                    <div>
                        <h2 class="text-xl font-extrabold tracking-tight">CatÃ¡logo de Productos</h2>
                        <p class="text-xs text-slate-400">Agrega, edita o cambia la disponibilidad de tus artÃ­culos.</p>
                    </div>
                    <div>
                        <button onclick="openProductModal()" class="px-5 py-2.5 bg-slate-800 hover:bg-slate-700 hover:opacity-90 text-white font-bold text-xs rounded-xl shadow-md transition-all flex items-center gap-1.5 whitespace-nowrap">
                            âž• Nuevo Producto
                        </button>
                    </div>
                </div>

                <!-- Barra de bÃºsqueda y filtros -->
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 bg-slate-50/50 p-3 rounded-xl border border-slate-100">
                    <div class="relative">
                        <span class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-xs">ðŸ”</span>
                        <input type="text" id="admin-search-input" onkeyup="filterAdminProducts()" placeholder="Buscar producto..."
                               class="w-full pl-8 pr-3 py-2.5 border border-slate-200 rounded-xl text-xs bg-white focus:outline-none focus:ring-1 focus:ring-[#00CFBD] transition-all">
                    </div>
                    <div>
                        <select id="admin-filter-category" onchange="filterAdminProducts()"
                                class="w-full px-3 py-2.5 border border-slate-200 rounded-xl text-xs bg-white focus:outline-none focus:ring-1 focus:ring-[#00CFBD] transition-all">
                            <option value="">Todas las CategorÃ­as</option>
                            <?php foreach ($categorias as $cat): ?>
                                <option value="<?= h($cat['id']) ?>"><?= h($cat['nombre_categoria']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <select id="admin-filter-available" onchange="filterAdminProducts()"
                                class="w-full px-3 py-2.5 border border-slate-200 rounded-xl text-xs bg-white focus:outline-none focus:ring-1 focus:ring-[#00CFBD] transition-all">
                            <option value="">Todos los Estados</option>
                            <option value="1">ðŸŸ¢ Disponibles</option>
                            <option value="0">ðŸ”´ Agotados / Inactivos</option>
                        </select>
                    </div>
                </div>

                <?php if (empty($productos)): ?>
                    <p class="text-sm text-slate-400 py-10 text-center">No has registrado productos aÃºn. Haz clic en "Nuevo Producto" para comenzar.</p>
                <?php else: ?>
                    <!-- Vista de Tarjetas para MÃ³vil -->
                    <div id="admin-product-cards-container" class="space-y-3 lg:hidden">
                        <?php foreach ($productos as $prod): ?>
                            <div class="admin-product-card bg-white border border-slate-100 rounded-xl p-4 space-y-3 shadow-sm"
                                 data-name="<?= h(strtolower($prod['nombre'])) ?>"
                                 data-desc="<?= h(strtolower($prod['descripcion'] ?? '')) ?>"
                                 data-category="<?= h($prod['categoria_id']) ?>"
                                 data-available="<?= $prod['disponible'] ? '1' : '0' ?>">
                                <div class="flex items-center space-x-3">
                                    <?php if (!empty($prod['imagen_url'])): ?>
                                        <img src="<?= h($prod['imagen_url']) ?>" alt="<?= h($prod['nombre']) ?>" class="w-12 h-12 object-cover rounded-lg bg-slate-100">
                                    <?php else: ?>
                                        <div class="w-12 h-12 rounded-lg bg-slate-100 flex items-center justify-center text-slate-400 text-lg font-bold">
                                            <?php
                                            $tipo = $config['tipo_negocio'] ?? 'gastronomia';
                                            if ($tipo === 'boutique') echo 'ðŸ‘•';
                                            elseif ($tipo === 'ferreteria_repuestos') echo 'ðŸ”§';
                                            elseif ($tipo === 'belleza_estetica') echo 'âœ‚ï¸';
                                            elseif ($tipo === 'otros') echo 'ðŸ›ï¸';
                                            else echo 'ðŸ”';
                                            ?>
                                        </div>
                                    <?php endif; ?>
                                    <div class="flex-1 min-w-0">
                                        <h4 class="font-bold text-slate-800 truncate"><?= h($prod['nombre']) ?></h4>
                                        <p class="text-xs text-slate-500 font-semibold mt-0.5"><?= h($categoriasMap[$prod['categoria_id']] ?? 'Sin CategorÃ­a') ?></p>
                                    </div>
                                    <div class="text-right">
                                        <span class="font-extrabold text-slate-850 text-sm">$<?= number_format($prod['precio'], 2) ?></span>
                                    </div>
                                </div>
                                
                                <?php if (!empty($prod['descripcion'])): ?>
                                    <p class="text-[11px] text-slate-500 leading-relaxed bg-slate-50 p-2 rounded-lg"><?= h($prod['descripcion']) ?></p>
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
                                        <button type="button" onclick="confirmDelete('delete_product', 'producto_id', <?= h($prod['id']) ?>, 'Â¿Seguro que deseas eliminar este producto?')" class="text-xs font-bold text-red-500 bg-red-50 hover:bg-red-100 px-3.5 py-2 rounded-xl transition-colors">
                                            Eliminar
                                        </button>
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
                                    <th class="py-3 px-2">CategorÃ­a</th>
                                    <th class="py-3 px-2">Precio</th>
                                    <th class="py-3 px-2 text-center">Disponible</th>
                                    <th class="py-3 px-2 text-right">Acciones</th>
                                </tr>
                            </thead>
                            <tbody id="admin-product-rows-container">
                                <?php foreach ($productos as $prod): ?>
                                    <tr class="admin-product-row border-b border-slate-50 hover:bg-slate-50 transition-colors"
                                        data-name="<?= h(strtolower($prod['nombre'])) ?>"
                                        data-desc="<?= h(strtolower($prod['descripcion'] ?? '')) ?>"
                                        data-category="<?= h($prod['categoria_id']) ?>"
                                        data-available="<?= $prod['disponible'] ? '1' : '0' ?>">
                                        <td class="py-3 px-2">
                                            <div class="flex items-center space-x-3">
                                                <?php if (!empty($prod['imagen_url'])): ?>
                                                    <img src="<?= h($prod['imagen_url']) ?>" alt="<?= h($prod['nombre']) ?>" class="w-10 h-10 object-cover rounded-lg bg-slate-100">
                                                <?php else: ?>
                                                    <div class="w-10 h-10 rounded-lg bg-slate-100 flex items-center justify-center text-slate-400 text-xs font-bold">
                                                        <?php
                                                        $tipo = $config['tipo_negocio'] ?? 'gastronomia';
                                                        if ($tipo === 'boutique') echo 'ðŸ‘•';
                                                        elseif ($tipo === 'ferreteria_repuestos') echo 'ðŸ”§';
                                                        elseif ($tipo === 'belleza_estetica') echo 'âœ‚ï¸';
                                                        elseif ($tipo === 'otros') echo 'ðŸ›ï¸';
                                                        else echo 'ðŸ”';
                                                        ?>
                                                    </div>
                                                <?php endif; ?>
                                                <div>
                                                    <h4 class="font-bold text-slate-800"><?= h($prod['nombre']) ?></h4>
                                                    <p class="text-[10px] text-slate-400 line-clamp-1 max-w-[200px]"><?= h($prod['descripcion']) ?></p>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="py-3 px-2 text-xs font-semibold text-slate-500">
                                            <?= h($categoriasMap[$prod['categoria_id']] ?? 'Sin CategorÃ­a') ?>
                                        </td>
                                        <td class="py-3 px-2 font-extrabold text-slate-800">$<?= number_format($prod['precio'], 2) ?></td>
                                        <td class="py-3 px-2 text-center">
                                            <!-- Formulario rÃ¡pido disponible/agotado -->
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
                                                <span class="text-slate-200">|</span>
                                                <button type="button" onclick="confirmDelete('delete_product', 'producto_id', <?= h($prod['id']) ?>, 'Â¿Seguro que deseas eliminar este producto?')" class="text-xs font-bold text-red-500 hover:text-red-700 transition-colors">
                                                    Eliminar
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </section>
    </main>

    <footer class="bg-white border-t border-slate-100 py-6 text-center text-xs text-slate-400 font-medium mt-auto flex flex-col sm:flex-row items-center justify-center gap-2 sm:gap-4">
        <span>&copy; 2026 <?= h(!empty($config['nombre']) && $config['nombre'] !== 'Mi Tienda' ? $config['nombre'] : 'PronttoGo') ?>. Desarrollado por Montero Studio.</span>
        <span class="hidden sm:inline text-slate-200">|</span>
        <a href="/legal" class="text-slate-400 hover:text-[#00CFBD] transition-colors">TÃ©rminos y Privacidad</a>
    </footer>

    <!-- Ventana Modal: Producto -->
    <div id="modal-product" class="fixed inset-0 bg-slate-900/40 backdrop-blur-sm z-50 flex items-center justify-center p-4 transition-all duration-300 opacity-0 pointer-events-none">
        <div class="modal-content bg-white rounded-3xl border border-slate-100 shadow-2xl w-full max-w-lg overflow-hidden transform scale-95 transition-all duration-300 flex flex-col max-h-[85vh] sm:max-h-[90vh]">
            <!-- Header -->
            <div class="bg-slate-800 hover:bg-slate-700 p-5 text-white flex justify-between items-center shrink-0">
                <h3 id="product-form-title" class="font-extrabold text-base">Nuevo Producto</h3>
                <button type="button" onclick="closeProductModal()" class="w-8 h-8 rounded-full bg-white/20 hover:bg-white/30 flex items-center justify-center font-bold text-white transition-all">âœ•</button>
            </div>
            <!-- Form -->
            <form id="form-product" action="admin.php" method="POST" class="p-6 space-y-4 overflow-y-auto">
                <?= csrf_input() ?>
                <input type="hidden" name="action" value="save_product">
                <input type="hidden" name="producto_id" id="prod-id" value="">
                
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 mb-1">Nombre</label>
                    <input type="text" name="nombre" id="prod-nombre" required placeholder="ej: Alternador Toyota, Hamburguesa Especial" 
                           class="w-full px-4 py-2.5 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-[#00CFBD] focus:border-transparent transition-all">
                </div>

                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 mb-1">CategorÃ­a</label>
                    <select name="categoria_id" id="prod-cat" required 
                            class="w-full px-4 py-2.5 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-[#00CFBD] focus:border-transparent bg-white transition-all">
                        <option value="">Selecciona una categorÃ­a</option>
                        <?php foreach ($categorias as $cat): ?>
                            <option value="<?= h($cat['id']) ?>"><?= h($cat['nombre_categoria']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 mb-1">DescripciÃ³n</label>
                    <textarea name="descripcion" id="prod-desc" rows="2" placeholder="Detalles, especificaciones, ingredientes..." 
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
                    <label class="flex items-center space-x-2 text-sm font-semibold cursor-pointer select-none">
                        <input type="checkbox" name="disponible" id="prod-disp" value="1" checked class="rounded text-[#00CFBD] focus:ring-[#00CFBD] w-4 h-4">
                        <span>Disponible para la venta</span>
                    </label>
                </div>

                <div class="flex gap-2 pt-2 border-t border-slate-50">
                    <button type="button" onclick="closeProductModal()" class="flex-1 py-2.5 border border-slate-200 text-slate-600 hover:bg-slate-50 font-bold text-xs rounded-xl transition-all">
                        Cancelar
                    </button>
                    <button type="submit" class="flex-1 py-2.5 bg-slate-800 hover:bg-slate-700 hover:opacity-90 text-white font-bold text-xs rounded-xl shadow-md transition-all">
                        Guardar Producto
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Ventana Modal: CategorÃ­a -->
    <div id="modal-category" class="fixed inset-0 bg-slate-900/40 backdrop-blur-sm z-50 flex items-center justify-center p-4 transition-all duration-300 opacity-0 pointer-events-none">
        <div class="modal-content bg-white rounded-3xl border border-slate-100 shadow-2xl w-full max-w-md overflow-hidden transform scale-95 transition-all duration-300 flex flex-col max-h-[85vh]">
            <!-- Header -->
            <div class="bg-slate-800 hover:bg-slate-700 p-5 text-white flex justify-between items-center shrink-0">
                <h3 id="category-form-title" class="font-extrabold text-base">Nueva CategorÃ­a</h3>
                <button type="button" onclick="closeCategoryModal()" class="w-8 h-8 rounded-full bg-white/20 hover:bg-white/30 flex items-center justify-center font-bold text-white transition-all">âœ•</button>
            </div>
            <!-- Form -->
            <form id="form-category" action="admin.php" method="POST" class="p-6 space-y-4 overflow-y-auto">
                <?= csrf_input() ?>
                <input type="hidden" name="action" value="save_category">
                <input type="hidden" name="categoria_id" id="cat-id" value="">
                
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 mb-1">Nombre de la CategorÃ­a</label>
                    <input type="text" name="nombre_categoria" id="cat-nombre" required placeholder="ej: Repuestos, Joyas, Tortas, Helados" 
                           class="w-full px-4 py-2.5 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-[#00CFBD] focus:border-transparent transition-all">
                </div>
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 mb-1">Orden de VisualizaciÃ³n</label>
                    <input type="number" name="orden_visual" id="cat-orden" value="<?= $next_cat_order ?>" required 
                           class="w-full px-4 py-2.5 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-[#00CFBD] focus:border-transparent transition-all">
                </div>

                <div class="flex gap-2 pt-2 border-t border-slate-50">
                    <button type="button" onclick="closeCategoryModal()" class="flex-1 py-2.5 border border-slate-200 text-slate-600 hover:bg-slate-50 font-bold text-xs rounded-xl transition-all">
                        Cancelar
                    </button>
                    <button type="submit" class="flex-1 py-2.5 bg-slate-800 hover:bg-slate-700 hover:opacity-90 text-white font-bold text-xs rounded-xl shadow-md transition-all">
                        Guardar CategorÃ­a
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Ventana Modal: ConfirmaciÃ³n de EliminaciÃ³n -->
    <div id="confirm-modal" class="fixed inset-0 bg-slate-900/40 backdrop-blur-sm z-50 flex items-center justify-center p-4 transition-all duration-300 opacity-0 pointer-events-none">
        <div class="modal-content bg-white rounded-3xl border border-slate-100 shadow-xl w-full max-w-sm overflow-hidden transform scale-95 transition-all duration-300 p-6 space-y-4">
            <div class="text-center space-y-2">
                <div class="w-12 h-12 bg-red-50 text-red-500 rounded-full flex items-center justify-center mx-auto text-xl font-bold">âš ï¸</div>
                <h3 class="font-extrabold text-base text-slate-800">Â¿EstÃ¡s seguro?</h3>
                <p id="confirm-modal-text" class="text-xs text-slate-500 leading-relaxed">Esta acciÃ³n es irreversible y podrÃ­a afectar datos vinculados.</p>
            </div>
            <div class="flex gap-2">
                <button type="button" onclick="closeConfirmModal()" class="flex-1 py-2.5 border border-slate-200 text-slate-600 hover:bg-slate-50 font-bold text-xs rounded-xl transition-all">
                    Cancelar
                </button>
                <button type="button" onclick="executePendingDelete()" class="flex-1 py-2.5 bg-red-600 hover:bg-red-700 text-white font-bold text-xs rounded-xl shadow-md transition-all">
                    Eliminar
                </button>
            </div>
        </div>
    </div>

    <script>
        // Control de Tabs
        function switchTab(hash) {
            window.location.hash = hash;
            
            document.querySelectorAll('.tab-content').forEach(tab => {
                tab.classList.remove('active');
            });
            
            document.querySelectorAll('.tab-btn').forEach(btn => {
                btn.className = "tab-btn flex-1 flex flex-col md:flex-row items-center justify-center gap-1 py-2 sm:py-2.5 text-center font-bold text-[10px] sm:text-xs md:text-sm rounded-xl text-slate-400 hover:text-slate-600 transition-all";
            });
            
            const targetTab = document.querySelector(hash);
            if (targetTab) {
                targetTab.classList.add('active');
            }
            
            const btnId = 'tab-btn-' + hash.replace('#', '');
            const targetBtn = document.getElementById(btnId);
            if (targetBtn) {
                targetBtn.className = "tab-btn flex-1 flex flex-col md:flex-row items-center justify-center gap-1 py-2 sm:py-2.5 text-center font-bold text-[10px] sm:text-xs md:text-sm rounded-xl bg-slate-800 hover:bg-slate-700 text-white shadow-md transition-all";
            }
        }

        // Modales de Productos
        function openProductModal() {
            resetProductForm();
            const modal = document.getElementById('modal-product');
            modal.classList.remove('opacity-0', 'pointer-events-none');
            modal.querySelector('.modal-content').classList.remove('scale-95');
            modal.querySelector('.modal-content').classList.add('scale-100');
        }

        // Cerrar modal producto
        function closeProductModal() {
            const modal = document.getElementById('modal-product');
            modal.classList.add('opacity-0', 'pointer-events-none');
            modal.querySelector('.modal-content').classList.add('scale-95');
            modal.querySelector('.modal-content').classList.remove('scale-100');
        }

        // Cargar producto en formulario y abrir modal
        function loadProductForEdit(product) {
            document.getElementById('product-form-title').textContent = 'Editar Producto';
            document.getElementById('prod-id').value = product.id;
            document.getElementById('prod-nombre').value = product.nombre;
            document.getElementById('prod-cat').value = product.categoria_id;
            document.getElementById('prod-desc').value = product.descripcion;
            document.getElementById('prod-precio').value = parseFloat(product.precio).toFixed(2);
            document.getElementById('prod-img').value = product.imagen_url;
            document.getElementById('prod-disp').checked = product.disponible === 1;
            
            const modal = document.getElementById('modal-product');
            modal.classList.remove('opacity-0', 'pointer-events-none');
            modal.querySelector('.modal-content').classList.remove('scale-95');
            modal.querySelector('.modal-content').classList.add('scale-100');
        }

        function resetProductForm() {
            document.getElementById('product-form-title').textContent = 'Nuevo Producto';
            document.getElementById('prod-id').value = '';
            document.getElementById('form-product').reset();
        }

        // Modales de CategorÃ­as
        function openCategoryModal() {
            resetCategoryForm();
            const modal = document.getElementById('modal-category');
            modal.classList.remove('opacity-0', 'pointer-events-none');
            modal.querySelector('.modal-content').classList.remove('scale-95');
            modal.querySelector('.modal-content').classList.add('scale-100');
        }

        function openCategoryModalForEdit(id, name, order) {
            document.getElementById('category-form-title').textContent = 'Editar CategorÃ­a';
            document.getElementById('cat-id').value = id;
            document.getElementById('cat-nombre').value = name;
            document.getElementById('cat-orden').value = order;
            
            const modal = document.getElementById('modal-category');
            modal.classList.remove('opacity-0', 'pointer-events-none');
            modal.querySelector('.modal-content').classList.remove('scale-95');
            modal.querySelector('.modal-content').classList.add('scale-100');
        }

        function closeCategoryModal() {
            const modal = document.getElementById('modal-category');
            modal.classList.add('opacity-0', 'pointer-events-none');
            modal.querySelector('.modal-content').classList.add('scale-95');
            modal.querySelector('.modal-content').classList.remove('scale-100');
        }

        function resetCategoryForm() {
            document.getElementById('category-form-title').textContent = 'Nueva CategorÃ­a';
            document.getElementById('cat-id').value = '';
            document.getElementById('cat-nombre').value = '';
            document.getElementById('cat-orden').value = '<?= $next_cat_order ?>';
        }

        // Filtro y BÃºsqueda Avanzada de Productos en Admin
        function filterAdminProducts() {
            const query = document.getElementById('admin-search-input').value.toLowerCase().trim();
            const catFilter = document.getElementById('admin-filter-category').value;
            const availFilter = document.getElementById('admin-filter-available').value;

            // Filtrar Filas de la Tabla (Escritorio)
            document.querySelectorAll('.admin-product-row').forEach(row => {
                const name = row.getAttribute('data-name') || '';
                const desc = row.getAttribute('data-desc') || '';
                const cat = row.getAttribute('data-category') || '';
                const avail = row.getAttribute('data-available') || '';

                const matchesQuery = name.includes(query) || desc.includes(query);
                const matchesCat = catFilter === "" || cat === catFilter;
                const matchesAvail = availFilter === "" || avail === availFilter;

                if (matchesQuery && matchesCat && matchesAvail) {
                    row.classList.remove('hidden');
                } else {
                    row.classList.add('hidden');
                }
            });

            // Filtrar Tarjetas de MÃ³vil
            document.querySelectorAll('.admin-product-card').forEach(card => {
                const name = card.getAttribute('data-name') || '';
                const desc = card.getAttribute('data-desc') || '';
                const cat = card.getAttribute('data-category') || '';
                const avail = card.getAttribute('data-available') || '';

                const matchesQuery = name.includes(query) || desc.includes(query);
                const matchesCat = catFilter === "" || cat === catFilter;
                const matchesAvail = availFilter === "" || avail === availFilter;

                if (matchesQuery && matchesCat && matchesAvail) {
                    card.classList.remove('hidden');
                } else {
                    card.classList.add('hidden');
                }
            });
        }

        // Confirmar EliminaciÃ³n (Modal Personalizado)
        let pendingDeleteForm = null;
        function confirmDelete(actionName, idFieldName, idValue, message) {
            const dialog = document.getElementById('confirm-modal');
            const text = document.getElementById('confirm-modal-text');
            text.textContent = message;
            
            pendingDeleteForm = document.createElement('form');
            pendingDeleteForm.method = 'POST';
            pendingDeleteForm.action = 'admin.php';
            
            const csrf = document.querySelector('input[name="csrf_token"]').cloneNode(true);
            pendingDeleteForm.appendChild(csrf);
            
            const actionInput = document.createElement('input');
            actionInput.type = 'hidden';
            actionInput.name = 'action';
            actionInput.value = actionName;
            pendingDeleteForm.appendChild(actionInput);
            
            const idInput = document.createElement('input');
            idInput.type = 'hidden';
            idInput.name = idFieldName;
            idInput.value = idValue;
            pendingDeleteForm.appendChild(idInput);
            
            document.body.appendChild(pendingDeleteForm);
            
            dialog.classList.remove('opacity-0', 'pointer-events-none');
            dialog.querySelector('.modal-content').classList.remove('scale-95');
            dialog.querySelector('.modal-content').classList.add('scale-100');
        }
        
        function executePendingDelete() {
            if (pendingDeleteForm) {
                pendingDeleteForm.submit();
            }
        }
        
        function closeConfirmModal() {
            const dialog = document.getElementById('confirm-modal');
            dialog.classList.add('opacity-0', 'pointer-events-none');
            dialog.querySelector('.modal-content').classList.add('scale-95');
            dialog.querySelector('.modal-content').classList.remove('scale-100');
            if (pendingDeleteForm) {
                pendingDeleteForm.remove();
                pendingDeleteForm = null;
            }
        }

        // Copiar Enlace al Portapapeles
        function copyToClipboard(text) {
            navigator.clipboard.writeText(text).then(() => {
                alert('Â¡Enlace copiado al portapapeles!');
            }).catch(err => {
                console.error('Error al copiar enlace:', err);
            });
        }

        // Toggle GuÃ­a de MigraciÃ³n
        function toggleMigrationGuide() {
            const content = document.getElementById('migration-guide-content');
            const arrow = document.getElementById('migration-arrow');
            if (content.classList.contains('hidden')) {
                content.classList.remove('hidden');
                arrow.textContent = 'â–²';
            } else {
                content.classList.add('hidden');
                arrow.textContent = 'â–¼';
            }
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
                input.classList.remove('bg-slate-50', 'text-slate-400');
                note.textContent = 'Introduce el valor de cambio manualmente.';
            } else {
                input.removeAttribute('readonly');
                input.classList.remove('bg-slate-50', 'text-slate-400');
                note.textContent = 'Tasa oficial. Puedes ajustarla manualmente o dejar la sugerida.';
                if (!isInitial) {
                    fetchRateFromClient(type);
                }
            }
        }

        // InicializaciÃ³n
        window.addEventListener('DOMContentLoaded', () => {
            let defaultTab = '<?= $active_tab ?? '#dashboard' ?>';
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

