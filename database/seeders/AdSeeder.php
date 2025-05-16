<?php

namespace Database\Seeders;

use App\Models\Ad;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class AdSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Ad::create([
            'title' => 'Reparacion de alumbrado',
            'description' => 'Se realizara el mantenimiento general de los postes',
            'start_day' => '2025-01-10',
            'start_time' => '14:30',
            'end_day' => '2025-01-10',
            'end_time' => '14:30',
            'file_path' => 'https://example.com/banner1.jpg',
            'active' => true,
        ]);

        Ad::create([
            'title' => 'Repación de bomba de agua',
            'description' => 'Se requiere hacer el cambio del sistema de la bomba de agua',
            'start_day' => '2025-03-08',
            'start_time' => '08:20',
            'end_day' => '2025-03-08',
            'end_time' => '08:20',
            'file_path' => 'https://example.com/banner2.jpg',
            'active' => true,
        ]);
        Ad::create([
            'title' => 'Mantenimiento de areas comunes',
            'description' => 'Se realizara las correciones necesarias',
            'start_day' => '2025-05-08',
            'start_time' => '18:20',
            'end_day' => '2025-05-08',
            'end_time' => '18:20',
            'file_path' => 'https://example.com/banner2.jpg',
            'active' => true,
        ]);
    }
}
