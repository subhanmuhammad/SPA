<?php

namespace Database\Factories;

use App\Models\Branch;
use App\Models\Therapist;
use Illuminate\Database\Eloquent\Factories\Factory;

class TherapistFactory extends Factory
{
    public function definition(): array
    {
        return ['name' => fake()->firstName(), 'is_active' => true];
    }

    public function at(Branch $branch): static
    {
        return $this->state(fn () => ['branch_id' => $branch->id]);
    }

    public function workingEveryDay(string $from = '09:00', string $to = '17:00'): static
    {
        return $this->afterCreating(fn (Therapist $therapist) => $therapist->schedules()->createMany(
            collect(range(0, 6))->map(fn ($day) => ['day_of_week' => $day, 'start_time' => $from, 'end_time' => $to])->all()
        ));
    }
}
