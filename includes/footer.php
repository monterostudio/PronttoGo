<?php
// includes/footer.php
$es_admin = isset($es_admin) ? $es_admin : false;
?>
<?php if ($es_admin): ?>
    <!-- Archivo Javascript del Panel -->
    <script src="/assets/js/main.js"></script>
</body>
</html>
<?php else: ?>
    <!-- Footer Bar -->
    <footer class="bg-white border-t border-slate-100 py-5 mt-auto">
        <div class="max-w-6xl w-full mx-auto px-4 sm:px-6 flex flex-col sm:flex-row items-center justify-between gap-3 text-xs font-semibold text-slate-500">
            <div class="flex items-center gap-4">
                <span>&copy; <?= date('Y') ?> <?= h(!empty($config['nombre']) && $config['nombre'] !== 'Mi Tienda' ? $config['nombre'] : 'PronttoGo') ?></span>
                <a href="/legal" class="text-slate-400 hover:text-primary transition-colors">Términos y Privacidad</a>
            </div>
            <a href="/admin" class="text-[10px] uppercase font-bold text-slate-400 hover:text-slate-600 transition-colors flex items-center gap-1">
                <span>Powered by</span>
                <span class="text-primary font-extrabold">Montero Studio</span>
            </a>
        </div>
    </footer>

    <!-- Drawer de Información del Local (Contacto, Redes, Dirección y Horario) -->
    <div id="info-drawer" class="fixed inset-0 z-50 hidden opacity-0 transition-opacity duration-300">
        <div onclick="toggleInfoDrawer(false)" class="absolute inset-0 bg-slate-950/40 backdrop-blur-sm"></div>
        
        <!-- Panel de Información (Bottom Sheet en móvil, Right Sidebar en desktop) -->
        <div class="absolute bottom-0 left-0 right-0 max-h-[85vh] w-full md:top-0 md:bottom-0 md:right-0 md:left-auto md:max-h-screen md:h-full md:w-[450px] bg-white rounded-t-3xl md:rounded-l-3xl md:rounded-tr-none shadow-2xl border-t md:border-t-0 md:border-l border-slate-100 flex flex-col overflow-hidden">
            
            <!-- Header del panel -->
            <div class="px-5 py-4 border-b border-slate-100 flex items-center justify-between shrink-0">
                <div class="flex items-center gap-2.5">
                    <div class="w-8 h-8 bg-primary/10 rounded-xl flex items-center justify-center">
                        <i class="bi bi-shop text-primary text-sm"></i>
                    </div>
                    <div>
                        <h3 class="font-extrabold text-sm text-slate-800 leading-tight">Contacto e Info</h3>
                        <p class="text-[10px] text-slate-400 leading-tight">Detalles y redes de la tienda</p>
                    </div>
                </div>
                <button onclick="toggleInfoDrawer(false)" class="w-8 h-8 rounded-xl bg-slate-100 hover:bg-slate-200 flex items-center justify-center text-slate-500 transition-colors">
                    <i class="bi bi-x-lg text-xs"></i>
                </button>
            </div>

            <!-- Contenido scrollable -->
            <div class="flex-1 overflow-y-auto p-6 space-y-6">
                <!-- Branding -->
                <div class="text-center space-y-3">
                    <div class="flex justify-center">
                        <?= render_logo('hero', $config) ?>
                    </div>
                </div>

                <!-- Ubicación y Horario -->
                <?php if (!empty($config['direccion']) || !empty($config['horario'])): ?>
                    <div class="space-y-4 pt-2 border-t border-slate-100">
                        <h5 class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Atención y Ubicación</h5>
                        <div class="space-y-3.5 text-xs font-semibold text-slate-600">
                            <?php if (!empty($config['direccion'])): ?>
                                <div class="flex items-start gap-2.5">
                                    <span class="text-primary text-sm mt-0.5"><i class="bi bi-geo-alt-fill"></i></span>
                                    <div class="space-y-0.5 text-left">
                                        <span class="block font-bold text-slate-800 text-[10px] uppercase tracking-wider">Dirección</span>
                                        <span class="leading-relaxed text-slate-500 font-semibold"><?= h($config['direccion']) ?></span>
                                    </div>
                                </div>
                            <?php endif; ?>
                            <?php if (!empty($config['horario'])): ?>
                                <div class="flex items-start gap-2.5">
                                    <span class="text-primary text-sm mt-0.5"><i class="bi bi-clock-fill"></i></span>
                                    <div class="space-y-0.5 text-left">
                                        <span class="block font-bold text-slate-800 text-[10px] uppercase tracking-wider">Horario de Atención</span>
                                        <span class="text-slate-500 font-semibold"><?= h($config['horario']) ?></span>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Redes Sociales y Canales de Contacto -->
                <div class="space-y-4 pt-4 border-t border-slate-100">
                    <h5 class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Redes y Canales</h5>
                    <div class="grid grid-cols-2 gap-2.5">
                        <?php if (!empty($config['telefono_whatsapp'])): ?>
                            <a href="https://wa.me/<?= h(preg_replace('/[^0-9]/', '', $config['telefono_whatsapp'])) ?>" target="_blank" class="flex items-center gap-2 p-2.5 bg-emerald-50 hover:bg-emerald-100 rounded-2xl border border-emerald-100/50 text-emerald-700 text-xs font-bold transition-all justify-center">
                                <i class="bi bi-whatsapp text-sm"></i>
                                <span>WhatsApp</span>
                            </a>
                        <?php endif; ?>
                        <?php if (!empty($config['correo_electronico'])): ?>
                            <a href="mailto:<?= h($config['correo_electronico']) ?>" class="flex items-center gap-2 p-2.5 bg-slate-50 hover:bg-slate-100 rounded-2xl border border-slate-200/50 text-slate-700 text-xs font-bold transition-all justify-center">
                                <i class="bi bi-envelope-at text-sm"></i>
                                <span>Correo</span>
                            </a>
                        <?php endif; ?>
                        <?php if (!empty($config['social_instagram'])): ?>
                            <a href="<?= h($config['social_instagram']) ?>" target="_blank" class="flex items-center gap-2 p-2.5 bg-rose-50 hover:bg-rose-100 rounded-2xl border border-rose-100/50 text-rose-700 text-xs font-bold transition-all justify-center">
                                <i class="bi bi-instagram text-sm"></i>
                                <span>Instagram</span>
                            </a>
                        <?php endif; ?>
                        <?php if (!empty($config['social_tiktok'])): ?>
                            <a href="<?= h($config['social_tiktok']) ?>" target="_blank" class="flex items-center gap-2 p-2.5 bg-slate-50 hover:bg-slate-100 rounded-2xl border border-slate-200/50 text-slate-800 text-xs font-bold transition-all justify-center">
                                <i class="bi bi-tiktok text-sm"></i>
                                <span>TikTok</span>
                            </a>
                        <?php endif; ?>
                        <?php if (!empty($config['social_facebook'])): ?>
                            <a href="<?= h($config['social_facebook']) ?>" target="_blank" class="flex items-center gap-2 p-2.5 bg-blue-50 hover:bg-blue-100 rounded-2xl border border-blue-100/50 text-blue-700 text-xs font-bold transition-all justify-center">
                                <i class="bi bi-facebook text-sm"></i>
                                <span>Facebook</span>
                            </a>
                        <?php endif; ?>
                        <?php if (!empty($config['social_telegram'])): ?>
                            <?php 
                            $telegram_url = $config['social_telegram'];
                            if (strpos($telegram_url, 'http') === false) {
                                $telegram_url = 'https://t.me/' . ltrim($telegram_url, '@');
                            }
                            ?>
                            <a href="<?= h($telegram_url) ?>" target="_blank" class="flex items-center gap-2 p-2.5 bg-sky-50 hover:bg-sky-100 rounded-2xl border border-sky-100/50 text-sky-700 text-xs font-bold transition-all justify-center">
                                <i class="bi bi-telegram text-sm"></i>
                                <span>Telegram</span>
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Script de control del drawer de información -->
    <script>
        function toggleInfoDrawer(show) {
            const drawer = document.getElementById('info-drawer');
            if (!drawer) return;
            if (show) {
                drawer.classList.remove('hidden');
                document.body.style.overflow = 'hidden';
                setTimeout(() => {
                    drawer.classList.add('opacity-100');
                    drawer.classList.remove('opacity-0');
                }, 10);
            } else {
                drawer.classList.add('opacity-0');
                drawer.classList.remove('opacity-100');
                document.body.style.overflow = '';
                setTimeout(() => drawer.classList.add('hidden'), 300);
            }
        }
    </script>

    <!-- Carrito Flotante (JS) -->
    <div id="floating-cart" class="fixed bottom-0 left-0 right-0 p-4 bg-transparent max-w-lg mx-auto z-40 hidden">
        <button onclick="toggleCartDrawer(true)" class="w-full py-3.5 px-5 bg-primary hover:opacity-95 text-white font-bold text-sm rounded-2xl shadow-xl flex justify-between items-center transition-all active:scale-98">
            <div class="flex items-center space-x-2.5">
                <div class="relative">
                    <i class="bi bi-bag-fill text-white text-base"></i>
                    <span id="cart-badge" class="absolute -top-1.5 -right-1.5 bg-white text-primary text-[9px] font-black rounded-full w-4 h-4 flex items-center justify-center leading-none">0</span>
                </div>
                <span id="cart-count" class="font-bold">0 artículos</span>
            </div>
            <div class="text-right">
                <span id="cart-total" class="block font-black text-sm md:text-base">$0.00</span>
                <?php if (isset($tasa_dolar) && $tasa_dolar > 1): ?>
                    <span id="cart-total-local" class="block text-[10px] opacity-90 font-bold font-mono"></span>
                <?php endif; ?>
            </div>
        </button>
    </div>

    <!-- Drawer del Carrito (Bottom Sheet en móvil, Right Sidebar en desktop) -->
    <div id="cart-drawer" class="fixed inset-0 z-50 hidden opacity-0 transition-opacity duration-300">
        <div onclick="toggleCartDrawer(false)" class="absolute inset-0 bg-slate-950/40 backdrop-blur-sm"></div>
        
        <!-- Panel del Carrito (Bottom Sheet en móvil, Right Sidebar en desktop) -->
        <div class="absolute bottom-0 left-0 right-0 max-h-[85vh] w-full md:top-0 md:bottom-0 md:right-0 md:left-auto md:max-h-screen md:h-full md:w-[450px] bg-white rounded-t-3xl md:rounded-l-3xl md:rounded-tr-none shadow-2xl border-t md:border-t-0 md:border-l border-slate-100 flex flex-col overflow-hidden">
            
            <!-- Header del panel -->
            <div class="px-5 py-4 border-b border-slate-100 flex items-center justify-between shrink-0">

                <div class="flex items-center gap-2.5">
                    <div class="w-8 h-8 bg-primary/10 rounded-xl flex items-center justify-center">
                        <i class="bi bi-bag-fill text-primary text-sm"></i>
                    </div>
                    <div>
                        <h3 class="font-extrabold text-base text-slate-800 leading-tight">Mi Pedido</h3>
                        <p class="text-[10px] text-slate-400 leading-tight">Revisa y confirma tus artículos</p>
                    </div>
                </div>
                <div class="flex items-center gap-2">
                    <button onclick="clearCart()" class="text-[10px] font-bold text-red-400 hover:text-red-600 transition-colors px-2 py-1 rounded-lg hover:bg-red-50">
                        <i class="bi bi-trash"></i> Vaciar
                    </button>
                    <button onclick="toggleCartDrawer(false)" class="w-8 h-8 rounded-xl bg-slate-100 hover:bg-slate-200 flex items-center justify-center text-slate-500 transition-colors">
                        <i class="bi bi-x-lg text-xs"></i>
                    </button>
                </div>
            </div>

            <!-- Contenedor scrollable principal -->
            <div class="flex-1 overflow-y-auto">
                <div class="p-5 space-y-5">
                    <!-- Listado de Productos -->
                    <div id="cart-items" class="space-y-1 divide-y divide-slate-100">
                        <!-- Se rellena por JS de forma segura -->
                    </div>

                    <!-- Formulario de Datos del Cliente -->
                    <div id="customer-data-form" class="border-t border-slate-100 pt-4 space-y-3.5">
                        <div class="pb-1">
                            <h4 class="font-extrabold text-sm text-slate-800">Datos del Cliente</h4>
                            <p class="text-[10px] text-slate-400 mt-0.5">Completa esta información para tu pedido.</p>
                        </div>

                        <!-- Nombre -->
                        <div class="space-y-1">
                            <label for="cust-name" class="block text-[10px] font-bold uppercase tracking-wider text-slate-500">Tu Nombre</label>
                            <div class="relative">
                                <span class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
                                    <i class="bi bi-person-fill text-slate-400 text-xs"></i>
                                </span>
                                <input type="text" id="cust-name" placeholder="Ej. Carlos Mendoza" required
                                       class="w-full pl-8 pr-3 py-2.5 border border-slate-200 rounded-xl text-xs bg-slate-50 text-slate-900 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-primary/30 focus:border-primary transition-all">
                            </div>
                        </div>

                        <!-- Tipo de entrega -->
                        <div class="space-y-1.5">
                            <label class="block text-[10px] font-bold uppercase tracking-wider text-slate-500">Tipo de Entrega</label>
                            <div class="grid grid-cols-2 gap-2 bg-slate-100 p-1 rounded-xl">
                                <button type="button" id="delivery-type-delivery" onclick="setDeliveryType('delivery')" 
                                        class="py-2 text-[11px] font-bold rounded-lg transition-all bg-white text-slate-800 shadow-sm border border-slate-200">
                                    <i class="bi bi-truck"></i> Delivery
                                </button>
                                <button type="button" id="delivery-type-pickup" onclick="setDeliveryType('pickup')" 
                                        class="py-2 text-[11px] font-bold rounded-lg transition-all text-slate-500 hover:text-slate-800">
                                    <i class="bi bi-shop"></i> Retiro
                                </button>
                            </div>
                            <p id="delivery-cost-note" class="text-[10px] text-amber-600 font-semibold flex items-center gap-1 pl-0.5">
                                <?php if (isset($costo_delivery) && $costo_delivery > 0): ?>
                                    <i class="bi bi-info-circle-fill"></i> Costo de envío: $<?= number_format($costo_delivery, 2) ?>
                                <?php else: ?>
                                    <i class="bi bi-info-circle-fill"></i> Envío gratis o a acordar.
                                <?php endif; ?>
                            </p>
                        </div>

                        <!-- Dirección -->
                        <div id="delivery-address-container" class="space-y-1 transition-all duration-300">
                            <label for="cust-address" class="block text-[10px] font-bold uppercase tracking-wider text-slate-500">Dirección de Entrega</label>
                            <div class="relative">
                                <i class="bi bi-geo-alt-fill absolute left-3 top-2.5 text-slate-400 text-xs pointer-events-none"></i>
                                <textarea id="cust-address" placeholder="Calle, edificio, número, referencias..." rows="2" required
                                          class="w-full pl-8 pr-3 py-2.5 border border-slate-200 rounded-xl text-xs bg-slate-50 text-slate-900 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-primary/30 focus:border-primary transition-all resize-none"></textarea>
                            </div>
                        </div>

                        <!-- Método de pago -->
                        <div class="space-y-1">
                            <label for="cust-payment" class="block text-[10px] font-bold uppercase tracking-wider text-slate-500">Método de Pago</label>
                            <div class="relative">
                                <span class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
                                    <i class="bi bi-credit-card-fill text-slate-400 text-xs"></i>
                                </span>
                                <select id="cust-payment" 
                                        class="w-full pl-8 pr-8 py-2.5 border border-slate-200 rounded-xl text-xs bg-slate-50 text-slate-900 focus:outline-none focus:ring-2 focus:ring-primary/30 focus:border-primary transition-all appearance-none">
                                    <option value="Pago Móvil">Pago Móvil (Bolívares - VES)</option>
                                    <option value="Efectivo Divisas">Efectivo Divisas (USD)</option>
                                    <option value="Zelle">Zelle (USD)</option>
                                    <option value="Efectivo Bs.">Efectivo Bolívares (VES)</option>
                                    <option value="Tarjeta / Punto de Venta">Tarjeta / Punto de Venta</option>
                                </select>
                                <span class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none">
                                    <i class="bi bi-chevron-down text-slate-400 text-[10px]"></i>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Footer del panel (Total + Enviar) -->
            <div class="p-5 border-t border-slate-100 space-y-3 bg-slate-50/80 shrink-0">
                <!-- Desglose de Pedido -->
                <div class="space-y-1 text-xs text-slate-500">
                    <div class="flex justify-between">
                        <span>Subtotal</span>
                        <span id="drawer-subtotal" class="font-bold text-slate-700">$0.00</span>
                    </div>
                    <div id="drawer-delivery-row" class="flex justify-between">
                        <span>Costo de Envío</span>
                        <span id="drawer-delivery-cost" class="font-bold text-slate-700">$0.00</span>
                    </div>
                </div>

                <div class="flex justify-between items-center font-extrabold text-slate-800 border-t border-slate-200/70 pt-2">
                    <span class="text-sm">Total a pagar</span>
                    <div class="text-right">
                        <span id="drawer-total" class="text-lg block text-slate-800">$0.00</span>
                        <?php if (isset($tasa_dolar) && $tasa_dolar > 1): ?>
                            <span id="drawer-total-local" class="text-[10px] font-bold text-slate-500 block mt-0.5 font-mono"></span>
                        <?php endif; ?>
                    </div>
                </div>

                <button onclick="checkoutOrder()" class="w-full py-3.5 px-5 bg-primary hover:opacity-95 text-white font-bold text-sm rounded-xl shadow-lg transition-all flex justify-between items-center active:scale-95 gap-2">
                    <div class="flex items-center gap-2">
                        <i class="bi bi-whatsapp text-base"></i>
                        <span>Enviar por WhatsApp</span>
                    </div>
                    <i class="bi bi-arrow-right font-bold"></i>
                </button>
            </div>
        </div>
    </div>

    <!-- Script de configuración y app -->
    <script>
        window.pronttogoConfig = {
            whatsappNumber: <?= json_encode($config['telefono_whatsapp'] ?? '') ?>,
            costoDelivery: parseFloat(<?= json_encode($costo_delivery ?? 0) ?>),
            tasaDolar: parseFloat(<?= json_encode($tasa_dolar ?? 1) ?>),
            monedaNombre: <?= json_encode($moneda_local_nombre ?? 'USD') ?>,
            tasaTipo: <?= json_encode($tasa_tipo ?? 'manual') ?>,
            nombre: <?= json_encode(!empty($config['nombre']) && $config['nombre'] !== 'Mi Tienda' ? $config['nombre'] : 'PronttoGo') ?>
        };
    </script>
    <script src="/assets/js/main.js"></script>
</body>
</html>
<?php endif; ?>
