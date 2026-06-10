<?php
// includes/header.php
$es_admin = isset($es_admin) ? $es_admin : false;
?>
<!DOCTYPE html>
<html lang="es" class="<?php echo !$es_admin ? 'scroll-smooth overflow-x-hidden' : ''; ?> h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>
        <?php 
        if ($es_admin) {
            echo 'Panel de Administración | PronttoGo';
        } else {
            echo h(!empty($config['nombre']) && $config['nombre'] !== 'Mi Tienda' ? $config['nombre'] : 'PronttoGo');
        }
        ?>
    </title>

    <!-- Google Fonts: Plus Jakarta Sans -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
        <!-- Compiled Tailwind CSS -->
    <link rel="stylesheet" href="/assets/css/tailwind.css">
    
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    
    <!-- Custom style.css -->
    <link rel="stylesheet" href="/assets/css/style.css?v=<?= time() ?>">

    <!-- Favicons (Compatibles con Caché Búster) -->
    <link rel="icon" type="image/svg+xml" href="/assets/img/favicon.svg?v=2">
    <link rel="icon" type="image/png" sizes="32x32" href="/assets/img/favicon.svg?v=2">
    <link rel="shortcut icon" href="/assets/img/favicon.svg?v=2">
    <link rel="apple-touch-icon" href="/assets/img/favicon.svg?v=2">

    <!-- Estilos Dinámicos -->
    <?php
    if (!function_exists('adjust_brightness')) {
        function adjust_brightness($hex, $percent) {
            $hex = ltrim($hex, '#');
            if (strlen($hex) == 3) {
                $hex = $hex[0].$hex[0].$hex[1].$hex[1].$hex[2].$hex[2];
            }
            $r = hexdec(substr($hex, 0, 2));
            $g = hexdec(substr($hex, 2, 2));
            $b = hexdec(substr($hex, 4, 2));

            // Oscurecer multiplicando por (1 - percent)
            $r = max(0, min(255, intval($r * (1 - $percent))));
            $g = max(0, min(255, intval($g * (1 - $percent))));
            $b = max(0, min(255, intval($b * (1 - $percent))));

            return '#' . str_pad(dechex($r), 2, '0', STR_PAD_LEFT) . str_pad(dechex($g), 2, '0', STR_PAD_LEFT) . str_pad(dechex($b), 2, '0', STR_PAD_LEFT);
        }
    }

    $color_niche = [
        'gastronomia' => ['primary' => '#4F46E5', 'primary-hover' => '#4338CA', 'soft' => '#EEF2FF', 'border-soft' => '#C7D2FE', 'hero-bg-from' => '#EEF2FF', 'hero-bg-via' => '#D5DEFF', 'hero-bg-to' => '#E8EDFF', 'hero-glow' => 'rgba(79,70,229,0.05)'],
        'comida_rapida' => ['primary' => '#EF4444', 'primary-hover' => '#DC2626', 'soft' => '#FEF2F2', 'border-soft' => '#FEE2E2', 'hero-bg-from' => '#FFF1F1', 'hero-bg-via' => '#FDCFCF', 'hero-bg-to' => '#FFE4E4', 'hero-glow' => 'rgba(239,68,68,0.05)'],
        'minimarket' => ['primary' => '#10B981', 'primary-hover' => '#059669', 'soft' => '#ECFDF5', 'border-soft' => '#D1FAE5', 'hero-bg-from' => '#E6FDF0', 'hero-bg-via' => '#C2F5DB', 'hero-bg-to' => '#EBFDFF', 'hero-glow' => 'rgba(16,185,129,0.05)'],
        'farmacia' => ['primary' => '#06B6D4', 'primary-hover' => '#0891B2', 'soft' => '#ECFEFF', 'border-soft' => '#CFFAFE', 'hero-bg-from' => '#E6FCFF', 'hero-bg-via' => '#BFF6FD', 'hero-bg-to' => '#EBFDFF', 'hero-glow' => 'rgba(6,182,212,0.05)'],
        'boutique' => ['primary' => '#8B5CF6', 'primary-hover' => '#7C3AED', 'soft' => '#F5F3FF', 'border-soft' => '#DDD6FE', 'hero-bg-from' => '#F8F0FF', 'hero-bg-via' => '#EBD5FF', 'hero-bg-to' => '#FAF0FF', 'hero-glow' => 'rgba(139,92,246,0.05)'],
        'ferreteria_repuestos' => ['primary' => '#F59E0B', 'primary-hover' => '#D97706', 'soft' => '#FFFBEB', 'border-soft' => '#FDE68A', 'hero-bg-from' => '#FFFCEB', 'hero-bg-via' => '#FDE69A', 'hero-bg-to' => '#FFF5D1', 'hero-glow' => 'rgba(245,158,11,0.05)'],
        'belleza_estetica' => ['primary' => '#EC4899', 'primary-hover' => '#DB2777', 'soft' => '#FDF2F8', 'border-soft' => '#FBCFE8', 'hero-bg-from' => '#FDF0F7', 'hero-bg-via' => '#FBD5EC', 'hero-bg-to' => '#FDF0F7', 'hero-glow' => 'rgba(236,72,153,0.05)'],
        'otros' => ['primary' => '#4F46E5', 'primary-hover' => '#4338CA', 'soft' => '#EEF2FF', 'border-soft' => '#C7D2FE', 'hero-bg-from' => '#EEF2FF', 'hero-bg-via' => '#D5DEFF', 'hero-bg-to' => '#E8EDFF', 'hero-glow' => 'rgba(79,70,229,0.05)']
    ];
    $tipo_negocio = $config['tipo_negocio'] ?? 'gastronomia';
    $colors = $color_niche[$tipo_negocio] ?? $color_niche['gastronomia'];

    if (!empty($config['color_primario'])) {
        $custom_primary = $config['color_primario'];
        $colors['primary'] = $custom_primary;
        $colors['primary-hover'] = adjust_brightness($custom_primary, 0.08);
        $colors['soft'] = $custom_primary . '0D'; // ~5% opacidad
        $colors['border-soft'] = $custom_primary . '26'; // ~15% opacidad
        $colors['hero-bg-from'] = $custom_primary . '0F'; // ~6% opacidad
        $colors['hero-bg-via'] = $custom_primary . '20'; // ~12.5% opacidad
        $colors['hero-bg-to'] = $custom_primary . '0C';  // ~5% opacidad
        $colors['hero-glow'] = $custom_primary . '0D';
    }
    ?>
    <meta name="theme-color" content="<?= h($colors['primary']) ?>">
    <style type="text/css">
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
        
        /* Color de fondo hover dinámico nativo sin filtros lentos de repintado */
        .bg-primary:hover, .hover\:bg-primary:hover {
            background-color: var(--color-primary-hover) !important;
            transition: background-color 0.2s ease-in-out;
        }
    </style>

    <?php if ($es_admin): ?>
        <!-- Alpine.js (Solo Admin) -->
        <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.13.3/dist/cdn.min.js"></script>
    <?php else: ?>
        <script>
            localStorage.removeItem('theme'); document.documentElement.classList.remove('dark');
        </script>
    <?php endif; ?>
</head>
<body class="<?= $es_admin ? 'bg-slate-50 text-slate-900 h-full flex flex-col' : 'bg-[#F1F5F9] text-[#0F172A] min-h-screen flex flex-col overflow-x-hidden' ?>">

    <?php if (!$es_admin): ?>
        <?php if (isset($dbError) && $dbError): ?>
            <div class="bg-red-600 text-white text-xs font-bold px-4 py-3 text-center shadow-md relative z-50">
                <i class="bi bi-exclamation-triangle-fill text-red-500"></i> <strong>Error de Base de Datos (Local):</strong> <?= h($dbError) ?> | URL configurada: <code class="bg-red-700 px-1.5 py-0.5 rounded"><?= h(SUPABASE_URL) ?></code>
            </div>
        <?php endif; ?>
        
        <header class="h-16 bg-white border-b border-slate-100 sticky top-0 z-30 shadow-sm flex items-center">
            <div class="max-w-6xl w-full mx-auto px-4 sm:px-6 flex items-center justify-between">
                <!-- Logo -->
                <div class="flex items-center space-x-2.5 min-w-0">
                    <?= render_logo('header', $config) ?>
                    <?php if (!empty($config['logo_url'])): ?>
                        <span class="font-extrabold text-lg tracking-tight text-slate-800 truncate max-w-[140px] sm:max-w-none block"><?= h($config['nombre']) ?></span>
                    <?php endif; ?>
                </div>

                <!-- Navegación de Escritorio (Oculta en móvil) -->
                <?php if (isset($categorias) && !empty($categorias)): ?>
                <nav class="hidden md:flex flex-1 items-center justify-center px-8">
                    <div class="flex items-center gap-6 overflow-x-auto no-scrollbar">
                        <?php foreach ($categorias as $cat): ?>
                            <a href="#cat-<?= $cat['id'] ?>" class="category-btn text-sm font-bold text-slate-500 hover:text-primary transition-colors whitespace-nowrap" data-category-id="<?= $cat['id'] ?>">
                                <?= h($cat['nombre']) ?>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </nav>
                <?php endif; ?>

                <!-- Iconos de Acción -->
                <div class="flex items-center gap-2 shrink-0 ml-auto md:ml-0" style="display: flex; align-items: center; gap: 8px;">
                    <?php if (!$es_admin): ?>
                    <a href="/admin" class="hidden md:flex text-xs font-bold text-slate-600 hover:text-slate-900 border border-slate-200 hover:border-slate-350 rounded-xl px-4 py-2 transition-all bg-white shadow-sm items-center" style="display: flex; align-items: center; gap: 6px;">
                        <i class="bi bi-box-arrow-in-right text-sm"></i>
                        <span>Iniciar Sesión</span>
                    </a>
                    <?php endif; ?>
                    
                    <?php
                    $has_info = !empty($config['telefono_whatsapp']) || !empty($config['direccion']) || !empty($config['horario']);
                    if ($has_info): 
                    ?>
                        <button type="button" onclick="toggleStoreInfoModal(true)" class="w-9 h-9 rounded-xl bg-white border border-slate-200 hover:bg-slate-50 flex items-center justify-center text-slate-500 hover:text-slate-800 transition-all shadow-sm" title="Información de la Tienda">
                            <i class="bi bi-info-circle-fill text-lg"></i>
                        </button>
                    <?php endif; ?>

                    <!-- Menú Hamburguesa Móvil (Visible solo en móvil) -->
                    <?php if (isset($categorias) && !empty($categorias)): ?>
                    <button type="button" onclick="toggleMobileMenu(true)" class="md:hidden w-9 h-9 rounded-xl bg-slate-50 border border-slate-200 hover:bg-slate-100 flex items-center justify-center text-slate-600 transition-all shadow-sm ml-1">
                        <i class="bi bi-list text-xl"></i>
                    </button>
                    <?php endif; ?>
                </div>
            </div>
        </header>

        <!-- Sidebar Menú Móvil -->
        <?php if (isset($categorias) && !empty($categorias)): ?>
        <div id="mobile-menu-overlay" class="fixed inset-0 z-[60] hidden opacity-0 transition-opacity duration-300">
            <!-- Backdrop -->
            <div onclick="toggleMobileMenu(false)" class="absolute inset-0 bg-slate-950/40 backdrop-blur-sm"></div>
            
            <!-- Panel Lateral (Aparece desde la derecha) -->
            <div id="mobile-menu-panel" class="absolute top-0 right-0 h-full w-4/5 max-w-sm bg-white shadow-2xl transform translate-x-full transition-transform duration-300 flex flex-col">
                <div class="px-5 py-4 border-b border-slate-100 flex items-center justify-between bg-slate-50">
                    <span class="font-extrabold text-base text-slate-800 tracking-tight">Categorías</span>
                    <button onclick="toggleMobileMenu(false)" class="w-8 h-8 rounded-xl bg-white hover:bg-slate-100 border border-slate-200 flex items-center justify-center text-slate-500 transition-colors shadow-sm">
                        <i class="bi bi-x-lg text-xs"></i>
                    </button>
                </div>
                <div class="flex-1 overflow-y-auto p-4 space-y-2">
                    <?php foreach ($categorias as $cat): ?>
                        <a href="#cat-<?= $cat['id'] ?>" onclick="toggleMobileMenu(false)" class="mobile-category-link block px-4 py-3 rounded-xl font-bold text-sm text-slate-600 bg-slate-50 border border-transparent hover:bg-primary/5 hover:text-primary hover:border-primary/20 transition-all" data-category-id="<?= $cat['id'] ?>">
                            <?= h($cat['nombre']) ?>
                        </a>
                    <?php endforeach; ?>
                </div>
                <div class="p-5 border-t border-slate-100">
                    <a href="/admin" class="flex items-center justify-center w-full py-3 bg-slate-50 border border-slate-200 text-slate-600 rounded-xl font-bold text-xs hover:bg-slate-100 transition-all shadow-sm">
                        Acceso Administrador <i class="bi bi-box-arrow-in-right ml-2"></i>
                    </a>
                </div>
            </div>
        </div>

        <script>
            function toggleMobileMenu(show) {
                const overlay = document.getElementById('mobile-menu-overlay');
                const panel = document.getElementById('mobile-menu-panel');
                if (!overlay || !panel) return;

                if (show) {
                    overlay.classList.remove('hidden');
                    document.body.style.overflow = 'hidden';
                    setTimeout(() => {
                        overlay.classList.remove('opacity-0');
                        panel.classList.remove('translate-x-full');
                    }, 10);
                } else {
                    overlay.classList.add('opacity-0');
                    panel.classList.add('translate-x-full');
                    document.body.style.overflow = '';
                    setTimeout(() => {
                        overlay.classList.add('hidden');
                    }, 300);
                }
            }
        </script>
        <?php endif; ?>

        <!-- Modal de Información del Comercio (Accesible al Cliente) -->
        <div id="store-info-modal" class="fixed inset-0 z-50 hidden opacity-0 transition-opacity duration-300 flex items-center justify-center p-4">
            <!-- Backdrop -->
            <div onclick="toggleStoreInfoModal(false)" class="absolute inset-0 bg-slate-950/40 backdrop-blur-sm"></div>
            <!-- Modal Content (Professional Edge-to-Edge List) -->
            <?php
            $nombre = !empty($config['nombre']) && $config['nombre'] !== 'Mi Tienda' ? $config['nombre'] : 'PronttoGo';
            ?>
            <div class="bg-slate-50 rounded-none shadow-[0_25px_50px_-12px_rgba(0,0,0,0.25)] border border-slate-200 w-full max-w-sm relative z-10 flex flex-col overflow-hidden transform scale-95 transition-transform duration-300">
                
                <!-- Dark Header Structure -->
                <div class="w-full bg-slate-900 flex flex-col items-center text-center" style="padding: 24px 20px;">
                    <span class="text-[9px] font-extrabold text-slate-400 uppercase tracking-widest mb-1.5 leading-none">Información Comercial</span>
                    <h3 class="font-extrabold text-xl sm:text-2xl text-white tracking-tight leading-tight">
                        <?= h($nombre) ?>
                    </h3>
                </div>

                <!-- Structured List (Edge-to-Edge) -->
                <div class="flex flex-col w-full bg-white divide-y divide-slate-100">
                    <!-- WhatsApp -->
                    <?php if (!empty($config['telefono_whatsapp'])): ?>
                        <a href="https://wa.me/<?= h(preg_replace('/[^0-9]/', '', $config['telefono_whatsapp'])) ?>" target="_blank" class="flex items-center px-6 py-4 hover:bg-slate-50 transition-colors group" style="display: flex; align-items: center;">
                            <div class="w-10 h-10 bg-emerald-50 text-emerald-600 rounded-none flex items-center justify-center group-hover:bg-emerald-500 group-hover:text-white transition-colors duration-300" style="min-width: 40px; min-height: 40px; display: flex; align-items: center; justify-content: center;">
                                <i class="bi bi-whatsapp text-xl"></i>
                            </div>
                            <div class="ml-4 flex flex-col text-left">
                                <span class="text-[10px] font-extrabold text-slate-400 uppercase tracking-widest mb-0.5">WhatsApp</span>
                                <span class="text-sm font-bold text-slate-800 group-hover:text-emerald-600 transition-colors">
                                    <?= h($config['telefono_whatsapp']) ?>
                                </span>
                            </div>
                        </a>
                    <?php endif; ?>

                    <!-- Horario -->
                    <?php if (!empty($config['horario'])): ?>
                        <div class="flex items-center px-6 py-4 hover:bg-slate-50 transition-colors group" style="display: flex; align-items: center;">
                            <div class="w-10 h-10 bg-amber-50 text-amber-600 rounded-none flex items-center justify-center group-hover:bg-amber-500 group-hover:text-white transition-colors duration-300" style="min-width: 40px; min-height: 40px; display: flex; align-items: center; justify-content: center;">
                                <i class="bi bi-clock-fill text-xl"></i>
                            </div>
                            <div class="ml-4 flex flex-col text-left">
                                <span class="text-[10px] font-extrabold text-slate-400 uppercase tracking-widest mb-0.5">Horario Laboral</span>
                                <span class="text-xs font-semibold text-slate-700 leading-snug break-words">
                                    <?= h($config['horario']) ?>
                                </span>
                            </div>
                        </div>
                    <?php endif; ?>

                    <!-- Dirección -->
                    <?php if (!empty($config['direccion'])): ?>
                        <a href="https://maps.google.com/?q=<?= urlencode($config['direccion']) ?>" target="_blank" class="flex items-center px-6 py-4 hover:bg-slate-50 transition-colors group" style="display: flex; align-items: center;">
                            <div class="w-10 h-10 bg-indigo-50 text-indigo-600 rounded-none flex items-center justify-center group-hover:bg-indigo-500 group-hover:text-white transition-colors duration-300" style="min-width: 40px; min-height: 40px; display: flex; align-items: center; justify-content: center;">
                                <i class="bi bi-geo-alt-fill text-xl"></i>
                            </div>
                            <div class="ml-4 flex flex-col text-left">
                                <span class="text-[10px] font-extrabold text-slate-400 uppercase tracking-widest mb-0.5">Dirección</span>
                                <span class="text-xs font-semibold text-slate-700 leading-snug">
                                    <?= h($config['direccion']) ?>
                                </span>
                            </div>
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <script>
            function toggleStoreInfoModal(show) {
                const modal = document.getElementById('store-info-modal');
                if (!modal) return;
                const card = modal.querySelector('.transform');
                if (show) {
                    modal.classList.remove('hidden');
                    setTimeout(() => {
                        modal.classList.remove('opacity-0');
                        card.classList.remove('scale-95');
                        card.classList.add('scale-100');
                    }, 10);
                } else {
                    modal.classList.add('opacity-0');
                    card.classList.remove('scale-100');
                    card.classList.add('scale-95');
                    setTimeout(() => {
                        modal.classList.add('hidden');
                    }, 300);
                }
            }
        </script>
    <?php endif; ?>
