<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\WebUser;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DoormanController extends Controller
{
    public function index()
    {
        return view('admin.doorman.scanner');
    }
    public function checkAccess(Request $request, $userId): JsonResponse
    {
        $tenant = WebUser::find($userId);

        if (!$tenant) {
            return response()->json(['message' => 'Usuario no encontrado.', 'status' => 'error'], 404);
        }


        $debtAmount = 500;

        $hasDebt = $debtAmount > 0;
        $debtMessage = $hasDebt
            ? "Tiene una deuda pendiente de $" . number_format($debtAmount, 2)
            : "Se encuentra al día con sus pagos.";

        // --- Preparar el resumen ---
        $summary = [
            'status' => true,
            'userInfo' => [
                'name'          => $tenant->name,
                'apartment'     => 201 ?? 'N/A', // Dato del apartamento
                'has_debt'      => $hasDebt,
                'debt_amount'   => (float) $debtAmount,
                'debt_message'  => $debtMessage,
                'file_path_url'  => $tenant->file_path_url,
            ]
        ];

        return response()->json($summary);
    }
}
