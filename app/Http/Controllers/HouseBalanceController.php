<?php

namespace App\Http\Controllers;

use App\Models\House;

// Asegúrate de importar tu modelo House
use App\Models\HousePayment;

// Tu modelo de pagos
use App\Models\HouseMonthlyCharge;

// Tu modelo de cobros
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Illuminate\View\View;

class HouseBalanceController extends Controller
{
    /**
     * Muestra el balance de la casa en una vista web.
     */
    public function show(House $house): View
    {
        // Cargamos las relaciones para obtener el nombre del propietario si es necesario
        $house->load('owner');
        $balanceData = $this->generateBalanceData($house);
        $reportDate = now();
        $attributes = $this->getSharedViewData(true);
        return view('pdf.balance_by_house', [
            'house' => $house,
            'balanceItems' => $balanceData['items'],
            'totals' => $balanceData['totals'],
            'reportDate' => $reportDate,
            'attributes' => $attributes,
            'isPdf' => false // Variable para ocultar el botón de descarga en el PDF
        ]);
    }

    /**
     * Genera y descarga el balance de la casa en formato PDF.
     */
    public function download(House $house)
    {
        $house->load('owner');
        $reportDate = now();

        $balanceData = $this->generateBalanceData($house);
        $attributes = $this->getSharedViewData(false);

        $pdf = PDF::loadView('pdf.balance_by_house', [
            'house' => $house,
            'balanceItems' => $balanceData['items'],
            'totals' => $balanceData['totals'],
            'reportDate' => $reportDate,
            'attributes' => $attributes,
            'isPdf' => true // Ocultará el botón de descarga en el PDF
        ]);

        // Nombre del archivo: balance-pompeya-204-2024-10-26.pdf
        $fileName = 'balance-' . Str::slug($house->address) . '-' . now()->format('Y-m-d') . '.pdf';

        return $pdf->download($fileName);
    }

    /**
     * Lógica central para obtener, combinar y calcular los datos del balance.
     * @param House $house
     * @return array
     */

    private function getSharedViewData(bool $preview = false): array
    {
        $logoPath = $preview
            ? asset('assets/images/logo.jpg')
            : storage_path('app/public/file_paths/profile/nVcxTYTvFIndE6SVndfDMUTG6uFp5CPcCSFKhmFc.jpg');

        return [
            'logo_path' => $logoPath,
            'date' => now()->format('d/m/Y'),
        ];
    }

    private function generateBalanceData(House $house): array
    {
        // 1. Obtener todos los pagos y darles un formato estándar
        $payments = HousePayment::query()
            ->where('house_id', $house->id)
            ->get()
            ->map(function ($payment) {
                return (object)[
                    'date' => $payment->payment_date,
                    'concept' => $payment->title ?: 'PAGO', // Usar el título del pago o 'PAGO'
                    'charge_amount' => 0,
                    'payment_amount' => $payment->amount,
                    'transaction_code' => $payment->transaction_code,
                    'type' => 'payment'
                ];
            });

        // 2. Obtener todos los cobros (recibos) y darles un formato estándar
        $charges = HouseMonthlyCharge::query()
            ->where('house_id', $house->id)
            ->where('due_date', '<=', now()) // Solo los recibos emitidos
            ->get()
            ->map(function ($charge) {
                // Formatear el concepto para que sea más descriptivo, ej: "Recibo Enero 2025"
                $monthName = Carbon::create()->month($charge->period_month)->locale('es')->monthName;
                $concept = 'RECIBO ' . ucfirst($monthName) . ' ' . $charge->period_year;
                return (object)[
                    'date' => $charge->issued_date,
                    'concept' => $concept,
                    'charge_amount' => $charge->total_amount,
                    'payment_amount' => 0,
                    'transaction_code' => '-', // Los recibos no tienen código de transacción
                    'type' => 'charge'
                ];
            });

        // 3. Unir ambas colecciones y ordenarlas por fecha
        $allItems = $payments->toBase()->merge($charges)->sortBy('date');



        // 4. Calcular el balance acumulado
        $balance = $house->opening_balance;
        $finalItems = new Collection();
        $totalCharges = $house->opening_balance; // El saldo inicial es un cobro
        $totalPayments = 0;

        // Añadir el Saldo Inicial como primera fila
        // Asumimos que la fecha del saldo inicial es la del primer movimiento o una fija
        $firstItemDate = $allItems->first()->date ?? now();
        $finalItems->push((object)[
            'date' => $firstItemDate->copy()->subDay(), // Un día antes del primer movimiento
            'concept' => 'SALDO INICIAL',
            'charge_amount' => $house->opening_balance,
            'payment_amount' => 0,
            'transaction_code' => '-',
            'balance' => $balance
        ]);

        foreach ($allItems as $item) {
            if ($item->type === 'charge') {
                $balance += $item->charge_amount;
                $totalCharges += $item->charge_amount;
            } else { // 'payment'
                $balance -= $item->payment_amount;
                $totalPayments += $item->payment_amount;
            }
            $item->balance = $balance;
            $finalItems->push($item);
        }

        return [
            'items' => $finalItems,
            'totals' => [
                'charges' => $totalCharges,
                'payments' => $totalPayments,
                'final_balance' => $balance,
            ]
        ];
    }
}
