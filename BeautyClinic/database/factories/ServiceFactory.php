<?php

namespace Database\Factories;

use App\Models\Admin;
use App\Models\Employee;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Service>
 */
class ServiceFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        return [
            'name' => fake()->unique()->name(),
            'status' => fake()->randomElement(['active', 'inactive']),
            'price' => $this->faker->numberBetween(10000, 100000),
            'image' => fake()->unique()->imageUrl(300, 300),
            'description' => $this->faker->sentence(15),
            'employee_id' => Employee::inRandomOrder()->first()->id,
        ];
    }

    
}
