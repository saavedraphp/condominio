<?php

namespace App\Services;

use App\Models\BudgetType;
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

        // El ordenamiento y la re-indexación se aplican al final, sobre la colección ya filtrada.
        return $filteredData
            ->sortByDesc('total_due')
            ->values();
    }

    /**
     * Obtiene el resumen de egresos agrupados por categoría,
     * incluyendo aquellas con total 0.00.
     */
    public function getExpensesSummary(?string $startDate, ?string $endDate, ?string $budgetScope = null): Collection
    {
        // 1. Obtenemos la consulta original y ejecutamos para traer los datos reales
        $expenses = $this->getExpensesQuery($startDate, $endDate, $budgetScope)->get();

        // 2. Agrupamos y sumamos solo lo que realmente se gastó
        $expensesTotals = $expenses->groupBy(function ($expense) {
            return $expense->annualBudget?->budgetType?->name ?? 'Sin Categoría';
        })->map->sum('amount');

        // 3. Consultamos TODOS los tipos de presupuesto
        // Mencionaste "correspondientes a ese año". Si guardas el año en annual_budgets,
        // puedes filtrar aquí. Si no, esto trae todo el catálogo ordenado.
        $allBudgetTypesQuery = BudgetType::query()
            ->when($budgetScope, function ($query) use ($budgetScope) {
                $query->where('budget_scope', $budgetScope);
            });

        // OPCIONAL: Si necesitas filtrar ESTRICTAMENTE para que solo salgan
        // los budget_types que tienen un presupuesto creado para ese año.
        // (Asumiendo que $startDate tiene formato 'Y-m-d' y extraes el año)
        if ($startDate) {
            $year = date('Y', strtotime($startDate));
            $allBudgetTypesQuery->whereHas('annualBudgets', function ($q) use ($year) {
                $q->where('year', $year);
            });
        }


        // Obtenemos solo los nombres ordenados de la A a la Z
        $allBudgetTypes = $allBudgetTypesQuery->orderBy('name', 'asc')->pluck('name');

        // 4. Mapeamos para estructurar el resultado final (fusionando nombres con totales)
        $summary = $allBudgetTypes->map(function ($budgetName) use ($expensesTotals) {
            return [
                'name' => $budgetName,
                // Si la categoría existe en los gastos, pone el total, si no, 0.00
                'total' => $expensesTotals->get($budgetName, 0.00)
            ];
        });

        // 5. Manejo de gastos huérfanos (si algún gasto se quedó sin categoría asignada)
        if ($expensesTotals->has('Sin Categoría')) {
            $summary->push([
                'name' => 'Sin Categoría',
                'total' => $expensesTotals->get('Sin Categoría')
            ]);
        }

        // Retornamos los valores limpios
        return $summary->values();
    }
}
