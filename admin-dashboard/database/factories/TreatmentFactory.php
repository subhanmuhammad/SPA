<?php

namespace Database\Factories;

use App\Enums\ServiceType;
use App\Models\TreatmentCategory;
use Illuminate\Database\Eloquent\Factories\Factory;

class TreatmentFactory extends Factory
{
    public function definition(): array
    {
        return [
            'treatment_category_id' => fn () => TreatmentCategory::firstOrCreate(['slug' => 'pijat'], ['name' => 'Pijat'])->id,
            'name' => 'Layanan '.fake()->unique()->word(),
            'service_type' => ServiceType::InStore,
            'duration_minutes' => 60,
            'price' => 100000,
        ];
    }
}
