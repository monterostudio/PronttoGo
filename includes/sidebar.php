<?php
// includes/sidebar.php
$es_admin = isset($es_admin) ? $es_admin : false;
?>
<?php if ($es_admin): ?>
<!-- Sidebar de Administrador -->
<aside :class="sidebarOpen ? 'translate-x-0' : 'translate-x-full'" class="fixed inset-y-0 right-0 z-50 w-64 bg-slate-900 text-slate-300 transition-transform duration-300 md:relative md:translate-x-0 flex flex-col">
    <div class="h-16 flex items-center justify-between px-5 bg-slate-950 border-b border-slate-800">
        <?= render_logo('admin', $config, true) ?>
        <!-- Botón cerrar solo en móvil -->
        <button @click="sidebarOpen = false" class="md:hidden w-8 h-8 flex items-center justify-center rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-400 hover:text-white transition-colors ml-2">
            <i class="bi bi-x-lg text-sm"></i>
        </button>
    </div>
    
    <nav class="flex-1 px-4 py-6 space-y-2 overflow-y-auto">
        <button @click="currentTab = 'config'; sidebarOpen = false" 
                :class="currentTab === 'config' ? 'bg-indigo-600 text-white' : 'hover:bg-slate-800 hover:text-white'"
                class="w-full flex items-center gap-3 px-4 py-3 rounded-xl transition-colors text-sm font-medium text-left">
            <i class="bi bi-gear-fill text-lg"></i>
            Configuración
        </button>
        <button @click="currentTab = 'categorias'; sidebarOpen = false" 
                :class="currentTab === 'categorias' ? 'bg-indigo-600 text-white' : 'hover:bg-slate-800 hover:text-white'"
                class="w-full flex items-center gap-3 px-4 py-3 rounded-xl transition-colors text-sm font-medium text-left">
            <i class="bi bi-tags-fill text-lg"></i>
            Categorías
        </button>
        <button @click="currentTab = 'productos'; sidebarOpen = false" 
                :class="currentTab === 'productos' ? 'bg-indigo-600 text-white' : 'hover:bg-slate-800 hover:text-white'"
                class="w-full flex items-center gap-3 px-4 py-3 rounded-xl transition-colors text-sm font-medium text-left">
            <i class="bi bi-basket-fill text-lg"></i>
            Productos
        </button>
    </nav>

    <div class="p-4 border-t border-slate-800">
        <form method="POST" action="/admin">
            <?= isset($csrfField) ? $csrfField : '' ?>
            <input type="hidden" name="action" value="logout">
            <button type="submit" class="w-full flex items-center justify-center gap-2 px-4 py-3 text-red-400 hover:bg-slate-800 hover:text-red-300 rounded-xl transition-colors text-sm font-medium">
                <i class="bi bi-box-arrow-right"></i> Cerrar Sesión
            </button>
        </form>
    </div>
</aside>

<!-- Overlay mobile -->
<div x-show="sidebarOpen" @click="sidebarOpen = false" x-cloak class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm z-40 md:hidden"></div>
<?php else: ?>
    <!-- No hay sidebar en la vista pública por ahora, pero se deja el espacio -->
<?php endif; ?>
