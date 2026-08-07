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
        abort_unless($publicacion && Storage::disk('local')->exists($publicacion->ruta_archivo), 404);

        return Storage::disk('local')->download($publicacion->ruta_archivo, 'Cursos-San-Vicente-Docentes-'.$publicacion->version.'.apk', [
            'Content-Type' => 'application/vnd.android.package-archive',
        ]);
    }

    private function publicacionActiva(): ?PublicacionApkDocente
    {
        return PublicacionApkDocente::query()->where('publicado', true)->latest('publicado_en')->first();
    }
}
