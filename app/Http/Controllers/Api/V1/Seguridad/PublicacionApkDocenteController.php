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
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class PublicacionApkDocenteController extends Controller
{
    public function index(): JsonResponse
    {
        try {
            return response()->json([
                'resultado' => 'A', 'codigo' => 0, 'mensaje' => 'OK',
                'data' => PublicacionApkDocente::query()->with(['creador:id,name', 'publicador:id,name'])
                    ->latest('version_code')->get(),
                'url_publica' => route('apk-docentes.publico'),
                'ruta_storage' => Storage::disk('local')->path('apk-docentes'),
                'diagnostico' => $this->diagnosticoStorage(),
            ]);
        } catch (\Throwable $e) {
            report($e);

            return response()->json([
                'resultado' => 'R', 'codigo' => 500, 'mensaje' => 'Error al consultar publicaciones',
                'error_diagnostico' => get_class($e).': '.$e->getMessage().' @ '.$e->getFile().':'.$e->getLine(),
            ], 500);
        }
    }

    private function diagnosticoStorage(): array
    {
        $disco = Storage::disk('local');
        $carpeta = 'apk-docentes';
        $carpetaVieja = storage_path('app/apk-docentes');

        $listar = function (string $absoluta): array {
            if (! is_dir($absoluta)) {
                return ['ubicacion' => $absoluta, 'existe' => false, 'archivos' => []];
            }

            return [
                'ubicacion' => $absoluta,
                'existe' => true,
                'archivos' => collect(File::allFiles($absoluta))
                    ->map(fn (\SplFileInfo $f) => [
                        'ruta' => $f->getPathname(),
                        'tamano' => $f->getSize(),
                        'modificado' => date('Y-m-d H:i:s', $f->getMTime()),
                    ])
                    ->sortByDesc('modificado')
                    ->values(),
            ];
        };

        try {
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
                'carpeta_objetivo' => $carpetaVieja,
                'lista_objetiva' => $listar($disco->path($carpeta)),
                'lista_vieja' => $listar($carpetaVieja),
                'error' => null,
            ];
        } catch (\Throwable $e) {
            return [
                'ruta_absoluta' => $disco->path($carpeta),
                'carpeta_existe' => null,
                'archivos' => [],
                'error' => get_class($e).': '.$e->getMessage(),
            ];
        }
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
            [$origen, $rutaOrigen] = str_starts_with($archivo, 'disk:') ? ['disk', substr($archivo, 5)] : ['file', substr($archivo, 5)];
            $rutaAbsoluta = $origen === 'disk' ? Storage::disk('local')->path($rutaOrigen) : $rutaOrigen;
            $hash = hash_file('sha256', $rutaAbsoluta);
            $tamano = filesize($rutaAbsoluta);
            $nombre = basename($rutaOrigen);
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
        $privada = collect(Storage::disk('local')->files('apk-docentes'))
            ->map(fn (string $ruta) => 'disk:'.$ruta);

        $carpetaVieja = storage_path('app/apk-docentes');
        $vieja = collect(is_dir($carpetaVieja) ? File::files($carpetaVieja) : [])
            ->map(fn (\SplFileInfo $f) => 'file:'.$f->getPathname());

        return $privada->concat($vieja)
            ->filter(fn (string $item) => str_ends_with(strtolower($item), '.apk'))
            ->sortByDesc(function (string $item) {
                $ruta = str_starts_with($item, 'disk:') ? Storage::disk('local')->path(substr($item, 5)) : substr($item, 5);

                return is_file($ruta) ? filemtime($ruta) : 0;
            })
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
