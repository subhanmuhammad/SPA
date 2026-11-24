<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\Therapist;
use App\Models\Treatment;
use Illuminate\Database\Seeder;

class DemoTherapistSeeder extends Seeder
{
    public function run(): void
    {
        $treatmentIds = Treatment::pluck('id');

        Branch::each(function (Branch $branch) use ($treatmentIds) {
            $branch->homecareAreas()->firstOrCreate(['name' => 'Area Demo'], ['travel_minutes' => 30]);

            foreach (['Terapis Demo 1', 'Terapis Demo 2'] as $name) {
                $therapist = Therapist::firstOrNew(['branch_id' => $branch->id, 'name' => $name]);
                $therapist->branch_id = $branch->id;
                $therapist->save();
                $therapist->treatments()->sync($treatmentIds);
                $therapist->schedules()->delete();
                $therapist->schedules()->createMany(collect(range(0, 6))->map(fn ($day) => [
                    'day_of_week' => $day,
                    'start_time' => substr($branch->opening_time, 0, 5),
                    'end_time' => substr($branch->closing_time, 0, 5),
                ])->all());
            }
        });
    }
}
