<?php
$es_admin = false;
/**
 * PronttoGo - Página Legal (Términos, Condiciones y Política de Privacidad)
 */

require_once __DIR__ . '/../core/config.php';
require_once __DIR__ . '/../core/db.php';
require_once __DIR__ . '/../core/auth.php';

// Obtener configuración del local
$response = supabase_request('GET', 'configuracion?id=eq.1');
$config = $response['success'] && !empty($response['data']) ? $response['data'][0] : [];
$nombre_tienda = !empty($config['nombre']) && $config['nombre'] !== 'Mi Tienda' ? $config['nombre'] : 'PronttoGo';
$tipo_negocio = $config['tipo_negocio'] ?? 'gastronomia';

require_once __DIR__ . '/../includes/header.php';
?>

    <!-- Contenido Principal -->
    <main class="flex-1 max-w-3xl w-full mx-auto px-4 sm:px-6 py-12 space-y-12 pb-24 md:pb-12">
        
        <!-- Encabezado de la página -->
        <div class="space-y-3 text-center sm:text-left">
            <span class="inline-flex items-center gap-1.5 px-3 py-1 bg-emerald-50 text-emerald-600 rounded-full text-xs font-bold tracking-wide uppercase">
                Documentación Legal
            </span>
            <h1 class="text-3xl sm:text-4xl font-extrabold tracking-tight text-slate-900">Términos, Condiciones y Privacidad</h1>
            <p class="text-slate-500 text-sm">Última actualización: Mayo 2026</p>
            <div class="pt-2 text-center sm:text-left">
                <a href="/" class="inline-flex items-center gap-1 px-4 py-2 border border-slate-200 text-slate-600 hover:bg-slate-50 rounded-xl font-bold text-xs transition-all">
                    <i class="bi bi-arrow-left"></i> Volver al Menú
                </a>
            </div>
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

<?php
require_once __DIR__ . '/../includes/footer.php';
?>
