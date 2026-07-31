<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\IntentoAcceso;
use App\Models\SesionUsuario;
use App\Services\CachePermisosService;
use App\Services\ServicioBitacora;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function __construct(
        protected CachePermisosService $cachePermisos,
        protected ServicioBitacora $bitacora,
    ) {}

    public function login(Request $request): JsonResponse
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        $usuario = \App\Models\User::where('email', $request->email)->first();

        if (!$usuario || !Hash::check($request->password, $usuario->password)) {
            $this->registrarIntento($request, $usuario, 'fallido', 'Credenciales inválidas');
            $this->bloquearSiExcedeIntentos($usuario, (string) $request->email, $request);

            throw ValidationException::withMessages([
                'email' => ['Las credenciales proporcionadas son incorrectas'],
            ])->status(401);
        }

        if ($usuario->estado !== 'activo') {
            $this->registrarIntento($request, $usuario, 'fallido', 'Usuario inactivo');

            return response()->json([
                'resultado' => 'R',
                'codigo' => 403,
                'mensaje' => 'Su cuenta está inactiva',
            ], 403);
        }

        if ($usuario->debe_cambiar_contrasena) {
            $this->registrarIntento($request, $usuario, 'fallido', 'Debe cambiar la contraseña');

            return response()->json([
                'resultado' => 'R',
                'codigo' => 423,
                'mensaje' => 'Debe cambiar su contraseña antes de continuar',
            ], 423);
        }

        if ($usuario->estaBloqueado()) {
            $this->registrarIntento($request, $usuario, 'fallido', 'Usuario bloqueado');

            return response()->json([
                'resultado' => 'R',
                'codigo' => 423,
                'mensaje' => 'Su cuenta está bloqueada temporalmente',
            ], 423);
        }

        $token = $usuario->createToken('auth-token', ['*'])->plainTextToken;

        $this->registrarSesion($usuario, $token, $request);
        $this->registrarIntento($request, $usuario, 'exitoso');
        $usuario->forceFill(['bloqueado_hasta' => null])->save();
        $this->cachePermisos->invalidarPermisos($usuario->id);

        $permisos = $this->cachePermisos->obtenerPermisos($usuario);

        return response()->json([
            'resultado' => 'A',
            'codigo' => 0,
            'mensaje' => 'Inicio de sesión exitoso',
            'data' => [
                'token' => $token,
                'expires_at' => optional(SesionUsuario::where('usuario_id', $usuario->id)->whereNull('revocado_en')->latest('id')->first()?->vencimiento)->toIso8601String(),
                'usuario' => [
                    'id' => $usuario->id,
                    'nombre' => $usuario->name,
                    'email' => $usuario->email,
                    'roles' => $usuario->roles()->activos()->get()->map(fn ($r) => [
                        'codigo' => $r->codigo,
                        'nombre' => $r->nombre,
                    ]),
                    'permisos' => $permisos->pluck('codigo'),
                    'sucursales' => $usuario->sucursales()->activos()->get()->map(fn ($s) => [
                        'codigo' => $s->codigo,
                        'nombre' => $s->nombre,
                    ]),
                    'debe_cambiar_contrasena' => $usuario->debe_cambiar_contrasena,
                ],
            ],
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $usuario = $request->user();

        if ($usuario) {
            SesionUsuario::where('usuario_id', $usuario->id)
                ->whereNull('revocado_en')
                ->update(['revocado_en' => now()]);
        }

        if ($usuario && $usuario->currentAccessToken()) {
            $usuario->currentAccessToken()->delete();
        }

        return response()->json([
            'resultado' => 'A',
            'codigo' => 0,
            'mensaje' => 'Sesión cerrada exitosamente',
        ]);
    }

    public function me(Request $request): JsonResponse
    {
        $usuario = $request->user() ?: auth()->user();

        if (!$usuario) {
            return response()->json([
                'resultado' => 'R',
                'codigo' => 401,
                'mensaje' => 'No autenticado',
            ], 401);
        }

        $permisos = $this->cachePermisos->obtenerPermisos($usuario);

        return response()->json([
            'resultado' => 'A',
            'codigo' => 0,
            'mensaje' => 'OK',
            'data' => [
                'id' => $usuario->id,
                'nombre' => $usuario->name,
                'email' => $usuario->email,
                'telefono' => $usuario->telefono,
                'roles' => $usuario->roles()->activos()->get()->map(fn ($r) => [
                    'codigo' => $r->codigo,
                    'nombre' => $r->nombre,
                ]),
                'permisos' => $permisos->pluck('codigo'),
                'sucursales' => $usuario->sucursales()->activos()->get()->map(fn ($s) => [
                    'codigo' => $s->codigo,
                    'nombre' => $s->nombre,
                ]),
                'debe_cambiar_contrasena' => $usuario->debe_cambiar_contrasena,
            ],
        ]);
    }

    protected function registrarSesion(\App\Models\User $usuario, string $token, Request $request): void
    {
        SesionUsuario::create([
            'usuario_id' => $usuario->id,
            'token_hash' => hash('sha256', $token),
            'ip' => $request->ip(),
            'agente' => $request->userAgent(),
            'vencimiento' => now()->addMinutes(config('seguridad.sesiones.duracion_minutos', 480)),
            'ultimo_acceso' => now(),
        ]);
    }

    public function webLogin(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        $usuario = \App\Models\User::where('email', $request->email)->first();

        if (!$usuario || !Hash::check($request->password, $usuario->password)) {
            $this->registrarIntento($request, $usuario, 'fallido', 'Credenciales inválidas');

            return back()->withErrors([
                'email' => 'Las credenciales proporcionadas son incorrectas',
            ])->onlyInput('email');
        }

        if ($usuario->estado !== 'activo') {
            $this->registrarIntento($request, $usuario, 'fallido', 'Usuario inactivo');

            return back()->withErrors([
                'email' => 'Su cuenta está inactiva',
            ])->onlyInput('email');
        }

        if ($usuario->debe_cambiar_contrasena) {
            $this->registrarIntento($request, $usuario, 'fallido', 'Debe cambiar la contraseña');

            return back()->withErrors([
                'email' => 'Debe cambiar su contraseña antes de continuar',
            ])->onlyInput('email');
        }

        if ($usuario->estaBloqueado()) {
            $this->registrarIntento($request, $usuario, 'fallido', 'Usuario bloqueado');

            return back()->withErrors([
                'email' => 'Su cuenta está bloqueada temporalmente',
            ])->onlyInput('email');
        }

        $request->session()->regenerate();
        auth()->login($usuario, $request->boolean('remember'));

        $this->registrarIntento($request, $usuario, 'exitoso');
        $usuario->forceFill(['bloqueado_hasta' => null])->save();

        return redirect()->intended(route('admin.dashboard'));
    }

    public function webLogout(Request $request)
    {
        $usuario = auth()->user();
        if ($usuario) {
            SesionUsuario::where('usuario_id', $usuario->id)
                ->whereNull('revocado_en')
                ->update(['revocado_en' => now()]);
        }

        auth()->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }

    protected function registrarIntento(Request $request, $usuario, string $resultado, string $motivo = ''): void
    {
        IntentoAcceso::create([
            'correo' => $request->email,
            'usuario_id' => $usuario?->id,
            'ip' => $request->ip(),
            'agente' => $request->userAgent(),
            'resultado' => $resultado,
            'motivo' => $motivo,
        ]);
    }

    protected function bloquearSiExcedeIntentos($usuario, string $correo, Request $request): void
    {
        if (!$usuario) {
            return;
        }

        $fallidos = IntentoAcceso::where('correo', $correo)
            ->where('resultado', 'fallido')
            ->where('creado_en', '>=', now()->subMinutes(30))
            ->count();

        if ($fallidos >= 5) {
            $usuario->update([
                'bloqueado_hasta' => now()->addMinutes(30),
                'actualizado_por' => $usuario->id,
            ]);

            $this->bitacora->registrarOperacionPermitida(
                $usuario->id,
                'bloqueo_temporal_login',
                'seguridad',
                $request->ip(),
                $request->userAgent() ?? '',
                $usuario->id,
                null,
                ['correo' => $correo, 'fallidos' => $fallidos]
            );
        }
    }
}
