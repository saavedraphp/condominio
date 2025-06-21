<?php

// database/seeders/PettyCashFundSeeder.php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\PettyCashFund;
use Carbon\Carbon;

class PettyCashFundSeeder extends Seeder
{
    public function run()
    {
        PettyCashFund::create([
            'white_label_id' => 1, // Cambia según tu white_label_id
            'opening_date' => Carbon::now()->subMonths(2)->startOfMonth(),
            'opening_balance' => 500.00,
            'responsible_person' => 'John Doe',
            'description' => 'General Expenses - 2 Months Ago',
            'status' => 'closed',
            'closing_date' => Carbon::now()->subMonths(2)->endOfMonth(),
            'counted_closing_balance' => 50.50,
            'calculated_balance_at_closing' => 45.00, // Asumiendo que este fue el calculado
        ]);

        PettyCashFund::create([
            'white_label_id' => 1,
            'opening_date' => Carbon::now()->subMonth()->startOfMonth(),
            'opening_balance' => 300.00,
            'responsible_person' => 'Jane Smith',
            'description' => 'Office Supplies - Last Month',
            'status' => 'open', // Dejamos uno abierto
        ]);

        PettyCashFund::create([
            'white_label_id' => 1,
            'opening_date' => Carbon::now()->startOfMonth(),
            'opening_balance' => 750.00,
            'responsible_person' => 'Peter Jones',
            'description' => 'Client Entertainment - Current Month',
            'status' => 'open',
        ]);

        PettyCashFund::create([
            'white_label_id' => 2, // Otro white_label_id
            'opening_date' => Carbon::now()->subDays(10),
            'opening_balance' => 200.00,
            'status' => 'open',
        ]);

        PettyCashFund::create([
            'white_label_id' => 1,
            'opening_date' => Carbon::now()->subYear(),
            'opening_balance' => 1000.00,
            'responsible_person' => 'Alice Wonderland',
            'description' => 'Archived Fund - Last Year',
            'status' => 'closed',
            'closing_date' => Carbon::now()->subYear()->addMonth(),
            'counted_closing_balance' => 150.00,
            'calculated_balance_at_closing' => 150.00,
        ]);
    }
}
