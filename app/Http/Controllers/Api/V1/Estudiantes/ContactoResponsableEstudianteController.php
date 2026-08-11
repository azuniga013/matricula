<?php

namespace App\Http\Controllers\Api\V1\Estudiantes;

use App\Http\Controllers\Controller;
use App\Models\ContactoResponsableEstudiante;
use App\Models\Estudiante;
use App\Services\ResolutorAlcanceDatos;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ContactoResponsableEstudianteController extends Controller
{
    public function index(Request $request, int $estudianteId): JsonResponse
    {
        $estudiante = $this->resolverEstudiante($request, $estudianteId);

        $contactos = $estudiante->contactosResponsable()
            ->orderBy('prioridad')
            ->orderBy('id')
            ->get();

        return response()->json([
            'resultado' => 'A',
            'codigo' => 200,
            'mensaje' => 'OK',
            'data' => $contactos,
        ]);
    }

    public function store(Request $request, int $estudianteId): JsonResponse
    {
        $estudiante = $this->resolverEstudiante($request, $estudianteId);
        $datos = $this->validar($request);

        $contacto = ContactoResponsableEstudiante::create([
            ...$datos,
            'estudiante_id' => $estudiante->id,
            'creado_por' => $request->user()->id,
            'creado_en' => now(),
            'actualizado_en' => now(),
        ]);

        return response()->json([
            'resultado' => 'A',
            'codigo' => 201,
            'mensaje' => 'Contacto responsable registrado exitosamente',
            'data' => $contacto,
        ], 201);
    }

    public function update(Request $request, int $estudianteId, int $contactoId): JsonResponse
    {
        $estudiante = $this->resolverEstudiante($request, $estudianteId);
        $contacto = $estudiante->contactosResponsable()->findOrFail($contactoId);
        $datos = $this->validar($request, true);

        $contacto->update([
            ...$datos,
            'actualizado_por' => $request->user()->id,
            'actualizado_en' => now(),
        ]);

        return response()->json([
            'resultado' => 'A',
            'codigo' => 200,
            'mensaje' => 'Contacto responsable actualizado exitosamente',
            'data' => $contacto->fresh(),
        ]);
    }

    public function destroy(Request $request, int $estudianteId, int $contactoId): JsonResponse
    {
        $estudiante = $this->resolverEstudiante($request, $estudianteId);
        $contacto = $estudiante->contactosResponsable()->findOrFail($contactoId);

        $contacto->update([
            'estado' => 'inactivo',
            'actualizado_por' => $request->user()->id,
            'actualizado_en' => now(),
        ]);

        return response()->json([
            'resultado' => 'A',
            'codigo' => 200,
            'mensaje' => 'Contacto responsable desactivado exitosamente',
        ]);
    }

    private function resolverEstudiante(Request $request, int $estudianteId): Estudiante
    {
        $query = Estudiante::query();
        app(ResolutorAlcanceDatos::class)->aplicarAlcance($query, $request->user(), 'estudiantes');

        return $query->findOrFail($estudianteId);
    }

    private function validar(Request $request, bool $parcial = false): array
    {
        $reglasBase = [
            'nombre' => [$parcial ? 'sometimes' : 'required', 'string', 'max:150'],
            'parentesco' => 'nullable|string|max:50',
            'correo' => 'nullable|email|max:150',
            'telefono_whatsapp' => 'nullable|string|max:30',
            'recibe_asistencia_email' => 'sometimes|boolean',
            'recibe_asistencia_whatsapp' => 'sometimes|boolean',
            'consentimiento_asistencia_en' => 'nullable|date',
            'consentimiento_evidencia' => 'nullable|string',
            'prioridad' => 'nullable|integer|min:1|max:99',
            'vigente_desde' => 'nullable|date',
            'vigente_hasta' => 'nullable|date|after_or_equal:vigente_desde',
            'estado' => 'nullable|in:activo,inactivo',
        ];

        $datos = $request->validate($reglasBase);

        $recibeEmail = (bool) ($datos['recibe_asistencia_email'] ?? false);
        $recibeWhatsapp = (bool) ($datos['recibe_asistencia_whatsapp'] ?? false);

        if (($recibeEmail || $recibeWhatsapp) && empty($datos['consentimiento_asistencia_en'])) {
            abort(response()->json([
                'resultado' => 'R',
                'codigo' => 422,
                'codigo_error' => '422_CONSENTIMIENTO_REQUERIDO',
                'mensaje' => 'Debe registrar el consentimiento antes de activar notificaciones de asistencia.',
            ], 422));
        }

        if ($recibeEmail && empty($datos['correo'])) {
            abort(response()->json([
                'resultado' => 'R',
                'codigo' => 422,
                'codigo_error' => '422_CORREO_REQUERIDO',
                'mensaje' => 'Debe registrar un correo para activar notificaciones por email.',
            ], 422));
        }

        if ($recibeWhatsapp && empty($datos['telefono_whatsapp'])) {
            abort(response()->json([
                'resultado' => 'R',
                'codigo' => 422,
                'codigo_error' => '422_TELEFONO_REQUERIDO',
                'mensaje' => 'Debe registrar un teléfono para activar notificaciones por WhatsApp.',
            ], 422));
        }

        if (! empty($datos['telefono_whatsapp'])) {
            $datos['telefono_whatsapp'] = $this->normalizarWhatsapp($datos['telefono_whatsapp']);
        }

        if (! isset($datos['estado'])) {
            $datos['estado'] = 'activo';
        }

        return $datos;
    }

    private function normalizarWhatsapp(string $telefono): string
    {
        $normalizado = preg_replace('/[^\d+]/', '', trim($telefono)) ?? '';

        if ($normalizado === '') {
            return $telefono;
        }

        if ($normalizado[0] !== '+') {
            $normalizado = '+'.$normalizado;
        }

        return $normalizado;
    }
}
