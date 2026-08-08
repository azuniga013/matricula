<?php

namespace App\Http\Controllers;

use App\Models\PublicacionApkDocente;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ApkDocentePublicoController extends Controller
{
    public function index(): View
    {
        return view('publico.apk-docentes', ['publicacion' => $this->publicacionActiva()]);
    }

    public function descargar(): BinaryFileResponse|StreamedResponse|Response
    {
        $publicacion = $this->publicacionActiva();
        abort_unless($publicacion !== null, 404);

        $archivo = $this->archivoExistente($publicacion);
        abort_unless($archivo !== null, 404);

        $nombre = 'Cursos-San-Vicente-Docentes-'.$publicacion->version.'.apk';
        $headers = ['Content-Type' => 'application/vnd.android.package-archive'];

        if (Storage::disk('local')->exists($publicacion->ruta_archivo)) {
            return Storage::disk('local')->download($publicacion->ruta_archivo, $nombre, $headers);
        }

        return response()->download($archivo, $nombre, $headers);
    }

    private function archivoExistente(PublicacionApkDocente $publicacion): ?string
    {
        if (Storage::disk('local')->exists($publicacion->ruta_archivo)) {
            return $publicacion->ruta_archivo;
        }

        $vieja = storage_path('app/apk-docentes/'.basename($publicacion->ruta_archivo));

        return is_file($vieja) ? $vieja : null;
    }

    private function publicacionActiva(): ?PublicacionApkDocente
    {
        return PublicacionApkDocente::query()->where('publicado', true)->latest('publicado_en')->first();
    }
}
