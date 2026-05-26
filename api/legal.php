<?php
/**
 * PronttoGo - Página Legal (Términos, Condiciones y Política de Privacidad)
 */

require_once __DIR__ . '/config.php';

// Obtener configuración del local
$response = supabase_request('GET', 'configuracion?id=eq.1');
$config = $response['success'] && !empty($response['data']) ? $response['data'][0] : [];
$nombre_tienda = !empty($config['nombre']) && $config['nombre'] !== 'Mi Tienda' ? $config['nombre'] : 'PronttoGo';
?>
<!DOCTYPE html>
<html lang="es" class="scroll-smooth overflow-x-hidden">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/svg+xml" href="/assets/favicon.svg">
    <link rel="icon" type="image/png" sizes="32x32" href="/assets/favicon.svg">
    <link rel="shortcut icon" href="/assets/favicon.svg">
    <link rel="apple-touch-icon" href="/assets/favicon.svg">
    <meta name="theme-color" content="#10B981">
    <title>Legal - <?= h($nombre_tienda) ?></title>
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
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; }
    </style>
</head>
<body class="bg-[#F8FAFC] text-[#0F172A] min-h-screen flex flex-col overflow-x-hidden">

    <!-- Header -->
    <header class="h-16 bg-white/95 backdrop-blur-md border-b border-slate-100 sticky top-0 z-30 shadow-sm flex items-center">
        <div class="max-w-4xl w-full mx-auto px-4 sm:px-6 flex items-center justify-between">
            <div class="flex items-center space-x-2.5">
                <?php if (!empty($config['logo_url'])): ?>
                    <img src="<?= h($config['logo_url']) ?>" alt="<?= h($nombre_tienda) ?>" class="h-8 w-auto object-contain rounded-lg">
                    <a href="/" class="font-extrabold text-lg tracking-tight bg-gradient-to-r from-[#10B981] to-[#06B6D4] bg-clip-text text-transparent"><?= h($nombre_tienda) ?></a>
                <?php else: ?>
                    <?php if (strtolower($nombre_tienda) === 'pronttogo' || $nombre_tienda === 'Mi Tienda'): ?>
                        <a href="/"><?= get_logo_svg('h-8 w-auto') ?></a>
                    <?php else: ?>
                        <a href="/" class="font-extrabold text-lg tracking-tight bg-gradient-to-r from-[#10B981] to-[#06B6D4] bg-clip-text text-transparent"><?= h($nombre_tienda) ?></a>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
            <a href="/" class="text-xs font-bold text-slate-600 hover:text-slate-900 border border-slate-200 hover:border-slate-350 rounded-xl px-4 py-2 transition-all bg-white shadow-sm">
                Volver al Menú
            </a>
        </div>
    </header>

    <!-- Contenido Principal -->
    <main class="flex-1 max-w-3xl w-full mx-auto px-4 sm:px-6 py-12 space-y-12">
        
        <!-- Encabezado de la página -->
        <div class="space-y-3 text-center sm:text-left">
            <span class="inline-flex items-center gap-1.5 px-3 py-1 bg-emerald-50 text-emerald-600 rounded-full text-xs font-bold tracking-wide uppercase">
                Documentación Legal
            </span>
            <h1 class="text-3xl sm:text-4xl font-extrabold tracking-tight text-slate-900">Términos, Condiciones y Privacidad</h1>
            <p class="text-slate-505 text-sm">Última actualización: Mayo 2026</p>
        </div>

        <!-- Secciones Legales -->
        <div class="bg-white p-6 sm:p-8 rounded-3xl border border-slate-100 shadow-sm space-y-10">
            
            <!-- Términos y Condiciones -->
            <section class="space-y-4">
                <h2 class="text-xl sm:text-2xl font-bold text-slate-900 flex items-center gap-2">
                    <span class="text-emerald-500">1.</span> Términos y Condiciones de Uso
                </h2>
                <div class="prose prose-slate text-sm text-slate-600 space-y-4 leading-relaxed">
                    <div>
                        <h3 class="font-semibold text-slate-800 text-base mb-1">¿Qué ofrece PronttoGo?</h3>
                        <p>
                            PronttoGo es un catálogo digital interactivo diseñado para comercios independientes (Single-Store). Esta aplicación permite al comercio administrar y publicar su menú de especialidades, productos, precios y categorías, facilitando a los clientes la selección de productos a través de un carrito de compras digital que canaliza el pedido directamente hacia el WhatsApp del comercio.
                        </p>
                    </div>
                    <div>
                        <h3 class="font-semibold text-slate-800 text-base mb-1">Responsabilidades del Usuario / Comercio</h3>
                        <ul class="list-disc pl-5 space-y-2">
                            <li><strong>Seguridad de Acceso:</strong> El comercio es responsable de proteger las credenciales administrativas de su panel de control.</li>
                            <li><strong>Legalidad de Productos:</strong> Queda prohibido el uso de la aplicación para ofrecer productos ilícitos, falsificados o que violen las políticas de mensajería de WhatsApp/Meta.</li>
                            <li><strong>Transparencia de Precios:</strong> El comercio se compromete a mantener actualizada la información de precios, tasas de cambio y disponibilidad en el panel de administración.</li>
                            <li><strong>Logística y Pagos:</strong> PronttoGo funciona exclusivamente como catálogo y canalizador de pedidos. No procesa pagos directos ni gestiona entregas; cualquier transacción final es responsabilidad exclusiva del comercio y el cliente.</li>
                        </ul>
                    </div>
                </div>
            </section>

            <hr class="border-slate-100">

            <!-- Política de Privacidad -->
            <section class="space-y-4">
                <h2 class="text-xl sm:text-2xl font-bold text-slate-900 flex items-center gap-2">
                    <span class="text-cyan-500">2.</span> Política de Privacidad
                </h2>
                <div class="prose prose-slate text-sm text-slate-600 space-y-4 leading-relaxed">
                    <p>
                        Para Montero Studio y <?= h($nombre_tienda) ?>, la privacidad y seguridad de sus datos es fundamental. Explicamos detalladamente cómo se maneja la información en este catálogo:
                    </p>
                    <div>
                        <h3 class="font-semibold text-slate-800 text-base mb-1">Datos del Comercio (Administrador)</h3>
                        <p>
                            La información configurada en el perfil (nombre comercial, número de WhatsApp para pedidos y URL del logotipo) se almacena de forma segura en la base de datos (Supabase) con la única finalidad de operar el catálogo público correctamente. Las credenciales de acceso administrativas son cifradas mediante algoritmos de hash seguros (`bcrypt`) antes de ser guardadas.
                        </p>
                    </div>
                    <div>
                        <h3 class="font-semibold text-slate-800 text-base mb-1">Datos de los Clientes (Compradores)</h3>
                        <p>
                            Los datos de los productos agregados al carrito se guardan únicamente de forma local en el navegador del dispositivo del cliente (utilizando `localStorage`). Ningún dato del cliente o del contenido del carrito se almacena en nuestros servidores de base de datos. Al hacer clic en enviar pedido, los datos se transfieren directamente a WhatsApp mediante su API oficial sin pasar por intermediarios.
                        </p>
                    </div>
                    <div>
                        <h3 class="font-semibold text-slate-800 text-base mb-1">Uso de Cookies y Almacenamiento</h3>
                        <p>
                            Utilizamos almacenamiento local exclusivamente para guardar los artículos del carrito activo y cookies técnicas de sesión para el acceso seguro al panel administrativo. No rastreamos la navegación de los usuarios con fines publicitarios ni compartimos información con terceros.
                        </p>
                    </div>
                </div>
            </section>

        </div>

    </main>

    <!-- Footer Bar -->
    <footer class="bg-white border-t border-slate-100 py-5 mt-auto">
        <div class="max-w-4xl w-full mx-auto px-4 sm:px-6 flex items-center justify-between text-xs font-semibold text-slate-500">
            <span>&copy; 2026 <?= h($nombre_tienda) ?></span>
            <a href="/" class="text-[10px] uppercase font-bold text-slate-400 hover:text-slate-600 transition-colors flex items-center gap-1">
                <span>Powered by</span>
                <span class="bg-gradient-to-r from-[#10B981] to-[#06B6D4] bg-clip-text text-transparent font-extrabold">Montero Studio</span>
            </a>
        </div>
    </footer>

</body>
</html>
