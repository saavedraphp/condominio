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
        $totalAmountDue = 0;
        try {
            /*CASAS CON PROPIETARIOS*/
            $houses = House::with([
                'owner:id,name,has_payment_arrangement',
                'payments:id,house_id,amount,payment_date',
                'monthlyCharges:id,house_id,period_year,period_month,due_date,total_amount,status',
            ])->get();
            // Solo casas donde el cálculo final es positivo
            $debtorHouses = $houses->filter(function ($house) {
                $balance = $house->calculateBalance();
                return $balance['amount_due'] > 0;
            })->values(); // `values()` limpia los índices

            // Opcional: mapear a una estructura limpia para Vue
            $response = $debtorHouses->map(function ($house) use (&$totalAmountDue) {
                $balance = $house->calculateBalance();
                $amountDue =  round((float)$balance['amount_due'], 2);
                $totalAmountDue +=  $amountDue;
                return [
                    'id' => $house->id,
                    'address' => $house->address,
                    'amount_due' => $amountDue,
                    'amount_paid' => $balance['amount_paid'],
                    'owner' => $house->owner[0]->name ?? 'Sin propietario',
                    'opening_balance' => number_format($house->opening_balance, 2),
                    'has_payment_arrangement' => $house->owner[0]->has_payment_arrangement ?? false,
                ];
            });
            return response()->json([
                'success' => true,
                'data' => $response,
                'total_amount_due' => round((float)$totalAmountDue, 2),
            ],JsonResponse::HTTP_OK);


        } catch (\Exception $e) {
            Log::error('Error al intentar obtener las casas: ' . $e->getMessage());

            return response()->json(['success' => false, 'message' => 'Ócurrio un error al intentar obtener las casas: ' . $e->getMessage()], 500);
        }

    }
}
