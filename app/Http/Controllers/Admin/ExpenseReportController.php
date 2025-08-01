<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Expense;
use App\Models\OtherExpenses;
use App\Services\SharedViewDataService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class ExpenseReportController extends Controller
{
    private SharedViewDataService $sharedViewDataService;
    const EXPENSE_TYPE_ASSOCIATION = 'ASOCIACION';
    const EXPENSE_TYPE_BUILDING = 'EDIFICIO';
    const EXPENSE_TYPE_ISLA_CERDENIA = 'ISLA CERDEÑA';

    public function __construct(SharedViewDataService $sharedViewDataService)
    {
        $this->sharedViewDataService = $sharedViewDataService;
    }

    public function showListPage(): View
    {
        return view('admin.reports.expenses.index');
    }

    public function index(Request $request): JsonResponse
    {
        try {
            $expensesData = $this->getExpensesData($request);
            return response()->json([
                'success' => true,
                'data' => $expensesData['items'],
                'totals' => $expensesData['totals'],
            ], JsonResponse::HTTP_OK);

        } catch (\Exception $e) {
            Log::error('Error al obtener el reporte de pagos: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Ocurrió un error al procesar la solicitud: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function previewPdf(Request $request): view
    {
        $data = $this->prepareReportData($request, true);
        return view('pdf.expenses_log', array_merge($data, ['isPdf' => true]));
    }

    public function downloadPdf(Request $request): \Illuminate\Http\Response
    {
        $data = $this->prepareReportData($request, false);

        // Cargamos la misma vista Blade en el generador de PDF
        $pdf = PDF::loadView('pdf.expenses_log', array_merge($data, ['isPdf' => false]));

        // Descargamos el archivo
        return $pdf->download('reporte-gastos-' . now()->format('Y-m-d') . '.pdf');
    }

    private function prepareReportData(Request $request, bool $isPreview): array
    {
        // 1. Obtener los datos de gastos
        $ExpensesArray = $this->getExpensesData($request);
        $groupedData = $this->groupDataByMonth($ExpensesArray['items']);
        $attributes = $this->sharedViewDataService->get($isPreview);

        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');

        // 2. Preparar los datos para la vista
        return [
            'reportData' => $groupedData,
            'totals' => $ExpensesArray['totals'],
            'attributes' => array_merge($attributes, [
                'start_date' => $startDate,
                'end_date' => $endDate,
            ]),
        ];
    }

    private function groupDataByMonth(Collection $items): Collection
    {
        return $items->groupBy(function ($item) {
            return Carbon::parse($item->date)->format('Y-m');
        })->map(function ($group) {
            $total = $group->sum('amount');
            return [
                'month_year' => Carbon::parse($group->first()->date)->format('F Y'),
                'items' => $group,
                'total' => $total,
            ];
        });

    }

    private function getExpensesData(Request $request): array
    {
        // 1. Validar las fechas de entrada
        $request->validate([
            'start_date' => 'nullable|date_format:Y-m-d',
            'end_date' => 'nullable|date_format:Y-m-d|after_or_equal:start_date',
        ]);

        // 1. Obtener todos lo expenses de Asociados y Edificio
        $expenses = Expense::with(['annualBudget.budgetType:id,budget_scope'])
            ->when($request->filled('start_date'), function ($query) use ($request) {
                $query->whereDate('expense_date', '>=', $request->input('start_date'));
            })
            ->when($request->filled('end_date'), function ($query) use ($request) {
                $query->whereDate('expense_date', '<=', $request->input('end_date'));
            })
            ->get()
            ->map(function ($expense) {
                return (object)[
                    'id' => $expense->id,
                    'type' => $expense->annualBudget?->budgetType?->budget_scope == 'association'
                        ? self::EXPENSE_TYPE_ASSOCIATION : self::EXPENSE_TYPE_BUILDING,
                    'title' => $expense->description ?: 'GASTO',
                    'description' => 'N/A',
                    'amount' => round((float)$expense->amount, 2),
                    'date' => $expense->expense_date?->format('Y-m-d') ?: 'No disponible',
                ];
            });

        $other_expenses = OtherExpenses::query()
            ->when($request->filled('start_date'), function ($query) use ($request) {
                $query->whereDate('date', '>=', $request->input('start_date'));
            })
            ->when($request->filled('end_date'), function ($query) use ($request) {
                $query->whereDate('date', '<=', $request->input('end_date'));
            })
            ->get()
            ->map(function ($expense) {
                return (object)[
                    'id' => $expense->id,
                    'type' => self::EXPENSE_TYPE_ISLA_CERDENIA,
                    'title' => $expense->title,
                    'description' => $expense->description,
                    'amount' => round((float)$expense->amount, 2),
                    'date' => $expense->date?->format('Y-m-d') ?: 'No disponible',
                ];
            });

        // 2. Combinar los gastos de ambos modelos
        $allItems = $expenses->toBase()->merge($other_expenses)->sortByDesc('date');

        $totalAmount = $allItems->sum('amount');
        $totalAssociation = $allItems->where('type', self::EXPENSE_TYPE_ASSOCIATION)->sum('amount');
        $totalBuilding = $allItems->where('type', self::EXPENSE_TYPE_BUILDING)->sum('amount');
        $totalCerdenia = $allItems->where('type', self::EXPENSE_TYPE_ISLA_CERDENIA)->sum('amount'); //


        return [
            'items' => $allItems->values(),
            'totals' => [
                'total_amount' => round((float)$totalAmount, 2),
                'total_association' => round((float)$totalAssociation, 2),
                'total_building' => round((float)$totalBuilding, 2),
                'total_cerdenia' => round((float)$totalCerdenia, 2),
            ]
        ];
    }
}
