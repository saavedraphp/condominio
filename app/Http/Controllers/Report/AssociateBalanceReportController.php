<?php

namespace App\Http\Controllers\Report;

use App\Exports\BalanceAssociateExport;
use App\Http\Controllers\Controller;
use App\Models\House;
use App\Models\WebUser;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;

class AssociateBalanceReportController extends Controller
{
    public function showListPage(): View
    {
        $routes = [
            'base' => route('admin.reports.associates.balance.index'),
            'export_excel' => route('admin.balance-associates.export.excel'),
        ];
        $data = [
            'routes' => $routes,
        ];
        return view('admin.reports.balance_by_associates.index', $data);
    }
    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'debt_status' => 'nullable|string|in:with_debt,no_debt',
        ]);

        try {
            $debtStatusFilter = $request->input('debt_status', 'all');
            $debtorData = $this->getDebtorData($debtStatusFilter);
            $totalAmountDue = $debtorData->sum('total_due');

            return response()->json([
                'success' => true,
                'data' => $debtorData,
                'total_amount_due' => round((float)$totalAmountDue, 2),
            ], JsonResponse::HTTP_OK);

        } catch (\Exception $e) {
            Log::error('Error al intentar obtener los asociados y sus deudas: ' . $e->getMessage());

            return response()->json(['success' => false, 'message' => 'Ócurrio un error al intentar obtener los asociados y sus deudas: ' . $e->getMessage()], 500);
        }

    }

    private function getDebtorData(string $debtStatus = 'all'): Collection
    {
        // --- ESTA PARTE NO CAMBIA ---
        $users = WebUser::where('is_associated', 1)
            ->with([
                'houses:id,address,opening_balance',
                'houses.payments:id,house_id,amount,payment_date',
                'houses.monthlyCharges:id,house_id,period_year,period_month,due_date,total_amount,status',
            ])
            ->get(['id', 'name', 'has_payment_arrangement']);

        $usersWithTotalDebt = $users->map(function (WebUser $user) {
            $totalDue = $user->houses->sum(function ($house) {
                return $house->calculateBalance()['amount_due'];
            });

            // Si quieres el detalle de cada casa en el reporte, puedes mapearlo aquí.
            $housesDetails = $user->houses->map(function (House $house) {
                $balance = $house->calculateBalance();
                return [
                    'address' => $house->address,
                    'amount_due' => round((float)$balance['amount_due'], 2),
                    'amount_paid' => round((float)$balance['amount_paid'],2),
                    'opening_balance' => $house->opening_balance
                ];
            });

            return [
                'user_id' => $user->id,
                'user_name' => $user->name,
                'has_payment_arrangement' => $user->has_payment_arrangement ? 'Sí' : 'No',
                'total_due' => round($totalDue, 2),
                'houses_count' => $user->houses->count(),
                'houses_details' => $housesDetails, // El detalle de las casas
                'houses_addresses' => $user->houses->pluck('address')->implode(' / '),
            ];
        });

        // --- AQUÍ VIENE EL CAMBIO ---
        // En lugar de filtrar siempre, aplicamos el filtro condicionalmente.

        $filteredData = $usersWithTotalDebt
            // Condición 1: Si el filtro es 'with_debt', aplicamos este filtro.
            ->when($debtStatus === 'with_debt', function ($collection) {
                return $collection->filter(function ($userData) {
                    return $userData['total_due'] > 0;
                });
            })
            // Condición 2: Si el filtro es 'no_debt', aplicamos este otro filtro.
            ->when($debtStatus === 'no_debt', function ($collection) {
                return $collection->filter(function ($userData) {
                    return $userData['total_due'] <= 0;
                });
            });
        // Si $debtStatus es 'all', ninguna de las condiciones 'when' se cumple,
        // y la colección original ($usersWithTotalDebt) pasa sin cambios. ¡Perfecto!

        // El ordenamiento y la re-indexación se aplican al final, sobre la colección ya filtrada.
        return $filteredData
            ->sortByDesc('total_due')
            ->values();
    }

    public function exportExcel(Request $request)
    {
        $debtStatus = $request->input('debt_status', 'all');
        $debtorData = $this->getDebtorData($debtStatus);

        // 2. Pasamos esa colección directamente a nuestra clase de exportación
        $fileName = 'reporte-balance-asociados-' . now()->format('Y-m-d') . '.xlsx';
        return Excel::download(new BalanceAssociateExport($debtorData), $fileName);
    }
}
