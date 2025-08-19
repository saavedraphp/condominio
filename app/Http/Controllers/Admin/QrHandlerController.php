<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

//use App\Services\VisitorPassService; // Servicio para la lógica de pases
use App\Services\PatrolService;
use Illuminate\Support\Facades\Auth;

// Servicio para la lógica de rondas

class QrHandlerController extends Controller
{
    public function __construct(
        //protected VisitorPassService $visitorPassService,
        protected PatrolService $patrolService
    ) {}

    public function handle(Request $request)
    {
        $validated = $request->validate([
            'type' => 'required|string',
            'payload' => 'required|array',
        ]);

        $type = $validated['type'];
        $payload = $validated['payload'];
        $user = Auth::user();
        //$webUserId = Auth::guard('web_user')->id();

        switch ($type) {
/*            case 'VISIT_PASS':
                // Llama al servicio que se encarga de validar el pase
                $result = $this->visitorPassService->validate($payload['code']);
                return response()->json($result);*/

            case 'PATROL_CHECKPOINT':
                // Llama al servicio que registra la marca del vigilante
                // Llamar al servicio para que maneje toda la lógica de negocio.
                $result = $this->patrolService->logCheckpoint($payload['zone_id'], $user);
                return response()->json($result, $result['http_code']);

            default:
                return response()->json([
                    'success' => false,
                    'message' => 'Tipo de operación no reconocida.'
                ], 400); // Bad Request
        }
    }

    public function updateRemarks(Request $request, ActivityLog $log): JsonResponse
    {
        // Opcional: Añadir una política de autorización para verificar
        // si el vigilante actual puede modificar este log.
        // Por ejemplo: $this->authorize('update', $log);

        $validated = $request->validate([
            'remarks' => 'nullable|string|max:200',
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
