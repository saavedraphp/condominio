<?php

namespace App\Http\Controllers;

use App\Http\Requests\ExpenseRequest;
use App\Models\Expense;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class ExpenseController extends Controller
{
    public function showPage(): View
    {
        return view('admin.expenses.index');
    }

    public function index(Request $request): JsonResponse
    {
        try {

            $query = Expense::with('annualBudget.budgetType');

            if ($request->has('annual_budget_id')) {
                $query->where('annual_budget_id', $request->annual_budget_id);
            }
            if ($request->has('month') && $request->has('year')) {
                $query->whereYear('expense_date', $request->year)
                    ->whereMonth('expense_date', $request->month);
            } elseif ($request->has('year')) {
                $query->whereYear('expense_date', $request->year);
            }

            $expenses = $query->orderBy('expense_date', 'desc')->get();
            return response()->json($expenses);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al intentar obtener los gastos: ' . $e->getMessage()
            ], 500);
        }
    }

    public function store(ExpenseRequest $request): JsonResponse
    {
        try {
            $validatedData = $request->validated();
            $expense = Expense::create([
                'annual_budget_id' => $validatedData['annual_budget_id'],
                'description' => $validatedData['description'],
                'amount' => $validatedData['amount'],
                'expense_date' => $validatedData['expense_date'],
                'white_label_id' => 1,
            ]);

            return response()->json([
                'success' => true,
                'message' => '¡Excelente! El gasto se registró exitosamente.',
                'data' => $expense,
            ], JsonResponse::HTTP_CREATED);

        } catch (\exception $e) {
            $messageError = 'Ócurrio un error al intentar agregar un gasto. ';
            Log::error($messageError . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => $messageError],
                500);
        }
    }

    public function update( ExpenseRequest $request, Expense $expense): JsonResponse
    {
        $validatedData = $request->validated();
        try {
            $expense->update([
                'annual_budget_id' => $validatedData['annual_budget_id'],
                'description' => $validatedData['description'],
                'amount' => $validatedData['amount'],
                'expense_date' => $validatedData['expense_date'],
            ]);

            return response()->json([
                'success' => true,
                'message' => '¡Excelente! El gasto se actualizó exitosamente.',
                'data' => $expense,
            ], JsonResponse::HTTP_OK);

        } catch (\Exception $e) {
            $messageError = 'Ocurrió un error al intentar actualizar el gasto. ';
            Log::error($messageError . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => $messageError
            ], 500);
        }
    }

    public function destroy(Expense $expense): JsonResponse
    {
        try {

            $expense->delete();

            return response()->json([
                'success' => true,
                'message' => '¡Excelente! El gasto se eliminado correctamente.',
                JsonResponse::HTTP_OK
            ]);

        } catch (\exception $e) {
            $errorMessage = 'Error eliminando el registro id: ' . $expense?->id;
            Log::error($errorMessage . ': ' . $e->getMessage(), ['exception' => $e]);

            return response()->json([
                'success' => false,
                'message' => 'Ocurrió un error al intentar eliminar el gasto.'
            ], JsonResponse::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
