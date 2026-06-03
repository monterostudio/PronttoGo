<?php
$es_admin = false;
/**
 * PronttoGo - Catálogo Público Responsivo (Single-Store)
 * Refactorizado a arquitectura MVC simplificada
 */

require_once __DIR__ . '/../core/config.php';
require_once __DIR__ . '/../core/db.php';
require_once __DIR__ . '/../core/auth.php';

// 1. OBTENER CONFIGURACIÓN DEL LOCAL (Fila única id = 1)
$response = supabase_request('GET', 'configuracion?id=eq.1');

if ($response['success'] && !empty($response['data'])) {
    $config = $response['data'][0];
} else {
    $config = [
        'nombre' => 'PronttoGo',
        'telefono_whatsapp' => '584121234567'
    ];
}

// Configuración de Tasa de Cambio y Moneda Local
$tipo_negocio = $config['tipo_negocio'] ?? 'gastronomia';
$tasa_dolar = floatval($config['tasa_dolar'] ?? 1.00);
$tasa_tipo = $config['tasa_tipo'] ?? 'manual';
$moneda_local_nombre = !empty($config['moneda_nombre']) ? $config['moneda_nombre'] : 'Bs.';
$moneda_local_simbolo = !empty($config['moneda_simbolo']) ? $config['moneda_simbolo'] : 'Bs.';
$costo_delivery = floatval($config['costo_delivery'] ?? 0.00);
$direccion_local = !empty($config['direccion']) ? $config['direccion'] : '';
$horario_local = !empty($config['horario']) ? $config['horario'] : '';

// 2. CONSULTAR CATEGORÍAS (Ordenadas)
$resCategorias = supabase_request('GET', 'categorias?order=orden_visual.asc');
$categorias = $resCategorias['success'] ? $resCategorias['data'] : [];
if (!empty($categorias)) {
    foreach ($categorias as &$c) {
        $c['nombre'] = $c['nombre_categoria'] ?? $c['nombre'] ?? 'Sin Categoría';
    }
    unset($c);
}

// 3. CONSULTAR PRODUCTOS DISPONIBLES
$resProductos = supabase_request('GET', 'productos?disponible=eq.true&order=id.asc');
$productos = $resProductos['success'] ? $resProductos['data'] : [];
if (!empty($productos)) {
    foreach ($productos as &$p) {
        $p['nombre'] = $p['nombre'] ?? $p['nombre_producto'] ?? 'Sin Nombre';
        $p['precio_usd'] = $p['precio_usd'] ?? $p['precio'] ?? 0;
    }
    unset($p);
}

// Agrupar productos por categoría
$productosPorCategoria = [];
foreach ($productos as $prod) {
    $productosPorCategoria[$prod['categoria_id']][] = $prod;
}

// Determinar si hay algún error de conexión o base de datos (visible solo en entorno local)
$dbError = null;
if ($isLocalhost) {
    if (!$resCategorias['success']) {
        $dbError = 'Error de Categorías: ' . ($resCategorias['error'] ?? $resCategorias['raw'] ?? 'Error de conexión.');
    } elseif (!$resProductos['success']) {
        $dbError = 'Error de Productos: ' . ($resProductos['error'] ?? $resProductos['raw'] ?? 'Error de conexión.');
    }
}

// --- VISTAS ---
require_once __DIR__ . '/../includes/header.php';
?>

    <!-- Full Hero Section (Presentación de Ancho Completo Premium en Blanco) -->
    <div class="relative w-full bg-gradient-to-br from-[var(--hero-bg-from)] via-[var(--hero-bg-via)] to-[var(--hero-bg-to)] text-slate-800 overflow-hidden border-b border-slate-200/80 shadow-sm">
        <div class="absolute -right-10 top-0 w-96 h-96 bg-primary/5 rounded-full blur-3xl pointer-events-none"></div>
        <div class="absolute -left-10 bottom-0 w-96 h-96 bg-[#2A3543]/4 rounded-full blur-3xl pointer-events-none"></div>
        <div class="absolute left-1/2 top-1/2 -translate-x-1/2 -translate-y-1/2 w-[600px] h-40 bg-primary/3 rounded-full blur-3xl pointer-events-none"></div>
        
        <div class="max-w-6xl mx-auto px-4 sm:px-6 pt-12 pb-10 md:pt-16 md:pb-12 flex flex-col items-center text-center space-y-4 relative z-10">
            <?= render_logo('hero', $config) ?>

            <?php
            $hero_titulo = !empty($config['hero_titulo']) ? $config['hero_titulo'] : 'Tu catálogo digital, siempre disponible';
            $hero_subtitulo = !empty($config['hero_subtitulo']) ? $config['hero_subtitulo'] : 'Explora nuestros productos, arma tu pedido y envíalo directo por WhatsApp en segundos.';
            ?>

            <div class="space-y-2 max-w-2xl pt-1">
                <h1 class="text-xl md:text-3xl font-extrabold text-[#2A3543] tracking-tight leading-snug">
                    <?= h($hero_titulo) ?>
                </h1>
                <p class="text-sm text-slate-500 leading-relaxed font-medium">
                    <?= h($hero_subtitulo) ?>
                </p>
            </div>
        </div>    <!-- Contenedor del Catálogo React -->
    <main class="max-w-6xl w-full mx-auto px-4 sm:px-6 py-8 flex-1 pb-24 md:pb-12">
        <div id="react-catalog-root"></div>
    </main>

    <script type="text/babel">
        // Inyección de variables de PHP a React
        const categories = <?= json_encode($categorias) ?>;
        const products = <?= json_encode($productos) ?>;
        const tasaDolar = <?= json_encode($tasa_dolar) ?>;
        const tasaTipo = <?= json_encode($tasa_tipo) ?>;
        const monedaLocalSimbolo = <?= json_encode($moneda_local_simbolo) ?>;
        const monedaLocalNombre = <?= json_encode($moneda_local_nombre) ?>;
        const tipoNegocio = <?= json_encode($tipo_negocio) ?>;

        const CatalogApp = () => {
            const [search, setSearch] = React.useState('');
            const [selectedCategory, setSelectedCategory] = React.useState(null);

            // Seleccionar por defecto la primera categoría que tenga productos
            React.useEffect(() => {
                const firstCat = categories.find(cat => products.some(p => String(p.categoria_id) === String(cat.id)));
                if (firstCat) {
                    setSelectedCategory(firstCat.id);
                }
            }, []);

            // Filtrar productos por categoría y por buscador
            const filteredProducts = products.filter(prod => {
                const matchesCategory = selectedCategory ? String(prod.categoria_id) === String(selectedCategory) : true;
                const matchesSearch = search 
                    ? prod.nombre.toLowerCase().includes(search.toLowerCase()) || 
                      (prod.descripcion && prod.descripcion.toLowerCase().includes(search.toLowerCase()))
                    : true;
                return matchesCategory && matchesSearch;
            });

            const handleAdd = (prod, e) => {
                if (prod.stock !== null && parseInt(prod.stock) <= 0) {
                    return;
                }
                if (window.addToCart) {
                    window.addToCart({
                        id: prod.id,
                        nombre: prod.nombre,
                        precio: parseFloat(prod.precio_usd),
                        stock: prod.stock !== null ? parseInt(prod.stock) : null
                    }, e);
                }
            };

            const placeholderIconClass = () => {
                if (tipoNegocio === 'boutique') return 'bi-handbag';
                if (tipoNegocio === 'ferreteria_repuestos') return 'bi-tools';
                if (tipoNegocio === 'belleza_estetica') return 'bi-scissors';
                if (tipoNegocio === 'otros') return 'bi-bag';
                return 'bi-shop'; // gastronomia / default
            };

            return (
                <div className="w-full space-y-6">
                    {products.length === 0 ? (
                        /* Catálogo Vacío */
                        <div className="text-center py-20 max-w-sm mx-auto space-y-3">
                            <div className="w-12 h-12 bg-slate-50 text-slate-400 rounded-full flex items-center justify-center mx-auto text-xl">
                                <i className={`bi ${placeholderIconClass()}`}></i>
                            </div>
                            <h3 className="font-bold text-slate-800 text-sm">El catálogo está vacío</h3>
                            <p className="text-slate-400 text-xs max-w-xs mx-auto leading-relaxed">
                                Aún no se han añadido productos. Inicia sesión en el panel para comenzar a cargar tu catálogo.
                            </p>
                            <div className="pt-2">
                                <a href="/admin" className="inline-flex items-center gap-1.5 px-4 py-2 border border-slate-200 text-slate-600 hover:bg-slate-50 rounded-xl font-bold text-xs transition-all shadow-sm">
                                    Ir al Panel <i className="bi bi-gear-fill"></i>
                                </a>
                            </div>
                        </div>
                    ) : (
                        <>
                            {/* Buscador de Productos */}
                            <div className="relative w-full shadow-sm rounded-2xl bg-white border border-slate-100 p-2 flex items-center space-x-2.5 transition-all focus-within:ring-2 focus-within:ring-primary/30 focus-within:border-primary">
                                <div className="pl-3.5 text-slate-400">
                                    <i className="bi bi-search"></i>
                                </div>
                                <input 
                                    type="text" 
                                    placeholder="Buscar productos..." 
                                    value={search}
                                    onChange={(e) => setSearch(e.target.value)}
                                    className="w-full bg-transparent border-0 outline-none text-slate-800 text-sm placeholder-slate-400 pr-4 py-1.5"
                                    autoComplete="off"
                                />
                                {search && (
                                    <button 
                                        onClick={() => setSearch('')} 
                                        className="pr-3 text-slate-400 hover:text-slate-600 font-bold text-sm transition-colors"
                                    >
                                        <i className="bi bi-x-lg text-xs"></i>
                                    </button>
                                )}
                            </div>

                            {/* Categorías Deslizables */}
                            {categories.length > 0 && (
                                <div className="-mx-4 sm:-mx-6 border-y border-slate-100 bg-white/95 backdrop-blur-sm sticky top-16 z-20 shadow-sm">
                                    <nav 
                                        className="flex overflow-x-auto no-scrollbar scroll-smooth"
                                        style={{padding: '10px 16px', gap: '8px', scrollSnapType: 'x mandatory'}}
                                    >
                                        {categories.map(cat => {
                                            const hasProducts = products.some(p => String(p.categoria_id) === String(cat.id));
                                            if (!hasProducts) return null;
                                            const isActive = String(selectedCategory) === String(cat.id);
                                            return (
                                                <button
                                                    key={cat.id}
                                                    onClick={() => setSelectedCategory(cat.id)}
                                                    style={{scrollSnapAlign: 'start', flexShrink: 0}}
                                                    className={`px-4 py-2 border rounded-xl font-bold text-xs whitespace-nowrap transition-all duration-200 active:scale-95 ${
                                                        isActive 
                                                            ? 'bg-primary border-primary text-white shadow-sm' 
                                                            : 'bg-slate-50 border-slate-100 text-slate-600 hover:bg-slate-100'
                                                    }`}
                                                >
                                                    {cat.nombre}
                                                </button>
                                            );
                                        })}
                                        {/* Espaciador al final para que el último elemento sea accesible */}
                                        <div style={{flexShrink: 0, width: '4px'}}></div>
                                    </nav>
                                </div>
                            )}

                            {/* Listado de Productos */}
                            {filteredProducts.length === 0 ? (
                                <div className="text-center py-16 space-y-3">
                                    <div className="w-12 h-12 bg-slate-50 text-slate-400 rounded-full flex items-center justify-center mx-auto text-xl">
                                        <i className="bi bi-search text-slate-350 text-xl"></i>
                                    </div>
                                    <h3 className="font-bold text-slate-800 text-sm">No se encontraron productos</h3>
                                    <p className="text-slate-400 text-xs max-w-xs mx-auto leading-relaxed">
                                        Intenta con otra palabra clave o explora las categorías del catálogo.
                                    </p>
                                </div>
                            ) : (
                                <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                                    {filteredProducts.map(prod => {
                                        const formattedPrice = parseFloat(prod.precio_usd).toFixed(2);
                                        const totalLocal = prod.precio_usd * tasaDolar;
                                        const formattedLocal = totalLocal.toLocaleString('es-VE', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

                                        const isAgotado = prod.stock !== null && parseInt(prod.stock) <= 0;
                                        const isStockCritico = prod.stock !== null && parseInt(prod.stock) > 0 && parseInt(prod.stock) <= 5;

                                        return (
                                            <div 
                                                key={prod.id}
                                                onClick={(e) => !isAgotado && handleAdd(prod, e)}
                                                className={`bg-white p-5 md:p-6 rounded-2xl border border-slate-100 shadow-sm transition-all duration-300 flex items-stretch justify-between gap-4 relative group ${
                                                    isAgotado 
                                                        ? 'opacity-65 cursor-not-allowed' 
                                                        : 'cursor-pointer hover:shadow-md hover:border-slate-200'
                                                }`}
                                            >
                                                <div className="flex-1 flex flex-col justify-between min-w-0 py-0.5">
                                                    <div className="space-y-1">
                                                        <h3 className={`font-extrabold text-slate-900 text-sm md:text-base leading-snug transition-colors ${!isAgotado ? 'group-hover:text-primary' : ''}`}>
                                                            {prod.nombre}
                                                        </h3>
                                                        {prod.descripcion && (
                                                            <p className="text-xs text-slate-500 line-clamp-2 md:line-clamp-3 leading-relaxed">
                                                                {prod.descripcion}
                                                            </p>
                                                        )}
                                                    </div>
                                                    <div>
                                                        <span className="block font-black text-sm md:text-base text-slate-900 mt-2">
                                                            ${formattedPrice}
                                                        </span>
                                                        {tasaDolar > 1 && (
                                                            <span className="block text-xs font-bold text-slate-500 mt-0.5">
                                                                {monedaLocalSimbolo} {formattedLocal} {monedaLocalNombre}
                                                            </span>
                                                        )}
                                                        {isAgotado && (
                                                            <span className="inline-flex items-center gap-1 text-[10px] font-bold text-red-500 mt-1.5 bg-red-50 px-2 py-0.5 rounded-md">
                                                                <i className="bi bi-x-circle-fill"></i> Agotado
                                                            </span>
                                                        )}
                                                        {isStockCritico && (
                                                            <span className="inline-flex items-center gap-1 text-[10px] font-bold text-amber-600 mt-1.5 bg-amber-50 px-2 py-0.5 rounded-md">
                                                                <i className="bi bi-exclamation-triangle-fill"></i> ¡Solo quedan {prod.stock}!
                                                            </span>
                                                        )}
                                                    </div>
                                                </div>

                                                {prod.imagen_url ? (
                                                    <div className="flex flex-col items-center justify-between shrink-0 gap-3 w-16 sm:w-20 md:w-24">
                                                        <img 
                                                            src={prod.imagen_url} 
                                                            alt={prod.nombre} 
                                                            className="w-16 h-16 sm:w-20 sm:h-20 md:w-24 md:h-24 object-cover rounded-xl bg-slate-50 border border-slate-100 shadow-sm group-hover:scale-[1.02] transition-transform duration-300"
                                                        />
                                                        <button 
                                                            onClick={(e) => { e.stopPropagation(); !isAgotado && handleAdd(prod, e); }}
                                                            disabled={isAgotado}
                                                            className={`w-full font-bold text-center text-[10px] md:text-xs py-1.5 rounded-full shadow-md transition-all active:scale-95 whitespace-nowrap ${
                                                                isAgotado 
                                                                    ? 'bg-slate-200 text-slate-400 cursor-not-allowed shadow-none' 
                                                                    : 'bg-primary hover:bg-primary-hover text-white'
                                                            }`}
                                                        >
                                                            {isAgotado ? 'Agotado' : '+ Agregar'}
                                                        </button>
                                                    </div>
                                                ) : (
                                                    <div className="flex flex-col items-end justify-between shrink-0 gap-3 w-16 sm:w-20 md:w-24">
                                                        <div className="w-16 sm:w-20 md:w-24 h-16 sm:h-20 md:h-24 rounded-xl bg-slate-50 border border-slate-100 flex items-center justify-center text-slate-350">
                                                            <i className="bi bi-image text-xl"></i>
                                                        </div>
                                                        <button 
                                                            onClick={(e) => { e.stopPropagation(); !isAgotado && handleAdd(prod, e); }}
                                                            disabled={isAgotado}
                                                            className={`w-full font-bold text-center text-[10px] md:text-xs py-1.5 rounded-full shadow-md transition-all active:scale-95 whitespace-nowrap ${
                                                                isAgotado 
                                                                    ? 'bg-slate-200 text-slate-400 cursor-not-allowed shadow-none' 
                                                                    : 'bg-primary hover:bg-primary-hover text-white'
                                                            }`}
                                                        >
                                                            {isAgotado ? 'Agotado' : '+ Agregar'}
                                                        </button>
                                                    </div>
                                                )}
                                            </div>
                                        );
                                    })}
                                </div>
                            )}
                        </>
                    )}
                </div>
            );
        };

        const root = ReactDOM.createRoot(document.getElementById('react-catalog-root'));
        root.render(<CatalogApp />);
    </script>

<?php
require_once __DIR__ . '/../includes/footer.php';
?>
