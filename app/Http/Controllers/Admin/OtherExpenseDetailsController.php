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

class OtherExpenseDetailsController extends Controller
{
    public function store(Request $request, OtherExpenses $otherExpense): JsonResponse
    {
        $validatedData = $request->validate([
            'file_path' => 'required|file|mimes:pdf,jpg,jpeg,png|max:2048',
        ]);

        try {
            $filePath = null;
            if ($request->hasFile('file_path') && $request->file('file_path')->isValid()) {
                $file = $request->file('file_path');
                $nameOriginal = $file->getClientOriginalName();
                $filePath = $file->store('file_paths/other_expenses');

                if (!$filePath) {
                    return response()->json(['error' => 'No se pudo guardar el archivo.'], JsonResponse::HTTP_INTERNAL_SERVER_ERROR);
                }
            } else {
                return response()->json(['error' => 'Archivo  inválido o no encontrado.'], JsonResponse::HTTP_BAD_REQUEST);
            }

            $expenseDetail = DetailsOtherExpenses::create([
                'other_expense_id' => $otherExpense->id,
                'file_path' => $filePath,
                'original_filename' => $nameOriginal,
            ]);

            return response()->json([
                'success' => true,
                'message' => '¡Excelente! El gasto se registró exitosamente.',
                'data' => $expenseDetail,
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

    public function destroy(OtherExpenses $otherExpense, DetailsOtherExpenses $detailsOtherExpense): JsonResponse
    {

        $filePath = $detailsOtherExpense->file_path;

        try {

            DB::transaction(function () use ($detailsOtherExpense, $filePath) {
                if ($filePath && Storage::exists($filePath)) {
                    Storage::delete($filePath);
                }
                $detailsOtherExpense->delete();

            });

            return response()->json([
                'success' => true,
                'message' => '¡Excelente! El Gasto se elimino correctamente.',
                JsonResponse::HTTP_OK
            ]);

        } catch (\exception $e) {
            $errorMessage = 'Error eliminando el registro id: ' . $detailsOtherExpense?->id;
            Log::error($errorMessage . ': ' . $e->getMessage(), ['exception' => $e]);

            return response()->json([
                'success' => false,
                'message' => 'Ocurrió un error al intentar eliminar el Gasto.'
            ], JsonResponse::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
