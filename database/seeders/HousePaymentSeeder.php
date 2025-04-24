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
        // HousePayment::truncate();
        HousePayment::factory()->count(50)->create();
        // HousePayment::factory()->create([
        //     'amount' => 123.45,
        //     'image_path' => 'payments/specific_image.jpg',
        // ]);
    }
}
