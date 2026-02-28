<?php

namespace App\Services;

use App\Models\HousePayment;
use App\Models\Expense;

// Asumiendo que tienes este modelo
use Illuminate\Database\Eloquent\Builder;

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
        return $expenses = Expense::with(['annualBudget.budgetType:id,budget_scope'])
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
}
