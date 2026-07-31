<?php

namespace App\Helpers;

use App\Services\ServicioBitacora;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class RespuestaError
{
    private string $codigoError;
    private int $httpCode;
    private string $mensajeUsuario;
    private string $mensajeTecnico;
    private ?array $errores;
    private ?array $registroAntes;
    private ?array $registroDespues;

    public function __construct(
        string $codigoError,
        int $httpCode,
        string $mensajeUsuario,
        string $mensajeTecnico = '',
        ?array $errores = null,
        ?array $registroAntes = null,
        ?array $registroDespues = null,
    ) {
        $this->codigoError = $codigoError;
        $this->httpCode = $httpCode;
        $this->mensajeUsuario = $mensajeUsuario;
        $this->mensajeTecnico = $mensajeTecnico ?: $mensajeUsuario;
        $this->errores = $errores;
        $this->registroAntes = $registroAntes;
        $this->registroDespues = $registroDespues;
    }

    public static function make(
        string $codigoError,
        int $httpCode,
        string $mensajeUsuario,
        string $mensajeTecnico = '',
        ?array $errores = null,
    ): self {
        return new self($codigoError, $httpCode, $mensajeUsuario, $mensajeTecnico, $errores);
    }

    public static function validacion(?array $errores = null, string $mensajeUsuario = 'Uno o más campos son inválidos'): self
    {
        return new self('422_VALIDACION', 422, $mensajeUsuario, 'Error de validación', $errores);
    }

    public static function noEncontrado(string $entidad = 'Registro'): self
    {
        return new self('404_NO_ENCONTRADO', 404, "{$entidad} no encontrado", "{$entidad} no encontrado en la base de datos");
    }

    public static function sinPermiso(string $permiso = ''): self
    {
        $msg = $permiso ? "No tiene permiso para: {$permiso}" : 'No tiene permiso para realizar esta acción';
        return new self('403_SIN_PERMISO', 403, $msg, "Permiso denegado: {$permiso}");
    }

    public static function noAutenticado(): self
    {
        return new self('401_NO_AUTENTICADO', 401, 'Debe iniciar sesión para continuar', 'Token no proporcionado o inválido');
    }

    public static function credencialesInvalidas(): self
    {
        return new self('401_CREDENCIALES_INVALIDAS', 401, 'Credenciales inválidas', 'Correo o contraseña incorrectos');
    }

    public static function interno(string $mensajeTecnico = 'Error interno del servidor'): self
    {
        return new self('500_ERROR_INTERNO', 500, 'Ocurrió un error inesperado. Intente de nuevo.', $mensajeTecnico);
    }

    public function conRegistro(?array $antes, ?array $despues): self
    {
        $this->registroAntes = $antes;
        $this->registroDespues = $despues;
        return $this;
    }

    public function getCodigoError(): string
    {
        return $this->codigoError;
    }

    public function getMensajeTecnico(): string
    {
        return $this->mensajeTecnico;
    }

    public function response(?Request $request = null, ?int $usuarioId = null): JsonResponse
    {
        $this->logError($request, $usuarioId);

        $payload = [
            'resultado' => 'R',
            'codigo' => $this->httpCode,
            'codigo_error' => $this->codigoError,
            'mensaje' => $this->mensajeUsuario,
            'mensaje_tecnico' => $this->mensajeTecnico,
        ];

        if ($this->errores !== null) {
            $payload['errores'] = $this->errores;
        }

        return response()->json($payload, $this->httpCode);
    }

    private function logError(?Request $request, ?int $usuarioId): void
    {
        try {
            $bitacora = app(ServicioBitacora::class);

            $datos = [
                'accion' => 'error_' . $this->codigoError,
                'modulo' => $this->extraerModulo(),
                'resultado' => 'rechazado',
                'motivo' => $this->mensajeTecnico,
                'valores_antes' => $this->registroAntes,
                'valores_despues' => $this->registroDespues,
            ];

            if ($request) {
                $datos['ip'] = $request->ip();
                $datos['agente'] = $request->userAgent();
                $datos['usuario_id'] ??= $request->user()?->id;
            }

            if ($usuarioId) {
                $datos['usuario_id'] ??= $usuarioId;
            }

            $bitacora->registrarSeguridad($datos);

            Log::channel('stack')->warning('[RespuestaError] ' . $this->codigoError . ' - ' . $this->mensajeTecnico);
        } catch (\Throwable $th) {
            Log::error('Error al registrar en bitácora: ' . $th->getMessage());
        }
    }

    private function extraerModulo(): string
    {
        if (preg_match('/^\d+_(\w+)/', $this->codigoError, $m)) {
            return strtolower($m[1]);
        }
        return 'general';
    }
}
