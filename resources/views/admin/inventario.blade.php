@extends('layouts.admin')
@section('content')
<div x-data="inventario()" x-init="init()">
    <div class="page-header">
        <div>
            <h1 class="page-title">Inventario y Libros</h1>
            <p class="page-subtitle">Catálogo de libros, existencias y kardex</p>
        </div>
        <div class="flex items-center gap-2">
            <button @click="loadAll()" class="btn btn-outline btn-sm">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0 3.181 3.183a8.25 8.25 0 0 0 13.803-3.7M4.031 9.865a8.25 8.25 0 0 1 13.803-3.7l3.181 3.182" /></svg>
            </button>
            <button x-show="api.hasPermission('inventario.crear')" @click="openModalLibro()" class="btn btn-primary">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg>
                Nuevo Libro
            </button>
        </div>
    </div>

    {{-- Tabs --}}
    <div class="border-b border-gray-200 mb-6">
        <nav class="flex space-x-1">
            <template x-for="t in [{id:'libros',label:'Catálogo de Libros'},{id:'stock',label:'Existencias'},{id:'kardex',label:'Kardex'}]" :key="t.id">
                <button @click="activeTab = t.id; if(t.id==='kardex') selectKardex()" :class="activeTab === t.id ? 'border-brand-500 text-brand-600' : 'border-transparent text-gray-500 hover:text-gray-700'" class="whitespace-nowrap py-3 px-4 border-b-2 text-sm font-medium" x-text="t.label"></button>
            </template>
        </nav>
    </div>

    {{-- TAB: LIBROS --}}
    <div x-show="activeTab === 'libros'">
        <div class="card mb-6">
            <div class="card-body">
                <div class="flex gap-4">
                    <div class="flex-1">
                        <input x-model="buscarLibro" @input.debounce.300ms="loadLibros()" type="text" placeholder="Buscar por código, título, autor o ISBN..." class="input">
                    </div>
                </div>
            </div>
        </div>

        <template x-if="loadingLibros">
            <div class="flex items-center justify-center py-20"><div class="animate-spin rounded-full h-8 w-8 border-2 border-brand-500/20 border-t-brand-500"></div></div>
        </template>

        <template x-if="!loadingLibros">
            <div class="card">
                <div class="table-container">
                    <table class="table">
                        <thead><tr><th>Código</th><th>Título</th><th>Autor</th><th>Editorial</th><th>Precio</th><th>Niveles</th><th>Estado</th><th class="text-right">Acciones</th></tr></thead>
                        <tbody>
                            <template x-for="l in libros" :key="l.id">
                                <tr>
                                    <td class="font-mono text-xs font-semibold text-brand-600" x-text="l.codigo"></td>
                                    <td class="font-medium" x-text="l.titulo"></td>
                                    <td class="text-gray-500 text-sm" x-text="l.autor || '-'"></td>
                                    <td class="text-gray-500 text-sm" x-text="l.editorial || '-'"></td>
                                    <td class="font-medium">L <span x-text="fmtMonto(l.precio_venta)"></span></td>
                                    <td><span x-text="l.niveles?.map(n => n.codigo).join(', ') || '-'" class="text-xs text-gray-500"></span></td>
                                    <td><span :class="l.estado === 'activo' ? 'badge-success' : 'badge-danger'" class="badge" x-text="l.estado"></span></td>
                                    <td class="text-right">
                                        <button @click="editLibro(l)" class="btn btn-ghost btn-sm">Editar</button>
                                    </td>
                                </tr>
                            </template>
                            <template x-if="libros.length === 0"><tr><td colspan="8" class="text-center text-gray-400 py-8">Sin libros registrados</td></tr></template>
                        </tbody>
                    </table>
                </div>
            </div>
        </template>
    </div>

    {{-- TAB: STOCK --}}
    <div x-show="activeTab === 'stock'">
        <div class="card mb-6">
            <div class="card-body">
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <div>
                        <label class="label">Sucursal</label>
                        <select x-model="filtroStockSucursal" @change="loadStock()" class="input">
                            <option value="">Todas</option>
                            <template x-for="s in sucursales" :key="s.id"><option :value="s.id" x-text="s.nombre"></option></template>
                        </select>
                    </div>
                    <div>
                        <label class="label">Libro</label>
                        <select x-model="filtroStockLibro" @change="loadStock()" class="input">
                            <option value="">Todos</option>
                            <template x-for="l in libros" :key="l.id"><option :value="l.id" x-text="l.codigo + ' - ' + l.titulo"></option></template>
                        </select>
                    </div>
                    <div class="flex items-end">
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="checkbox" x-model="filtroStockBajo" @change="loadStock()" class="rounded border-gray-300 text-brand-600">
                            <span class="text-sm text-gray-600">Solo stock bajo</span>
                        </label>
                    </div>
                </div>
                <div class="mt-3">
                    <button x-show="api.hasPermission('inventario.crear')" @click="openModalStock()" class="btn btn-outline btn-sm">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg>
                        Registrar Stock
                    </button>
                </div>
            </div>
        </div>

        <template x-if="loadingStock">
            <div class="flex items-center justify-center py-20"><div class="animate-spin rounded-full h-8 w-8 border-2 border-brand-500/20 border-t-brand-500"></div></div>
        </template>

        <template x-if="!loadingStock">
            <div class="card">
                <div class="table-container">
                    <table class="table">
                        <thead><tr><th>Libro</th><th>Sucursal</th><th class="text-center">Existencia</th><th class="text-center">Mínimo</th><th class="text-center">Estado</th><th class="text-right">Acciones</th></tr></thead>
                        <tbody>
                            <template x-for="s in stock" :key="s.id">
                                <tr :class="s.existencia_actual <= s.existencia_minima ? 'bg-red-50' : ''">
                                    <td>
                                        <span class="font-medium text-sm" x-text="s.libro?.codigo + ' - ' + s.libro?.titulo"></span>
                                    </td>
                                    <td x-text="s.sucursal?.nombre || '-'"></td>
                                    <td class="text-center font-semibold" :class="s.existencia_actual <= s.existencia_minima ? 'text-red-600' : 'text-gray-900'" x-text="s.existencia_actual"></td>
                                    <td class="text-center text-gray-500" x-text="s.existencia_minima"></td>
                                    <td class="text-center">
                                        <span :class="s.existencia_actual <= s.existencia_minima ? 'badge-danger' : 'badge-success'" class="badge" x-text="s.existencia_actual <= s.existencia_minima ? 'Stock Bajo' : 'OK'"></span>
                                    </td>
                                    <td class="text-right">
                                        <div class="flex items-center justify-end gap-1">
                                            <button @click="ajustarStock(s)" class="btn btn-ghost btn-sm">Ajustar</button>
                                            <button @click="venderLibro(s)" class="btn btn-ghost btn-sm">Vender</button>
                                        </div>
                                    </td>
                                </tr>
                            </template>
                            <template x-if="stock.length === 0"><tr><td colspan="6" class="text-center text-gray-400 py-8">Sin existencias registradas</td></tr></template>
                        </tbody>
                    </table>
                </div>
            </div>
        </template>
    </div>

    {{-- TAB: KARDEX --}}
    <div x-show="activeTab === 'kardex'">
        <div class="card mb-6">
            <div class="card-body">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="label">Registro de inventario</label>
                        <select x-model="kardexInventarioId" @change="loadKardex()" class="input">
                            <option value="">Seleccionar...</option>
                            <template x-for="s in stock" :key="s.id">
                                <option :value="s.id" x-text="(s.libro?.codigo || '') + ' - ' + (s.sucursal?.nombre || '') + ' (actual: ' + s.existencia_actual + ')'"></option>
                            </template>
                        </select>
                    </div>
                </div>
            </div>
        </div>

        <template x-if="loadingKardex">
            <div class="flex items-center justify-center py-20"><div class="animate-spin rounded-full h-8 w-8 border-2 border-brand-500/20 border-t-brand-500"></div></div>
        </template>

        <template x-if="!loadingKardex && kardexData">
            <div class="card">
                <div class="card-body border-b">
                    <div class="grid grid-cols-3 gap-4 text-sm">
                        <div><span class="text-gray-500">Libro:</span> <span class="font-medium" x-text="kardexData.libro?.codigo + ' - ' + kardexData.libro?.titulo"></span></div>
                        <div><span class="text-gray-500">Sucursal:</span> <span class="font-medium" x-text="kardexData.sucursal?.nombre"></span></div>
                        <div><span class="text-gray-500">Existencia actual:</span> <span class="font-semibold" x-text="kardexData.existencia_actual"></span></div>
                    </div>
                </div>
                <div class="table-container">
                    <table class="table">
                        <thead><tr><th>Fecha</th><th>Tipo</th><th class="text-center">Cantidad</th><th class="text-center">Antes</th><th class="text-center">Después</th><th>Motivo</th></tr></thead>
                        <tbody>
                            <template x-for="m in kardexData.movimientos" :key="m.id">
                                <tr>
                                    <td class="text-xs text-gray-500" x-text="fmtFecha(m.creado_en)"></td>
                                    <td><span :class="m.tipo_movimiento === 'entrada' ? 'badge-success' : 'badge-danger'" class="badge" x-text="m.tipo_movimiento"></span></td>
                                    <td class="text-center font-medium" x-text="m.cantidad"></td>
                                    <td class="text-center text-gray-500" x-text="m.existencia_antes"></td>
                                    <td class="text-center font-semibold" x-text="m.existencia_despues"></td>
                                    <td class="text-sm text-gray-500" x-text="m.motivo || '-'"></td>
                                </tr>
                            </template>
                            <template x-if="!kardexData.movimientos?.length"><tr><td colspan="6" class="text-center text-gray-400 py-8">Sin movimientos</td></tr></template>
                        </tbody>
                    </table>
                </div>
            </div>
        </template>
    </div>

    {{-- MODAL: Libro --}}
    <div x-show="showModalLibro" class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-black/50" @click="showModalLibro = false"></div>
        <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-lg max-h-[90vh] overflow-y-auto">
            <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100">
                <h3 class="text-lg font-semibold text-gray-900" x-text="editandoLibro ? 'Editar Libro' : 'Nuevo Libro'"></h3>
                <button @click="showModalLibro = false" class="text-gray-400 hover:text-gray-600"><svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" /></svg></button>
            </div>
            <form @submit.prevent="saveLibro()" class="p-6 space-y-4">
                <div class="grid grid-cols-2 gap-4">
                    <div><label class="label">Código</label><input x-model="formLibro.codigo" type="text" required class="input"></div>
                    <div><label class="label">Precio Venta (L)</label><input x-model="formLibro.precio_venta" type="number" step="0.01" min="0" required class="input"></div>
                    <div class="col-span-2"><label class="label">Título</label><input x-model="formLibro.titulo" type="text" required class="input"></div>
                    <div><label class="label">Autor</label><input x-model="formLibro.autor" type="text" class="input"></div>
                    <div><label class="label">Editorial</label><input x-model="formLibro.editorial" type="text" class="input"></div>
                    <div><label class="label">ISBN</label><input x-model="formLibro.isbn" type="text" class="input"></div>
                    <div x-show="editandoLibro"><label class="label">Estado</label><select x-model="formLibro.estado" class="input"><option value="activo">Activo</option><option value="inactivo">Inactivo</option></select></div>
                </div>
                <div x-show="errorLibro" class="bg-red-50 border border-red-200 rounded-lg px-4 py-3"><p class="text-sm text-red-600" x-text="errorLibro"></p></div>
                <div class="flex justify-end gap-3 pt-2">
                    <button type="button" @click="showModalLibro = false" class="btn btn-outline">Cancelar</button>
                    <button type="submit" :disabled="savingLibro" class="btn btn-primary"><span x-text="savingLibro ? 'Guardando...' : 'Guardar'"></span></button>
                </div>
            </form>
        </div>
    </div>

    {{-- MODAL: Ajustar Stock --}}
    <div x-show="showModalAjustar" class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-black/50" @click="showModalAjustar = false"></div>
        <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-md max-h-[90vh] overflow-y-auto">
            <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100">
                <h3 class="text-lg font-semibold text-gray-900">Ajustar Stock</h3>
                <button @click="showModalAjustar = false" class="text-gray-400 hover:text-gray-600"><svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" /></svg></button>
            </div>
            <div class="p-6">
                <p class="text-sm text-gray-500 mb-4" x-text="'Ajustando: ' + (stockTarget?.libro?.codigo || '') + ' - ' + (stockTarget?.libro?.titulo || '') + ' (Actual: ' + (stockTarget?.existencia_actual || 0) + ')'"></p>
                <form @submit.prevent="saveAjustar()" class="space-y-4">
                    <div><label class="label">Cantidad (positiva = entrada, negativa = salida)</label><input x-model="formAjustar.cantidad" type="number" required class="input"></div>
                    <div><label class="label">Motivo</label><input x-model="formAjustar.motivo" type="text" required class="input" placeholder="Ej: Reabastecimiento, daño, etc."></div>
                    <div x-show="errorAjustar" class="bg-red-50 border border-red-200 rounded-lg px-4 py-3"><p class="text-sm text-red-600" x-text="errorAjustar"></p></div>
                    <div class="flex justify-end gap-3 pt-2">
                        <button type="button" @click="showModalAjustar = false" class="btn btn-outline">Cancelar</button>
                        <button type="submit" :disabled="savingAjustar" class="btn btn-primary"><span x-text="savingAjustar ? 'Guardando...' : 'Ajustar'"></span></button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- MODAL: Vender --}}
    <div x-show="showModalVender" class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-black/50" @click="showModalVender = false"></div>
        <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-md max-h-[90vh] overflow-y-auto">
            <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100">
                <h3 class="text-lg font-semibold text-gray-900">Vender Libro</h3>
                <button @click="showModalVender = false" class="text-gray-400 hover:text-gray-600"><svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" /></svg></button>
            </div>
            <div class="p-6">
                <p class="text-sm text-gray-500 mb-4" x-text="'Vendiendo: ' + (stockTarget?.libro?.codigo || '') + ' - ' + (stockTarget?.libro?.titulo || '') + ' (Disponible: ' + (stockTarget?.existencia_actual || 0) + ', Precio: L ' + fmtMonto(stockTarget?.libro?.precio_venta || 0) + ')'"></p>
                <form @submit.prevent="saveVender()" class="space-y-4">
                    <div><label class="label">Cantidad</label><input x-model="formVender.cantidad" type="number" min="1" required class="input"></div>
                    <div><label class="label">Motivo (opcional)</label><input x-model="formVender.motivo" type="text" class="input" placeholder="Venta a estudiante"></div>
                    <div x-show="errorVender" class="bg-red-50 border border-red-200 rounded-lg px-4 py-3"><p class="text-sm text-red-600" x-text="errorVender"></p></div>
                    <div class="flex justify-end gap-3 pt-2">
                        <button type="button" @click="showModalVender = false" class="btn btn-outline">Cancelar</button>
                        <button type="submit" :disabled="savingVender" class="btn btn-primary"><span x-text="savingVender ? 'Procesando...' : 'Vender'"></span></button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- MODAL: Registrar Stock --}}
    <div x-show="showModalStock" class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-black/50" @click="showModalStock = false"></div>
        <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-md max-h-[90vh] overflow-y-auto">
            <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100">
                <h3 class="text-lg font-semibold text-gray-900">Registrar Stock</h3>
                <button @click="showModalStock = false" class="text-gray-400 hover:text-gray-600"><svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" /></svg></button>
            </div>
            <form @submit.prevent="saveStock()" class="p-6 space-y-4">
                <div><label class="label">Libro</label><select x-model="formStock.libro_id" required class="input"><option value="">Seleccionar...</option><template x-for="l in libros" :key="l.id"><option :value="l.id" x-text="l.codigo + ' - ' + l.titulo"></option></template></select></div>
                <div><label class="label">Sucursal</label><select x-model="formStock.sucursal_id" required class="input"><option value="">Seleccionar...</option><template x-for="s in sucursales" :key="s.id"><option :value="s.id" x-text="s.nombre"></option></template></select></div>
                <div><label class="label">Existencia Actual</label><input x-model="formStock.existencia_actual" type="number" min="0" required class="input"></div>
                <div><label class="label">Existencia Mínima</label><input x-model="formStock.existencia_minima" type="number" min="0" class="input"></div>
                <div x-show="errorStock" class="bg-red-50 border border-red-200 rounded-lg px-4 py-3"><p class="text-sm text-red-600" x-text="errorStock"></p></div>
                <div class="flex justify-end gap-3 pt-2">
                    <button type="button" @click="showModalStock = false" class="btn btn-outline">Cancelar</button>
                    <button type="submit" :disabled="savingStock" class="btn btn-primary"><span x-text="savingStock ? 'Guardando...' : 'Guardar'"></span></button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
function inventario() {
    return {
        activeTab: 'libros',
        loadingLibros: true, loadingStock: true, loadingKardex: false,
        libros: [], stock: [], sucursales: [],
        buscarLibro: '',
        filtroStockSucursal: '', filtroStockLibro: '', filtroStockBajo: false,
        kardexInventarioId: '', kardexData: null,

        showModalLibro: false, editandoLibro: false, savingLibro: false, errorLibro: '',
        formLibro: {},

        showModalStock: false, savingStock: false, errorStock: '',
        formStock: {},

        showModalAjustar: false, savingAjustar: false, errorAjustar: '',
        formAjustar: {}, stockTarget: null,

        showModalVender: false, savingVender: false, errorVender: '',
        formVender: {}, venderTarget: null,

        async init() {
            await Promise.all([this.loadLibros(), this.loadSucursales()]);
        },

        async loadAll() {
            this.loadingLibros = true; this.loadingStock = true;
            await Promise.all([this.loadLibros(), this.loadSucursales(), this.loadStock()]);
        },

        async loadSucursales() {
            try {
                const { data } = await window.axios.get('/api/v1/catalogos-academicos/sucursales', { headers: { Authorization: `Bearer ${localStorage.getItem('auth_token')}` } });
                this.sucursales = data.data?.data || data.data || [];
            } catch(e) {}
        },

        async loadLibros() {
            this.loadingLibros = true;
            try {
                let url = '/api/v1/inventario/libros?';
                if (this.buscarLibro) url += `buscar=${this.buscarLibro}&`;
                const { data } = await window.axios.get(url, { headers: { Authorization: `Bearer ${localStorage.getItem('auth_token')}` } });
                this.libros = data.data || [];
            } catch(e) { console.error(e); }
            finally { this.loadingLibros = false; }
        },

        async loadStock() {
            this.loadingStock = true;
            try {
                let url = '/api/v1/inventario/stock?';
                if (this.filtroStockSucursal) url += `sucursal_id=${this.filtroStockSucursal}&`;
                if (this.filtroStockLibro) url += `libro_id=${this.filtroStockLibro}&`;
                if (this.filtroStockBajo) url += `stock_bajo=1&`;
                const { data } = await window.axios.get(url, { headers: { Authorization: `Bearer ${localStorage.getItem('auth_token')}` } });
                this.stock = data.data || [];
            } catch(e) { console.error(e); }
            finally { this.loadingStock = false; }
        },

        async loadKardex() {
            if (!this.kardexInventarioId) { this.kardexData = null; return; }
            this.loadingKardex = true;
            try {
                const { data } = await window.axios.get(`/api/v1/inventario/kardex?inventario_libro_id=${this.kardexInventarioId}`, { headers: { Authorization: `Bearer ${localStorage.getItem('auth_token')}` } });
                this.kardexData = data.data;
            } catch(e) { console.error(e); }
            finally { this.loadingKardex = false; }
        },

        selectKardex() {
            if (this.stock.length === 0) this.loadStock();
        },

        openModalLibro() {
            this.editandoLibro = false; this.errorLibro = '';
            this.formLibro = { codigo: '', titulo: '', autor: '', editorial: '', isbn: '', precio_venta: '' };
            this.showModalLibro = true;
        },

        editLibro(l) {
            this.editandoLibro = true; this.errorLibro = '';
            this.formLibro = {
                codigo: l.codigo, titulo: l.titulo, autor: l.autor||'', editorial: l.editorial||'',
                isbn: l.isbn||'', precio_venta: l.precio_venta, estado: l.estado,
            };
            this.editLibroId = l.id;
            this.showModalLibro = true;
        },

        async saveLibro() {
            this.savingLibro = true; this.errorLibro = '';
            try {
                const url = this.editandoLibro ? `/api/v1/inventario/libros/${this.editLibroId}` : '/api/v1/inventario/libros';
                const { data } = await window.api.actualizar(url, this.formLibro, { headers: { Authorization: `Bearer ${localStorage.getItem('auth_token')}` } });
                if (data.resultado === 'A') {
                    this.showModalLibro = false;
                    window.dispatchEvent(new CustomEvent('show-toast', { detail: { message: 'Libro guardado', type: 'success' } }));
                    await this.loadLibros();
                } else { this.errorLibro = data.mensaje || 'Error'; }
            } catch(e) { this.errorLibro = window.extractError(e, 'Error'); }
            finally { this.savingLibro = false; }
        },

        openModalStock() {
            this.errorStock = '';
            this.formStock = { libro_id: '', sucursal_id: '', existencia_actual: 0, existencia_minima: 0 };
            this.showModalStock = true;
        },

        async saveStock() {
            this.savingStock = true; this.errorStock = '';
            try {
                const { data } = await window.axios.post('/api/v1/inventario/stock', this.formStock, { headers: { Authorization: `Bearer ${localStorage.getItem('auth_token')}` } });
                if (data.resultado === 'A') {
                    this.showModalStock = false;
                    window.dispatchEvent(new CustomEvent('show-toast', { detail: { message: 'Stock registrado', type: 'success' } }));
                    await Promise.all([this.loadStock(), this.loadLibros()]);
                } else { this.errorStock = data.mensaje || 'Error'; }
            } catch(e) { this.errorStock = window.extractError(e, 'Error'); }
            finally { this.savingStock = false; }
        },

        ajustarStock(s) {
            this.stockTarget = s; this.errorAjustar = '';
            this.formAjustar = { inventario_libro_id: s.id, cantidad: 0, motivo: '' };
            this.showModalAjustar = true;
        },

        async saveAjustar() {
            this.savingAjustar = true; this.errorAjustar = '';
            try {
                const { data } = await window.axios.post(`/api/v1/inventario/stock/${this.stockTarget.id}/ajustar`, this.formAjustar, { headers: { Authorization: `Bearer ${localStorage.getItem('auth_token')}` } });
                if (data.resultado === 'A') {
                    this.showModalAjustar = false;
                    window.dispatchEvent(new CustomEvent('show-toast', { detail: { message: data.mensaje, type: 'success' } }));
                    await this.loadStock();
                } else { this.errorAjustar = data.mensaje || 'Error'; }
            } catch(e) { this.errorAjustar = window.extractError(e, 'Error'); }
            finally { this.savingAjustar = false; }
        },

        venderLibro(s) {
            this.stockTarget = s; this.errorVender = '';
            this.formVender = { inventario_libro_id: s.id, cantidad: 1, motivo: '' };
            this.showModalVender = true;
        },

        async saveVender() {
            this.savingVender = true; this.errorVender = '';
            try {
                const { data } = await window.axios.post(`/api/v1/inventario/stock/${this.stockTarget.id}/vender`, this.formVender, { headers: { Authorization: `Bearer ${localStorage.getItem('auth_token')}` } });
                if (data.resultado === 'A') {
                    this.showModalVender = false;
                    window.dispatchEvent(new CustomEvent('show-toast', { detail: { message: 'Venta registrada', type: 'success' } }));
                    await this.loadStock();
                } else { this.errorVender = data.mensaje || 'Error'; }
            } catch(e) { this.errorVender = window.extractError(e, 'Error'); }
            finally { this.savingVender = false; }
        },

        fmtMonto(v) { return (parseFloat(v) || 0).toFixed(2); },
        fmtFecha(d) { return window.formatDateLocal(d, { year:'numeric', month:'2-digit', day:'2-digit' }); },
    }
}
</script>
@endsection
