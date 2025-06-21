<?php

namespace App\Http\Controllers;

use App\Models\BudgetType;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class BudgetTypeController extends Controller
{

    public function index(Request $request): JsonResponse
    {
        try {
            $budgetTypes = BudgetType::all();

            return response()->json($budgetTypes);
        } catch (\Exception $e) {
            $message = 'Error al intentar obtener los tipos de presupuesto: ';
            Log::error($message . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => $message . $e->getMessage()
            ], 500);
        }
    }
}
