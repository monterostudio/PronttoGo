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
    
    <!-- Tailwind CSS v4.0 Play CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
    
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    
    <!-- Custom style.css -->
    <link rel="stylesheet" href="/assets/css/style.css">

    <!-- React + Babel Standalone (Sin Node.js/terminal) -->
    <script src="https://unpkg.com/react@18/umd/react.production.min.js" crossorigin></script>
    <script src="https://unpkg.com/react-dom@18/umd/react-dom.production.min.js" crossorigin></script>
    <script src="https://unpkg.com/@babel/standalone/babel.min.js"></script>

    <?php if ($es_admin): ?>
        <!-- Alpine.js (Solo Admin) -->
        <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.13.3/dist/cdn.min.js"></script>
    <?php else: ?>
        <!-- Favicon y Estilos Dinámicos (Solo Público) -->
        <link rel="icon" type="image/svg+xml" href="/assets/img/favicon.svg">
        <link rel="icon" type="image/png" sizes="32x32" href="/assets/img/favicon.svg">
        <link rel="shortcut icon" href="/assets/img/favicon.svg">
        <link rel="apple-touch-icon" href="/assets/img/favicon.svg">
        <?php
        $color_niche = [
            'gastronomia' => ['primary' => '#00CFBD', 'primary-hover' => '#00B5A5', 'soft' => '#E6FBF9', 'border-soft' => '#B2EFE9', 'hero-bg-from' => '#F0FDFB', 'hero-bg-via' => '#E2F8F5', 'hero-bg-to' => '#F0FDFB', 'hero-glow' => 'rgba(0,207,189,0.05)'],
            'boutique' => ['primary' => '#8B5CF6', 'primary-hover' => '#7C3AED', 'soft' => '#F5F3FF', 'border-soft' => '#DDD6FE', 'hero-bg-from' => '#FAF5FF', 'hero-bg-via' => '#F3E8FF', 'hero-bg-to' => '#FAF5FF', 'hero-glow' => 'rgba(139,92,246,0.05)'],
            'ferreteria_repuestos' => ['primary' => '#F59E0B', 'primary-hover' => '#D97706', 'soft' => '#FFFBEB', 'border-soft' => '#FDE68A', 'hero-bg-from' => '#FFFDF5', 'hero-bg-via' => '#FEF3C7', 'hero-bg-to' => '#FFFDF5', 'hero-glow' => 'rgba(245,158,11,0.05)'],
            'belleza_estetica' => ['primary' => '#EC4899', 'primary-hover' => '#DB2777', 'soft' => '#FDF2F8', 'border-soft' => '#FBCFE8', 'hero-bg-from' => '#FDF2F8', 'hero-bg-via' => '#FCE7F3', 'hero-bg-to' => '#FDF2F8', 'hero-glow' => 'rgba(236,72,153,0.05)'],
            'otros' => ['primary' => '#4F46E5', 'primary-hover' => '#4338CA', 'soft' => '#EEF2FF', 'border-soft' => '#C7D2FE', 'hero-bg-from' => '#EEF2FF', 'hero-bg-via' => '#E0E7FF', 'hero-bg-to' => '#EEF2FF', 'hero-glow' => 'rgba(79,70,229,0.05)']
        ];
        $tipo_negocio = $config['tipo_negocio'] ?? 'gastronomia';
        $colors = $color_niche[$tipo_negocio] ?? $color_niche['gastronomia'];
        ?>
        <meta name="theme-color" content="<?= h($colors['primary']) ?>">
        
        <!-- Configuración de Tema Personalizado para Tailwind CSS v4.0 -->
        <style type="text/tailwindcss">
            @theme {
                --color-primary: <?= $colors['primary'] ?>;
                --color-primary-hover: <?= $colors['primary-hover'] ?>;
                --color-soft: <?= $colors['soft'] ?>;
                --color-border-soft: <?= $colors['border-soft'] ?>;
                --hero-bg-from: <?= $colors['hero-bg-from'] ?>;
                --hero-bg-via: <?= $colors['hero-bg-via'] ?>;
                --hero-bg-to: <?= $colors['hero-bg-to'] ?>;
                --hero-glow: <?= $colors['hero-glow'] ?>;
            }
        </style>
        
        <script>
            localStorage.removeItem('theme'); document.documentElement.classList.remove('dark');
        </script>
    <?php endif; ?>
</head>
<body class="<?= $es_admin ? 'bg-slate-50 text-slate-900 h-full flex flex-col' : 'bg-[#F8FAFC] text-[#0F172A] min-h-screen flex flex-col overflow-x-hidden' ?>">

    <?php if (!$es_admin): ?>
        <?php if (isset($dbError) && $dbError): ?>
            <div class="bg-red-600 text-white text-xs font-bold px-4 py-3 text-center shadow-md relative z-50">
                <i class="bi bi-exclamation-triangle-fill text-red-500"></i> <strong>Error de Base de Datos (Local):</strong> <?= h($dbError) ?> | URL configurada: <code class="bg-red-700 px-1.5 py-0.5 rounded"><?= h(SUPABASE_URL) ?></code>
            </div>
        <?php endif; ?>
        
        <header class="h-16 bg-white/95 backdrop-blur-md border-b border-slate-100 sticky top-0 z-30 shadow-sm flex items-center">
            <div class="max-w-6xl w-full mx-auto px-4 sm:px-6 flex items-center justify-between">
                <div class="flex items-center space-x-2.5 min-w-0">
                    <?php if (!empty($config['logo_url'])): ?>
                        <img src="<?= h($config['logo_url']) ?>" alt="<?= h($config['nombre']) ?>" class="h-8 w-auto object-contain rounded-lg shrink-0">
                        <span class="font-extrabold text-lg tracking-tight text-slate-800 truncate max-w-[140px] sm:max-w-none block"><?= h($config['nombre']) ?></span>
                    <?php else: ?>
                        <?php if (strtolower($config['nombre'] ?? 'pronttogo') === 'pronttogo' || ($config['nombre'] ?? 'Mi Tienda') === 'Mi Tienda'): ?>
                            <?= get_logo_svg('h-8 w-auto shrink-0') ?>
                        <?php else: ?>
                            <span class="font-extrabold text-lg tracking-tight text-slate-800 truncate max-w-[140px] sm:max-w-none block"><?= h($config['nombre']) ?></span>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
                <a href="/admin" class="text-xs font-bold text-slate-600 hover:text-slate-900 border border-slate-200 hover:border-slate-350 rounded-xl px-4 py-2 transition-all bg-white shadow-sm shrink-0">
                    Iniciar Sesión
                </a>
            </div>
        </header>
    <?php endif; ?>
