<?php

namespace Database\Seeders;

use App\Models\HousePayment;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class HousePaymentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        HousePayment::factory()->count(50)->create();
    }
}
