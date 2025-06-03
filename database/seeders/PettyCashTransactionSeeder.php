<?php

// database/seeders/PettyCashTransactionSeeder.php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\PettyCashTransaction;
use App\Models\PettyCashFund;
use Carbon\Carbon;

class PettyCashTransactionSeeder extends Seeder
{
    public function run()
    {
        $fund1 = PettyCashFund::find(1); // El primer fondo creado, que está cerrado
        if ($fund1) {
            PettyCashTransaction::create([
                'white_label_id' => $fund1->white_label_id,
                'petty_cash_fund_id' => $fund1->id,
                'transaction_date' => Carbon::parse($fund1->opening_date)->addDays(2),
                'description' => 'Stationery',
                'type' => 'expense',
                'amount' => 150.00,
            ]);
            PettyCashTransaction::create([
                'white_label_id' => $fund1->white_label_id,
                'petty_cash_fund_id' => $fund1->id,
                'transaction_date' => Carbon::parse($fund1->opening_date)->addDays(5),
                'description' => 'Team Lunch',
                'type' => 'expense',
                'amount' => 305.00, // 500 - 150 - 305 = 45 (calculated_balance_at_closing)
            ]);
        }

        $fund2 = PettyCashFund::find(2); // El segundo fondo, que está abierto
        if ($fund2) {
            PettyCashTransaction::create([
                'white_label_id' => $fund2->white_label_id,
                'petty_cash_fund_id' => $fund2->id,
                'transaction_date' => Carbon::parse($fund2->opening_date)->addDay(),
                'description' => 'Coffee Supplies',
                'type' => 'expense',
                'amount' => 25.75,
            ]);
            PettyCashTransaction::create([
                'white_label_id' => $fund2->white_label_id,
                'petty_cash_fund_id' => $fund2->id,
                'transaction_date' => Carbon::parse($fund2->opening_date)->addDays(3),
                'description' => 'Refund from vendor',
                'type' => 'income',
                'amount' => 10.00,
            ]);
            PettyCashTransaction::create([
                'white_label_id' => $fund2->white_label_id,
                'petty_cash_fund_id' => $fund2->id,
                'transaction_date' => Carbon::parse($fund2->opening_date)->addDays(7),
                'description' => 'Courier Service',
                'type' => 'expense',
                'amount' => 40.00,
                'file_path' => 'seed_files/sample_receipt.pdf' // Asegúrate de tener este archivo o quítalo
            ]);
        }
    }
}
