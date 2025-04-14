<?php

namespace Database\Seeders;

use App\Models\WebUser;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class WebUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        /** @var WebUser $user */
        $user = WebUser::query()->create([
            'name' => 'Luis Saavedra',
            'email' => 'saavedraphp@gmail.com',
            'password' => Hash::make('123456'),
            'phone' => '960203783',
            'status' => 'active'
        ]);

        $user->assignRole('user');
    }
}
