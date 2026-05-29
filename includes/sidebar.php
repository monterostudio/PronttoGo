<?php
// includes/sidebar.php
$es_admin = isset($es_admin) ? $es_admin : false;
?>
<?php if ($es_admin): ?>
<!-- Sidebar de Administrador -->
<aside :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'" class="fixed inset-y-0 left-0 z-50 w-64 bg-slate-900 text-slate-300 transition-transform duration-300 md:relative md:translate-x-0 flex flex-col">
    <div class="h-20 flex items-center px-8 bg-slate-950 border-b border-slate-800">
        <i class="bi bi-box-seam text-indigo-400 text-2xl mr-3"></i>
        <span class="text-white font-bold text-xl tracking-tight">PronttoGo</span>
    </div>
    
    <nav class="flex-1 px-4 py-6 space-y-2 overflow-y-auto">
        <button @click="currentTab = 'config'; sidebarOpen = false" 
                :class="currentTab === 'config' ? 'bg-indigo-600 text-white' : 'hover:bg-slate-800 hover:text-white'"
                class="w-full flex items-center gap-3 px-4 py-3 rounded-xl transition-colors text-sm font-medium text-left">
            <i class="bi bi-gear-fill text-lg"></i>
            ConfiguraciÃ³n
        </button>
        <button @click="currentTab = 'categorias'; sidebarOpen = false" 
                :class="currentTab === 'categorias' ? 'bg-indigo-600 text-white' : 'hover:bg-slate-800 hover:text-white'"
                class="w-full flex items-center gap-3 px-4 py-3 rounded-xl transition-colors text-sm font-medium text-left">
            <i class="bi bi-tags-fill text-lg"></i>
            CategorÃ­as
        </button>
        <button @click="currentTab = 'productos'; sidebarOpen = false" 
                :class="currentTab === 'productos' ? 'bg-indigo-600 text-white' : 'hover:bg-slate-800 hover:text-white'"
                class="w-full flex items-center gap-3 px-4 py-3 rounded-xl transition-colors text-sm font-medium text-left">
            <i class="bi bi-basket-fill text-lg"></i>
            Productos
        </button>
    </nav>

    <div class="p-4 border-t border-slate-800">
        <form method="POST" action="/administrador/index.php">
            <?= isset($csrfField) ? $csrfField : '' ?>
            <input type="hidden" name="action" value="logout">
            <button type="submit" class="w-full flex items-center justify-center gap-2 px-4 py-3 text-red-400 hover:bg-slate-800 hover:text-red-300 rounded-xl transition-colors text-sm font-medium">
                <i class="bi bi-box-arrow-right"></i> Cerrar SesiÃ³n
            </button>
        </form>
    </div>
</aside>

<!-- Overlay mobile -->
<div x-show="sidebarOpen" @click="sidebarOpen = false" x-cloak class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm z-40 md:hidden"></div>
<?php else: ?>
    <!-- No hay sidebar en la vista pÃºblica por ahora, pero se deja el espacio -->
<?php endif; ?>
