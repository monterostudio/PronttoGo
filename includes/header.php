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
    
    <!-- Tailwind CSS v3 Play CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    
    <!-- Custom style.css -->
    <link rel="stylesheet" href="/assets/css/style.css">

    <!-- Favicons (Compatibles con Caché Búster) -->
    <link rel="icon" type="image/svg+xml" href="/assets/img/favicon.svg?v=2">
    <link rel="icon" type="image/png" sizes="32x32" href="/assets/img/favicon.svg?v=2">
    <link rel="shortcut icon" href="/assets/img/favicon.svg?v=2">
    <link rel="apple-touch-icon" href="/assets/img/favicon.svg?v=2">

    <?php if ($es_admin): ?>
        <!-- Alpine.js (Solo Admin) -->
        <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.13.3/dist/cdn.min.js"></script>
    <?php else: ?>
        <!-- Estilos Dinámicos (Solo Público) -->
        <?php
        $color_niche = [
            'gastronomia' => ['primary' => '#4F46E5', 'primary-hover' => '#4338CA', 'soft' => '#EEF2FF', 'border-soft' => '#C7D2FE', 'hero-bg-from' => '#EEF2FF', 'hero-bg-via' => '#E0E7FF', 'hero-bg-to' => '#EEF2FF', 'hero-glow' => 'rgba(79,70,229,0.05)'],
            'comida_rapida' => ['primary' => '#EF4444', 'primary-hover' => '#DC2626', 'soft' => '#FEF2F2', 'border-soft' => '#FEE2E2', 'hero-bg-from' => '#FEF2F2', 'hero-bg-via' => '#FEE2E2', 'hero-bg-to' => '#FEF2F2', 'hero-glow' => 'rgba(239,68,68,0.05)'],
            'minimarket' => ['primary' => '#10B981', 'primary-hover' => '#059669', 'soft' => '#ECFDF5', 'border-soft' => '#D1FAE5', 'hero-bg-from' => '#ECFDF5', 'hero-bg-via' => '#D1FAE5', 'hero-bg-to' => '#ECFDF5', 'hero-glow' => 'rgba(16,185,129,0.05)'],
            'farmacia' => ['primary' => '#06B6D4', 'primary-hover' => '#0891B2', 'soft' => '#ECFEFF', 'border-soft' => '#CFFAFE', 'hero-bg-from' => '#ECFEFF', 'hero-bg-via' => '#CFFAFE', 'hero-bg-to' => '#ECFEFF', 'hero-glow' => 'rgba(6,182,212,0.05)'],
            'boutique' => ['primary' => '#8B5CF6', 'primary-hover' => '#7C3AED', 'soft' => '#F5F3FF', 'border-soft' => '#DDD6FE', 'hero-bg-from' => '#FAF5FF', 'hero-bg-via' => '#F3E8FF', 'hero-bg-to' => '#FAF5FF', 'hero-glow' => 'rgba(139,92,246,0.05)'],
            'ferreteria_repuestos' => ['primary' => '#F59E0B', 'primary-hover' => '#D97706', 'soft' => '#FFFBEB', 'border-soft' => '#FDE68A', 'hero-bg-from' => '#FFFDF5', 'hero-bg-via' => '#FEF3C7', 'hero-bg-to' => '#FFFDF5', 'hero-glow' => 'rgba(245,158,11,0.05)'],
            'belleza_estetica' => ['primary' => '#EC4899', 'primary-hover' => '#DB2777', 'soft' => '#FDF2F8', 'border-soft' => '#FBCFE8', 'hero-bg-from' => '#FDF2F8', 'hero-bg-via' => '#FCE7F3', 'hero-bg-to' => '#FDF2F8', 'hero-glow' => 'rgba(236,72,153,0.05)'],
            'otros' => ['primary' => '#4F46E5', 'primary-hover' => '#4338CA', 'soft' => '#EEF2FF', 'border-soft' => '#C7D2FE', 'hero-bg-from' => '#EEF2FF', 'hero-bg-via' => '#E0E7FF', 'hero-bg-to' => '#EEF2FF', 'hero-glow' => 'rgba(79,70,229,0.05)']
        ];
        $tipo_negocio = $config['tipo_negocio'] ?? 'gastronomia';
        $colors = $color_niche[$tipo_negocio] ?? $color_niche['gastronomia'];

        // Color personalizado de marca desde configuracion si existe
        if (!empty($config['color_primario'])) {
            $custom_primary = $config['color_primario'];
            $colors['primary'] = $custom_primary;
            $colors['primary-hover'] = $custom_primary;
            $colors['soft'] = $custom_primary . '0D'; // ~5% opacidad
            $colors['border-soft'] = $custom_primary . '26'; // ~15% opacidad
            $colors['hero-bg-from'] = $custom_primary . '08'; // ~3% opacidad
            $colors['hero-bg-via'] = $custom_primary . '13'; // ~7% opacidad
            $colors['hero-bg-to'] = $custom_primary . '08';
            $colors['hero-glow'] = $custom_primary . '0D';
        }
        ?>
        <meta name="theme-color" content="<?= h($colors['primary']) ?>">
        
        <!-- Configuración de Tema Personalizado para Tailwind CSS v3 -->
        <script>
            tailwind.config = {
                theme: {
                    extend: {
                        colors: {
                            primary: '<?= $colors['primary'] ?>',
                            'primary-hover': '<?= $colors['primary-hover'] ?>',
                            soft: '<?= $colors['soft'] ?>',
                            'border-soft': '<?= $colors['border-soft'] ?>',
                        }
                    }
                }
            }
        </script>
        <style type="text/css">
            :root {
                --hero-bg-from: <?= $colors['hero-bg-from'] ?>;
                --hero-bg-via: <?= $colors['hero-bg-via'] ?>;
                --hero-bg-to: <?= $colors['hero-bg-to'] ?>;
                --hero-glow: <?= $colors['hero-glow'] ?>;
            }
            
            /* Filtro de oscurecimiento automático para hovers con color personalizado */
            .bg-primary:hover, .hover\:bg-primary:hover {
                filter: brightness(0.92) !important;
                transition: filter 0.2s ease-in-out;
            }
        </style>
        
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
        
        <header class="h-16 bg-white/95 backdrop-blur-md border-b border-slate-100 sticky top-0 z-30 shadow-sm flex items-center">
            <div class="max-w-6xl w-full mx-auto px-4 sm:px-6 flex items-center justify-between">
                <div class="flex items-center space-x-2.5 min-w-0">
                    <?= render_logo('header', $config) ?>
                    <?php if (!empty($config['logo_url'])): ?>
                        <span class="font-extrabold text-lg tracking-tight text-slate-800 truncate max-w-[140px] sm:max-w-none block"><?= h($config['nombre']) ?></span>
                    <?php endif; ?>
                </div>
                <div class="flex items-center space-x-2 shrink-0">
                    <button onclick="toggleInfoDrawer(true)" class="text-xs font-bold text-slate-600 hover:text-slate-900 border border-slate-200 hover:border-slate-350 rounded-xl px-4 py-2 transition-all bg-white shadow-sm flex items-center gap-1.5 cursor-pointer">
                        <i class="bi bi-info-circle text-sm text-primary"></i>
                        <span class="hidden sm:inline">Información</span>
                    </button>
                    <a href="/admin" class="text-xs font-bold text-slate-600 hover:text-slate-900 border border-slate-200 hover:border-slate-350 rounded-xl px-4 py-2 transition-all bg-white shadow-sm">
                        Iniciar Sesión
                    </a>
                </div>
            </div>
        </header>
    <?php endif; ?>
