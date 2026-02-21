<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Expense;
use App\Models\OtherExpenses;
use App\Models\Setting;
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
    private $settings;
    const EXPENSE_TYPE_ASSOCIATION = 'ASOCIACION';
    const EXPENSE_TYPE_BUILDING = 'EDIFICIO';
    const EXPENSE_TYPE_ISLA_CERDENIA = 'ISLA CERDEÑA';

    private const EXPENSE_TYPE_TITLES = [
        self::EXPENSE_TYPE_ASSOCIATION => 'Gastos de Asociación',
        self::EXPENSE_TYPE_BUILDING => 'Gastos de Edificio',
        self::EXPENSE_TYPE_ISLA_CERDENIA => 'Gastos de Isla Cerdeña',
    ];

    public function __construct(SharedViewDataService $sharedViewDataService)
    {
        $this->sharedViewDataService = $sharedViewDataService;
        $this->settings = Setting::query()
            ->where('group', 'general')
            ->pluck('value', 'key')
            ->toArray();
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
        $siteName = strtoupper($this->settings['site_title']);

        // 2. Preparar los datos para la vista
        return [
            'reportData' => $groupedData,
            'totals' => $ExpensesArray['totals'],
            'details_total' => $ExpensesArray['details_total'],
            'attributes' => array_merge($attributes, [
                'start_date' => $startDate,
                'end_date' => $endDate,
                'types' => $request->input('types', []),
                'site_name' => $siteName,
            ]),
        ];
    }

    private function groupDataByMonth(Collection $items): Collection
    {
        return $items->groupBy(function ($item) {
            return Carbon::parse($item->date)->format('Y-m');
        })->map(function ($group) {
            $total = $group->sum('amount');
            $totalsByType = $group->groupBy('type')
                ->map(function ($itemsByType) {
                    return $itemsByType->sum('amount');
                })
                ->sortKeys(); // Opcional: ordena los totales por el nombre del tipo (A-Z)
            return [
                'month_year' => Carbon::parse($group->first()->date)->format('F Y'),
                'items' => $group,
                'total' => $total,
                'totalsByType' => $totalsByType,
            ];
        });

    }

    private function getExpensesData(Request $request): array
    {
        // 1. Validar las fechas de entrada
        $validated = $request->validate([
            'start_date' => 'nullable|date_format:Y-m-d',
            'end_date' => 'nullable|date_format:Y-m-d|after_or_equal:start_date',
            'types' => 'required|array',
            'types.*' => ['required', 'string', \Illuminate\Validation\Rule::in([
                self::EXPENSE_TYPE_ASSOCIATION,
                self::EXPENSE_TYPE_BUILDING,
                self::EXPENSE_TYPE_ISLA_CERDENIA,
            ])],
        ]);

        $selectedTypes = $validated['types'];
        $allItems = collect();
        $expenseModelTypes = [self::EXPENSE_TYPE_ASSOCIATION, self::EXPENSE_TYPE_BUILDING];

        if (count(array_intersect($selectedTypes, $expenseModelTypes)) > 0) {
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
                    // Mapeamos el 'budget_scope' a nuestro tipo estandarizado
                    $type = $expense->annualBudget?->budgetType?->budget_scope === 'association'
                        ? self::EXPENSE_TYPE_ASSOCIATION
                        : self::EXPENSE_TYPE_BUILDING;

                    return (object)[
                        'unique_id' => 'expense-' . $expense->id,
                        'id' => $expense->id,
                        'type' => $type,
                        'title' => $expense->title ?: 'GASTO',
                        'description' => $expense->description ?: '',
                        'amount' => round((float)$expense->amount, 2),
                        'date' => $expense->expense_date?->format('Y-m-d') ?: 'No disponible',
                    ];
                })
                ->whereIn('type', $selectedTypes);
            $allItems = $allItems->merge($expenses);

        }

        if (in_array(self::EXPENSE_TYPE_ISLA_CERDENIA, $selectedTypes)) {
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
                        'unique_id' => 'other-' . $expense->id,
                        'id' => $expense->id,
                        'type' => self::EXPENSE_TYPE_ISLA_CERDENIA,
                        'title' => $expense->title,
                        'description' => $expense->description ?: '',
                        'amount' => round((float)$expense->amount, 2),
                        'date' => $expense->date?->format('Y-m-d') ?: 'No disponible',
                    ];
                });
            $allItems = $allItems->merge($other_expenses);
        }
        // 2. Combinar los gastos de ambos modelos
        //$allItems = $expenses->toBase()->merge($other_expenses)->sortByDesc('date');
        $allItems = $allItems->sortByDesc('date');

        // 4. CÁLCULO DE TOTALES EFICIENTE Y DINÁMICO
        // Agrupamos por tipo y sumamos los montos. Esto hace UNA SOLA pasada por la colección.
        $totalsByType = $allItems->groupBy('type')->map(function ($group) {
            return $group->sum('amount');
        });

        $totalAmount = $allItems->sum('amount');

        // 5. CONSTRUCCIÓN DE LA RESPUESTA DINÁMICA
        $detailsTotal = [];
        $finalTotals = [];
        // Construimos los arrays de respuesta solo con los datos de los tipos solicitados y encontrados.
        foreach ($totalsByType as $type => $amount) {
            // Para el array 'details_total'
            $detailsTotal[$type] = [
                'title' => self::EXPENSE_TYPE_TITLES[$type] ?? 'Gasto Desconocido', // Usamos nuestro mapa de títulos
                'amount' => round((float)$amount, 2),
            ];

            // Para el array 'totals'
            $finalTotals['total_' . $type] = round((float)$amount, 2);
        }

        // Añadimos el total general
        $finalTotals['total_amount'] = round((float)$totalsByType->sum(), 2);

        return [
            'items' => $allItems->values(),
            'totals' => $finalTotals,
            'details_total' => $detailsTotal,
        ];
    }
}
