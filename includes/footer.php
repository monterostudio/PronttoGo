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
        <div onclick="toggleCartDrawer(false)" class="absolute inset-0 bg-slate-950/40 backdrop-blur-sm"></div>
        
        <!-- Panel del Carrito (Panel Lateral) -->
        <div class="relative w-full max-w-[400px] h-full bg-white shadow-2xl flex flex-col transition-transform duration-300 translate-x-full" id="cart-drawer-panel">
            
            <!-- Header del panel -->
            <div class="px-6 py-5 border-b border-slate-100 flex items-center justify-between shrink-0 bg-white">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 bg-primary/10 rounded-full flex items-center justify-center">
                        <i class="bi bi-cart3 text-primary text-lg"></i>
                    </div>
                    <div>
                        <h3 class="font-bold text-lg text-slate-800 leading-tight">Tu Carrito</h3>
                        <p class="text-xs text-slate-500 leading-tight">Revisa tus artículos</p>
                    </div>
                </div>
                <div class="flex items-center gap-3">
                    <button onclick="clearCart()" class="text-xs font-semibold text-red-500 hover:text-red-700 transition-colors px-2 py-1 rounded hover:bg-red-50 flex items-center gap-1">
                        <i class="bi bi-trash"></i> Vaciar
                    </button>
                    <button onclick="toggleCartDrawer(false)" class="w-8 h-8 rounded-full bg-slate-100 hover:bg-slate-200 flex items-center justify-center text-slate-600 transition-colors">
                        <i class="bi bi-x-lg text-sm"></i>
                    </button>
                </div>
            </div>

            <!-- Contenedor scrollable principal con Pasos -->
            <div class="flex-1 overflow-y-auto relative overflow-x-hidden bg-slate-50">
                <!-- PASO 1: Listado de Productos -->
                <div id="cart-step-1" class="absolute inset-0 w-full transition-transform duration-300 transform translate-x-0 bg-slate-50">
                    <div class="p-6 space-y-4 pb-24">
                        <div id="cart-items" class="space-y-3">
                            <!-- Se rellena por JS -->
                        </div>
                    </div>
                </div>

                <!-- PASO 2: Datos de Envío -->
                <div id="cart-step-2" class="absolute inset-0 w-full transition-transform duration-300 transform translate-x-full bg-slate-50 hidden">
                    <div class="p-6 space-y-5 pb-24">
                        <div class="pb-2 border-b border-slate-200">
                            <h4 class="font-bold text-slate-800">Datos de Entrega</h4>
                            <p class="text-xs text-slate-500">Por favor, indícanos cómo y a quién entregaremos tu pedido.</p>
                        </div>

                        <!-- Nombre -->
                        <div class="space-y-1.5">
                            <label for="cust-name" class="block text-xs font-bold text-slate-600">Tu Nombre *</label>
                            <div class="relative">
                                <span class="absolute pointer-events-none text-slate-400" style="left: 12px; top: 50%; transform: translateY(-50%);">
                                    <i class="bi bi-person"></i>
                                </span>
                                <input type="text" id="cust-name" placeholder="Ej. Carlos Mendoza" required
                                       class="w-full pl-9 pr-3 py-2.5 border border-slate-200 rounded-lg text-sm bg-white text-slate-800 placeholder-slate-400 focus:outline-none focus:border-primary transition-colors">
                            </div>
                        </div>

                        <!-- Tipo de entrega -->
                        <div class="space-y-1.5">
                            <label class="block text-xs font-bold text-slate-600">Tipo de Entrega</label>
                            <div class="grid grid-cols-2 gap-2 bg-white p-1 rounded-lg border border-slate-200">
                                <button type="button" id="delivery-type-delivery" onclick="setDeliveryType('delivery')" 
                                        class="py-2 text-sm font-semibold rounded-md transition-all bg-primary text-white shadow-sm" style="display: flex; align-items: center; justify-content: center; gap: 6px;">
                                    <i class="bi bi-truck"></i> <span>Delivery</span>
                                </button>
                                <button type="button" id="delivery-type-pickup" onclick="setDeliveryType('pickup')" 
                                        class="py-2 text-sm font-semibold rounded-md transition-all text-slate-600 hover:bg-slate-100" style="display: flex; align-items: center; justify-content: center; gap: 6px;">
                                    <i class="bi bi-shop"></i> <span>Retiro</span>
                                </button>
                            </div>
                            <p id="delivery-cost-note" class="text-xs text-amber-600 font-medium flex items-center gap-1.5 pt-1">
                                <?php if (isset($costo_delivery) && $costo_delivery > 0): ?>
                                    <i class="bi bi-info-circle"></i> Costo de envío: $<?= number_format($costo_delivery, 2) ?>
                                <?php else: ?>
                                    <i class="bi bi-info-circle"></i> Envío gratis o a acordar.
                                <?php endif; ?>
                            </p>
                        </div>

                        <!-- Dirección -->
                        <div id="delivery-address-container" class="space-y-1.5 transition-all duration-300">
                            <label for="cust-address" class="block text-xs font-bold text-slate-600">Dirección de Entrega *</label>
                            <div class="relative">
                                <span class="absolute pointer-events-none text-slate-400" style="left: 12px; top: 12px;">
                                    <i class="bi bi-geo-alt"></i>
                                </span>
                                <textarea id="cust-address" placeholder="Calle, edificio, número, referencias..." rows="3" required
                                          class="w-full pl-9 pr-3 py-2.5 border border-slate-200 rounded-lg text-sm bg-white text-slate-800 placeholder-slate-400 focus:outline-none focus:border-primary transition-colors resize-none"></textarea>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Footer del panel (Total + Enviar) -->
            <div class="p-6 border-t border-slate-200 bg-white shrink-0 shadow-[0_-10px_20px_-10px_rgba(0,0,0,0.05)]">
                <!-- Desglose de Pedido -->
                <div class="space-y-2 mb-4">
                    <div class="flex justify-between text-sm text-slate-600">
                        <span>Subtotal</span>
                        <span id="drawer-subtotal" class="font-medium">$0.00</span>
                    </div>
                    <div id="drawer-delivery-row" class="flex justify-between text-sm text-slate-600">
                        <span>Costo de Envío</span>
                        <span id="drawer-delivery-cost" class="font-medium">$0.00</span>
                    </div>
                    <div class="flex justify-between items-center border-t border-slate-100 pt-3 mt-3">
                        <span class="font-bold text-slate-800">Total a pagar</span>
                        <div class="text-right">
                            <span id="drawer-total" class="font-bold text-xl text-primary">$0.00</span>
                            <?php if (isset($tasa_dolar) && $tasa_dolar > 1): ?>
                                <span id="drawer-total-local" class="text-xs font-semibold text-slate-500 block"></span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Botones Paso 1 -->
                <div id="cart-buttons-step1" class="w-full">
                    <button onclick="goToCartStep(2)" class="w-full py-3.5 bg-primary hover:bg-primary-hover text-white font-bold rounded-lg shadow-md transition-all flex justify-center items-center gap-2">
                        <span>Continuar</span>
                        <i class="bi bi-arrow-right"></i>
                    </button>
                </div>
                
                <!-- Botones Paso 2 -->
                <div id="cart-buttons-step2" class="w-full hidden flex gap-3">
                    <button onclick="goToCartStep(1)" class="w-12 shrink-0 bg-white border border-slate-300 text-slate-600 hover:bg-slate-50 rounded-lg transition-all flex justify-center items-center">
                        <i class="bi bi-arrow-left"></i>
                    </button>
                    <button onclick="checkoutOrder()" class="flex-1 py-3.5 bg-[#25D366] hover:bg-[#128C7E] text-white font-bold rounded-lg shadow-md transition-all flex justify-center items-center gap-2">
                        <i class="bi bi-whatsapp"></i>
                        <span>Pedir por WhatsApp</span>
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
