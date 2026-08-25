@extends('layouts.admin')
@section('content')
<div x-data="parametrosGlobales()" x-init="init()">
    <div class="page-header">
        <div>
            <h1 class="page-title">Parámetros Globales</h1>
            <p class="page-subtitle">Configuración institucional, formatos y separadores para reportes y exportaciones</p>
        </div>
        <button x-show="api.hasPermission('seguridad.parametros.crear')" @click="openModal()" class="btn btn-primary">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg>
            Nuevo Parámetro
        </button>
    </div>

    {{-- Filtros --}}
    <div class="card mb-6">
        <div class="card-body">
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div>
                    <label class="label">Grupo</label>
                    <select x-model="filtroGrupo" @change="load()" class="input">
                        <option value="">Todos</option>
                        <template x-for="g in grupos" :key="g">
                            <option :value="g" x-text="g"></option>
                        </template>
                    </select>
                </div>
                <div>
                    <label class="label">Buscar</label>
                    <input x-model="busqueda" @input.debounce.300ms="load()" type="text" class="input" placeholder="Código o nombre...">
                </div>
            </div>
        </div>
    </div>

    <template x-if="loading"><div class="flex items-center justify-center py-20"><div class="animate-spin rounded-full h-8 w-8 border-2 border-brand-500/20 border-t-brand-500"></div></div></template>

    <template x-if="!loading">
        <div class="card">
            <div class="table-container">
                <table class="table">
                    <thead><tr><th>Grupo</th><th>Código</th><th>Nombre</th><th>Tipo</th><th>Valor</th><th>Estado</th><th class="text-right">Acciones</th></tr></thead>
                    <tbody>
                        <template x-for="p in parametros" :key="p.id">
                            <tr>
                                <td class="font-mono text-xs" x-text="p.grupo"></td>
                                <td class="font-mono text-xs font-semibold text-brand-600" x-text="p.codigo"></td>
                                <td class="font-medium" x-text="p.nombre"></td>
                                <td><span class="badge badge-info" x-text="p.tipo"></span></td>
                                <td class="text-sm text-gray-700 max-w-xs truncate" x-text="p.valor || '-'"></td>
                                <td><span :class="p.estado ? 'badge-success' : 'badge-danger'" class="badge" x-text="p.estado ? 'Activo' : 'Inactivo'"></span></td>
                                <td class="text-right">
                                    <button x-show="api.hasPermission('seguridad.parametros.modificar')" @click="editItem(p)" class="btn btn-ghost btn-sm">Editar</button>
                                    <button x-show="api.hasPermission('seguridad.parametros.eliminar')" @click="deleteItem(p)" class="btn btn-ghost btn-sm text-red-600">Eliminar</button>
                                </td>
                            </tr>
                        </template>
                        <template x-if="parametros.length === 0">
                            <tr><td colspan="7" class="text-center py-8 text-gray-400">No hay parámetros registrados</td></tr>
                        </template>
                    </tbody>
                </table>
            </div>
        </div>
    </template>

    {{-- Modal --}}
    <div x-show="showModal" class="fixed inset-0 z-50 flex items-center justify-center p-4" x-cloak>
        <div class="absolute inset-0 bg-black/50" @click="showModal = false"></div>
        <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-lg">
            <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100">
                <h3 class="text-lg font-semibold text-gray-900" x-text="editId ? 'Editar Parámetro' : 'Nuevo Parámetro'"></h3>
                <button @click="showModal = false" class="text-gray-400 hover:text-gray-600"><svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" /></svg></button>
            </div>
            <form @submit.prevent="save()" class="p-6 space-y-4">
                <div class="grid grid-cols-2 gap-4">
                    <div><label class="label">Grupo *</label><input x-model="form.grupo" type="text" required maxlength="50" class="input" placeholder="01"></div>
                    <div><label class="label">Tipo *</label>
                        <select x-model="form.tipo" required class="input">
                            <option value="texto">Texto</option>
                            <option value="numero">Número</option>
                            <option value="booleano">Booleano</option>
                            <option value="seleccion">Selección</option>
                        </select>
                    </div>
                </div>
                <div><label class="label">Código *</label><input x-model="form.codigo" type="text" required maxlength="100" class="input" placeholder="EMPRESA_NOMBRE" :disabled="editId !== null"></div>
                <div><label class="label">Nombre *</label><input x-model="form.nombre" type="text" required maxlength="150" class="input" placeholder="Nombre de la institución"></div>
                <div><label class="label">Valor</label><input x-model="form.valor" type="text" class="input" placeholder="Valor del parámetro"></div>
                <template x-if="form.tipo === 'seleccion'">
                    <div><label class="label">Opciones (una por línea)</label><textarea x-model="form.opcionesTexto" rows="3" class="input" placeholder="d/m/Y&#10;Y-m-d&#10;m/d/Y"></textarea></div>
                </template>
                <div><label class="label">Descripción</label><textarea x-model="form.descripcion" rows="2" maxlength="255" class="input" placeholder="Descripción del parámetro"></textarea></div>
                <div><label class="flex items-center gap-2"><input type="checkbox" x-model="form.estado" class="w-4 h-4 text-brand-600 border-gray-300 rounded"> <span class="text-sm font-medium text-gray-700">Activo</span></label></div>
                <template x-if="error"><p class="text-sm text-red-600" x-text="error"></p></template>
                <div class="flex justify-end gap-3 pt-2">
                    <button type="button" @click="showModal = false" class="btn btn-outline">Cancelar</button>
                    <button type="submit" :disabled="saving" class="btn btn-primary"><span x-text="saving ? 'Guardando...' : 'Guardar'"></span></button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
@section('scripts')
<script>
function parametrosGlobales() {
    return {
        loading: true, parametros: [], grupos: [], showModal: false, editId: null, saving: false, error: '',
        filtroGrupo: '', busqueda: '',
        form: { grupo: '01', codigo: '', nombre: '', valor: '', tipo: 'texto', opcionesTexto: '', descripcion: '', estado: true },

        async init() { await this.load(); await this.loadGrupos(); },

        async load() {
            this.loading = true;
            try {
                let url = '/api/v1/seguridad/parametros-globales?';
                if (this.filtroGrupo) url += `grupo=${this.filtroGrupo}&`;
                if (this.busqueda) url += `buscar=${encodeURIComponent(this.busqueda)}&`;
                const { data } = await window.axios.get(url, { headers: { Authorization: `Bearer ${localStorage.getItem('auth_token')}` } });
                this.parametros = data.data || [];
            } catch(e) { this.toast('Error al cargar', 'error'); } finally { this.loading = false; }
        },

        async loadGrupos() {
            try {
                const { data } = await window.axios.get('/api/v1/seguridad/parametros-globales/grupos', { headers: { Authorization: `Bearer ${localStorage.getItem('auth_token')}` } });
                this.grupos = data.data || [];
            } catch(e) {}
        },

        openModal() {
            this.editId = null; this.error = ''; this.showModal = true;
            this.form = { grupo: '01', codigo: '', nombre: '', valor: '', tipo: 'texto', opcionesTexto: '', descripcion: '', estado: true };
        },

        editItem(p) {
            this.editId = p.id; this.error = ''; this.showModal = true;
            this.form = {
                grupo: p.grupo, codigo: p.codigo, nombre: p.nombre, valor: p.valor || '', tipo: p.tipo,
                opcionesTexto: Array.isArray(p.opciones) ? p.opciones.join('\n') : '',
                descripcion: p.descripcion || '', estado: p.estado,
            };
        },

        async save() {
            this.saving = true; this.error = '';
            try {
                const token = localStorage.getItem('auth_token');
                const h = { headers: { Authorization: `Bearer ${token}` } };
                const payload = {
                    grupo: this.form.grupo, codigo: this.form.codigo, nombre: this.form.nombre,
                    valor: this.form.valor || null, tipo: this.form.tipo,
                    descripcion: this.form.descripcion || null, estado: this.form.estado,
                };
                if (this.form.tipo === 'seleccion' && this.form.opcionesTexto) {
                    payload.opciones = this.form.opcionesTexto.split('\n').map(s => s.trim()).filter(s => s);
                }
                if (this.editId) {
                    await window.axios.post(`/api/v1/seguridad/parametros-globales/${this.editId}`, payload, h);
                } else {
                    await window.axios.post('/api/v1/seguridad/parametros-globales', payload, h);
                }
                this.showModal = false; await this.load(); await this.loadGrupos();
                this.toast('Parámetro guardado', 'success');
            } catch(e) { this.error = e.response?.data?.mensaje || 'Error al guardar'; } finally { this.saving = false; }
        },

        async deleteItem(p) {
            if (!confirm(`¿Eliminar el parámetro "${p.codigo}"?`)) return;
            try {
                await window.axios.post(`/api/v1/seguridad/parametros-globales/${p.id}`, { headers: { Authorization: `Bearer ${localStorage.getItem('auth_token')}` } });
                await this.load(); this.toast('Parámetro eliminado', 'success');
            } catch(e) { this.toast('Error al eliminar', 'error'); }
        },

        toast(msg, type) { this.$dispatch('toast', { message: msg, type: type || 'info' }); },
    };
}
</script>
@endsection
