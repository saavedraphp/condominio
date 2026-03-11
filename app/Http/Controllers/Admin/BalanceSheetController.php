<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Expense;
use App\Models\Setting;
use App\Services\FinancialReportService;
use App\Services\SharedViewDataService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

class BalanceSheetController extends Controller
{
    private SharedViewDataService $sharedViewDataService;
    public $balance;

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
        return view('admin.reports.balance_sheet.index');
    }

    public function previewPdf(Request $request): view
    {
        $data = $this->prepareData($request);
        $data['attributes'] = $this->getAttributeToReport($request, true);

        $viewToLoad = 'pdf.balance_sheet';
        return view($viewToLoad, $data);

    }

    public function downloadPdf(Request $request): \Illuminate\Http\Response
    {
        $data = $this->prepareData($request);
        $data['attributes'] = $this->getAttributeToReport($request, false);
        $viewToLoad = 'pdf.balance_sheet';
        // Cargamos la misma vista Blade en el generador de PDF
        $pdf = PDF::loadView($viewToLoad, $data);

        // Descargamos el archivo
        return $pdf->download('balance-sheet-' . now()->format('Y-m-d') . '.pdf');
    }

    private function BalanceLast(Request $request): float
    {
        $year = $request->input('anho');
        $month = $request->input('month');

        // Obtenemos el último día del mes anterior
        $date = Carbon::create($year, $month, 1);
        $endPreviousMonth = $date->copy()->subMonth()->endOfMonth()->toDateString();

        /*
         * CALCULO PARA EL SALDO DEL MES ANTERIOR (ACUMULADO HISTÓRICO)
         * Pasamos 'null' como fecha de inicio para que sume TODO desde el principio
         * hasta el último día del mes anterior.
         */
        $paymentsQueryPrev = $this->financialService->getPaymentsQuery(null, $endPreviousMonth);
        $expensesQueryPrev = $this->financialService->getExpensesQuery(null, $endPreviousMonth, 'building');

        // Calcular totales acumulados
        $totalPaymentsPrev = round((float)$paymentsQueryPrev->clone()->sum('amount'), 2);
        $totalExpensesPrev = round((float)$expensesQueryPrev->clone()->sum('amount'), 2);

        // El balance real es la diferencia histórica
        $lastBalance = $totalPaymentsPrev - $totalExpensesPrev;

        return $lastBalance;
    }

    private function prepareData(Request $request): array
    {
        $year = $request->input('anho');
        $month = $request->input('month');
        $date = Carbon::create($year, $month, 1);

        // Mes actual
        $startDate = $date->copy()->startOfMonth()->toDateString();
        $endDate = $date->copy()->endOfMonth()->toDateString();

        $lastBalance = $this->BalanceLast($request);

        /*CALCULO PARA EL SALDO DEL MES ANTERIOR*/
        $expensesSummary = $this->financialService->getExpensesSummary($startDate, $endDate, 'building');

        $grandExpensesTotal = $expensesSummary->sum('total');

        $incomesGeneralItems = $this->getIncomeGeneral($startDate, $endDate);
        $balance = ($lastBalance + array_sum($incomesGeneralItems)) - $grandExpensesTotal;

        // 1. CALCULAS UNA SOLA VEZ AL PRINCIPIO
        $debtorData = $this->financialService->getDebtorData('deparments');
        $totalAmountDue = $debtorData->sum('total_due');

        // 2. PASAS EL VALOR CALCULADO A TUS MÉTODOS
        $currentAssets = $this->getCurrentAssets($balance, $totalAmountDue);
        $nonCurrentAssets = $this->getNonCurrentAssets($year, $month);
        $liabilities = $this->getLiabilities($totalAmountDue);
        $totalAssets = array_sum($currentAssets) + array_sum($nonCurrentAssets);


        return [
            'incomes_general' => $incomesGeneralItems ?? [],
            'current_total_incomes' => array_sum($incomesGeneralItems), 2,
            'last_balance' => $lastBalance,
            'grandTotalIncome' => $lastBalance + array_sum($incomesGeneralItems),
            'balance' => $balance,
            'balance_formated' => $balance >= 0 ? number_format($balance, 2) : '(' . number_format(abs($balance), 2) . ')',
            'expenses' => $expensesSummary,
            'grand_total_expenses' => $grandExpensesTotal,
            'current_assets' => $currentAssets,
            'non_current_assets' => $nonCurrentAssets,
            'total_assets' => $totalAssets,
            'liabilities' => $liabilities,
            'equity_balance' => $totalAssets - array_sum($liabilities)
        ];
    }

    private function getCurrentAssets(float $balance, float $totalAmountDue): array
    {
        return [
            'cash_bank' => $balance - $totalAmountDue,
            'accounts_receivable' => $totalAmountDue > 0 ? $totalAmountDue : 0.00,
            'expenses_prepaid' => 0.00,
        ];

    }

    private function getTotalAssets(string $type, string $year = null, string $month = null): float
    {
        $assets = Expense::query()
            ->where('is_asset', true)
            ->where('asset_type', $type)
            ->when($month, function (Builder $query) use ($year, $month) {
                $date = Carbon::createFromDate($year, $month, 1)->endOfMonth();
                $query->whereDate('expense_date', '<=', $date);
            })
            ->sum('amount');

        return round((float)$assets, 2);

    }

    private function getNonCurrentAssets(string $year, string $month): array
    {
        $totalAsset = $this->getTotalAssets('asset', $year, $month);
        $totalSupplies = $this->getTotalAssets('supply', $year, $month);

        return [
            'assets' => $totalAsset,
            'supplies' => $totalSupplies,
        ];

    }

    private function getLiabilities(float $totalAmountDue): array
    {
        return [
            'pending_repairs' => 0.00,
            'debts_payable' => 0.00,
            'advances_payments' => $totalAmountDue < 0 ? abs($totalAmountDue) : 0.00,
        ];

    }

    private function getIncomeGeneral(string $startDate, string $endDate): array
    {
        $paymentsQuery = $this->financialService->getPaymentsQuery($startDate, $endDate);
        $totalPaymentsCommon = round((float)$paymentsQuery->clone()->sum('amount'), 2);
        $incomesGeneral = [
            'common_income' => $totalPaymentsCommon,
            'extraordinary_income' => 0.00, // Reemplaza con tus datos de ingresos extraordinarios
            'grill_rental_income' => 0.00, // Reemplaza con tus datos de ingresos por alquiler de parrilla
            'cine_rental_income' => 0.00, // Reemplaza con tus datos de ingresos por alquiler de cine
            'penalties_income' => 0.00, // Reemplaza con tus datos de ingresos por penalidades
        ];

        return $incomesGeneral;

    }

    private function getAttributeToReport(Request $request, $isPreview = false): array
    {
        $logoPathDB = $this->settings['logo_for_receipts_imagen'] ?? null;
        $logoPath = $this->sharedViewDataService->get($logoPathDB, $isPreview);
        $signaturePathDB = $this->settings['signature_for_receipts_imagen'] ?? null;
        $signaturePath = $this->sharedViewDataService->get($signaturePathDB, $isPreview);

        return [
            'logo_path' => $logoPath,
            'anho' => $request->input('anho', date('Y')),
            'month' => $request->input('month', date('m')),
            'month_name' => strtoupper(Carbon::create()->month($request->input('month'))->translatedFormat('F')),
            'last_day_month' => Carbon::create($request->input('year'), $request->input('month'), 1)->endOfMonth()->day,
            'site_name' => strtoupper($this->settings['site_title']),
            'signature_path' => $signaturePath,
            'name_president' => $this->settings['name_president'],
            'is_preview' => $isPreview,
        ];
    }
}
