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
    <link rel="stylesheet" href="/assets/css/style.css">

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
                <div class="flex items-center space-x-2.5 min-w-0">
                    <?= render_logo('header', $config) ?>
                    <?php if (!empty($config['logo_url'])): ?>
                        <span class="font-extrabold text-lg tracking-tight text-slate-800 truncate max-w-[140px] sm:max-w-none block"><?= h($config['nombre']) ?></span>
                    <?php endif; ?>
                </div>
                <div class="flex items-center space-x-2 shrink-0">
                    <?php
                    $has_info = !empty($config['telefono_whatsapp']) || !empty($config['direccion']) || !empty($config['horario']);
                    if ($has_info): 
                    ?>
                        <button type="button" onclick="toggleStoreInfoModal(true)" class="w-9 h-9 rounded-xl bg-white border border-slate-200 hover:bg-slate-50 flex items-center justify-center text-slate-500 hover:text-slate-800 transition-all shadow-sm" title="Información de la Tienda">
                            <i class="bi bi-info-circle-fill text-lg"></i>
                        </button>
                    <?php endif; ?>
                    <a href="/admin" class="text-xs font-bold text-slate-600 hover:text-slate-900 border border-slate-200 hover:border-slate-350 rounded-xl px-4 py-2 transition-all bg-white shadow-sm">
                        Iniciar Sesión
                    </a>
                </div>
            </div>
        </header>

        <!-- Modal de Información del Comercio (Accesible al Cliente) -->
        <div id="store-info-modal" class="fixed inset-0 z-50 hidden opacity-0 transition-opacity duration-300 flex items-center justify-center p-4">
            <!-- Backdrop -->
            <div onclick="toggleStoreInfoModal(false)" class="absolute inset-0 bg-slate-950/40 backdrop-blur-sm"></div>
            
            <!-- Modal Content (Centered Card) -->
            <div class="bg-white rounded-3xl shadow-2xl border border-slate-100 max-w-sm w-full p-6 relative z-10 flex flex-col items-center text-center transform scale-95 transition-transform duration-300">
                <!-- Close Button -->
                <button onclick="toggleStoreInfoModal(false)" class="absolute top-4 right-4 w-8 h-8 rounded-xl bg-slate-100 hover:bg-slate-200 flex items-center justify-center text-slate-500 transition-colors">
                    <i class="bi bi-x-lg text-[10px]"></i>
                </button>
                <!-- Logo at the top -->
                <div class="mb-2 shrink-0">
                    <?php
                    $nombre = !empty($config['nombre']) && $config['nombre'] !== 'Mi Tienda' ? $config['nombre'] : 'PronttoGo';
                    $es_default_brand = (strtolower($nombre) === 'pronttogo' || $nombre === 'Mi Tienda');
                    $is_customized = !empty($config['logo_url']) || !$es_default_brand;
                    $powered_text = $is_customized ? 'PronttoGo' : 'Montero Studio';
                    ?>
                    <?php if (!empty($config['logo_url'])): ?>
                        <?= render_logo('login', $config) ?>
                    <?php elseif ($es_default_brand): ?>
                        <?= render_logo('login', $config) ?>
                    <?php else: ?>
                        <!-- Icono premium según el tipo de negocio en lugar del logo de PronttoGo -->
                        <div class="w-16 h-16 bg-primary/10 rounded-2xl flex items-center justify-center text-primary shadow-inner border border-primary/10 mb-4">
                            <?php
                            $biz_icon = 'bi-shop-window';
                            $tipo_negocio = $config['tipo_negocio'] ?? 'gastronomia';
                            if ($tipo_negocio === 'gastronomia') $biz_icon = 'bi-egg-fried';
                            elseif ($tipo_negocio === 'comida_rapida') $biz_icon = 'bi-fire';
                            elseif ($tipo_negocio === 'minimarket') $biz_icon = 'bi-cart-fill';
                            elseif ($tipo_negocio === 'farmacia') $biz_icon = 'bi-heart-pulse-fill';
                            elseif ($tipo_negocio === 'boutique') $biz_icon = 'bi-handbag-fill';
                            elseif ($tipo_negocio === 'ferreteria_repuestos') $biz_icon = 'bi-tools';
                            elseif ($tipo_negocio === 'belleza_estetica') $biz_icon = 'bi-stars';
                            ?>
                            <i class="bi <?= $biz_icon ?> text-3xl"></i>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Store Name -->
                <h3 class="font-extrabold text-base text-slate-800 mb-1">
                    <?= h($nombre) ?>
                </h3>
                
                <hr class="w-12 border-t-2 border-slate-100 my-3">

                <!-- Info Grid (Centrada) -->
                <div class="space-y-3.5 w-full overflow-y-auto max-h-[60vh] pr-1">
                    <!-- Teléfono / WhatsApp -->
                    <?php if (!empty($config['telefono_whatsapp'])): ?>
                        <div class="flex flex-col items-center">
                            <div class="w-8 h-8 bg-emerald-50 text-emerald-600 rounded-xl flex items-center justify-center mb-1 border border-emerald-100/50">
                                <i class="bi bi-whatsapp text-sm"></i>
                            </div>
                            <span class="text-[9px] font-bold text-slate-400 uppercase tracking-wider leading-none">Teléfono / WhatsApp</span>
                            <a href="https://wa.me/<?= h(preg_replace('/[^0-9]/', '', $config['telefono_whatsapp'])) ?>" target="_blank" class="text-xs font-bold text-slate-650 hover:text-emerald-600 transition-colors mt-1">
                                <?= h($config['telefono_whatsapp']) ?>
                            </a>
                        </div>
                    <?php endif; ?>

                    <!-- Dirección -->
                    <?php if (!empty($config['direccion'])): ?>
                        <div class="flex flex-col items-center">
                            <div class="w-8 h-8 bg-indigo-50 text-indigo-600 rounded-xl flex items-center justify-center mb-1 border border-indigo-100/50">
                                <i class="bi bi-geo-alt-fill text-sm"></i>
                            </div>
                            <span class="text-[9px] font-bold text-slate-400 uppercase tracking-wider leading-none">Dirección</span>
                            <span class="text-xs font-semibold text-slate-650 px-2 leading-relaxed mt-1 text-center break-words w-full"><?= h($config['direccion']) ?></span>
                            <a href="https://maps.google.com/?q=<?= urlencode($config['direccion']) ?>" target="_blank" class="inline-flex items-center gap-1 text-[9px] font-bold text-primary hover:underline mt-1.5">
                                Ver en mapa <i class="bi bi-arrow-up-right"></i>
                            </a>
                        </div>
                    <?php endif; ?>

                    <!-- Horario Laboral -->
                    <?php if (!empty($config['horario'])): ?>
                        <div class="flex flex-col items-center">
                            <div class="w-8 h-8 bg-amber-50 text-amber-600 rounded-xl flex items-center justify-center mb-1 border border-amber-100/50">
                                <i class="bi bi-clock-fill text-sm"></i>
                            </div>
                            <span class="text-[9px] font-bold text-slate-400 uppercase tracking-wider leading-none">Horario Laboral</span>
                            <span class="text-xs font-semibold text-slate-650 mt-1 text-center"><?= h($config['horario']) ?></span>
                        </div>
                    <?php endif; ?>

                    <!-- Redes Sociales -->
                    <?php
                    $has_socials = !empty($config['correo_electronico']) || 
                                   !empty($config['social_instagram']) || 
                                   !empty($config['social_tiktok']) || 
                                   !empty($config['social_facebook']) || 
                                   !empty($config['social_telegram']);
                    ?>
                    <?php if ($has_socials): ?>
                        <div class="flex flex-col items-center pt-1.5">
                            <span class="text-[9px] font-bold text-slate-400 uppercase tracking-wider mb-2 leading-none">Síguenos en Redes</span>
                            <div class="flex flex-wrap justify-center gap-2">
                                <?php if (!empty($config['correo_electronico'])): ?>
                                    <a href="mailto:<?= h($config['correo_electronico']) ?>" title="Correo Electrónico" 
                                       class="w-8 h-8 rounded-lg bg-slate-50 text-slate-600 hover:bg-slate-100 flex items-center justify-center transition-all border border-slate-200/50 hover:scale-[1.05]">
                                        <i class="bi bi-envelope-fill text-xs"></i>
                                    </a>
                                <?php endif; ?>

                                <?php if (!empty($config['social_instagram'])): ?>
                                    <a href="<?= h($config['social_instagram']) ?>" target="_blank" title="Instagram" 
                                       class="w-8 h-8 rounded-lg bg-rose-50 text-rose-600 hover:bg-rose-100 flex items-center justify-center transition-all border border-rose-100/50 hover:scale-[1.05]">
                                        <i class="bi bi-instagram text-xs"></i>
                                    </a>
                                <?php endif; ?>

                                <?php if (!empty($config['social_tiktok'])): ?>
                                    <a href="<?= h($config['social_tiktok']) ?>" target="_blank" title="TikTok" 
                                       class="w-8 h-8 rounded-lg bg-slate-50 text-slate-800 hover:bg-slate-100 flex items-center justify-center transition-all border border-slate-200/50 hover:scale-[1.05]">
                                        <i class="bi bi-tiktok text-xs"></i>
                                    </a>
                                <?php endif; ?>

                                <?php if (!empty($config['social_facebook'])): ?>
                                    <a href="<?= h($config['social_facebook']) ?>" target="_blank" title="Facebook" 
                                       class="w-8 h-8 rounded-lg bg-blue-50 text-blue-600 hover:bg-blue-100 flex items-center justify-center transition-all border border-blue-100/50 hover:scale-[1.05]">
                                        <i class="bi bi-facebook text-xs"></i>
                                    </a>
                                <?php endif; ?>

                                <?php if (!empty($config['social_telegram'])): ?>
                                    <?php 
                                    $tg = $config['social_telegram'];
                                    if (strpos($tg, 'http') === false) {
                                        $tg = 'https://t.me/' . ltrim($tg, '@');
                                    }
                                    ?>
                                    <a href="<?= h($tg) ?>" target="_blank" title="Telegram" 
                                       class="w-8 h-8 rounded-lg bg-sky-50 text-sky-600 hover:bg-sky-100 flex items-center justify-center transition-all border border-sky-100/50 hover:scale-[1.05]">
                                        <i class="bi bi-telegram text-xs"></i>
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Mini Footer of the App at the very end of the modal -->
                <div class="mt-4 pt-3 border-t border-slate-150 w-full text-center">
                    <span class="text-[9px] uppercase font-bold text-slate-400">Powered by</span>
                    <span class="text-primary font-extrabold text-[10px] block mt-0.5"><?= $powered_text ?></span>
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
