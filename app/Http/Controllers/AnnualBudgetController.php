<?php

namespace App\Http\Controllers;

use App\Http\Requests\AnnualBudgetRequest;
use App\Models\AnnualBudget;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class AnnualBudgetController extends Controller
{
    public function showPage(): View
    {
        return view('admin.annual_budget.index');
    }

    public function index(Request $request): JsonResponse
    {

        try {
            $query = AnnualBudget::with('budgetType');

            if ($request->has('year')) {
                $query->where('year', $request->year);
            }
            if ($request->has('budget_type_id')) {
                $query->where('budget_type_id', $request->budget_type_id);
            }

            $annualBudgets = $query
                ->orderBy('year', 'desc')
                ->orderBy('id')->get();

            return response()->json($annualBudgets);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al intentar obtener los tipos de presupuesto: ' . $e->getMessage()
            ], 500);
        }
    }

    public function store(AnnualBudgetRequest $request): JsonResponse
    {
        try {
            $validatedData = $request->validated();
            $annualBudget = AnnualBudget::create([
                'budget_type_id' => $validatedData['budget_type_id'],
                'year' => $validatedData['year'],
                'amount' => $validatedData['amount'],
                'white_label_id' => 1,
            ]);

            return response()->json([
                'success' => true,
                'message' => '¡Excelente! El presupuesto se registró exitosamente.',
                'data' => $annualBudget->load('budgetType'),
            ], JsonResponse::HTTP_CREATED);

        } catch (\exception $e) {
            $messageError = 'Ócurrio un error al intentar agregar un presupuesto. ';
            Log::error($messageError . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => $messageError],
                500);
        }
    }

    public function update(AnnualBudgetRequest $request, AnnualBudget $annualBudget): JsonResponse
    {
        $validatedData = $request->validated();
        try {
            $annualBudget->update([
                'budget_type_id' => $validatedData['budget_type_id'],
                'year' => $validatedData['year'],
                'amount' => $validatedData['amount'],
            ]);

            return response()->json([
                'success' => true,
                'message' => '¡Excelente! El presupuesto se actualizó exitosamente.',
                'data' => $annualBudget->load('budgetType'),
            ], JsonResponse::HTTP_OK);

        } catch (\exception $e) {
            $messageError = 'Ócurrio un error al intentar actualizar el presupuesto. ';
            Log::error($messageError . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => $messageError],
                500);
        }
    }

    public function destroy(AnnualBudget $annualBudget): JsonResponse
    {
        try {

            $annualBudget->delete();

            return response()->json([
                'success' => true,
                'message' => '¡Excelente! El presupuesto se eliminado correctamente.',
                JsonResponse::HTTP_OK
            ]);

        } catch (\exception $e) {
            $errorMessage = 'Error eliminando el registro id: ' . $annualBudget?->id;
            Log::error($errorMessage . ': ' . $e->getMessage(), ['exception' => $e]);

            return response()->json([
                'success' => false,
                'message' => 'Ocurrió un error al intentar eliminar el presupuesto.'
            ], JsonResponse::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
