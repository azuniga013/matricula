@extends('layouts.portal')
@section('title', 'Certificado Electrónico')
@section('content')
<div x-data="certificadoView()" x-init="load()" x-cloak class="max-w-5xl mx-auto px-4 py-6" data-token="{{ $token }}">

    {{-- Barra de acciones --}}
    <template x-if="!loading && certificado">
        <div class="flex flex-wrap items-center justify-between gap-3 mb-4">
            <div class="inline-flex items-center gap-2 px-3 py-1.5 rounded-full bg-green-100 text-green-700 text-xs font-semibold">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" /></svg>
                Certificado válido y verificado
            </div>
            <div class="flex flex-wrap gap-2">
                <button @click="descargarImagen()" :disabled="descargandoImagen"
                    class="inline-flex items-center gap-2 rounded-lg bg-gray-800 px-4 py-2 text-xs font-semibold text-white hover:bg-gray-900 disabled:opacity-60">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m2.25 15.75 5.159-5.25a3.375 3.375 0 0 1 5.613 0L17.25 15.75M13.5 11.25l1.5-1.5a3.375 3.375 0 0 1 5.613 0L21.75 12M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5" /></svg>
                    <span x-text="descargandoImagen ? 'Generando...' : 'Descargar Imagen (PNG)'"></span>
                </button>
                <a :href="pdfUrl" download
                    class="inline-flex items-center gap-2 rounded-lg bg-brand-600 px-4 py-2 text-xs font-semibold text-white hover:bg-brand-700">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M16.5 12 12 16.5m0 0L7.5 12m4.5 4.5V3" /></svg>
                    Descargar PDF
                </a>
                <button @click="compartir()" class="inline-flex items-center gap-2 rounded-lg border border-gray-300 px-4 py-2 text-xs font-semibold text-gray-700 hover:bg-gray-50">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M7.217 10.907a2.25 2.25 0 1 0 0 2.186m0-2.186c.18.324.283.696.283 1.093s-.103.77-.283 1.093m0-2.186 9.566-5.314m-9.566 7.5 9.566 5.314m0 0a2.25 2.25 0 1 0 3.935 2.186 2.25 2.25 0 0 0-3.935-2.186Zm0-12.814a2.25 2.25 0 1 0 3.933-2.185 2.25 2.25 0 0 0-3.933 2.185Z" /></svg>
                    Compartir
                </button>
            </div>
        </div>
    </template>

    {{-- Loader --}}
    <template x-if="loading">
        <div class="flex items-center justify-center py-24">
            <div class="animate-spin rounded-full h-10 w-10 border-2 border-brand-500/20 border-t-brand-500"></div>
        </div>
    </template>

    {{-- Error --}}
    <template x-if="!loading && !certificado">
        <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-12 text-center">
            <div class="text-red-600 font-semibold text-lg mb-1">Certificado no encontrado</div>
            <p class="text-sm text-gray-500">El enlace no es válido o el certificado no existe. Verifique que la dirección sea la correcta.</p>
        </div>
    </template>

    {{-- Certificado visual (Udemy-style) --}}
    <template x-if="!loading && certificado">
        <div>
            <div id="cert-canvas-wrap" class="rounded-2xl overflow-hidden shadow-md">
                <div class="cert-card">
                    <div class="cf-frame cf-frame-outer"></div>
                    <div class="cf-frame cf-frame-inner"></div>
                    <div class="cf-corner cf-corner-tl"></div>
                    <div class="cf-corner cf-corner-tr"></div>
                    <div class="cf-corner cf-corner-bl"></div>
                    <div class="cf-corner cf-corner-br"></div>

                    <div class="cf-content">
                        <div class="cf-brand-row">
                            <span class="cf-seal">SV</span>
                            <div style="text-align:left">
                                <div class="cf-brand-name">Cursos San Vicente de Paul</div>
                                <div class="cf-sub-brand">Formación Académica · <span x-text="certificado.sucursal || 'Honduras'"></span></div>
                            </div>
                        </div>

                        <div class="cf-title-h1">CERTIFICADO DE APROBACIÓN</div>
                        <div class="cf-title-h2">Se otorga el presente certificado a</div>
                        <div class="cf-decor"></div>

                        <div class="cf-student-name" x-text="certificado.estudiante?.nombre_completo || (certificado.estudiante?.nombre + ' ' + certificado.estudiante?.apellido)"></div>

                        <p class="cf-lead">Por haber aprobado satisfactoriamente el nivel</p>
                        <div class="cf-nivel" x-text="certificado.nivel?.nombre || '-'"></div>

                        <div class="cf-cols">
                            <div class="cf-col cf-col-left">
                                <p class="cf-detail"><strong>Programa:</strong> <span x-text="certificado.plan_estudio || '-'"></span></p>
                                <p class="cf-detail"><strong>Departamento:</strong> <span x-text="certificado.departamento_academico || '-'"></span></p>
                                <p class="cf-detail"><strong>Período:</strong> <span x-text="certificado.periodo || '-'"></span></p>
                                <p class="cf-detail"><strong>Modalidad:</strong> <span x-text="certificado.modalidad || '-'"></span></p>
                                <p class="cf-detail"><strong>Docente:</strong> <span x-text="certificado.docente || '-'"></span></p>
                            </div>

                            <div class="cf-col cf-col-center cf-sign">
                                <div class="cf-sign-line"></div>
                                <div class="cf-sign-name">Coordinación Académica</div>
                                <div class="cf-sign-label">Cursos San Vicente de Paul</div>
                            </div>

                            <div class="cf-col cf-col-right">
                                <img :src="qrUrl" alt="QR de validación" class="cf-qr-img" />
                                <div class="cf-verify-code" x-text="certificado.codigo_verificacion"></div>
                                <div class="cf-verify-url"><span x-text="vistaUrl"></span></div>
                            </div>
                        </div>

                        <div class="cf-meta">
                            <span><strong>Nota final:</strong> <span x-text="certificado.nota_final"></span></span>
                            <span class="cf-dot">·</span>
                            <span><strong>Emitido el:</strong> <span x-text="certificado.emitido_en"></span></span>
                            <span class="cf-dot">·</span>
                            <span><strong>Certificado N°:</strong> <span x-text="certificado.codigo"></span></span>
                        </div>

                        <div class="cf-footer">DOCUMENTO VERIFICABLE · ESCANEE EL CÓDIGO QR PARA VALIDAR · CURSOS SAN VICENTE DE PAUL</div>
                    </div>
                </div>
            </div>

            <p class="text-center text-xs text-gray-400 mt-4">
                Para validar manualmente, visite <span class="font-mono text-gray-600" x-text="vistaUrl"></span> o utilice el código de verificación: <strong class="font-mono text-gray-700" x-text="certificado.codigo_verificacion"></strong>
            </p>
        </div>
    </template>
</div>

<style>
.cert-card {
    position: relative;
    width: 100%;
    aspect-ratio: 1.414 / 1;
    background: #ffffff;
    padding: 4% 5%;
    font-family: 'Inter', 'DejaVu Sans', sans-serif;
    color: #1f2937;
}
.cf-frame { position: absolute; border-radius: 10px; }
.cf-frame-outer { top: 1.5%; right: 1.5%; bottom: 1.5%; left: 1.5%; border: 3px solid #1d4ed8; }
.cf-frame-inner { top: 2.5%; right: 2.5%; bottom: 2.5%; left: 2.5%; border: 1.5px solid #d4af37; }
.cf-corner { position: absolute; width: 2.2%; aspect-ratio: 1; border-radius: 6px; background: #d4af37; }
.cf-corner-tl { top: 1%; left: 1%; }
.cf-corner-tr { top: 1%; right: 1%; }
.cf-corner-bl { bottom: 1%; left: 1%; }
.cf-corner-br { bottom: 1%; right: 1%; }
.cf-content { position: relative; z-index: 2; text-align: center; height: 100%; display: flex; flex-direction: column; justify-content: flex-start; }
.cf-brand-row { display: inline-flex; align-items: center; gap: 10px; justify-content: center; margin-bottom: 6px; }
.cf-seal { width: 38px; height: 38px; border-radius: 50%; background: #1d4ed8; color: #fff; display: inline-flex; align-items: center; justify-content: center; font-weight: 800; font-size: 15px; border: 2px solid #d4af37; }
.cf-brand-name { font-size: 11px; font-weight: 800; letter-spacing: 3px; text-transform: uppercase; color: #1d4ed8; }
.cf-sub-brand { font-size: 9px; color: #6b7280; letter-spacing: 1px; margin-top: 1px; }
.cf-title-h1 { font-size: clamp(20px, 3vw, 30px); font-weight: 800; color: #111827; margin: 8px 0 2px; letter-spacing: 1px; }
.cf-title-h2 { font-size: clamp(11px, 1.4vw, 14px); color: #6b7280; margin-bottom: 12px; font-style: italic; }
.cf-decor { width: 200px; height: 2px; margin: 0 auto 14px; background: linear-gradient(90deg, transparent, #d4af37, transparent); }
.cf-lead { font-size: clamp(11px, 1.3vw, 13px); color: #374151; margin-bottom: 4px; }
.cf-student-name { font-size: clamp(20px, 3vw, 28px); font-weight: 800; color: #1d4ed8; text-transform: uppercase; letter-spacing: 1px; margin: 2px 0 8px; }
.cf-nivel { font-size: clamp(16px, 2.2vw, 20px); font-weight: 700; color: #111827; margin-top: 2px; }
.cf-cols { display: flex; width: 100%; margin-top: 16px; align-items: stretch; }
.cf-col { flex: 1; }
.cf-col-left { text-align: left; padding-right: 12px; flex: 1.5; }
.cf-col-center { text-align: center; }
.cf-col-right { text-align: center; flex: 0.8; }
.cf-detail { font-size: clamp(10px, 1.2vw, 12px); color: #374151; margin: 1px 0; }
.cf-sign { display: flex; flex-direction: column; justify-content: flex-end; }
.cf-sign-line { border-top: 1.5px solid #1d4ed8; width: 70%; margin: 0 auto 3px; }
.cf-sign-name { font-size: 10px; font-weight: 700; color: #111827; }
.cf-sign-label { font-size: 9px; color: #6b7280; }
.cf-qr-img { width: 100px; height: 100px; border: 2px solid #d4af37; border-radius: 8px; padding: 4px; background: #fff; margin: 0 auto; }
.cf-verify-code { font-family: monospace; font-size: 10px; font-weight: 700; color: #1d4ed8; letter-spacing: 2px; margin-top: 3px; }
.cf-verify-url { font-size: 6px; color: #9ca3af; word-break: break-all; max-width: 120px; margin: 2px auto 0; }
.cf-meta { margin-top: 14px; font-size: clamp(10px, 1.2vw, 12px); color: #374151; display: flex; gap: 6px; justify-content: center; flex-wrap: wrap; }
.cf-dot { color: #d4af37; }
.cf-footer { margin-top: 12px; padding-top: 8px; border-top: 1px solid #e5e7eb; font-size: 8px; color: #9ca3af; letter-spacing: 1px; }
</style>
@endsection
@section('scripts')
<script src="https://cdn.jsdelivr.net/npm/html2canvas@1.4.1/dist/html2canvas.min.js"></script>
<script>
function certificadoView() {
    return {
        loading: true,
        certificado: null,
        descargandoImagen: false,
        get token() { return this.$root.dataset.token; },
        get pdfUrl() { return `/certificados/${this.token}/pdf`; },
        get vistaUrl() { return `${window.location.origin}/certificados/${this.token}`; },
        get qrUrl() {
            return this.certificado ? `https://quickchart.io/qr?text=${encodeURIComponent(window.location.href)}&size=220&margin=2` : '';
        },
        async load() {
            try {
                const { data } = await window.axios.get(`/api/v1/estudiantes/certificados/${this.token}`);
                if (data.resultado === 'A') this.certificado = data.data;
            } catch (e) {
                this.certificado = null;
            } finally {
                this.loading = false;
            }
        },
        async descargarImagen() {
            const node = document.getElementById('cert-canvas-wrap');
            if (!node) return;
            this.descargandoImagen = true;
            try {
                const canvas = await html2canvas(node, { scale: 2, backgroundColor: '#ffffff', useCORS: true, allowTaint: false });
                const link = document.createElement('a');
                link.download = `certificado-${this.certificado.codigo}.png`;
                link.href = canvas.toDataURL('image/png');
                link.click();
            } catch (e) {
                alert('No se pudo generar la imagen. Intente descargar el PDF.');
            } finally {
                this.descargandoImagen = false;
            }
        },
        compartir() {
            if (navigator.share) {
                navigator.share({ title: 'Certificado Académico', url: this.vistaUrl }).catch(() => {});
            } else {
                navigator.clipboard?.writeText(this.vistaUrl);
                this.$dispatch('show-toast', { message: 'Enlace copiado', type: 'success' });
            }
        }
    }
}
</script>
@endsection