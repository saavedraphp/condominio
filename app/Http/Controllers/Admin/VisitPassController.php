<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\JsonResponse;
use Carbon\Carbon;

// Importa tus modelos
use App\Models\VisitPass;
use App\Models\AccessLog;
use Illuminate\Support\Facades\Log;

class VisitPassController extends Controller
{
    /**
     * Valida un pase de visita a través de su código único.
     * Registra el intento en la bitácora y devuelve el resultado.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function validatePass(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'code' => 'required|string|max:14', // Ej: 'EVWE-FHQU-EZSI'
        ]);

        $code = $validated['code'];

        $guard = Auth::guard('web')->user();

        try {
            // Usamos una transacción para asegurar la integridad de los datos
            $result = DB::transaction(function () use ($code, $guard) {

                // 1. Registrar el intento de acceso INMEDIATAMENTE
                $log = AccessLog::create([
                    'user_id'       => $guard->id,
                    'visit_pass_id' => null, // Aún no lo sabemos
                    'code_entered'  => $code,
                    'event_type'    => 'VISIT_ACCESS_ATTEMPT',
                    'status'        => 'PENDING', // Estado inicial
                ]);

                // 2. Buscar el pase de visita por el código
                $pass = VisitPass::where('access_code', $code)->first();

                // 3. Manejar caso: Código no encontrado
                if (!$pass) {
                    $log->update(['status' => 'FAILED_NOT_FOUND']);
                    return response()->json([
                        'status'  => 'FAILED_NOT_FOUND',
                        'message' => 'Código de visita no existe.',
                    ], 404); // Not Found
                }

                // Si se encontró el pase, asociarlo al log
                $log->visit_pass_id = $pass->id;
                $log->save();

                // 4. Validar la vigencia del pase
                $now = Carbon::now();

                if (!$now->between($pass->starts_at, $pass->expires_at)) {
                    $log->update(['status' => 'FAILED_EXPIRED']);
                    return response()->json([
                        'status'  => 'FAILED_EXPIRED',
                        'message' => 'Este pase de visita ha caducado.',
                        'server' => [
                            'datetime' => $now->format('Y-m-d H:i:s'),
                        ],
                    ], 422); // Unprocessable Entity
                }

                // 5. Manejar caso: Validación exitosa
                $log->update(['status' => 'SUCCESS']);

                // Cargar relaciones para la respuesta (Eager Loading)
                $pass->load(['creator', 'house']);

                // Estructurar la respuesta para el frontend
                return response()->json([
                    'status' => 'SUCCESS',
                    'message' => 'Pase validado correctamente.',
                    'data' => [
                        'log_id' => $log->id, // ID del log para actualizar las observaciones
                        'property' => [
                            'address' => $pass->house->address ?? 'No especificada',
                        ],
                        'owner' => [
                            'name' => $pass->creator->name,
                            'phone' => $pass->creator->phone ?? 'No disponible',
                        ],
                        'pass' => [
                            'title' => $pass->title,
                            'details' => $pass->details,
                            'start_date' => $pass->starts_at,
                            'end_date' => $pass->expires_at,
                            'members' => $pass->members, // Asume que 'members' es una relación
                        ],
                        'server' => [
                            'datetime' => $now->format('Y-m-d H:i:s'),
                        ],
                    ]
                ]);
            });

            return $result;

        } catch (\Exception $e) {
            // Loguear el error real
            Log::error("Error en validación de pase: " . $e->getMessage());

            // Devolver una respuesta de error genérica
            return response()->json([
                'status' => 'SERVER_ERROR',
                'message' => 'Ocurrió un error inesperado. Por favor, contacte a soporte.'
            ], 500);
        }
    }

    /**
     * Actualiza las observaciones de un registro de acceso específico.
     *
     * @param Request $request
     * @param AccessLog $log // Route Model Binding
     * @return JsonResponse
     */
    public function updateRemarks(Request $request, AccessLog $log): JsonResponse
    {
        // Opcional: Añadir una política de autorización para verificar
        // si el vigilante actual puede modificar este log.
        // Por ejemplo: $this->authorize('update', $log);

        $validated = $request->validate([
            'remarks' => 'nullable|string|max:1000',
        ]);

        $log->update([
            'remarks' => $validated['remarks'] ?? null,
        ]);

        return response()->json([
            'message' => 'Observaciones guardadas correctamente.',
            'data' => $log,
        ]);
    }
}
