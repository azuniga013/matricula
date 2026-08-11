<div style="font-family: Arial, sans-serif; color: #111827; line-height: 1.5;">
    <h2 style="margin-bottom: 12px;">Aviso de asistencia</h2>

    <p>
        Se registró una <strong>{{ $notificacion->tipo }}</strong> para el estudiante
        <strong>{{ trim(($notificacion->estudiante?->nombre ?? '').' '.($notificacion->estudiante?->apellido ?? '')) }}</strong>.
    </p>

    <ul>
        <li>Fecha: {{ optional($notificacion->asistencia?->fecha)->format('d/m/Y') }}</li>
        <li>Estado: {{ ucfirst($notificacion->tipo) }}</li>
        <li>Oferta: {{ $notificacion->asistencia?->ofertaAcademica?->codigo ?? '-' }}</li>
        <li>Nivel: {{ $notificacion->asistencia?->ofertaAcademica?->nivelAcademico?->nombre ?? '-' }}</li>
    </ul>

    @if($notificacion->asistencia?->observacion)
        <p><strong>Observación:</strong> {{ $notificacion->asistencia->observacion }}</p>
    @endif

    <p>
        Si necesita aclaraciones, use los canales institucionales oficiales.
    </p>
</div>
