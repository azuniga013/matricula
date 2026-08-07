<?php

namespace App\Http\Controllers\Api\V1\Seguridad;

use App\Helpers\RespuestaError;
use App\Http\Controllers\Controller;
use App\Models\PublicacionApkDocente;
use App\Services\ServicioBitacora;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class PublicacionApkDocenteController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json([
            'resultado' => 'A', 'codigo' => 0, 'mensaje' => 'OK',
            'data' => PublicacionApkDocente::query()->with(['creador:id,nombre', 'publicador:id,nombre'])
                ->latest('version_code')->get(),
            'url_publica' => route('apk-docentes.publico'),
            'ruta_storage' => Storage::disk('local')->path('apk-docentes'),
            'diagnostico' => $this->diagnosticoStorage(),
        ]);
    }

    private function diagnosticoStorage(): array
    {
        $disco = Storage::disk('local');
        $carpeta = 'apk-docentes';

        return [
            'raiz_disco' => $disco->path(''),
            'carpeta_absoluta' => $disco->path($carpeta),
            'carpeta_existe' => $disco->directoryExists($carpeta),
            'archivos' => collect($disco->files($carpeta, true))
                ->map(fn (string $ruta) => [
                    'ruta' => $ruta,
                    'tamano' => $disco->size($ruta),
                    'modificado' => date('Y-m-d H:i:s', $disco->lastModified($ruta)),
                ])
                ->sortByDesc('modificado')
                ->values(),
        ];
    }

    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all() + $request->allFiles(), [
            'version' => ['required', 'string', 'max:40'],
            'version_code' => ['required', 'integer', 'min:1', 'unique:publicaciones_apk_docentes,version_code'],
            'archivo' => ['required_unless:desde_servidor,true', 'file', 'extensions:apk', 'max:102400'],
            'desde_servidor' => ['nullable', 'boolean'],
            'notas_version' => ['nullable', 'string', 'max:4000'],
            'publicar' => ['nullable', 'boolean'],
        ]);
        if ($validator->fails()) {
            return RespuestaError::validacion($validator->errors()->toArray())->response($request);
        }

        $datos = $validator->validated();
        $desdeServidor = $request->boolean('desde_servidor');

        if ($desdeServidor) {
            $archivos = $this->apkEnServidor();
            if ($archivos->isEmpty()) {
                $encontrados = collect(Storage::disk('local')->files('apk-docentes', true))->map(fn (string $ruta) => basename($ruta));
                $detalle = $encontrados->isEmpty()
                    ? 'La carpeta '.Storage::disk('local')->path('apk-docentes').' está vacía o no existe en el servidor.'
                    : 'Se encontraron: '.$encontrados->implode(', ').'. El archivo debe tener extensión .apk (se ignora mayúsculas).';

                return RespuestaError::validacion(
                    ['archivo_servidor' => ['No se encontró ningún archivo .apk en '.Storage::disk('local')->path('apk-docentes').'. '.$detalle]],
                    'No hay un APK disponible en el servidor para registrar'
                )->response($request);
            }

            $archivo = $archivos->first();
            $rutaAbsoluta = Storage::disk('local')->path($archivo);
            $hash = hash_file('sha256', $rutaAbsoluta);
            $tamano = Storage::disk('local')->size($archivo);
            $nombre = basename($archivo);
        } else {
            $archivoSubido = $datos['archivo'];
            $hash = hash_file('sha256', $archivoSubido->getRealPath());
            $tamano = $archivoSubido->getSize();
            $nombre = 'CursosSanVicente-Docentes-v'.$datos['version_code'].'-'.substr($hash, 0, 12).'.apk';
            Storage::disk('local')->putFileAs('apk-docentes', $archivoSubido, $nombre);
        }

        $ruta = 'apk-docentes/'.$nombre;

        try {
            $publicacion = DB::transaction(function () use ($datos, $request, $hash, $nombre, $ruta, $tamano) {
                $publicar = (bool) ($datos['publicar'] ?? false);
                if ($publicar) {
                    PublicacionApkDocente::where('publicado', true)->update(['publicado' => false]);
                }

                return PublicacionApkDocente::create([
                    'version' => $datos['version'], 'version_code' => $datos['version_code'],
                    'nombre_archivo' => $nombre, 'ruta_archivo' => $ruta, 'tamano_bytes' => $tamano,
                    'sha256' => $hash, 'notas_version' => $datos['notas_version'] ?? null,
                    'publicado' => $publicar, 'publicado_en' => $publicar ? now() : null,
                    'creado_por' => $request->user()->id, 'publicado_por' => $publicar ? $request->user()->id : null,
                ]);
            });
        } catch (\Throwable $e) {
            Storage::disk('local')->delete($ruta);
            throw $e;
        }

        app(ServicioBitacora::class)->registrarOperacionPermitida($request->user()->id, 'crear_apk_docente', 'distribucion_apk', $request->ip(), $request->userAgent(), $publicacion->id, null, $publicacion->only(['version', 'version_code', 'sha256', 'publicado']));

        return response()->json(['resultado' => 'A', 'codigo' => 201, 'mensaje' => 'APK registrada correctamente', 'data' => $publicacion], 201);
    }

    private function apkEnServidor(): Collection
    {
        return collect(Storage::disk('local')->files('apk-docentes'))
            ->filter(fn (string $ruta) => str_ends_with(strtolower($ruta), '.apk'))
            ->sortByDesc(fn (string $ruta) => Storage::disk('local')->lastModified($ruta))
            ->values();
    }

    public function publicar(Request $request, PublicacionApkDocente $publicacionApkDocente): JsonResponse
    {
        $antes = $publicacionApkDocente->only(['publicado', 'publicado_en', 'publicado_por']);
        DB::transaction(function () use ($publicacionApkDocente, $request) {
            PublicacionApkDocente::where('publicado', true)->whereKeyNot($publicacionApkDocente->id)->update(['publicado' => false]);
            $publicacionApkDocente->update(['publicado' => true, 'publicado_en' => now(), 'publicado_por' => $request->user()->id]);
        });

        app(ServicioBitacora::class)->registrarOperacionPermitida($request->user()->id, 'publicar_apk_docente', 'distribucion_apk', $request->ip(), $request->userAgent(), $publicacionApkDocente->id, $antes, $publicacionApkDocente->fresh()->only(['publicado', 'publicado_en', 'publicado_por']));

        return response()->json(['resultado' => 'A', 'codigo' => 0, 'mensaje' => 'APK publicada en la URL pública', 'data' => $publicacionApkDocente->fresh()]);
    }
}
