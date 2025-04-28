<?php

namespace Database\Factories;

use App\Models\House;
use App\Models\WebUser;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\PaymentService>
 */
class PaymentServiceFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $imagePath = $this->faker->imageUrl(640, 480, 'car', true, 'payment');

        return [
            'web_user_id' => WebUser::inRandomOrder()->first()->id,
            'house_id' => House::inRandomOrder()->first()->id,
            'quantity' => rand(1000, 1500),
            'file_path' => $imagePath,
            'replace' => rand(0, 1),
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }
}
