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
                <span class="text-primary font-extrabold"><?= !empty($config['logo_url']) || (!empty($config['nombre']) && !in_array(strtolower($config['nombre']), ['pronttogo', 'mi tienda'])) ? 'PronttoGo' : 'Montero Studio' ?></span>
            </a>
        </div>
    </footer>



    <!-- Carrito Flotante (JS) -->
    <div id="floating-cart" class="fixed z-40 hidden pointer-events-none" style="bottom: 24px; left: 0; right: 0; display: flex; justify-content: center; padding: 0 16px;">
        <button onclick="toggleCartDrawer(true)" class="w-full py-3.5 px-6 bg-primary hover:opacity-95 text-white font-bold text-sm rounded-full transition-all active:scale-95 pointer-events-auto" style="max-width: 320px; box-shadow: 0 10px 40px -10px rgba(0,0,0,0.4); border: 1px solid rgba(255,255,255,0.2); display: flex; justify-content: space-between; align-items: center; pointer-events: auto !important; cursor: pointer;">
            <div class="flex items-center space-x-3">
                <div class="relative">
                    <i class="bi bi-bag-fill text-white text-lg"></i>
                    <span id="cart-badge" class="absolute bg-white text-primary text-[10px] font-black rounded-full min-w-[18px] h-[18px] px-1 flex items-center justify-center leading-none shadow-sm" style="top: -6px; right: -8px;">0</span>
                </div>
                <span id="cart-count" class="font-bold tracking-wide">0 arts</span>
            </div>
            <div class="text-right flex flex-col items-end">
                <span id="cart-total" class="block font-black text-base leading-none">$0.00</span>
                <?php if (isset($tasa_dolar) && $tasa_dolar > 1): ?>
                    <span id="cart-total-local" class="block text-[10px] opacity-90 font-bold font-mono" style="margin-top: 2px;"></span>
                <?php endif; ?>
            </div>
        </button>
    </div>

    <!-- Drawer del Carrito (Lateral Derecho) -->
    <div id="cart-drawer" class="fixed inset-0 z-50 transition-opacity duration-300 flex justify-end" style="display: none; visibility: hidden; opacity: 0;">
        <div onclick="toggleCartDrawer(false)" class="absolute inset-0 bg-slate-900/50 backdrop-blur-[2px]"></div>
        
        <!-- Panel del Carrito -->
        <div class="relative w-full max-w-[360px] h-full bg-[#f8fafc] shadow-2xl flex flex-col transition-transform duration-300 translate-x-full" id="cart-drawer-panel">
            
            <!-- Header Compacto -->
            <div class="px-5 py-4 border-b border-slate-200 bg-white flex items-center justify-between shrink-0">
                <div class="flex items-center gap-3">
                    <button onclick="toggleCartDrawer(false)" class="w-8 h-8 flex items-center justify-center text-slate-400 hover:text-slate-700 hover:bg-slate-100 rounded-full transition-colors">
                        <i class="bi bi-arrow-right text-lg"></i>
                    </button>
                    <div class="flex flex-col">
                        <h3 class="font-black text-slate-800 text-base leading-none">Mi Pedido</h3>
                        <span id="cart-header-count" class="text-[11px] text-slate-500 font-semibold mt-1">0 artículos</span>
                    </div>
                </div>
                <button onclick="clearCart()" class="text-[11px] font-bold text-red-500 hover:text-white hover:bg-red-500 transition-colors px-2.5 py-1.5 rounded-md flex items-center gap-1.5 border border-red-100 hover:border-red-500">
                    <i class="bi bi-trash3"></i> Vaciar
                </button>
            </div>

            <!-- Contenedor scrollable -->
            <div class="flex-1 overflow-y-auto relative overflow-x-hidden">
                <!-- PASO 1: Listado de Productos -->
                <div id="cart-step-1" class="absolute inset-0 w-full transition-transform duration-300 transform translate-x-0">
                    <div class="p-4 space-y-3 pb-24">
                        <div id="cart-items" class="space-y-2">
                            <!-- JS inyectará los items aquí. Asegurarse que el CSS de main.js esté adaptado o lo adaptaremos -->
                        </div>
                    </div>
                </div>

                <!-- PASO 2: Datos de Envío -->
                <div id="cart-step-2" class="absolute inset-0 w-full transition-transform duration-300 transform translate-x-full hidden bg-white">
                    <div class="p-5 space-y-4 pb-24">
                        <div class="mb-2">
                            <h4 class="font-bold text-slate-800 text-sm">Detalles de Entrega</h4>
                            <p class="text-[11px] text-slate-500">Completa la información para tu pedido.</p>
                        </div>

                        <!-- Nombre -->
                        <div class="space-y-1">
                            <label for="cust-name" class="block text-[10px] font-bold uppercase tracking-wider text-slate-500">Nombre</label>
                            <div class="relative">
                                <span class="absolute pointer-events-none text-slate-400 flex items-center justify-center w-9 h-full">
                                    <i class="bi bi-person text-sm"></i>
                                </span>
                                <input type="text" id="cust-name" placeholder="Tu nombre y apellido" required
                                       class="w-full pl-9 pr-3 py-2 border border-slate-200 rounded-lg text-sm text-slate-800 placeholder-slate-400 focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary transition-colors bg-slate-50 focus:bg-white">
                            </div>
                        </div>

                        <!-- Tipo de entrega -->
                        <div class="space-y-1 pt-2">
                            <label class="block text-[10px] font-bold uppercase tracking-wider text-slate-500">Método</label>
                            <div class="grid grid-cols-2 gap-2 bg-slate-100 p-1 rounded-lg">
                                <button type="button" id="delivery-type-delivery" onclick="setDeliveryType('delivery')" 
                                        class="py-1.5 text-xs font-bold rounded-md transition-all bg-white text-slate-800 shadow-sm border border-slate-200 flex items-center justify-center gap-1.5">
                                    <i class="bi bi-bicycle"></i> Delivery
                                </button>
                                <button type="button" id="delivery-type-pickup" onclick="setDeliveryType('pickup')" 
                                        class="py-1.5 text-xs font-bold rounded-md transition-all text-slate-500 hover:text-slate-800 flex items-center justify-center gap-1.5">
                                    <i class="bi bi-shop"></i> Retiro
                                </button>
                            </div>
                            <p id="delivery-cost-note" class="text-[11px] text-primary font-semibold flex items-center gap-1 pt-1 pl-1">
                                <?php if (isset($costo_delivery) && $costo_delivery > 0): ?>
                                    <i class="bi bi-info-circle"></i> Delivery: $<?= number_format($costo_delivery, 2) ?>
                                <?php else: ?>
                                    <i class="bi bi-info-circle"></i> Envío gratis
                                <?php endif; ?>
                            </p>
                        </div>

                        <!-- Dirección -->
                        <div id="delivery-address-container" class="space-y-1 pt-2 transition-all duration-300">
                            <label for="cust-address" class="block text-[10px] font-bold uppercase tracking-wider text-slate-500">Dirección</label>
                            <div class="relative">
                                <span class="absolute pointer-events-none text-slate-400 flex items-center justify-center w-9 pt-2.5">
                                    <i class="bi bi-geo-alt text-sm"></i>
                                </span>
                                <textarea id="cust-address" placeholder="Ej. Av. Principal, Edificio Torre A..." rows="2" required
                                          class="w-full pl-9 pr-3 py-2 border border-slate-200 rounded-lg text-sm text-slate-800 placeholder-slate-400 focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary transition-colors resize-none bg-slate-50 focus:bg-white"></textarea>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Footer del panel -->
            <div class="p-5 border-t border-slate-200 bg-white shrink-0">
                <!-- Desglose de Pedido -->
                <div class="space-y-1 mb-4">
                    <div class="flex justify-between text-[13px] text-slate-500 font-medium">
                        <span>Subtotal</span>
                        <span id="drawer-subtotal">$0.00</span>
                    </div>
                    <div id="drawer-delivery-row" class="flex justify-between text-[13px] text-slate-500 font-medium">
                        <span>Delivery</span>
                        <span id="drawer-delivery-cost">$0.00</span>
                    </div>
                    <div class="flex justify-between items-end pt-2 mt-2 border-t border-slate-100">
                        <span class="font-black text-slate-800 text-sm">Total</span>
                        <div class="text-right">
                            <span id="drawer-total" class="font-black text-xl text-primary leading-none block">$0.00</span>
                            <?php if (isset($tasa_dolar) && $tasa_dolar > 1): ?>
                                <span id="drawer-total-local" class="text-[10px] font-bold text-slate-400 block mt-1"></span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Botones Paso 1 -->
                <div id="cart-buttons-step1" class="w-full">
                    <button onclick="goToCartStep(2)" class="w-full py-3 bg-primary hover:bg-primary-hover text-white font-bold text-sm rounded-xl shadow-md shadow-primary/20 transition-all flex justify-center items-center gap-2 active:scale-[0.98]">
                        <span>Completar Pedido</span>
                    </button>
                </div>
                
                <!-- Botones Paso 2 -->
                <div id="cart-buttons-step2" class="w-full hidden flex gap-2">
                    <button onclick="goToCartStep(1)" class="w-11 shrink-0 bg-slate-100 text-slate-600 hover:bg-slate-200 rounded-xl transition-all flex justify-center items-center active:scale-[0.98]">
                        <i class="bi bi-chevron-left"></i>
                    </button>
                    <button onclick="checkoutOrder()" class="flex-1 py-3 bg-[#25D366] hover:bg-[#128C7E] text-white font-bold text-sm rounded-xl shadow-md shadow-[#25D366]/20 transition-all flex justify-center items-center gap-2 active:scale-[0.98]">
                        <i class="bi bi-whatsapp text-base"></i>
                        <span>Enviar por WhatsApp</span>
                    </button>
                </div>
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
    <script src="/assets/js/main.js?v=<?= time() ?>"></script>
</body>
</html>
<?php endif; ?>
