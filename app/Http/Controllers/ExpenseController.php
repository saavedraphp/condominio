<?php

namespace App\Http\Controllers;

use App\Enums\BudgetScope;
use App\Http\Requests\ExpenseRequest;
use App\Models\Expense;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class ExpenseController extends Controller
{
    public const TYPE_RECEIPT = 'receipt';
    public const TYPE_PAYMENT = 'payment';
    public const TYPE_JOB = 'job';

    private const ARRAY_TYPE_IMAGES = [
        self::TYPE_RECEIPT => 'file_path_receipt',
        self::TYPE_PAYMENT => 'file_path',
        self::TYPE_JOB => 'file_path_job',
    ];

    public function getTypeExpenses()
    {
        // 2. Creamos el arreglo usando las constantes para el 'id'
        $arrayTypeExpenses = [
            [
                'id' => self::TYPE_RECEIPT, // Esto equivale a 'receipt'
                'name' => 'Recibo'
            ],
            [
                'id' => self::TYPE_PAYMENT, // Esto equivale a 'payment'
                'name' => 'Pago'
            ],
            [
                'id' => self::TYPE_JOB,     // Esto equivale a 'job'
                'name' => 'Trabajo'
            ],
        ];

        // 3. Lo retornamos a Vue
        return response()->json($arrayTypeExpenses);
    }

    public function showPage(): View
    {
        $routes = [
            'base' => route('admin.expenses.index'),
            'budget_scope' => route('admin.annual-budget.index'),
        ];
        $meta = [
            'title' => 'Gastos de la Asociación',
            'subtitle' => 'Gestión de gastos de la Asociación',
            'icon' => 'mdi mdi-account-group',
        ];
        return view('admin.expenses.index', [
            'routes' => $routes,
            'budget_scope' => BudgetScope::ASSOCIATION->value,
            'meta' => $meta,
        ]);
    }

    public function previewImage(Expense $expense): JsonResponse
    {
        $url = Storage::url($expense->file_path);
        $mimeType = Storage::mimeType($expense->file_path);

        return response()->json([
            'success' => true,
            'message' => '¡Excelente! El documento fue encontrado exitosamente.',
            'data' => [
                'title' => 'Imagen del Pago de: ' . $expense->title,
                'url' => $url,
                'original_filename' => $expense->original_filename,
                'mime_type' => $mimeType,
            ],
        ]);

    }

    public function previewReceipt(Expense $expense): JsonResponse
    {
        $url = Storage::url($expense->file_path_receipt);
        $mimeType = Storage::mimeType($expense->file_path_receipt);

        return response()->json([
            'success' => true,
            'message' => '¡Excelente! El documento fue encontrado exitosamente.',
            'data' => [
                'title' => 'Imagen del Recibo de: ' . $expense->title,
                'url' => $url,
                'original_filename' => $expense->original_filename,
                'mime_type' => $mimeType,
            ],
        ]);

    }

    public function previewJob(Expense $expense): JsonResponse
    {
        $url = Storage::url($expense->file_path_job);
        $mimeType = Storage::mimeType($expense->file_path_job);

        return response()->json([
            'success' => true,
            'message' => '¡Excelente! El documento fue encontrado exitosamente.',
            'data' => [
                'title' => 'Imagen de trabajo de: ' . $expense->title,
                'url' => $url,
                'original_filename' => $expense->original_filename,
                'mime_type' => $mimeType,
            ],
        ]);

    }

    public function showPageBuilding(): View
    {
        $routes = [
            'base' => route('admin.building-expenses.index'),
            'budget_scope' => route('admin.annual-budget.index'),
            'delete_image_expense' => route('admin.expense.delete.image', [
                'expense' => 'PLACEHOLDER_1',
                'type_image' => 'PLACEHOLDER_2',
            ]),
            'preview_payment' => route('admin.expense.preview.payment', [
                'expense' => 'PLACEHOLDER_1'
            ]),
            'preview_receipt' => route('admin.expense.preview.receipt', [
                'expense' => 'PLACEHOLDER_1'
            ]),
            'preview_job' => route('admin.expense.preview.job', [
                'expense' => 'PLACEHOLDER_1'
            ]),
        ];
        $meta = [
            'title' => 'Gastos del Edificio',
            'subtitle' => 'Gestión de gastos del Edificio',
            'icon' => 'mdi mdi-office-building',
        ];

        return view('admin.expenses.index', [
            'routes' => $routes,
            'budget_scope' => BudgetScope::BUILDING->value,
            'meta' => $meta,
        ]);
    }

    public function index(Request $request): JsonResponse
    {
        try {
            $query = $this->queryBase($request);

            $expenses = $query->orderBy('expense_date', 'desc')->get();

            return response()->json([
                'data' => $expenses,
                'types_expenses' => $this->getTypeExpenses()->getData()
            ], JsonResponse::HTTP_OK);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al intentar obtener los gastos: ' . $e->getMessage()
            ], 500);
        }
    }

    public function store(ExpenseRequest $request): JsonResponse
    {
        try {
            $validatedData = $request->validated();
            $expense = Expense::create([
                'annual_budget_id' => $validatedData['annual_budget_id'],
                'title' => $validatedData['title'],
                'description' => $validatedData['description'],
                'amount' => $validatedData['amount'],
                'expense_date' => $validatedData['expense_date'],
                'white_label_id' => 1,
            ]);

            return response()->json([
                'success' => true,
                'message' => '¡Excelente! El gasto se registró exitosamente.',
                'data' => $expense,
            ], JsonResponse::HTTP_CREATED);

        } catch (\exception $e) {
            $messageError = 'Ócurrio un error al intentar agregar un gasto. ';
            Log::error($messageError . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => $messageError],
                500);
        }
    }

    public function update(ExpenseRequest $request, Expense $expense): JsonResponse
    {
        $validatedData = $request->validated();
        try {
            $dataToUpdate = [
                'annual_budget_id' => $validatedData['annual_budget_id'],
                'title' => $validatedData['title'],
                'description' => $validatedData['description'],
                'amount' => $validatedData['amount'],
                'expense_date' => $validatedData['expense_date'],
            ];

            $expense->update($dataToUpdate);

            return response()->json([
                'success' => true,
                'message' => '¡Excelente! El gasto se actualizó exitosamente.',
                'data' => $expense,
            ], JsonResponse::HTTP_OK);

        } catch (\Exception $e) {
            $messageError = 'Ocurrió un error al intentar actualizar el gasto. ';
            Log::error($messageError . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => $messageError
            ], 500);
        }
    }

    public function uploadImage(Request $request, Expense $expense): JsonResponse
    {
        try {

            // 1. Definimos el mapa de relación (Tipo de Gasto => Columna en Base de Datos)
            $columnsMap = [
                self::TYPE_RECEIPT => 'file_path_receipt',
                self::TYPE_PAYMENT => 'file_path',
                self::TYPE_JOB => 'file_path_job',
            ];
            $typeId = $request->input('type_expense_id');

            // 2. Validamos que el tipo de gasto enviado sea válido
            if (!array_key_exists($typeId, $columnsMap)) {
                return response()->json([
                    'success' => false,
                    'message' => 'El tipo de gasto proporcionado no es válido.'
                ], JsonResponse::HTTP_BAD_REQUEST);
            }

            // 3. Obtenemos el nombre exacto de la columna que vamos a afectar
            $targetColumn = $columnsMap[$typeId];
            $dataToUpdate = [];

            // 4. Procesamos el archivo si viene en la petición
            if ($request->hasFile('file_path') && $request->file('file_path')->isValid()) {

                // --- AQUÍ ESTABA EL BUG ---
                // Leemos el valor anterior de forma dinámica usando llaves {}
                $oldFilePath = $expense->{$targetColumn};

                // Si existe un archivo viejo en esa columna específica, lo borramos
                if ($oldFilePath && Storage::exists($oldFilePath)) {
                    Storage::delete($oldFilePath);
                }

                // Guardamos el nuevo archivo
                $filePath = $request->file('file_path')->store('file_paths/expenses');

                // Asignamos la nueva ruta a la columna dinámica
                $dataToUpdate[$targetColumn] = $filePath;

            }

            // 5. Solo actualizamos si realmente hay datos para actualizar
            if (!empty($dataToUpdate)) {
                $expense->update($dataToUpdate);
            }

            return response()->json([
                'success' => true,
                'message' => '¡Excelente! El gasto se actualizó exitosamente.',
                'data' => $expense->fresh(), // Usamos fresh() para devolver los datos más recientes
            ], JsonResponse::HTTP_OK);

        } catch (\Exception $e) {
            $messageError = 'Ocurrió un error al intentar actualizar el gasto. ';
            Log::error($messageError . $e->getMessage(), ['exception' => $e]);

            return response()->json([
                'success' => false,
                'message' => $messageError
            ], JsonResponse::HTTP_INTERNAL_SERVER_ERROR);
        }

    }

    public function destroy(Expense $expense): JsonResponse
    {


        try {
            DB::transaction(function () use ($expense) {
                $filePath = $expense->file_path;
                $filePath_receipt = $expense->file_path_receipt;
                $filePath_job = $expense->file_path_job;

                if ($filePath && Storage::exists($filePath)) {
                    Storage::delete($filePath);
                }
                if ($filePath_receipt && Storage::exists($filePath_receipt)) {
                    Storage::delete($filePath_receipt);
                }
                if ($filePath_job && Storage::exists($filePath_job)) {
                    Storage::delete($filePath_job);
                }
                $expense->delete();
            });

            return response()->json([
                'success' => true,
                'message' => '¡Excelente! El gasto se eliminado correctamente.',
                JsonResponse::HTTP_OK
            ]);

        } catch (\exception $e) {
            $errorMessage = 'Error eliminando el registro id: ' . $expense?->id;
            Log::error($errorMessage . ': ' . $e->getMessage(), ['exception' => $e]);

            return response()->json([
                'success' => false,
                'message' => 'Ocurrió un error al intentar eliminar el gasto.'
            ], JsonResponse::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function destroyImages(Expense $expense, string $type_expense): JsonResponse
    {
        $columnMap = self::ARRAY_TYPE_IMAGES;
        $typeId = $type_expense;
        if (!array_key_exists($typeId, $columnMap)) {
            return response()->json([
                'success' => false,
                'message' => 'El tipo de gasto proporcionado no es válido.'
            ], JsonResponse::HTTP_BAD_REQUEST);
        }

        $targetColumn = $columnMap[$typeId];

        $filePath = $expense->{$targetColumn};

        try {
            if ($filePath && Storage::exists($filePath)) {
                Storage::delete($filePath);
            }
            $expense->{$targetColumn} = null;
            $expense->save();
            return response()->json([
                'success' => true,
                'message' => '¡Excelente! El gasto se eliminado correctamente.',
                'data' => $expense->fresh(),
                JsonResponse::HTTP_OK
            ]);

        } catch (\exception $e) {
            $errorMessage = 'Error al intentar eliminar la imagen  de registro id: ' . $expense?->id. ' de la columna: ' . $targetColumn;
            Log::error($errorMessage . ': ' . $e->getMessage(), ['exception' => $e]);

            return response()->json([
                'success' => false,
                'message' => 'Ocurrió un error al intentar eliminar la imagen.'
            ], JsonResponse::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function queryBase(Request $request): Builder
    {
        $type = $request->get('budget_scope');
        $query = Expense::query()
            ->whereHas('annualBudget.budgetType', function ($q) use ($type) {
                $q->where('budget_scope', $type);
            })
            ->with('annualBudget.budgetType');

        if ($request->has('annual_budget_id')) {
            $query->where('annual_budget_id', $request->annual_budget_id);
        }
        if ($request->has('month') && $request->has('year')) {
            $query->whereYear('expense_date', $request->year)
                ->whereMonth('expense_date', $request->month);
        } elseif ($request->has('year')) {
            $query->whereYear('expense_date', $request->year);
        }

        return $query;
    }
}
