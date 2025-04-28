<?php

namespace Database\Seeders;

use App\Models\PaymentService;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Eloquent\Factories\Sequence;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class PaymentServiceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $startDate = Carbon::now()->startOfMonth()->subMonths(9);
        PaymentService::factory()
            ->count(20)
            ->state(new Sequence(
                fn (Sequence $sequence) => [
                    'payment_date' => $startDate->copy()->addMonths($sequence->index - 1),
                    'service_id' => 1,
                ],
            ))
            ->create();

        $startDate = Carbon::now()->startOfMonth()->subMonths(9);
        PaymentService::factory()
            ->count(20)
            ->state(new Sequence(
                fn (Sequence $sequence) => [
                    'payment_date' => $startDate->copy()->addMonths($sequence->index - 1),
                    'service_id' => 0,
                ],
            ))
            ->create();
    }
}
