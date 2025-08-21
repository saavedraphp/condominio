<?php

namespace App\Http\Controllers;

use App\Exports\DebtsExport;
use App\Models\House;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;

class ReportBalanceDueController extends Controller
{
    public function showListPage(): View
    {
        return view('admin.reports.balance-due.index');
    }

    public function index(): JsonResponse
    {
       // $totalAmountDue = 0;
        try {
            $debtorData = $this->getDebtorData();

            // Calcular el total adeudado a partir de la colección ya procesada
            $totalAmountDue = $debtorData->sum('amount_due');

            return response()->json([
                'success' => true,
                'data' => $debtorData,
                'total_amount_due' => round($totalAmountDue, 2),
            ], JsonResponse::HTTP_OK);

            /*CASAS CON PROPIETARIOS*/
           /* $houses = House::with([
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
            ],JsonResponse::HTTP_OK);*/


        } catch (\Exception $e) {
            Log::error('Error al intentar obtener las casas: ' . $e->getMessage());

            return response()->json(['success' => false, 'message' => 'Ócurrio un error al intentar obtener las casas: ' . $e->getMessage()], 500);
        }

    }

    private function getDebtorData(): Collection
    {
        $houses = House::with([
            'owner:id,name,has_payment_arrangement',
            'payments:id,house_id,amount,payment_date',
            'monthlyCharges:id,house_id,period_year,period_month,due_date,total_amount,status',
        ])->get();

        // Filtrar solo casas con deuda
        $debtorHouses = $houses->filter(function ($house) {
            $balance = $house->calculateBalance();
            return $balance['amount_due'] > 0;
        })->values();

        // Mapear a la estructura final
        return $debtorHouses->map(function ($house) {
            $balance = $house->calculateBalance();
            $amountDue = round((float)$balance['amount_due'], 2);
            $owner = $house->owner->first();
            return [
                // El orden debe coincidir con tus headings()
                'id' => $house->id,
                'address' => $house->address,
                'amount_due' => $amountDue,
                'amount_paid' => $balance['amount_paid'],
                'owner' => optional($owner)->name ?? 'Sin propietario',
                'opening_balance' => $house->opening_balance, // No formatear aquí para el Excel
                'has_payment_arrangement' => optional($owner)->has_payment_arrangement ? 'Sí' : 'No',
            ];
        })
            ->sortByDesc('amount_due')
            ->values();
    }

    public function exportExcel(Request $request)
    {
        // 1. Obtenemos la misma colección de datos que usa la UI
        $debtorData = $this->getDebtorData();

        // 2. Pasamos esa colección directamente a nuestra clase de exportación
        $fileName = 'reporte-deudores-' . now()->format('Y-m-d') . '.xlsx';
        return Excel::download(new DebtsExport($debtorData), $fileName);
    }
}
