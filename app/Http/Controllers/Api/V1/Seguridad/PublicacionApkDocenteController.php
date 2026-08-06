<?php

namespace App\Http\Controllers\Api\V1\Seguridad;

use App\Helpers\RespuestaError;
use App\Http\Controllers\Controller;
use App\Models\PublicacionApkDocente;
use App\Services\ServicioBitacora;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
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
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'version' => ['required', 'string', 'max:40'],
            'version_code' => ['required', 'integer', 'min:1', 'unique:publicaciones_apk_docentes,version_code'],
            'archivo' => ['required', 'file', 'extensions:apk', 'max:102400'],
            'notas_version' => ['nullable', 'string', 'max:4000'],
            'publicar' => ['nullable', 'boolean'],
        ]);
        if ($validator->fails()) {
            return RespuestaError::validacion($validator->errors()->toArray())->response($request);
        }

        $datos = $validator->validated();
        $archivo = $datos['archivo'];
        $hash = hash_file('sha256', $archivo->getRealPath());
        $nombre = 'docentes-v'.$datos['version_code'].'-'.substr($hash, 0, 12).'.apk';
        $ruta = 'apk-docentes/'.$nombre;
        Storage::disk('local')->putFileAs('apk-docentes', $archivo, $nombre);

        try {
            $publicacion = DB::transaction(function () use ($datos, $request, $hash, $nombre, $ruta, $archivo) {
                $publicar = (bool) ($datos['publicar'] ?? false);
                if ($publicar) {
                    PublicacionApkDocente::where('publicado', true)->update(['publicado' => false]);
                }

                return PublicacionApkDocente::create([
                    'version' => $datos['version'], 'version_code' => $datos['version_code'],
                    'nombre_archivo' => $nombre, 'ruta_archivo' => $ruta, 'tamano_bytes' => $archivo->getSize(),
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
