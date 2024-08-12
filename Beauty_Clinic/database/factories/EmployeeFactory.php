<?php

namespace Database\Factories;

use App\Models\Admin;
use App\Models\Employee;
use App\Models\Salon;
use App\Models\Service;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Employee>
 */
class EmployeeFactory extends Factory
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
            'admin_id' => Admin::inRandomOrder()->first()->id,
            'salary' => $this->faker->numberBetween(1000000, 20000000),
            'image' => fake()->unique()->imageUrl(300, 300),
            'salon_id' => Salon::inRandomOrder()->first()->id, 
        ];
    }

    public function configure()
    {
        return $this->afterCreating(function (Employee $employee) {
            Service::factory()->create(['employee_id' => $employee->id]);
        });
    }
}
