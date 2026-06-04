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
            <?php
            $nombre = !empty($config['nombre']) && $config['nombre'] !== 'Mi Tienda' ? $config['nombre'] : 'PronttoGo';
            ?>
            <div class="bg-white rounded-none shadow-[0_20px_50px_rgba(0,0,0,0.12)] border border-slate-100 w-full max-w-[390px] aspect-square p-5 sm:p-6 relative z-10 flex flex-col justify-between items-center text-center transform scale-95 transition-transform duration-300 overflow-hidden">
                
                <!-- Store Name & Header -->
                <div class="w-full flex flex-col items-center">
                    <span class="text-[8px] font-extrabold text-primary uppercase tracking-widest mb-1 leading-none">Información</span>
                    <h3 class="font-extrabold text-base sm:text-lg text-slate-800 tracking-tight leading-tight">
                        <?= h($nombre) ?>
                    </h3>
                    <div class="h-0.5 w-6 bg-primary/20 mx-auto mt-2 rounded-full"></div>
                </div>

                <!-- Info Grid (Professional Compact Layout) -->
                <div class="grid grid-cols-2 gap-3 w-full my-auto text-left">
                    <!-- WhatsApp -->
                    <?php if (!empty($config['telefono_whatsapp'])): ?>
                        <a href="https://wa.me/<?= h(preg_replace('/[^0-9]/', '', $config['telefono_whatsapp'])) ?>" target="_blank" 
                           class="col-span-1 p-2.5 bg-slate-50/50 hover:bg-white border border-slate-100 hover:border-emerald-200 transition-all duration-300 flex items-center gap-2.5 group">
                            <div class="w-8 h-8 bg-emerald-50 text-emerald-600 rounded-lg flex items-center justify-center shrink-0 group-hover:scale-105 transition-all">
                                <i class="bi bi-whatsapp text-sm"></i>
                            </div>
                            <div class="min-w-0">
                                <span class="block text-[8px] font-extrabold text-slate-405 uppercase tracking-wider leading-none mb-0.5">WhatsApp</span>
                                <span class="block text-[11px] font-bold text-slate-700 truncate"><?= h($config['telefono_whatsapp']) ?></span>
                            </div>
                        </a>
                    <?php endif; ?>

                    <!-- Horario -->
                    <?php if (!empty($config['horario'])): ?>
                        <div class="col-span-1 p-2.5 bg-slate-50/50 hover:bg-white border border-slate-100 hover:border-amber-200 transition-all duration-300 flex items-center gap-2.5 group">
                            <div class="w-8 h-8 bg-amber-50 text-amber-600 rounded-lg flex items-center justify-center shrink-0 group-hover:scale-105 transition-all">
                                <i class="bi bi-clock-fill text-sm"></i>
                            </div>
                            <div class="min-w-0">
                                <span class="block text-[8px] font-extrabold text-slate-405 uppercase tracking-wider leading-none mb-0.5">Horario</span>
                                <span class="block text-[10px] font-semibold text-slate-650 leading-tight" title="<?= h($config['horario']) ?>"><?= h($config['horario']) ?></span>
                            </div>
                        </div>
                    <?php endif; ?>

                    <!-- Dirección -->
                    <?php if (!empty($config['direccion'])): ?>
                        <a href="https://maps.google.com/?q=<?= urlencode($config['direccion']) ?>" target="_blank"
                           class="col-span-2 p-2.5 bg-slate-50/50 hover:bg-white border border-slate-100 hover:border-indigo-200 transition-all duration-300 flex items-start gap-2.5 group">
                            <div class="w-8 h-8 bg-indigo-50 text-indigo-600 rounded-lg flex items-center justify-center shrink-0 mt-0.5 group-hover:scale-105 transition-all">
                                <i class="bi bi-geo-alt-fill text-sm"></i>
                            </div>
                            <div class="min-w-0 flex-1">
                                <div class="flex items-center justify-between">
                                    <span class="text-[8px] font-extrabold text-slate-405 uppercase tracking-wider leading-none mb-0.5">Dirección</span>
                                    <span class="text-[8px] font-bold text-primary group-hover:underline flex items-center gap-0.5 leading-none">
                                        Mapa <i class="bi bi-arrow-up-right text-[7px]"></i>
                                    </span>
                                </div>
                                <span class="block text-[10px] sm:text-[11px] font-semibold text-slate-650 leading-normal line-clamp-2"><?= h($config['direccion']) ?></span>
                            </div>
                        </a>
                    <?php endif; ?>
                </div>

                <!-- Redes Sociales -->
                <?php
                $has_socials = !empty($config['correo_electronico']) || 
                               !empty($config['social_instagram']) || 
                               !empty($config['social_tiktok']) || 
                               !empty($config['social_facebook']) || 
                               !empty($config['social_telegram']);
                ?>
                <?php if ($has_socials): ?>
                    <div class="w-full pt-3 border-t border-slate-100 flex flex-col items-center">
                        <span class="text-[8px] font-extrabold text-slate-400 uppercase tracking-wider mb-2 leading-none">Síguenos en Redes</span>
                        <div class="flex flex-wrap justify-center gap-2">
                            <?php if (!empty($config['correo_electronico'])): ?>
                                <a href="mailto:<?= h($config['correo_electronico']) ?>" title="Correo Electrónico" 
                                   class="w-8 h-8 rounded-lg bg-slate-50 text-slate-500 hover:text-white hover:bg-slate-700 border border-slate-200/50 flex items-center justify-center transition-all duration-200 hover:-translate-y-0.5">
                                    <i class="bi bi-envelope-fill text-xs"></i>
                                </a>
                            <?php endif; ?>

                            <?php if (!empty($config['social_instagram'])): ?>
                                <a href="<?= h($config['social_instagram']) ?>" target="_blank" title="Instagram" 
                                   class="w-8 h-8 rounded-lg bg-slate-50 text-slate-500 hover:text-white hover:bg-rose-500 border border-slate-200/50 flex items-center justify-center transition-all duration-200 hover:-translate-y-0.5">
                                    <i class="bi bi-instagram text-xs"></i>
                                </a>
                            <?php endif; ?>

                            <?php if (!empty($config['social_tiktok'])): ?>
                                <a href="<?= h($config['social_tiktok']) ?>" target="_blank" title="TikTok" 
                                   class="w-8 h-8 rounded-lg bg-slate-50 text-slate-500 hover:text-white hover:bg-black border border-slate-200/50 flex items-center justify-center transition-all duration-200 hover:-translate-y-0.5">
                                    <i class="bi bi-tiktok text-xs"></i>
                                </a>
                            <?php endif; ?>

                            <?php if (!empty($config['social_facebook'])): ?>
                                <a href="<?= h($config['social_facebook']) ?>" target="_blank" title="Facebook" 
                                   class="w-8 h-8 rounded-lg bg-slate-50 text-slate-500 hover:text-white hover:bg-blue-600 border border-slate-200/50 flex items-center justify-center transition-all duration-200 hover:-translate-y-0.5">
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
                                   class="w-8 h-8 rounded-lg bg-slate-50 text-slate-500 hover:text-white hover:bg-sky-500 border border-slate-200/50 flex items-center justify-center transition-all duration-200 hover:-translate-y-0.5">
                                    <i class="bi bi-telegram text-xs"></i>
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endif; ?>
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
