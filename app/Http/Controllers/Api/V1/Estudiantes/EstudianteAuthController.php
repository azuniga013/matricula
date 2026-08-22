<?php

namespace App\Http\Controllers\Api\V1\Estudiantes;

use App\Http\Controllers\Controller;
use App\Models\AccesoEstudiante;
use App\Models\BitacoraCorreo;
use App\Models\Estudiante;
use App\Models\Calificacion;
use App\Mail\CredencialesEstudiante;
use App\Services\ResolutorFlujoMatricula;
use App\Services\ServicioNomenclatura;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class EstudianteAuthController extends Controller
{
    public function login(Request $request): JsonResponse
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        $acceso = AccesoEstudiante::where('email', $request->email)
            ->where('estado', 'activo')
            ->with('estudiante')
            ->first();

        if (!$acceso || !Hash::check($request->password, $acceso->password)) {
            return response()->json([
                'resultado' => 'R',
                'codigo' => 401,
                'mensaje' => 'Credenciales inválidas',
            ], 401);
        }

        if ($acceso->estudiante->estado !== 'activo') {
            return response()->json([
                'resultado' => 'R',
                'codigo' => 403,
                'mensaje' => 'Su cuenta está inactiva',
            ], 403);
        }

        $token = Str::random(60);
        $acceso->update([
            'token' => hash('sha256', $token),
            'ultimo_acceso' => now(),
        ]);

        $nivelActual = $this->obtenerNivelActual($acceso->estudiante);

        return response()->json([
            'resultado' => 'A',
            'codigo' => 0,
            'mensaje' => 'Inicio de sesión exitoso',
            'data' => [
                'token' => $token,
                'expires_at' => now()->addMinutes(5)->toIso8601String(),
                'estudiante' => [
                    'id' => $acceso->estudiante->id,
                    'codigo' => $acceso->estudiante->codigo,
                    'nombre' => $acceso->estudiante->nombre_completo,
                    'nivel_actual' => $nivelActual,
                ],
            ],
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $acceso = $request->attributes->get('acceso_estudiante');

        if ($acceso) {
            $acceso->update(['token' => null]);
        }

        return response()->json([
            'resultado' => 'A',
            'codigo' => 0,
            'mensaje' => 'Sesión cerrada exitosamente',
        ]);
    }

    public function registro(Request $request): JsonResponse
    {
        $datos = $request->validate([
            'identidad' => 'required|string|max:30|unique:estudiantes,identidad',
            'nombre' => 'required|string|max:150',
            'apellido' => 'required|string|max:150',
            'correo' => 'required|email|max:100|unique:estudiantes,correo',
            'telefono' => 'nullable|string|max:30',
            'sucursal_id' => 'required|exists:sucursales,id',
            'password' => 'required|string|min:6|confirmed',
        ]);

        $resultadoCodigo = app(ServicioNomenclatura::class)->generarCodigo(
            entidad: 'estudiantes_' . date('Y'),
            formato: 'EST-{ANIO}-{SECUENCIA:8}',
            longitudSecuencia: 8,
            anio: date('Y'),
        );
        $codigo = $resultadoCodigo['codigo'];

        $estudiante = Estudiante::create([
            'codigo' => $codigo,
            'nombre' => $datos['nombre'],
            'apellido' => $datos['apellido'],
            'identidad' => $datos['identidad'],
            'correo' => $datos['correo'],
            'telefono' => $datos['telefono'] ?? null,
            'sucursal_id' => $datos['sucursal_id'],
            'estado' => 'activo',
            'es_primer_ingreso' => true,
        ]);

        AccesoEstudiante::create([
            'estudiante_id' => $estudiante->id,
            'email' => $datos['correo'],
            'password' => $datos['password'],
            'estado' => 'activo',
        ]);

        try {
            $mailable = new CredencialesEstudiante(
                nombre: $datos['nombre'] . ' ' . $datos['apellido'],
                codigo: $codigo,
                email: $datos['correo'],
                password: $datos['password'],
                esRegistro: true,
            );
            $cuerpoHtml = $mailable->render();
            Mail::to($datos['correo'])->send($mailable);
            BitacoraCorreo::create([
                'destinatario' => $datos['correo'],
                'asunto' => 'Bienvenido — Credenciales de Acceso',
                'tipo' => 'registro',
                'codigo_estudiante' => $codigo,
                'estado' => 'enviado',
                'cuerpo_html' => $cuerpoHtml,
                'creado_en' => now(),
            ]);
        } catch (\Throwable $e) {
            BitacoraCorreo::create([
                'destinatario' => $datos['correo'],
                'asunto' => 'Bienvenido — Credenciales de Acceso',
                'tipo' => 'registro',
                'codigo_estudiante' => $codigo,
                'estado' => 'fallido',
                'error' => $e->getMessage(),
                'creado_en' => now(),
            ]);
        }

        return response()->json([
            'resultado' => 'A',
            'codigo' => 0,
            'mensaje' => 'Registro exitoso. Revise su correo para las credenciales de acceso.',
            'data' => [
                'estudiante_id' => $estudiante->id,
                'codigo' => $estudiante->codigo,
            ],
        ], 201);
    }

    public function activar(Request $request): JsonResponse
    {
        $datos = $request->validate([
            'identidad' => 'required|string|max:30',
            'codigo' => 'required|string|max:50',
        ]);

        $estudiante = Estudiante::where('identidad', $datos['identidad'])
            ->where('codigo', $datos['codigo'])
            ->first();

        if (!$estudiante) {
            return response()->json([
                'resultado' => 'R',
                'codigo' => 404,
                'mensaje' => 'Datos no coinciden con un estudiante registrado',
            ], 404);
        }

        if ($estudiante->acceso) {
            return response()->json([
                'resultado' => 'R',
                'codigo' => 422,
                'mensaje' => 'El estudiante ya tiene acceso activo',
            ], 422);
        }

        $correo = $estudiante->correo ?? ($estudiante->identidad . '@temp.com');
        $password = Str::random(10);

        AccesoEstudiante::create([
            'estudiante_id' => $estudiante->id,
            'email' => $correo,
            'password' => $password,
            'estado' => 'activo',
        ]);

        $estudiante->update(['es_primer_ingreso' => false]);

        try {
            $mailable = new CredencialesEstudiante(
                nombre: $estudiante->nombre_completo,
                codigo: $estudiante->codigo,
                email: $correo,
                password: $password,
                esRegistro: false,
            );
            $cuerpoHtml = $mailable->render();
            Mail::to($correo)->send($mailable);
            BitacoraCorreo::create([
                'destinatario' => $correo,
                'asunto' => 'Cuenta Activada — Credenciales de Acceso',
                'tipo' => 'activacion',
                'codigo_estudiante' => $estudiante->codigo,
                'estado' => 'enviado',
                'cuerpo_html' => $cuerpoHtml,
                'creado_en' => now(),
            ]);
        } catch (\Throwable $e) {
            BitacoraCorreo::create([
                'destinatario' => $correo,
                'asunto' => 'Cuenta Activada — Credenciales de Acceso',
                'tipo' => 'activacion',
                'codigo_estudiante' => $estudiante->codigo,
                'estado' => 'fallido',
                'error' => $e->getMessage(),
                'creado_en' => now(),
            ]);
        }

        return response()->json([
            'resultado' => 'A',
            'codigo' => 0,
            'mensaje' => 'Acceso activado. Revise su correo para las credenciales.',
            'data' => [
                'estudiante_id' => $estudiante->id,
                'correo' => $estudiante->correo_enmascarado,
            ],
        ]);
    }

    public function reenviarCredenciales(Request $request): JsonResponse
    {
        $datos = $request->validate([
            'email' => 'required|email',
        ]);

        $acceso = AccesoEstudiante::where('email', $datos['email'])
            ->where('estado', 'activo')
            ->with('estudiante')
            ->first();

        if (!$acceso) {
            return response()->json([
                'resultado' => 'R',
                'codigo' => 404,
                'mensaje' => 'No se encontró una cuenta activa con ese correo electrónico',
            ], 404);
        }

        $password = Str::random(10);
        $acceso->password = $password;
        $acceso->save();

        try {
            $mailable = new CredencialesEstudiante(
                nombre: $acceso->estudiante->nombre_completo,
                codigo: $acceso->estudiante->codigo,
                email: $datos['email'],
                password: $password,
                esRegistro: false,
            );
            $cuerpoHtml = $mailable->render();
            Mail::to($datos['email'])->send($mailable);
            BitacoraCorreo::create([
                'destinatario' => $datos['email'],
                'asunto' => 'Credenciales Reenviadas — Acceso al Portal',
                'tipo' => 'reenvio',
                'codigo_estudiante' => $acceso->estudiante->codigo,
                'estado' => 'enviado',
                'cuerpo_html' => $cuerpoHtml,
                'creado_en' => now(),
            ]);
        } catch (\Throwable $e) {
            BitacoraCorreo::create([
                'destinatario' => $datos['email'],
                'asunto' => 'Credenciales Reenviadas — Acceso al Portal',
                'tipo' => 'reenvio',
                'codigo_estudiante' => $acceso->estudiante->codigo,
                'estado' => 'fallido',
                'error' => $e->getMessage(),
                'creado_en' => now(),
            ]);
        }

        return response()->json([
            'resultado' => 'A',
            'codigo' => 0,
            'mensaje' => 'Credenciales reenviadas. Revise su correo electrónico.',
            'data' => [
                'correo' => $acceso->estudiante->correo_enmascarado,
            ],
        ]);
    }

    public function portal(Request $request): JsonResponse
    {
        $estudiante = $request->attributes->get('estudiante');
        $estudiante->load('sucursal');
        $periodoActual = \App\Models\PeriodoAcademico::abierto()->orderByDesc('fecha_inicio')->first();

        $matriculasConObligaciones = $estudiante->matriculas()
            ->whereIn('estado', ['reservada', 'en_revision', 'matriculado'])
            ->with([
                'ofertaAcademica.nivelAcademico',
                'ofertaAcademica.horario',
                'ofertaAcademica.periodoAcademico',
                'ofertaAcademica.nivelAcademico.regimenAcademico',
                'ofertaAcademica.modalidad',
                'ofertaAcademica.docente',
                'ofertaAcademica.grupoWhatsapp',
                'obligaciones' => fn ($q) => $q->whereIn('estado', ['pendiente', 'parcial'])->with('conceptoPago'),
            ])
            ->latest('fecha_reserva')
            ->get();

        $matriculaActivaPeriodoActual = $periodoActual
            ? $matriculasConObligaciones->first(fn ($m) => $m->ofertaAcademica?->periodo_academico_id === $periodoActual->id)
            : null;

        $matriculaActiva = $matriculaActivaPeriodoActual
            ?? $matriculasConObligaciones->first(fn ($m) => $m->obligaciones->isNotEmpty())
            ?? $matriculasConObligaciones->firstWhere('estado', 'matriculado')
            ?? $matriculasConObligaciones->first();

        $nivelActual = null;
        $ofertaActual = null;
        $matriculaActivaId = $matriculaActiva?->id;
        if ($matriculaActiva && $matriculaActiva->ofertaAcademica && (!$periodoActual || $matriculaActiva->ofertaAcademica->periodo_academico_id === $periodoActual->id)) {
            $nivelActual = [
                'codigo' => $matriculaActiva->ofertaAcademica->nivelAcademico->codigo,
                'nombre' => $matriculaActiva->ofertaAcademica->nivelAcademico->nombre,
                'periodo' => $periodoActual?->nombre ?? $matriculaActiva->ofertaAcademica->periodoAcademico->nombre ?? null,
                'horario' => $matriculaActiva->ofertaAcademica->horario
                    ? $matriculaActiva->ofertaAcademica->horario->hora_inicio . ' - ' . $matriculaActiva->ofertaAcademica->horario->hora_fin
                    : null,
                'regimen' => $matriculaActiva->ofertaAcademica->nivelAcademico->regimenAcademico->nombre ?? null,
                'modalidad' => $matriculaActiva->ofertaAcademica->modalidad->nombre ?? null,
                'docente' => $matriculaActiva->ofertaAcademica->docente
                    ? trim($matriculaActiva->ofertaAcademica->docente->nombre . ' ' . $matriculaActiva->ofertaAcademica->docente->apellido)
                    : null,
            ];
            $ofertaActual = $matriculaActiva->ofertaAcademica;
        }

        $matriculasPendientes = $matriculasConObligaciones
            ->filter(fn ($m) => !$periodoActual || $m->ofertaAcademica?->periodo_academico_id === $periodoActual->id)
            ->filter(fn ($m) => $m->obligaciones->isNotEmpty())
            ->values()
            ->map(fn ($m) => [
                'id' => $m->id,
                'codigo' => $m->codigo,
                'estado' => $m->estado,
                'nivel' => $m->ofertaAcademica->nivelAcademico->nombre ?? null,
                'horario' => $m->ofertaAcademica->horario
                    ? $m->ofertaAcademica->horario->hora_inicio . ' - ' . $m->ofertaAcademica->horario->hora_fin
                    : null,
                'regimen' => $m->ofertaAcademica->nivelAcademico->regimenAcademico->nombre ?? null,
                'obligaciones' => $m->obligaciones->map(fn ($o) => [
                    'id' => $o->id,
                    'nombre_cargo' => $o->nombre_cargo,
                    'concepto' => $o->conceptoPago->codigo ?? null,
                    'monto' => $o->monto,
                    'monto_pagado' => $o->monto_pagado,
                    'saldo' => $o->saldoPendiente(),
                    'fecha_vencimiento' => $o->fecha_vencimiento?->format('d/m/Y'),
                    'estado' => $o->estado,
                ]),
            ]);

        $obligaciones = $matriculaActivaPeriodoActual?->obligaciones?->values()?->map(fn ($o) => [
            'id' => $o->id,
            'nombre_cargo' => $o->nombre_cargo,
            'concepto' => $o->conceptoPago->codigo ?? null,
            'monto' => $o->monto,
            'monto_pagado' => $o->monto_pagado,
            'saldo' => $o->saldoPendiente(),
            'fecha_vencimiento' => $o->fecha_vencimiento?->format('d/m/Y'),
            'estado' => $o->estado,
        ]) ?? [];

        $matriculas = $estudiante->matriculas()
            ->with([
                'ofertaAcademica.nivelAcademico',
                'ofertaAcademica.horario',
                'ofertaAcademica.nivelAcademico.regimenAcademico',
                'ofertaAcademica.modalidad',
            ])
            ->latest('fecha_reserva')
            ->get()
            ->map(fn ($m) => [
                'id' => $m->id,
                'codigo' => $m->codigo,
                'estado' => $m->estado,
                'fecha_reserva' => $m->fecha_reserva?->format('d/m/Y'),
                'fecha_confirmacion' => $m->fecha_confirmacion?->format('d/m/Y'),
                'nivel' => $m->ofertaAcademica->nivelAcademico->nombre ?? null,
                'horario' => $m->ofertaAcademica->horario
                    ? $m->ofertaAcademica->horario->hora_inicio . ' - ' . $m->ofertaAcademica->horario->hora_fin
                    : null,
                'regimen' => $m->ofertaAcademica->nivelAcademico->regimenAcademico->nombre ?? null,
                'modalidad' => $m->ofertaAcademica->modalidad->nombre ?? null,
            ]);

        $pagos = $estudiante->pagos()
            ->with(['conceptoPago', 'metodoPago'])
            ->latest('creado_en')
            ->limit(20)
            ->get()
            ->map(fn ($p) => [
                'id' => $p->id,
                'codigo' => $p->codigo,
                'monto' => $p->monto,
                'estado' => $p->estado,
                'concepto' => $p->conceptoPago->nombre ?? null,
                'metodo' => $p->metodoPago->nombre ?? null,
                'fecha' => $p->creado_en?->format('d/m/Y H:i'),
            ]);

        $recibos = $estudiante->recibos()
            ->with(['pago:id,codigo', 'conceptoPago', 'metodoPago'])
            ->where('estado', '!=', 'anulado')
            ->latest('creado_en')
            ->limit(20)
            ->get()
            ->map(fn ($r) => [
                'id' => $r->id,
                'numero_recibo' => $r->numero_recibo,
                'codigo_pago' => $r->pago?->codigo,
                'monto' => $r->monto,
                'concepto' => $r->conceptoPago->nombre ?? null,
                'metodo' => $r->metodoPago->nombre ?? null,
                'fecha' => $r->creado_en?->format('d/m/Y H:i'),
            ]);

        $calificaciones = Calificacion::with(['matricula.ofertaAcademica.periodoAcademico'])
            ->where('estudiante_id', $estudiante->id)
            ->latest('id')
            ->limit(20)
            ->get()
            ->map(fn ($c) => [
                'id' => $c->id,
                'periodo' => $c->matricula?->ofertaAcademica?->periodoAcademico?->nombre,
                'estado' => $c->nota_final === null ? 'pendiente' : ($c->estaAprobada() ? 'aprobado' : 'reprobado'),
            ]);

        $whatsapp = null;
        $whatsappLink = $ofertaActual?->whatsapp_link_periodo ?: $ofertaActual?->grupoWhatsapp?->link;
        $whatsappGrupo = $ofertaActual?->whatsapp_grupo_nombre ?: $ofertaActual?->grupoWhatsapp?->nombre;
        if ($ofertaActual) {
            $whatsapp = [
                'periodo_id' => $ofertaActual->periodo_academico_id,
                'periodo' => $ofertaActual->periodoAcademico->nombre ?? null,
                'link' => $whatsappLink,
                'grupo' => $whatsappGrupo,
                'nivel' => $ofertaActual->nivelAcademico->nombre ?? null,
                'horario' => $ofertaActual->horario
                    ? $ofertaActual->horario->hora_inicio . ' - ' . $ofertaActual->horario->hora_fin
                    : null,
            ];
        }

        $flujoPagoMatricula = app(ResolutorFlujoMatricula::class)->resolver('portal_estudiante', null, null);
        $cuentasBancarias = \App\Models\CuentaBancaria::activas()
            ->orderBy('banco')
            ->get(['id', 'codigo', 'nombre', 'banco', 'numero_cuenta', 'tipo_cuenta']);

        return response()->json([
            'resultado' => 'A',
            'codigo' => 0,
            'mensaje' => 'OK',
            'data' => [
                'estudiante' => [
                    'id' => $estudiante->id,
                    'codigo' => $estudiante->codigo,
                    'nombre' => $estudiante->nombre_completo,
                    'correo' => $estudiante->correo,
                    'sucursal' => $estudiante->sucursal->nombre,
                ],
                'nivel_actual' => $nivelActual,
                'matriculas' => $matriculas,
                'matriculas_pendientes' => $matriculasPendientes,
                'obligaciones' => $obligaciones,
                'matricula_activa_id' => $matriculaActivaId,
                'pagos' => $pagos,
                'recibos' => $recibos,
                'calificaciones' => $calificaciones,
                'cuentas_bancarias' => $cuentasBancarias,
                'whatsapp' => $whatsapp,
                'flujo_pago_matricula' => $flujoPagoMatricula,
                'periodo_actual' => $periodoActual ? [
                    'id' => $periodoActual->id,
                    'codigo' => $periodoActual->codigo,
                    'nombre' => $periodoActual->nombre,
                ] : null,
            ],
        ]);
    }

    public function misCalificaciones(Request $request): JsonResponse
    {
        $estudiante = $request->attributes->get('estudiante');

        $historial = \App\Models\HistorialAcademico::with([
                'ofertaAcademica.nivelAcademico:id,codigo,nombre',
                'ofertaAcademica.periodoAcademico:id,codigo,nombre',
                'matricula:id,codigo',
            ])
            ->where('historial_academico.estudiante_id', $estudiante->id)
            ->orderByDesc('historial_academico.id')
            ->limit(20)
            ->get()
            ->map(fn ($h) => [
                'id' => $h->id,
                'codigo' => $h->codigo,
                'periodo' => $h->ofertaAcademica?->periodoAcademico?->nombre ?? $h->periodoAcademico?->nombre,
                'nivel' => $h->ofertaAcademica?->nivelAcademico?->nombre ?? $h->nivelAcademico?->nombre,
                'nota_final' => $h->nota_final,
                'faltas' => $h->faltas,
                'estado' => $h->estado,
                'aprobada' => $h->estado === 'aprobado',
            ]);

        return response()->json([
            'resultado' => 'A',
            'codigo' => 0,
            'mensaje' => 'OK',
            'data' => $historial,
        ]);
    }

    protected function obtenerNivelActual(Estudiante $estudiante): ?array
    {
        $matricula = $estudiante->matriculas()
            ->where('estado', 'matriculado')
            ->with([
                'ofertaAcademica.nivelAcademico',
                'ofertaAcademica.horario',
                'ofertaAcademica.nivelAcademico.regimenAcademico',
                'ofertaAcademica.modalidad',
            ])
            ->latest('fecha_confirmacion')
            ->first();

        if (!$matricula || !$matricula->ofertaAcademica) {
            return null;
        }

        $o = $matricula->ofertaAcademica;

        return [
            'codigo' => $o->nivelAcademico->codigo,
            'nombre' => $o->nivelAcademico->nombre,
            'horario' => $o->horario ? $o->horario->hora_inicio . ' - ' . $o->horario->hora_fin : null,
            'regimen' => $o->nivelAcademico->regimenAcademico->nombre ?? null,
            'modalidad' => $o->modalidad->nombre ?? null,
            'docente' => $o->docente ? trim($o->docente->nombre . ' ' . $o->docente->apellido) : null,
            'periodo' => $o->periodoAcademico->nombre ?? null,
        ];
    }
}
