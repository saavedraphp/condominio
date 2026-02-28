<?php

namespace App\Http\Controllers\Admin;
use App\Http\Requests\ReportFilterRequest;
use App\Http\Resources\ExpenseResource;
use App\Http\Resources\PaymentResource;
//use App\Http\Resources\ExpenseResource; // Deberás crearlo igual que el de pagos
use App\Models\Setting;
use App\Services\FinancialReportService;
use App\Services\SharedViewDataService;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class IncomeStatementController extends Controller
{
    protected $financialService;
    private SharedViewDataService $sharedViewDataService;
    public function __construct(FinancialReportService $financialService, SharedViewDataService $sharedViewDataService)
    {
        $this->financialService = $financialService;
        $this->sharedViewDataService = $sharedViewDataService;
        $this->settings = Setting::query()
            ->where('group', 'general')
            ->pluck('value', 'key')
            ->toArray();
    }

    public function showListPage(): View
    {
        return view('admin.reports.income_statement.index');
    }
    public function getSummary(ReportFilterRequest $request): JsonResponse
    {
        $data = $this->prepareData($request);

        return response()->json([
            'success' => true,
            'summary' => [
                'total_incomes' => $data['summary']['total_incomes'],
                'total_expenses' => $data['summary']['total_expenses'],
                'balance' => $data['summary']['balance']
            ],
            'payments_detail' => $data['payments_detail'],
            'expenses_detail' => $data['expenses_detail'],
        ]);
    }

    public function prepareData(Request $request): array
    {
        // Obtener queries de pagos y gastos
        $paymentsQuery = $this->financialService->getPaymentsQuery($request->start_date, $request->end_date);
        $expensesQuery = $this->financialService->getExpensesQuery($request->start_date, $request->end_date, 'building');

        // Calcular totales
        $totalPayments = round((float) $paymentsQuery->clone()->sum('amount'), 2);
        $totalExpenses = round((float) $expensesQuery->clone()->sum('amount'), 2);
        $balance = $totalPayments - $totalExpenses;

        // Obtener colecciones
        $payments = $paymentsQuery->orderBy('payment_date', 'desc')->get();
        $expenses = $expensesQuery->orderBy('expense_date', 'desc')->get();

        return [
            'summary' => [
                'total_incomes' => $totalPayments,
                'total_expenses' => $totalExpenses,
                'balance' => round($balance, 2)
            ],
            'payments_detail' => PaymentResource::collection($payments),
            'expenses_detail' => ExpenseResource::collection($expenses),
        ];
    }
    public function previewPdf(ReportFilterRequest $request): view
    {
        $data = $this->prepareData($request);
        $data['attributes'] = $this->getAttributeToReport($request, true);
        $viewToLoad = 'pdf.income_statement_all';
        return view($viewToLoad,$data);

    }

    private function getAttributeToReport(Request $request, $isPreview = false): array
    {
        $logoPathDB = $this->settings['logo_for_receipts_imagen'] ?? null;
        $logoPath = $this->sharedViewDataService->get($logoPathDB, $isPreview);
        $tablaImagePathDB = $this->settings['annual_expense_statistics_imagen'] ?? null;
        $tablaImagePath = $this->sharedViewDataService->get($tablaImagePathDB, $isPreview);
        $signaturePathDB = $this->settings['signature_for_receipts_imagen'] ?? null;
        $signaturePath = $this->sharedViewDataService->get($signaturePathDB, $isPreview);

        return [
            'logo_path' => $logoPath,
            'start_date' => $request->input('start_date'),
            'end_date' => $request->input('end_date'),
            'site_name' => strtoupper($this->settings['site_title']),
            'chart_description' => $this->settings['chart_description'] ?? '',
            'tablaImagePath' => $tablaImagePath,
            'signature_path' => $signaturePath,
            'name_president' => $this->settings['name_president'],
            'is_preview' => $isPreview,
        ];

    }
}
