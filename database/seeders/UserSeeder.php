<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        /** @var User $admin */


        $admin = User::query()->create([
            'name' => 'Sergio Astete',
            'email' => 'sergioastete@live.com',
            'password' => Hash::make('123456'),
            'phone' => '960203783',
            'status' => 'active'
        ]);

        $admin->assignRole('admin');

/*        $user = User::query()->create([
            'name' => 'Luis Saavedra',
            'email' => 'saavedraphp@gmail.com',
            'phone' => '960203783',
            'password' => Hash::make('123456'),
            'status' => 'active'
        ]);
        $user->assignRole('user');*/
    }
}
