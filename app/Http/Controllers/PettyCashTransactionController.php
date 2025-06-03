<?php

namespace App\Http\Controllers;

use App\Models\PettyCashTransaction;
use App\Models\PettyCashFund;
use App\Http\Requests\PettyCashTransactionRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class PettyCashTransactionController extends Controller
{
    public function showListPage($fund_id): View
    {
        return view('admin.petty_cash.funds_details.index',['fund_id' => $fund_id]);
    }


    public function index(Request $request, PettyCashFund $pettyCashFund): JsonResponse
    {
        try {

            $transactions = PettyCashTransaction::where('petty_cash_fund_id', $pettyCashFund->id)
                ->orderBy('transaction_date', 'desc')
                ->orderBy('created_at', 'desc')
                ->paginate($request->input('per_page', 10));

            return response()->json([
                'success' => true,
                'data' => $transactions,
                'fund_details' => [ // Devolver detalles actualizados del fondo es útil
                    'id' => $pettyCashFund->id,
                    'current_balance' => $pettyCashFund->current_balance,
                    'status' => $pettyCashFund->status,
                ]
            ]);
        } catch (\Exception $e) {
            Log::error("Error fetching transactions for fund {$pettyCashFund->id}: " . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error fetching transactions.'
            ], JsonResponse::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(PettyCashTransactionRequest $request): JsonResponse
    {
        try {
            // La autorización de que el fondo está abierto y pertenece al white_label_id ya se hizo en el FormRequest
            $validatedData = $request->validated();
            $fund = PettyCashFund::find($validatedData['petty_cash_fund_id']);

            $filePath = null;
            if ($request->hasFile('file') && $request->file('file')->isValid()) {
                $file = $request->file('file');
                // Usar un subdirectorio por white_label y luego por fund_id podría ser buena idea
                $filePath = $file->store("petty_cash_attachments/wl_{$fund->white_label_id}/fund_{$fund->id}", 'public');
            }
            $validatedData['file_path'] = $filePath;

            $transaction = PettyCashTransaction::create($validatedData);

            return response()->json([
                'success' => true,
                'message' => 'Transaction recorded successfully.',
                'data' => $transaction,
                'updated_fund_balance' => $fund->fresh()->current_balance // Enviar el nuevo balance
            ], JsonResponse::HTTP_CREATED);
        } catch (\Exception $e) {
            Log::error('Error recording transaction: ' . $e->getMessage());
            // Si hay un filePath y la transacción falló, considerar eliminar el archivo si se subió
            if (!empty($filePath) && Storage::disk('public')->exists($filePath)) {
                Storage::disk('public')->delete($filePath);
            }
            return response()->json([
                'success' => false,
                'message' => 'Error recording transaction.'
            ], JsonResponse::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Display the specified resource.
     * (No es tan común para transacciones individuales, usualmente se listan bajo el fondo)
     */
    public function show(Request $request, PettyCashTransaction $pettyCashTransaction): JsonResponse
    {
        try {
            // Autorización
            $whiteLabelId = $request->input('white_label_id', auth()->user()->white_label_id ?? null);
            if ($pettyCashTransaction->white_label_id != $whiteLabelId) {
                return response()->json(['success' => false, 'message' => 'Unauthorized.'], JsonResponse::HTTP_FORBIDDEN);
            }

            return response()->json([
                'success' => true,
                'data' => $pettyCashTransaction
            ]);
        } catch (\Exception $e) {
            Log::error("Error fetching transaction {$pettyCashTransaction->id}: " . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error fetching transaction details.'
            ], JsonResponse::HTTP_INTERNAL_SERVER_ERROR);
        }
    }


    /**
     * Update the specified resource in storage.
     */
    public function update(PettyCashTransactionRequest $request, PettyCashTransaction $pettyCashTransaction): JsonResponse
    {
        try {
            // Autorización de fondo abierto y white_label ya en FormRequest
            $validatedData = $request->validated();
            $fund = $pettyCashTransaction->pettyCashFund; // Obtener el fondo asociado

            if ($request->hasFile('file')) {
                // Eliminar archivo antiguo si existe
                if ($pettyCashTransaction->file_path && Storage::disk('public')->exists($pettyCashTransaction->file_path)) {
                    Storage::disk('public')->delete($pettyCashTransaction->file_path);
                }
                // Subir nuevo archivo
                $file = $request->file('file');
                $validatedData['file_path'] = $file->store("petty_cash_attachments/wl_{$fund->white_label_id}/fund_{$fund->id}", 'public');
            } elseif ($request->filled('remove_file') && $request->input('remove_file') == true) {
                // Lógica para eliminar el archivo si se envía un campo 'remove_file'
                if ($pettyCashTransaction->file_path && Storage::disk('public')->exists($pettyCashTransaction->file_path)) {
                    Storage::disk('public')->delete($pettyCashTransaction->file_path);
                }
                $validatedData['file_path'] = null;
            }


            $pettyCashTransaction->update($validatedData);

            return response()->json([
                'success' => true,
                'message' => 'Transaction updated successfully.',
                'data' => $pettyCashTransaction->fresh(),
                'updated_fund_balance' => $fund->fresh()->current_balance
            ]);
        } catch (\Exception $e) {
            Log::error("Error updating transaction {$pettyCashTransaction->id}: " . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error updating transaction.'
            ], JsonResponse::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, PettyCashTransaction $pettyCashTransaction): JsonResponse
    {
        try {
            // Autorización
            $fund = $pettyCashTransaction->pettyCashFund;
            $whiteLabelId = $request->input('white_label_id', auth()->user()->white_label_id ?? null);

            if ($fund->white_label_id != $whiteLabelId || $fund->status !== 'open') {
                return response()->json(['success' => false, 'message' => 'Unauthorized or fund is not open.'], JsonResponse::HTTP_FORBIDDEN);
            }

            // Eliminar archivo si existe
            if ($pettyCashTransaction->file_path && Storage::disk('public')->exists($pettyCashTransaction->file_path)) {
                Storage::disk('public')->delete($pettyCashTransaction->file_path);
            }
            $pettyCashTransaction->delete();

            return response()->json([
                'success' => true,
                'message' => 'Transaction deleted successfully.',
                'updated_fund_balance' => $fund->fresh()->current_balance
            ]);
        } catch (\Exception $e) {
            Log::error("Error deleting transaction {$pettyCashTransaction->id}: " . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error deleting transaction.'
            ], JsonResponse::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
