<?php

namespace App\Http\Controllers;

use App\Enums\BudgetScope;
use App\Http\Requests\AnnualBudgetRequest;
use App\Models\AnnualBudget;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class AnnualBudgetController extends Controller
{
    public function showPage(): View
    {
        $routes = [
            'base' => route('admin.annual-budget.index'),
        ];
        $meta = [
            'title' => 'Presupuestos anuales',
            'subtitle' => 'Gestión de presupuestos anuales de la asociación',
            'icon' => 'mdi mdi-account-group',
        ];

        return view('admin.annual_budget.index', [
            'routes' => $routes,
            'budget_scope' => BudgetScope::ASSOCIATION->value,
            'meta' => $meta,
        ]);
    }

    public function showPageBuilding(): View
    {
        $routes = [
            'base' => route('admin.building-budget.index'),
        ];
        $meta = [
            'title' => 'Presupuestos anuales',
            'subtitle' => 'Gestión de presupuestos anuales del Edificio',
            'icon' => 'mdi mdi-office-building',
        ];

        return view('admin.annual_budget.index', [
            'building' => true,
            'routes' => $routes,
            'budget_scope' => BudgetScope::BUILDING->value,
            'meta' => $meta,
        ]);
    }

    public function index(Request $request): JsonResponse
    {
        try {
            $type = $request->get('budget_scope', BudgetScope::ASSOCIATION->value);
            $query = $this->queryBase($request, $type);
            $annualBudgets = $query
                ->orderBy('year', 'desc')
                ->orderBy('id')->get();


            return response()->json($annualBudgets);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al intentar obtener los tipos de presupuesto: ' . $e->getMessage()
            ], 500);
        }
    }

    public function store(AnnualBudgetRequest $request): JsonResponse
    {
        try {
            $validatedData = $request->validated();
            $annualBudget = AnnualBudget::create([
                'budget_type_id' => $validatedData['budget_type_id'],
                'year' => $validatedData['year'],
                'amount' => $validatedData['amount'],
                'white_label_id' => 1,
            ]);

            return response()->json([
                'success' => true,
                'message' => '¡Excelente! El presupuesto se registró exitosamente.',
                'data' => $annualBudget->load('budgetType'),
            ], JsonResponse::HTTP_CREATED);

        } catch (\exception $e) {
            $messageError = 'Ócurrio un error al intentar agregar un presupuesto. ';
            Log::error($messageError . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => $messageError],
                500);
        }
    }

    public function update(AnnualBudgetRequest $request, AnnualBudget $annualBudget): JsonResponse
    {
        $validatedData = $request->validated();
        try {
            $annualBudget->update([
                'budget_type_id' => $validatedData['budget_type_id'],
                'year' => $validatedData['year'],
                'amount' => $validatedData['amount'],
            ]);

            return response()->json([
                'success' => true,
                'message' => '¡Excelente! El presupuesto se actualizó exitosamente.',
                'data' => $annualBudget->load('budgetType'),
            ], JsonResponse::HTTP_OK);

        } catch (\exception $e) {
            $messageError = 'Ócurrio un error al intentar actualizar el presupuesto. ';
            Log::error($messageError . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => $messageError],
                500);
        }
    }

    public function destroy(AnnualBudget $annualBudget): JsonResponse
    {
        try {

            $annualBudget->delete();

            return response()->json([
                'success' => true,
                'message' => '¡Excelente! El presupuesto se eliminado correctamente.',
                JsonResponse::HTTP_OK
            ]);

        } catch (\exception $e) {
            $errorMessage = 'Error eliminando el registro id: ' . $annualBudget?->id;
            Log::error($errorMessage . ': ' . $e->getMessage(), ['exception' => $e]);

            return response()->json([
                'success' => false,
                'message' => 'Ocurrió un error al intentar eliminar el presupuesto.'
            ], JsonResponse::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    private function queryBase(Request $request, string $type): Builder
    {
        $query = AnnualBudget::query()
            ->whereHas('budgetType', function ($builder) use ($type) {
                $builder->where('budget_scope', $type);
            })
            ->with('budgetType');

        if ($request->has('year')) {
            $query->where('year', $request->input('year'));
        }

        if ($request->has('budget_type_id')) {
            $query->where('budget_type_id', $request->input('budget_type_id'));
        }

        return $query;
    }
}
