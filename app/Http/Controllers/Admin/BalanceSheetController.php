<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Expense;
use App\Models\Setting;
use App\Services\FinancialReportService;
use App\Services\SharedViewDataService;
use Barryvdh\DomPDF\Facade\Pdf;
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
        $date = Carbon::create($year, $month, 1);
        $startPreviousMonth = $date->copy()->subMonth()->startOfMonth()->toDateString();
        $endPreviousMonth = $date->copy()->subMonth()->endOfMonth()->toDateString();

        /*CALCULO PARA EL SALDO DEL MES ANTERIOR*/
        $paymentsQueryPrev = $this->financialService->getPaymentsQuery($startPreviousMonth, $endPreviousMonth);
        $expensesQueryPrev = $this->financialService->getExpensesQuery($startPreviousMonth, $endPreviousMonth, 'building');

        // Calcular totales
        $totalPaymentsPrev = round((float)$paymentsQueryPrev->clone()->sum('amount'), 2);
        $totalExpensesPrev = round((float)$expensesQueryPrev->clone()->sum('amount'), 2);
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

        $expensesQuery = $this->financialService->getExpensesQuery($startDate, $endDate, 'building');

        // Calcular totales

        $totalExpenses = round((float)$expensesQuery->clone()->sum('amount'), 2);
        $expenses = $expensesQuery->orderBy('expense_date', 'desc')->get();

        $expensesSummary = $expenses->groupBy(function ($expense) {
            return $expense->annualBudget?->budgetType?->name ?? 'Sin Categoría';
        })->map(function ($group, $budgetName) {
            return [
                'name' => $budgetName,
                'total' => $group->sum('amount')
            ];
        })->values();

        $grandExpensesTotal = $expensesSummary->sum('total');


        $incomesGeneralItems = $this->getIncomeGeneral($startDate, $endDate);
        $balance = ($lastBalance + array_sum($incomesGeneralItems)) - $grandExpensesTotal;

        // 1. CALCULAS UNA SOLA VEZ AL PRINCIPIO
        $debtorData = $this->financialService->getDebtorData('deparments');
        $totalAmountDue = $debtorData->sum('total_due');

        // 2. PASAS EL VALOR CALCULADO A TUS MÉTODOS
        $currentAssets = $this->getCurrentAssets($balance, $totalAmountDue);
        $nonCurrentAssets = $this->getNonCurrentAssets();
        $liabilities = $this->getLiabilities($totalAmountDue);
        $totalAssets = array_sum($currentAssets) + array_sum($nonCurrentAssets);


        return [
            'incomes_general' => $incomesGeneralItems ?? [],
            'current_total_incomes' => array_sum($incomesGeneralItems), 2,
            'last_balance' => $lastBalance,
            'grandTotalIncome' => $lastBalance + array_sum($incomesGeneralItems),
            'balance' => $balance,
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

    private function getTotalAssets(string $type): float
    {
        $assets = Expense::query()
            ->where('is_asset', true)
            ->where('asset_type', $type)
            ->sum('amount');

        return round((float)$assets, 2);

    }

    private function getNonCurrentAssets(): array
    {
        $totalAsset = $this->getTotalAssets('asset');
        $totalSupplies = $this->getTotalAssets('supply');

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

        return [
            'logo_path' => $logoPath,
            'anho' => $request->input('anho', date('Y')),
            'month' => $request->input('month', date('m')),
            'month_name' => strtoupper(Carbon::create()->month($request->input('month'))->translatedFormat('F')),
            'last_day_month' => Carbon::create()->month($request->input('month'))->endOfMonth()->day,
            'site_name' => strtoupper($this->settings['site_title']),
            'is_preview' => $isPreview,
        ];
    }
}
