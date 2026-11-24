<?php

namespace Tests;

use App\Models\Booking;
use App\Models\Branch;
use App\Models\Customer;
use App\Models\Therapist;
use App\Models\Treatment;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function bookingAt(Branch $branch, Treatment $treatment, string $start = '+1 day 10:00', ?Therapist $therapist = null): Booking
    {
        $treatment->branches()->syncWithoutDetaching([$branch->id]);
        if (! $therapist) {
            $therapist = Therapist::factory()->at($branch)->workingEveryDay('08:00', '20:00')->create();
            $therapist->treatments()->syncWithoutDetaching([$treatment->id]);
        }
        $offered = Treatment::offeredAt($branch)->with(['category', 'items'])->findOrFail($treatment->id);
        $customer = Customer::create(['name' => 'Budi', 'phone' => '0812'.random_int(1000000, 9999999)]);

        return Booking::reserve($branch, $offered, CarbonImmutable::parse($start)->startOfMinute(), $therapist->id, $customer, ['name' => 'Budi']);
    }
}
