<?php

namespace App\Services;

use App\Models\House;
use App\Models\HousePayment;
use App\Models\Expense;

// Asumiendo que tienes este modelo
use App\Models\WebUser;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class FinancialReportService
{
    /**
     * Devuelve la Query base para Pagos
     */
    public function getPaymentsQuery(?string $startDate, ?string $endDate, ): Builder
    {
        return HousePayment::with('house:id,address')
            ->when($startDate, function ($query) use ($startDate) {
                $query->whereDate('payment_date', '>=', $startDate);
            })
            ->when($endDate, function ($query) use ($endDate) {
                $query->whereDate('payment_date', '<=', $endDate);
            });
    }

    /**
     * Devuelve la Query base para Gastos (Para tu otro controlador)
     */
    public function getExpensesQuery(?string $startDate, ?string $endDate,  ?string $budgetScope = null): Builder
    {
        return $expenses = Expense::with(['annualBudget.budgetType:id,budget_scope,name'])
            ->when($startDate, function ($query) use ($startDate) {
                $query->whereDate('expense_date', '>=', $startDate);
            })
            ->when($endDate, function ($query) use ($endDate) {
                $query->whereDate('expense_date', '<=', $endDate);
            })
            ->when($budgetScope, function ($query) use ($budgetScope) {
                // whereHas busca en las relaciones anidadas
                $query->whereHas('annualBudget.budgetType', function ($subQuery) use ($budgetScope) {
                    $subQuery->where('budget_scope', $budgetScope);
                });
            });
    }

    public function getDebtorData(string $typeHouse = 'deparments', string $debtStatus  = 'all'): Collection
    {
        $users = WebUser::where('is_associated', 1)
            ->with([
                'houses' => function ($q) use ($typeHouse) {
                    $q->select('id','address','opening_balance','is_department');

                    if ($typeHouse === 'deparments') {
                        $q->where('is_department', true);

                    }
                },
                'houses.payments:id,house_id,amount,payment_date',
                'houses.monthlyCharges:id,house_id,period_year,period_month,due_date,total_amount,status',
            ])->when($typeHouse === 'deparments', function ($q) {
                $q->whereHas('houses', function ($houseQuery) {
                    $houseQuery->where('is_department', true);
                });
            })
            ->get(['id', 'name', 'has_payment_arrangement']);

        // 2. CÁLCULOS: Para cada usuario, calculamos el total adeudado sumando el balance de cada una de sus casas.
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

        // 3. FILTRADO POR DEUDA
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
}
