<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Controllers\HouseBalanceController;
use App\Models\House;
use App\Models\HousePayment;
use App\Models\PaymentService;
use App\Services\SharedViewDataService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class PaymentReportController extends Controller
{
    // 2. Declara una propiedad para guardar la instancia del servicio
    private SharedViewDataService $sharedViewDataService;

    // 3. Inyecta el servicio en el constructor
    public function __construct(SharedViewDataService $sharedViewDataService)
    {
        $this->sharedViewDataService = $sharedViewDataService;
    }

    public function showListPage(): View
    {
        return view('admin.reports.payments.index');
    }

    public function index(Request $request): JsonResponse
    {
        try {
            $query = $this->getQueryBase($request);
            $totalAmount = round((float)$query->clone()->sum('amount'), 2);
            $query = $query->orderBy('payment_date', 'desc')->get();

            $payments = $query->map(function ($payment) {
                return [
                    'id' => $payment->id,
                    'house_id' => $payment->house_id,
                    'address' => $payment->house->address ?? 'Sin dirección',
                    'amount' => round((float)$payment->amount, 2),
                    'payment_date' => $payment->payment_date ? Carbon::parse($payment->payment_date)->format('Y-m-d') : 'No disponible',
                    'transaction_code' => $payment->transaction_code ?? 'No disponible',
                ];
            });

            return response()->json([
                'success' => true,
                'data' => $payments,
                'total_amount' => round((float)$totalAmount, 2),
            ], JsonResponse::HTTP_OK);


        } catch (\Exception $e) {
            Log::error('Error al obtener el reporte de pagos: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Ocurrió un error al procesar la solicitud',
            ], 500);
        }

    }

    public function previewPdf(Request $request): view
    {
        $data = $this->prepareReportData($request, true);
        return view('pdf.payment_log', array_merge($data, ['isPdf' => true]));
    }

    private function prepareReportData(Request $request, bool $isPreview): array
    {
        $query = $this->getQueryBase($request);

        $totalAmount = round((float) $query->clone()->sum('amount'), 2);
        $groupedData = $this->groupDataByMonth($query);
        $attributes = $this->sharedViewDataService->get($isPreview);

        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');

        return [
            'reportData' => $groupedData,
            'attributes' => array_merge($attributes, [
                'total_amount' => $totalAmount,
                'start_date' => $startDate,
                'end_date' => $endDate,
            ]),
        ];
    }


    public function downloadPdf(Request $request): \Illuminate\Http\Response
    {
        $data = $this->prepareReportData($request, false);

        // Cargamos la misma vista Blade en el generador de PDF
        $pdf = PDF::loadView('pdf.payment_log', array_merge($data, ['isPdf' => false]));

        // Descargamos el archivo
        return $pdf->download('reporte-pagos-' . now()->format('Y-m-d') . '.pdf');
    }

    private function getQueryBase(Request $request): \Illuminate\Database\Eloquent\Builder
    {
        // 1. Validar las fechas de entrada
        $request->validate([
            'start_date' => 'nullable|date_format:Y-m-d',
            'end_date' => 'nullable|date_format:Y-m-d|after_or_equal:start_date',
        ]);

        // Usamos when() para una sintaxis más fluida y legible
        return HousePayment::with('house:id,address')
            ->when($request->filled('start_date'), function ($query) use ($request) {
                $query->whereDate('payment_date', '>=', $request->input('start_date'));
            })
            ->when($request->filled('end_date'), function ($query) use ($request) {
                $query->whereDate('payment_date', '<=', $request->input('end_date'));
            });

    }

    private function groupDataByMonth(\Illuminate\Database\Eloquent\Builder $query): \Illuminate\Support\Collection
    {
        // 3. Obtener los resultados
        $houses = $query->orderBy('payment_date', 'desc')->get();

        // 4. Agrupar por mes y año y calcular totales
        // ¡Esta es la parte clave!
        $groupedData = $houses->groupBy(function ($item) {
            // Agrupamos por una clave "Año-Mes", ej: "2025-01"
            return Carbon::parse($item->payment_date)->format('Y-m');
        })->map(function ($group) {
            $total = $group->sum('amount');

            return [
                'month_year' => Carbon::parse($group->first()->payment_date)->format('F Y'),
                'items' => $group,
                'total' => $total,
            ];
        });
        return $groupedData;

    }
}
