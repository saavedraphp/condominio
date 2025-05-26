<?php

namespace App\Http\Controllers;

use App\Models\AnnualBudget;
use App\Models\BudgetType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Barryvdh\DomPDF\Facade\Pdf; // Import DOMPDF
use Carbon\Carbon;
use Illuminate\View\View;

class BudgetReportController extends Controller
{

    public function showPage(): View
    {
        return view('user.budgets_vs_expenses.index');
    }

    // Helper para obtener el white_label_id, reemplaza con tu lógica real
    private function getWhiteLabelId(Request $request)
    {
        // Ejemplo: return auth()->user()->current_white_label_id;
        return $request->input('white_label_id', 1); // Default a 1 para prueba
    }

    private function getReportData(Request $request)
    {
        $whiteLabelId = $this->getWhiteLabelId($request);
        $year = $request->input('year', Carbon::now()->year);
        $month = $request->input('month'); // Opcional: mes hasta el cual calcular gastos

        $reportItems = [];
        $grandTotalBudgeted = 0;
        $grandTotalSpent = 0;

        $annualBudgets = AnnualBudget::with('budgetType')
            ->where('white_label_id', $whiteLabelId)
            ->where('year', $year)
            ->get();

        foreach ($annualBudgets as $annualBudget) {
            $expensesQuery = $annualBudget->expenses();

            if ($month) {
                // Gastos hasta el final del mes especificado
                $endDate = Carbon::createFromDate($year, $month, 1)->endOfMonth();
                $expensesQuery->where('expense_date', '<=', $endDate);
            } else {
                // Si no hay mes, todos los gastos del año del presupuesto
                $expensesQuery->whereYear('expense_date', $year);
            }

            $spentAmount = $expensesQuery->sum('amount');
            $budgetedAmount = $annualBudget->amount;
            $percentageSpent = $budgetedAmount > 0 ? round(($spentAmount / $budgetedAmount) * 100, 2) : 0;

            $reportItems[] = [
                'budget_type_name' => $annualBudget->budgetType->name,
                'description' => $annualBudget->budgetType->name, // Similar a la imagen
                'period_label' => $month ? "Expenses for {$annualBudget->budgetType->name} up to " . Carbon::create()->month($month)->format('F') . " {$year}" : "Expenses for {$annualBudget->budgetType->name} {$year}",
                'budgeted_amount' => (float) $budgetedAmount,
                'spent_amount' => (float) $spentAmount,
                'percentage_spent' => (float) $percentageSpent,
            ];

            $grandTotalBudgeted += $budgetedAmount;
            $grandTotalSpent += $spentAmount;
        }

        $overallPercentageSpent = $grandTotalBudgeted > 0 ? round(($grandTotalSpent / $grandTotalBudgeted) * 100, 2) : 0;

        return [
            'year' => (int) $year,
            'month_label' => $month ? Carbon::create()->month($month)->format('F') : 'Full Year',
            'report_title' => "Budgets vs. Real Expenses ({$year}" . ($month ? " up to " . Carbon::create()->month($month)->format('F') : "") . ")",
            'items' => $reportItems,
            'totals' => [
                'total_budgeted' => (float) $grandTotalBudgeted,
                'total_spent' => (float) $grandTotalSpent,
                'overall_percentage_spent' => (float) $overallPercentageSpent,
            ],
            'currency_symbol' => 'S/', // Puedes hacerlo configurable
        ];
    }

    public function generateReportData(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'year' => 'sometimes|required|integer|min:2000|max:2100',
            'month' => 'sometimes|nullable|integer|min:1|max:12',
            'white_label_id' => 'sometimes|required|integer' // o tu validación
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $data = $this->getReportData($request);
        return response()->json($data);
    }

    public function downloadPdfReport(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'year' => 'sometimes|required|integer|min:2000|max:2100',
            'month' => 'sometimes|nullable|integer|min:1|max:12',
            'white_label_id' => 'sometimes|required|integer'
        ]);

        if ($validator->fails()) {
            // Podrías redirigir con error o mostrar una vista de error simple
            return response("Invalid parameters for PDF report.", 422);
        }

        $data = $this->getReportData($request);
        $data['report_owner'] = "Propietarios de Islas Cerdeñas"; // Ejemplo de la imagen

        $pdf = Pdf::loadView('pdf.budget_summary_pdf', $data);

        // return $pdf->download('budget_report_' . $data['year'] . '.pdf'); // Para descarga directa
        return $pdf->stream('budget_report_' . $data['year'] . '.pdf'); // Para mostrar en navegador
    }
}
