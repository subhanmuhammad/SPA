<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class BranchFactory extends Factory
{
    public function definition(): array
    {
        $name = 'Cabang '.fake()->unique()->city();

        return ['name' => $name, 'slug' => Str::slug($name)];
    }
}
