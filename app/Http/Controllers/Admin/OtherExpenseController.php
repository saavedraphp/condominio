<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DetailsOtherExpenses;
use App\Models\OtherExpenses;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class OtherExpenseController extends Controller
{
    public function showPage(): View
    {
        return view('admin.other_expenses.index', [
            'routes' => [
                'base' => route('admin.other-expenses.index'),
                'store_details' => route('admin.details-other-expenses.store', ['otherExpense' => 'PLACEHOLDER']),
                'destroy_details' => route('admin.details-other-expenses.destroy', [
                    'otherExpense' => 'PLACEHOLDER_1',
                    'detailsOtherExpense' => 'PLACEHOLDER_2'
                ]),
            ],
        ]);
    }

    public function index(Request $request): JsonResponse
    {
        try {
            $query = OtherExpenses::query()
                ->with('detailsOtherExpenses')
                ->orderBy('date', 'desc')
                ->get();
            return response()->json($query);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al intentar obtener los gastos: ' . $e->getMessage()
            ], 500);
        }
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'title' => 'required|string|max:50',
            'description' => 'nullable|string|max:100',
            'amount' => 'required|numeric|min:0',
            'date' => 'required|date',
        ]);

        try {
            $data = array_merge($data, [
                'white_label_id' => 1,
            ]);
            $expense = OtherExpenses::create($data);
            return response()->json([
                'success' => true,
                'message' => 'Gasto guardado correctamente',
                'data' => $expense
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al intentar guardar el gasto: ' . $e->getMessage()
            ], 500);
        }
    }

    public function update(Request $request, OtherExpenses $otherExpense): JsonResponse
    {
        $data = $request->validate([
            'title' => 'required|string|max:50',
            'description' => 'nullable|string|max:100',
            'amount' => 'required|numeric|min:0',
            'date' => 'required|date',
        ]);

        try {
            $otherExpense->update($data);
            return response()->json([
                'success' => true,
                'message' => 'Gasto actualizado correctamente',
                'data' => $otherExpense
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al intentar actualizar el gasto: ' . $e->getMessage()
            ], 500);
        }
    }



    public function destroy(OtherExpenses $otherExpense): JsonResponse
    {
        try {
            DB::transaction(function () use ($otherExpense) {
                // Eliminar los detalles asociados al gasto
                foreach ($otherExpense->detailsOtherExpenses as $detail) {
                    if ($detail->file_path && Storage::exists($detail->file_path)) {
                        Storage::delete($detail->file_path);
                    }
                    $detail->delete();
                }
                $otherExpense->delete();
            });

            return response()->json([
                'success' => true,
                'message' => 'Gasto eliminado correctamente'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al intentar eliminar el gasto: ' . $e->getMessage()
            ], 500);
        }
    }


}
