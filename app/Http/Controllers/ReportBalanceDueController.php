<?php

namespace App\Http\Controllers;

use App\Models\House;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class ReportBalanceDueController extends Controller
{
    public function showListPage(): View
    {
        return view('admin.reports.balance-due.index');
    }

    public function index(): JsonResponse
    {

        try {
            $houses = House::with([
                'owner:id,name,has_payment_arrangement',
                'payments:id,house_id,amount,payment_date',
                'monthlyCharges:id,house_id,period_year,period_month,total_amount,status',
            ])->get();
            // Solo casas donde el cálculo final es positivo
            $debtorHouses = $houses->filter(function ($house) {
                $balance = $house->calculateBalance();
                return $balance['amount_due'] > 0;
            })->values(); // `values()` limpia los índices

            // Opcional: mapear a una estructura limpia para Vue
            $response = $debtorHouses->map(function ($house) {
                $balance = $house->calculateBalance();

                return [
                    'id' => $house->id,
                    'address' => $house->address,
                    'amount_due' => number_format($balance['amount_due'], 2),
                    'amount_paid' => $balance['amount_paid'],
                    'owner' => $house->owner[0]->name ?? 'Sin propietario',
                    'opening_balance' => number_format($house->opening_balance, 2),
                    'has_payment_arrangement' => $house->owner[0]->has_payment_arrangement ?? false,
                ];
            });
            return response()->json($response);

        } catch (\Exception $e) {
            Log::error('Error al intentar obtener las casas: ' . $e->getMessage());

            return response()->json(['success' => false, 'message' => 'Ócurrio un error al intentar obtener las casas: ' . $e->getMessage()], 500);
        }

    }
}
