<?php

namespace Database\Seeders;

use App\Models\House;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class HouseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        House::query()->create([
            'payment_code' => '245132',
            'property_unit' => 'POPEYA01',
            'address' => 'Popeya01 201',
            'construction_area' => '200',
            'participation_percentage' =>'100',
        ]);
    }
}
