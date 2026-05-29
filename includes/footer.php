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

    <!-- Carrito Flotante (JS) -->
    <div id="floating-cart" class="fixed bottom-0 left-0 right-0 p-4 bg-transparent max-w-md mx-auto z-40 hidden">
        <button onclick="toggleCartDrawer(true)" class="w-full py-4 px-6 bg-primary hover:opacity-95 text-white font-bold text-sm rounded-2xl shadow-xl flex justify-between items-center transition-all active:scale-98">
            <div class="flex items-center space-x-2">
                <span><i class="bi bi-clock-fill text-primary"></i></span>
                <span id="cart-count">0 artículos</span>
            </div>
            <div class="text-right">
                <span id="cart-total" class="block font-black text-sm md:text-base">$0.00</span>
                <?php if (isset($tasa_dolar) && $tasa_dolar > 1): ?>
                    <span id="cart-total-local" class="block text-[10px] opacity-90 font-bold font-mono"></span>
                <?php endif; ?>
            </div>
        </button>
    </div>

    <!-- Drawer del Carrito -->
    <div id="cart-drawer" class="fixed inset-0 z-50 hidden transition-opacity duration-300">
        <div onclick="toggleCartDrawer(false)" class="absolute inset-0 bg-slate-950/40 backdrop-blur-sm"></div>
        
        <!-- Panel Desplizable -->
        <div class="absolute bottom-0 left-0 right-0 max-h-[85vh] bg-white rounded-t-3xl shadow-2xl border-t border-slate-100 flex flex-col max-w-md mx-auto overflow-hidden">
            <div class="px-6 py-5 border-b border-slate-50 flex items-center justify-between">
                <div>
                    <h3 class="font-extrabold text-lg text-slate-800">Mi Pedido</h3>
                    <p class="text-xs text-slate-400">Verifica los artículos seleccionados</p>
                </div>
                <div class="flex items-center space-x-3">
                    <button onclick="clearCart()" class="text-xs font-bold text-red-500 hover:text-red-700 transition-colors">
                        Vaciar
                    </button>
                    <button onclick="toggleCartDrawer(false)" class="w-8 h-8 rounded-full bg-slate-50 hover:bg-slate-100 flex items-center justify-center text-slate-500 transition-colors">
                        <i class="bi bi-x-lg text-xs"></i>
                    </button>
                </div>
            </div>

            <!-- Contenedor scrollable principal -->
            <div class="flex-1 overflow-y-auto p-5 sm:p-6 space-y-6">
                <!-- Listado de Productos -->
                <div id="cart-items" class="space-y-1 divide-y divide-slate-100">
                    <!-- Se rellena por JS de forma segura -->
                </div>

                <!-- Formulario de Datos del Cliente -->
                <div id="customer-data-form" class="border-t border-slate-100 pt-5 space-y-4">
                    <div class="border-b border-slate-50 pb-2">
                        <h4 class="font-extrabold text-sm text-slate-800">Datos del Cliente</h4>
                        <p class="text-[10px] text-slate-400">Completa esta información para procesar tu pedido.</p>
                    </div>

                    <!-- Nombre -->
                    <div class="space-y-1.5">
                        <label for="cust-name" class="block text-[10px] font-bold uppercase tracking-wider text-slate-500">Tu Nombre</label>
                        <div class="relative">
                            <span class="absolute left-3 top-1/2 -translate-y-1/2 text-xs"><i class="bi bi-person-fill"></i></span>
                            <input type="text" id="cust-name" placeholder="Ej. Carlos Mendoza" required
                                   class="w-full pl-8 pr-3 py-2 border border-slate-200 rounded-xl text-xs bg-slate-50/50 text-slate-900 placeholder-slate-400 focus:outline-none focus:ring-1 focus:ring-primary focus:border-primary transition-all">
                        </div>
                    </div>

                    <!-- Tipo de entrega -->
                    <div class="space-y-2">
                        <label class="block text-[10px] font-bold uppercase tracking-wider text-slate-500">Tipo de Entrega</label>
                        <div class="grid grid-cols-2 gap-2 bg-slate-100 p-1 rounded-xl">
                            <button type="button" id="delivery-type-delivery" onclick="setDeliveryType('delivery')" 
                                    class="py-1.5 text-[11px] font-bold rounded-lg transition-all bg-white text-slate-800 shadow-sm border border-slate-100">
                                <i class="bi bi-truck"></i> Delivery
                            </button>
                            <button type="button" id="delivery-type-pickup" onclick="setDeliveryType('pickup')" 
                                    class="py-1.5 text-[11px] font-bold rounded-lg transition-all text-slate-500 hover:text-slate-800">
                                <i class="bi bi-bag-check-fill"></i> Retiro
                            </button>
                        </div>
                        <p id="delivery-cost-note" class="text-[10px] text-amber-600 font-semibold flex items-center gap-1 mt-1 pl-1">
                            <?php if (isset($costo_delivery) && $costo_delivery > 0): ?>
                                <i class="bi bi-truck"></i> Costo de envío: $<?= number_format($costo_delivery, 2) ?>
                            <?php else: ?>
                                <i class="bi bi-truck"></i> Envío gratis o a acordar con el vendedor.
                            <?php endif; ?>
                        </p>
                    </div>

                    <!-- Dirección -->
                    <div id="delivery-address-container" class="space-y-1.5 transition-all duration-300">
                        <label for="cust-address" class="block text-[10px] font-bold uppercase tracking-wider text-slate-500">Dirección de Entrega</label>
                        <div class="relative">
                            <span class="absolute left-3 top-3 text-slate-400 text-sm"><i class="bi bi-geo-alt-fill"></i></span>
                            <textarea id="cust-address" placeholder="Indica calle, edificio, nro de casa y puntos de referencia..." rows="2" required
                                      class="w-full pl-8 pr-3 py-2 border border-slate-200 rounded-xl text-xs bg-slate-50/50 text-slate-900 placeholder-slate-400 focus:outline-none focus:ring-1 focus:ring-primary focus:border-primary transition-all resize-none"></textarea>
                        </div>
                    </div>

                    <!-- Método de pago -->
                    <div class="space-y-1.5">
                        <label for="cust-payment" class="block text-[10px] font-bold uppercase tracking-wider text-slate-500">Método de Pago</label>
                        <div class="relative">
                            <span class="absolute left-3 top-1/2 -translate-y-1/2 text-xs"><i class="bi bi-credit-card-fill"></i></span>
                            <select id="cust-payment" 
                                    class="w-full pl-8 pr-3 py-2 border border-slate-200 rounded-xl text-xs bg-slate-50/50 text-slate-900 focus:outline-none focus:ring-1 focus:ring-primary focus:border-primary transition-all appearance-none pr-8">
                                <option value="Pago Móvil">Pago Móvil (Bolívares - VES)</option>
                                <option value="Efectivo Divisas">Efectivo Divisas (Dólares - USD)</option>
                                <option value="Zelle">Zelle (Dólares - USD)</option>
                                <option value="Efectivo Bs.">Efectivo Bolívares (VES)</option>
                                <option value="Tarjeta / Punto de Venta">Tarjeta / Punto de Venta</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>

            <div class="p-6 border-t border-slate-50 space-y-4 bg-slate-50/50">
                <!-- Desglose de Pedido -->
                <div class="space-y-1.5 text-xs text-slate-500 border-b border-slate-200/50 pb-3">
                    <div class="flex justify-between">
                        <span>Subtotal</span>
                        <span id="drawer-subtotal" class="font-bold text-slate-700">$0.00</span>
                    </div>
                    <div id="drawer-delivery-row" class="flex justify-between">
                        <span>Costo de Envío</span>
                        <span id="drawer-delivery-cost" class="font-bold text-slate-700">$0.00</span>
                    </div>
                </div>

                <div class="flex justify-between items-center font-extrabold text-slate-800 pt-1">
                    <span>Total a pagar</span>
                    <div class="text-right">
                        <span id="drawer-total" class="text-xl block text-slate-800">$0.00</span>
                        <?php if (isset($tasa_dolar) && $tasa_dolar > 1): ?>
                            <span id="drawer-total-local" class="text-xs font-bold text-slate-500 block mt-0.5 font-mono"></span>
                        <?php endif; ?>
                    </div>
                </div>
                <button onclick="checkoutOrder()" class="w-full py-4 px-6 bg-primary hover:opacity-95 text-white font-bold text-sm rounded-xl shadow-lg transition-all flex justify-between items-center active:scale-95">
                    <span>Enviar Pedido por WhatsApp</span>
                    <span><i class="bi bi-whatsapp"></i></span>
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
