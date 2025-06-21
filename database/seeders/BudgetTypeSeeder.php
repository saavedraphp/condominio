<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\BudgetType;
use App\Models\AnnualBudget; // Para el seeder más completo
use App\Models\Expense;      // Para el seeder más completo
use Carbon\Carbon;           // Para el seeder más completo

class BudgetTypeSeeder extends Seeder
{
    public function run(): void
    {
        // Asumimos un white_label_id para el ejemplo, cámbialo según necesites
        $whiteLabelId = 1;

        $types = [
            ['name' => 'Personal', 'white_label_id' => $whiteLabelId],
            ['name' => 'Arreglos', 'white_label_id' => $whiteLabelId],
            ['name' => 'Proyectos', 'white_label_id' => $whiteLabelId],
            ['name' => 'Material de oficina', 'white_label_id' => $whiteLabelId],
        ];

        foreach ($types as $type) {
            BudgetType::firstOrCreate($type);
        }

        $this->command->info('Budget types seeded successfully!');

        // ---- OPCIONAL: Seeder más completo para probar todo el flujo ----
        // Puedes mover esta lógica a un seeder separado como `AnnualBudgetAndExpenseSeeder`
        // si prefieres mantenerlo más organizado.

        $payrollType = BudgetType::where('name', 'Personal')->where('white_label_id', $whiteLabelId)->first();
        $marketingType = BudgetType::where('name', 'Arreglos')->where('white_label_id', $whiteLabelId)->first();
        $currentYear = Carbon::now()->year;
        $nextYear = Carbon::now()->addYear()->year;


        if ($payrollType) {
            // Payroll Budget 2024
            $payrollBudget2024 = AnnualBudget::firstOrCreate(
                [
                    'budget_type_id' => $payrollType->id,
                    'year' => $currentYear,
                    'white_label_id' => $whiteLabelId,
                ],
                ['amount' => 50000.00]
            );

            // Payroll Budget 2025
            $payrollBudget2025 = AnnualBudget::firstOrCreate(
                [
                    'budget_type_id' => $payrollType->id,
                    'year' => $nextYear, // Ejemplo para 2025
                    'white_label_id' => $whiteLabelId,
                ],
                ['amount' => 55000.00]
            );

            // Expenses for Payroll 2024
            Expense::firstOrCreate(
                [
                    'annual_budget_id' => $payrollBudget2024->id,
                    'description' => 'January Payroll 2024',
                    'expense_date' => Carbon::create($currentYear, 1, 15),
                    'white_label_id' => $whiteLabelId,
                ],
                ['amount' => 4000.00]
            );
            Expense::firstOrCreate(
                [
                    'annual_budget_id' => $payrollBudget2024->id,
                    'description' => 'February Payroll 2024',
                    'expense_date' => Carbon::create($currentYear, 2, 15),
                    'white_label_id' => $whiteLabelId,
                ],
                ['amount' => 4100.00]
            );

            // Example Expense for Payroll 2025 - "pago de personal 2025" con mes de junio
            Expense::firstOrCreate(
                [
                    'annual_budget_id' => $payrollBudget2025->id,
                    'description' => 'June Payroll 2025 Advance',
                    'expense_date' => Carbon::create($nextYear, 6, 10), // Mes de Junio
                    'white_label_id' => $whiteLabelId,
                ],
                ['amount' => 2000.00] // Supongamos un gasto
            );
            Expense::firstOrCreate(
                [
                    'annual_budget_id' => $payrollBudget2025->id,
                    'description' => 'June Payroll 2025 Main',
                    'expense_date' => Carbon::create($nextYear, 6, 28), // Mes de Junio
                    'white_label_id' => $whiteLabelId,
                ],
                ['amount' => 8450.00] // Supongamos otro gasto. Total: 10450 / 55000 = 19%
            );

            $this->command->info('Payroll budgets and sample expenses seeded!');
        }


        if ($marketingType) {
            $marketingBudget2024 = AnnualBudget::firstOrCreate(
                [
                    'budget_type_id' => $marketingType->id,
                    'year' => $currentYear,
                    'white_label_id' => $whiteLabelId,
                ],
                ['amount' => 10000.00]
            );

            Expense::firstOrCreate(
                [
                    'annual_budget_id' => $marketingBudget2024->id,
                    'description' => 'Social Media Campaign Q1 2024',
                    'expense_date' => Carbon::create($currentYear, 3, 5),
                    'white_label_id' => $whiteLabelId,
                ],
                ['amount' => 1500.00]
            );
            $this->command->info('Marketing budget and sample expense seeded!');
        }
    }
}
