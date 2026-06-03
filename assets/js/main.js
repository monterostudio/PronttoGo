// assets/js/main.js
// Archivo JavaScript unificado para PronttoGo

document.addEventListener('DOMContentLoaded', () => {
    console.log('PronttoGo App Inicializada.');
});

// Configuración global (lee del objeto inyectado en el footer de la vista pública)
const pronttogoConfig = window.pronttogoConfig || {
    whatsappNumber: '584121234567',
    costoDelivery: 0,
    tasaDolar: 1.0,
    monedaNombre: 'USD',
    tasaTipo: 'manual',
    nombre: 'PronttoGo'
};

const whatsappNumber = pronttogoConfig.whatsappNumber;
const cartKey = 'cart_pronttogo';
const costoDelivery = pronttogoConfig.costoDelivery;

let isScrolling = false;
let scrollTimeout;

// Animación volar al carrito
function triggerFlyAnimation(startElement) {
    const floatingCart = document.getElementById('floating-cart');
    if (!floatingCart) return;

    // Asegurarnos de que el carrito no esté oculto para obtener su posición
    const wasHidden = floatingCart.classList.contains('hidden');
    if (wasHidden) {
        floatingCart.classList.remove('hidden');
    }

    const rect = startElement.getBoundingClientRect();
    const cartRect = floatingCart.getBoundingClientRect();

    if (wasHidden) {
        floatingCart.classList.add('hidden');
    }

    // Crear la partícula
    const particle = document.createElement('div');
    particle.className = 'fixed z-50 w-6 h-6 bg-primary rounded-full pointer-events-none transition-all duration-750 ease-in-out flex items-center justify-center text-white text-[10px] font-black shadow-lg';
    particle.textContent = '+1';
    particle.style.left = `${rect.left + rect.width / 2 - 12}px`;
    particle.style.top = `${rect.top + rect.height / 2 - 12}px`;
    document.body.appendChild(particle);

    // Animar hacia el carrito flotante
    setTimeout(() => {
        particle.style.transform = 'scale(0.3)';
        particle.style.opacity = '0.5';
        particle.style.left = `${cartRect.left + cartRect.width / 2 - 12}px`;
        particle.style.top = `${cartRect.top + cartRect.height / 2 - 12}px`;
    }, 30);

    // Eliminar y aplicar bounce
    setTimeout(() => {
        particle.remove();
        floatingCart.classList.add('scale-105', 'rotate-3');
        setTimeout(() => {
            floatingCart.classList.remove('scale-105', 'rotate-3');
        }, 150);
    }, 720);
}

function handleCategoryLinkClick(e) {
    isScrolling = true;
    clearTimeout(scrollTimeout);
    
    const href = this.getAttribute('href');
    if (href && href.startsWith('#')) {
        const id = href.substring(1);
        setActiveCategory(id);
    }
    
    scrollTimeout = setTimeout(() => {
        isScrolling = false;
    }, 800);
}

// ScrollSpy para Categorías
window.addEventListener('DOMContentLoaded', () => {
    const observerOptions = {
        root: null,
        rootMargin: '-10% 0px -75% 0px',
        threshold: 0
    };

    const observer = new IntersectionObserver((entries) => {
        if (isScrolling) return;
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                const id = entry.target.id;
                setActiveCategory(id);
            }
        });
    }, observerOptions);

    document.querySelectorAll('section[id^="cat-"]').forEach(section => {
        observer.observe(section);
    });

    // Registrar eventos de clic para detener temporalmente el ScrollSpy y fijar el activo inmediatamente
    document.querySelectorAll('.mobile-category-pill').forEach(link => {
        link.addEventListener('click', handleCategoryLinkClick);
    });
});

function setActiveCategory(id) {
    // Activar pill correspondiente en la barra de categorías unificada
    document.querySelectorAll('.mobile-category-pill').forEach(pill => {
        if (pill.getAttribute('href') === `#${id}`) {
            pill.classList.add('active');
            
            // Centrar el elemento en el scroll horizontal de forma suave
            const container = pill.parentElement;
            if (container) {
                const containerRect = container.getBoundingClientRect();
                const pillRect = pill.getBoundingClientRect();
                const scrollLeft = container.scrollLeft;
                const targetScrollLeft = scrollLeft + (pillRect.left - containerRect.left) - (containerRect.width / 2) + (pillRect.width / 2);
                container.scrollTo({
                    left: targetScrollLeft,
                    behavior: 'smooth'
                });
            }
        } else {
            pill.classList.remove('active');
        }
    });
}

function getCart() {
    try {
        const cartData = localStorage.getItem(cartKey);
        return cartData ? JSON.parse(cartData) : [];
    } catch (e) {
        console.error(e);
        return [];
    }
}

function saveCart(cart) {
    try {
        localStorage.setItem(cartKey, JSON.stringify(cart));
        updateCartUI();
    } catch (e) {
        console.error(e);
    }
}

function addToCart(product, event) {
    const evt = event || window.event;
    const success = addToCartWithDetails(product, 1, '');
    if (success && evt && evt.target) {
        triggerFlyAnimation(evt.target);
    }
}

// Agregar al carrito con detalles (notas y cantidad)
function addToCartWithDetails(product, quantity, notes) {
    let cart = getCart();
    
    // Calcular cantidad total actual de este producto en el carrito
    const currentQtyInCart = cart
        .filter(item => item.id === product.id)
        .reduce((sum, item) => sum + item.quantity, 0);
    const targetQty = currentQtyInCart + quantity;
    if (product.stock !== undefined && product.stock !== null) {
        const stockVal = parseInt(product.stock);
        if (targetQty > stockVal) {
            alert(`Lo sentimos, no puedes agregar más de ${stockVal} unidades de este producto (disponibles: ${stockVal}, en tu pedido actual: ${currentQtyInCart}).`);
            return false;
        }
    }
    
    const existingItem = cart.find(item => item.id === product.id && (item.notes || '') === (notes || ''));
    if (existingItem) {
        existingItem.quantity += quantity;
    } else {
        cart.push({
            id: product.id,
            nombre: product.nombre,
            precio: parseFloat(product.precio),
            quantity: quantity,
            notes: notes || '',
            stock: product.stock !== undefined ? product.stock : null
        });
    }
    saveCart(cart);
    
    const bar = document.getElementById('floating-cart');
    if (bar) {
        bar.classList.add('scale-105');
        setTimeout(() => bar.classList.remove('scale-105'), 150);
    }
    return true;
}

function updateQuantity(productId, change, notes = '') {
    let cart = getCart();
    const item = cart.find(item => item.id === productId && (item.notes || '') === (notes || ''));
    if (!item) return;

    if (change > 0 && item.stock !== undefined && item.stock !== null) {
        const stockVal = parseInt(item.stock);
        const totalQtyInCart = cart
            .filter(i => i.id === productId)
            .reduce((sum, i) => sum + i.quantity, 0);
        if (totalQtyInCart + change > stockVal) {
            alert(`No puedes agregar más unidades. El stock máximo disponible para este producto es ${stockVal}.`);
            return;
        }
    }

    item.quantity += change;
    if (item.quantity <= 0) {
        cart = cart.filter(item => !(item.id === productId && (item.notes || '') === (notes || '')));
    }
    saveCart(cart);
    
    if (cart.length === 0) {
        toggleCartDrawer(false);
    }
}

function clearCart() {
    if (confirm('¿Seguro que deseas vaciar tu carrito de compras?')) {
        saveCart([]);
        toggleCartDrawer(false);
    }
}

function toggleCartDrawer(show) {
    const drawer = document.getElementById('cart-drawer');
    if (!drawer) return;
    if (show) {
        drawer.classList.remove('hidden');
        document.body.style.overflow = 'hidden';
        setTimeout(() => drawer.classList.add('opacity-100'), 10);
    } else {
        drawer.classList.remove('opacity-100');
        document.body.style.overflow = '';
        setTimeout(() => drawer.classList.add('hidden'), 300);
    }
}

function updateCartUI() {
    const cart = getCart();
    const floatingCart = document.getElementById('floating-cart');
    const cartCount = document.getElementById('cart-count');
    const cartTotal = document.getElementById('cart-total');
    const drawerTotal = document.getElementById('drawer-total');
    const cartItemsContainer = document.getElementById('cart-items');

    if (!floatingCart) return;

    if (cart.length === 0) {
        floatingCart.classList.add('hidden');
        return;
    }

    let totalItems = 0;
    let totalPrice = 0;
    cart.forEach(item => {
        totalItems += item.quantity;
        totalPrice += item.precio * item.quantity;
    });

    const subtotal = totalPrice;
    let deliveryFee = 0;
    if (currentDeliveryType === 'delivery') {
        deliveryFee = costoDelivery;
    }
    const grandTotal = subtotal + deliveryFee;

    if (cartCount) cartCount.textContent = `${totalItems} ${totalItems === 1 ? 'artículo' : 'artículos'}`;
    if (cartTotal) cartTotal.textContent = `$${grandTotal.toFixed(2)}`;
    
    const subtotalEl = document.getElementById('drawer-subtotal');
    if (subtotalEl) subtotalEl.textContent = `$${subtotal.toFixed(2)}`;
    
    const deliveryCostEl = document.getElementById('drawer-delivery-cost');
    const deliveryRowEl = document.getElementById('drawer-delivery-row');

    if (deliveryCostEl) {
        if (deliveryFee > 0) {
            deliveryCostEl.textContent = `$${deliveryFee.toFixed(2)}`;
            if (deliveryRowEl) deliveryRowEl.classList.remove('hidden');
        } else {
            deliveryCostEl.textContent = costoDelivery > 0 ? 'Gratis / Convenir' : 'Gratis';
            if (deliveryRowEl) {
                if (costoDelivery > 0) {
                    deliveryRowEl.classList.add('hidden');
                } else {
                    deliveryRowEl.classList.remove('hidden');
                }
            }
        }
    }
    
    if (drawerTotal) drawerTotal.textContent = `$${grandTotal.toFixed(2)}`;

    // Tasa de cambio local dinámica (lee del objeto inyectado)
    const tasaDolar = pronttogoConfig.tasaDolar;
    const monedaNombre = pronttogoConfig.monedaNombre;
    const tasaTipo = pronttogoConfig.tasaTipo;
    if (tasaDolar > 1) {
        const totalLocal = grandTotal * tasaDolar;
        const formattedLocal = totalLocal.toLocaleString('es-VE', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        
        const cartTotalLocal = document.getElementById('cart-total-local');
        const drawerTotalLocal = document.getElementById('drawer-total-local');
        if (cartTotalLocal) cartTotalLocal.textContent = `${monedaNombre} ${formattedLocal}`;
        if (drawerTotalLocal) drawerTotalLocal.textContent = `${monedaNombre} ${formattedLocal}`;
    }

    if (!cartItemsContainer) return;

    // Limpiar de forma segura
    cartItemsContainer.replaceChildren();

    // Construir DOM seguro sin emojis en botones
    cart.forEach(item => {
        const itemEl = document.createElement('div');
        itemEl.className = "flex flex-col py-4 border-b border-slate-100 first:pt-2 last:border-b-0 gap-2";

        // Fila principal (Info y Controles)
        const mainRow = document.createElement('div');
        mainRow.className = "flex justify-between items-center w-full";

        const infoEl = document.createElement('div');
        
        const nameEl = document.createElement('h4');
        nameEl.className = "font-bold text-sm text-slate-800";
        nameEl.textContent = item.nombre;
        infoEl.appendChild(nameEl);

        const priceEl = document.createElement('p');
        priceEl.className = "text-xs font-semibold text-slate-400 mt-0.5";
        priceEl.textContent = `$${item.precio.toFixed(2)} c/u`;
        infoEl.appendChild(priceEl);

        const controlsEl = document.createElement('div');
        controlsEl.className = "flex items-center space-x-2.5";

        const btnMinus = document.createElement('button');
        btnMinus.className = "w-7 h-7 rounded-xl bg-slate-50 hover:bg-slate-100 flex items-center justify-center font-bold text-sm text-slate-600 transition-colors";
        btnMinus.innerHTML = '<i class="bi bi-dash"></i>';
        btnMinus.onclick = () => updateQuantity(item.id, -1, item.notes);

        const qtyEl = document.createElement('span');
        qtyEl.className = "text-sm font-extrabold w-4 text-center text-slate-800";
        qtyEl.textContent = item.quantity;

        const btnPlus = document.createElement('button');
        btnPlus.className = "w-7 h-7 rounded-xl bg-slate-50 hover:bg-slate-100 flex items-center justify-center font-bold text-sm text-slate-600 transition-colors";
        btnPlus.innerHTML = '<i class="bi bi-plus"></i>';
        btnPlus.onclick = () => updateQuantity(item.id, 1, item.notes);

        const btnRemove = document.createElement('button');
        btnRemove.className = "w-7 h-7 rounded-xl bg-red-50 hover:bg-red-100 flex items-center justify-center text-red-500 hover:text-red-700 transition-colors ml-1.5 font-bold text-xs";
        btnRemove.innerHTML = '<i class="bi bi-trash"></i>';
        btnRemove.onclick = () => {
            let cart = getCart().filter(i => !(i.id === item.id && (i.notes || '') === (item.notes || '')));
            saveCart(cart);
            if (cart.length === 0) {
                toggleCartDrawer(false);
            }
        };

        controlsEl.appendChild(btnMinus);
        controlsEl.appendChild(qtyEl);
        controlsEl.appendChild(btnPlus);
        controlsEl.appendChild(btnRemove);

        mainRow.appendChild(infoEl);
        mainRow.appendChild(controlsEl);
        itemEl.appendChild(mainRow);

        // Fila de notas del producto
        const notesRow = document.createElement('div');
        notesRow.className = "w-full";

        const notesInput = document.createElement('input');
        notesInput.type = "text";
        notesInput.placeholder = "Indica aquí la talla, color, modelo o detalles...";
        notesInput.value = item.notes || '';
        notesInput.className = "w-full px-3 py-1.5 bg-slate-50 border border-slate-200/60 rounded-xl text-[11px] text-slate-700 placeholder-slate-400 focus:outline-none focus:ring-1 focus:ring-primary/40 focus:border-primary transition-all";
        notesInput.onchange = (e) => {
            updateItemNotes(item.id, item.notes, e.target.value.trim());
        };

        notesRow.appendChild(notesInput);
        itemEl.appendChild(notesRow);

        cartItemsContainer.appendChild(itemEl);
    });

    // Actualizar badge en botón flotante
    const cartBadge = document.getElementById('cart-badge');
    if (cartBadge) cartBadge.textContent = totalItems > 99 ? '99+' : totalItems;

    floatingCart.classList.remove('hidden');
}

let currentDeliveryType = 'delivery';

function setDeliveryType(type) {
    currentDeliveryType = type;
    const btnDelivery = document.getElementById('delivery-type-delivery');
    const btnPickup = document.getElementById('delivery-type-pickup');
    const addressContainer = document.getElementById('delivery-address-container');
    const addressInput = document.getElementById('cust-address');
    const costNote = document.getElementById('delivery-cost-note');
    
    if (!btnDelivery || !btnPickup) return;

    if (type === 'delivery') {
        btnDelivery.className = "py-1.5 text-[11px] font-bold rounded-lg transition-all bg-white text-slate-800 shadow-sm border border-slate-100";
        btnPickup.className = "py-1.5 text-[11px] font-bold rounded-lg transition-all text-slate-500 hover:text-slate-800";
        if (addressContainer) addressContainer.classList.remove('hidden');
        if (addressInput) addressInput.setAttribute('required', 'true');
        if (costNote) costNote.classList.remove('hidden');
    } else {
        btnDelivery.className = "py-1.5 text-[11px] font-bold rounded-lg transition-all text-slate-500 hover:text-slate-800";
        btnPickup.className = "py-1.5 text-[11px] font-bold rounded-lg transition-all bg-white text-slate-800 shadow-sm border border-slate-100";
        if (addressContainer) addressContainer.classList.add('hidden');
        if (addressInput) addressInput.removeAttribute('required');
        if (costNote) costNote.classList.add('hidden');
    }
    saveCustomerData();
    updateCartUI();
}

function loadCustomerData() {
    try {
        const name = localStorage.getItem('cust_name') || '';
        const address = localStorage.getItem('cust_address') || '';
        const payment = localStorage.getItem('cust_payment') || 'Pago Móvil';
        const deliveryType = localStorage.getItem('cust_delivery_type') || 'delivery';

        const nameInput = document.getElementById('cust-name');
        const addressInput = document.getElementById('cust-address');
        const paymentInput = document.getElementById('cust-payment');

        if (nameInput) nameInput.value = name;
        if (addressInput) addressInput.value = address;
        if (paymentInput) paymentInput.value = payment;
        
        setDeliveryType(deliveryType);
    } catch (e) {
        console.error(e);
    }
}

function saveCustomerData() {
    try {
        const nameInput = document.getElementById('cust-name');
        const addressInput = document.getElementById('cust-address');
        const paymentInput = document.getElementById('cust-payment');

        const name = nameInput ? nameInput.value.trim() : '';
        const address = addressInput ? addressInput.value.trim() : '';
        const payment = paymentInput ? paymentInput.value : 'Pago Móvil';
        
        localStorage.setItem('cust_name', name);
        localStorage.setItem('cust_address', address);
        localStorage.setItem('cust_payment', payment);
        localStorage.setItem('cust_delivery_type', currentDeliveryType);
    } catch (e) {
        console.error(e);
    }
}

function updateItemNotes(productId, oldNotes, newNotes) {
    let cart = getCart();
    const item = cart.find(item => item.id === productId && (item.notes || '') === (oldNotes || ''));
    if (item) {
        item.notes = newNotes;
        saveCart(cart);
    }
}

function checkoutOrder() {
    const cart = getCart();
    if (cart.length === 0) return;

    // Validar campos de cliente
    const nameInput = document.getElementById('cust-name');
    const clientName = nameInput ? nameInput.value.trim() : '';
    if (!clientName) {
        alert('Por favor, ingresa tu nombre para poder enviar el pedido.');
        if (nameInput) nameInput.focus();
        return;
    }

    const addressInput = document.getElementById('cust-address');
    const clientAddress = addressInput ? addressInput.value.trim() : '';
    if (currentDeliveryType === 'delivery' && !clientAddress) {
        alert('Por favor, ingresa tu dirección para el delivery.');
        if (addressInput) addressInput.focus();
        return;
    }

    const paymentInput = document.getElementById('cust-payment');
    const clientPayment = paymentInput ? paymentInput.value : 'Pago Móvil';

    let totalPrice = 0;
    let itemsText = "";

    cart.forEach(item => {
        totalPrice += item.precio * item.quantity;
        const notesStr = item.notes ? ` (${item.notes})` : '';
        itemsText += `${item.quantity}x ${item.nombre}${notesStr} ($${item.precio.toFixed(2)} c/u)\n`;
    });

    const deliveryFee = currentDeliveryType === 'delivery' ? costoDelivery : 0;
    const grandTotal = totalPrice + deliveryFee;

    // Tasa de cambio local dinámica
    const tasaDolar = pronttogoConfig.tasaDolar;
    const monedaNombre = pronttogoConfig.monedaNombre;
    const tasaTipo = pronttogoConfig.tasaTipo;
    let formattedLocal = "";
    if (tasaDolar > 1) {
        const totalLocal = grandTotal * tasaDolar;
        formattedLocal = totalLocal.toLocaleString('es-VE', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }

    const storeName = pronttogoConfig.nombre;
    const subtotalUsd = `$${totalPrice.toFixed(2)}`;
    const costoEnvio = deliveryFee > 0 ? `$${deliveryFee.toFixed(2)}` : 'Gratis / Retiro';
    const totalUsd = `$${grandTotal.toFixed(2)}`;
    
    let totalMonedaLocal = "";
    if (tasaDolar > 1) {
        totalMonedaLocal = `*Total en ${monedaNombre}: ${monedaNombre} ${formattedLocal}* (tasa: ${tasaDolar.toFixed(2)})`;
    }

    let deliveryText = "";
    if (currentDeliveryType === 'delivery') {
        deliveryText = `📍 *Dirección:* ${clientAddress}`;
    } else {
        deliveryText = `📦 *Despacho:* Retiro en local`;
    }

    let message = `*Pedido de ${storeName}* 🛍️\n` +
                  `--------------------------\n` +
                  `👤 *Cliente:* ${clientName}\n` +
                  `${deliveryText}\n` +
                  `💸 *Pago:* ${clientPayment}\n` +
                  `--------------------------\n` +
                  `${itemsText}` +
                  `--------------------------\n` +
                  `*Subtotal:* ${subtotalUsd}\n` +
                  (deliveryFee > 0 ? `*Envío:* ${costoEnvio}\n` : '') +
                  `*Total a pagar: ${totalUsd}*\n` +
                  (totalMonedaLocal ? `${totalMonedaLocal}\n` : '');

    const encodedText = encodeURIComponent(message);
    const waUrl = `https://wa.me/${whatsappNumber}?text=${encodedText}`;

    window.open(waUrl, '_blank');
}

window.addEventListener('DOMContentLoaded', () => {
    updateCartUI();
    loadCustomerData();

    // Guardar datos del cliente mientras escribe
    const nameInput = document.getElementById('cust-name');
    const addressInput = document.getElementById('cust-address');
    const paymentInput = document.getElementById('cust-payment');

    if (nameInput) nameInput.addEventListener('input', saveCustomerData);
    if (addressInput) addressInput.addEventListener('input', saveCustomerData);
    if (paymentInput) paymentInput.addEventListener('change', saveCustomerData);
});

console.log("Assets JS unificado cargado.");
