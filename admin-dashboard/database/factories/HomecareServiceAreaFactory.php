<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class HomecareServiceAreaFactory extends Factory
{
    public function definition(): array
    {
        return ['name' => 'Area '.fake()->unique()->word(), 'travel_fee' => 0, 'travel_minutes' => 0];
    }
}
