<?php

namespace App\Http\Controllers;

use App\Models\PettyCashFund;
use App\Http\Requests\PettyCashFundRequest;
use App\Http\Requests\ClosePettyCashFundRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class PettyCashFundController extends Controller
{
    public function showListPage(): View
    {
        return view('admin.petty_cash.funds.index');
    }

    public function index(Request $request): JsonResponse
    {
        try {

            $funds = PettyCashFund::query()
                ->withCount('transactions')
                ->orderBy('opening_date', 'desc')
                ->paginate($request->input('per_page', 10));

            // Añadir 'current_balance' y 'has_transactions' al payload si no se hace con withCount/accessor global
            $funds->getCollection()->transform(function ($fund) {
                $fund->current_balance = $fund->current_balance; // Accesor
                $fund->has_transactions = $fund->transactions_count > 0; // O $fund->has_transactions accessor
                return $fund;
            });


            return response()->json([
                'success' => true,
                'data' => $funds
            ]);
        } catch (\Exception $e) {
            Log::error('Error al obtener los datos de caja chica: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener los datos de caja chica.'
            ], JsonResponse::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(PettyCashFundRequest $request): JsonResponse
    {
        try {
            $validatedData = $request->validated();
            $fund = PettyCashFund::create($validatedData);

            return response()->json([
                'success' => true,
                'message' => 'Petty cash fund opened successfully.',
                'data' => $fund
            ], JsonResponse::HTTP_CREATED);
        } catch (\Exception $e) {
            Log::error('Error opening petty cash fund: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error opening petty cash fund.'
            ], JsonResponse::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request, PettyCashFund $pettyCashFund): JsonResponse
    {
        try {
            // Autorización: Asegurarse que el fondo pertenece al white_label_id del request/usuario
            $whiteLabelId = $request->input('white_label_id', auth()->user()->white_label_id ?? null);
            if ($pettyCashFund->white_label_id != $whiteLabelId) {
                return response()->json(['success' => false, 'message' => 'Unauthorized.'], JsonResponse::HTTP_FORBIDDEN);
            }

            $pettyCashFund->load('transactions'); // Cargar transacciones
            $pettyCashFund->current_balance = $pettyCashFund->current_balance; // Calcular balance
            $pettyCashFund->has_transactions = $pettyCashFund->has_transactions;

            return response()->json([
                'success' => true,
                'data' => $pettyCashFund
            ]);
        } catch (\Exception $e) {
            Log::error("Error fetching fund {$pettyCashFund->id}: " . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error fetching fund details.'
            ], JsonResponse::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(PettyCashFundRequest $request, PettyCashFund $pettyCashFund): JsonResponse
    {
        try {
            // La autorización ya se hizo en UpdatePettyCashFundRequest
            $validatedData = $request->validated();
            $pettyCashFund->update($validatedData);

            return response()->json([
                'success' => true,
                'message' => 'Petty cash fund updated successfully.',
                'data' => $pettyCashFund->fresh() // Devuelve el modelo actualizado
            ]);
        } catch (\Exception $e) {
            Log::error("Error updating petty cash fund {$pettyCashFund->id}: " . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error updating petty cash fund.'
            ], JsonResponse::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, PettyCashFund $pettyCashFund): JsonResponse
    {
        try {
            // Autorización: Asegurarse que el fondo pertenece al white_label_id y está abierto
            $whiteLabelId = $request->input('white_label_id', auth()->user()->white_label_id ?? null);
            if ($pettyCashFund->white_label_id != $whiteLabelId || $pettyCashFund->status !== 'open') {
                return response()->json(['success' => false, 'message' => 'Unauthorized or fund is not open.'], JsonResponse::HTTP_FORBIDDEN);
            }

            if ($pettyCashFund->has_transactions) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot delete a fund that has transactions.'
                ], JsonResponse::HTTP_BAD_REQUEST);
            }

            $pettyCashFund->delete();

            return response()->json([
                'success' => true,
                'message' => 'Petty cash fund deleted successfully.'
            ]);
        } catch (\Exception $e) {
            Log::error("Error deleting petty cash fund {$pettyCashFund->id}: " . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error deleting petty cash fund.'
            ], JsonResponse::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Close the specified petty cash fund.
     */
    public function close(ClosePettyCashFundRequest $request, PettyCashFund $pettyCashFund): JsonResponse
    {
        try {
            // La autorización ya se hizo en ClosePettyCashFundRequest
            // y también chequea que pertenezca al white_label
            $whiteLabelId = $request->input('white_label_id', auth()->user()->white_label_id ?? null);
            if ($pettyCashFund->white_label_id != $whiteLabelId) {
                return response()->json(['success' => false, 'message' => 'Unauthorized.'], JsonResponse::HTTP_FORBIDDEN);
            }

            $validatedData = $request->validated();
            $pettyCashFund->closeFund(
                $validatedData['closing_date'],
                $validatedData['counted_closing_balance']
            );

            return response()->json([
                'success' => true,
                'message' => 'Petty cash fund closed successfully.',
                'data' => $pettyCashFund->fresh()
            ]);

        } catch (\Exception $e) {
            Log::error("Error closing petty cash fund {$pettyCashFund->id}: " . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error closing petty cash fund: ' . $e->getMessage()
            ], JsonResponse::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
