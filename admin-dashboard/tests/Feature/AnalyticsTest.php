<?php

namespace Tests\Feature;

use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Models\Branch;
use App\Models\PaymentMethod;
use App\Models\Therapist;
use App\Models\Treatment;
use App\Models\User;
use App\Support\Analytics;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Tests\TestCase;

class AnalyticsTest extends TestCase
{
    use RefreshDatabase;

    private Branch $branch;

    private Therapist $therapist;

    private Treatment $treatment;

    private PaymentMethod $cash;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2026-11-10 07:00:00');
        $this->branch = Branch::factory()->create();
        $this->treatment = Treatment::factory()->create(['name' => 'Pijat 60', 'price' => 100000, 'duration_minutes' => 60]);
        $this->therapist = Therapist::factory()->at($this->branch)->workingEveryDay('09:00', '17:00')->create(['name' => 'Ayu']);
        $this->therapist->treatments()->attach($this->treatment);
        $this->cash = PaymentMethod::create(['name' => 'Cash']);
        $this->admin = User::factory()->create();
        $this->admin->branches()->attach($this->branch);
    }

    private function completedAndPaid(string $time, int $pay = 100000): Booking
    {
        $booking = $this->bookingAt($this->branch, $this->treatment, "2026-11-10 {$time}", $this->therapist);
        $booking->forceFill(['status' => BookingStatus::Completed])->save();
        if ($pay) {
            $booking->recordPayment($pay, $this->cash, (string) Str::uuid(), $this->admin);
        }

        return $booking;
    }

    private function analytics(?Branch $branch = null): Analytics
    {
        return new Analytics(collect([($branch ?? $this->branch)->id]), CarbonImmutable::parse('2026-11-10'), CarbonImmutable::parse('2026-11-10'));
    }

    public function test_revenue_refund_outstanding_and_average(): void
    {
        $paid = $this->completedAndPaid('09:00');
        $this->completedAndPaid('10:00', 40000);
        $paid->refund(20000, $this->cash, (string) Str::uuid(), $this->admin, 'Koreksi');

        $revenue = $this->analytics()->revenue();

        $this->assertSame(140000, $revenue['received']);
        $this->assertSame(20000, $revenue['refunded']);
        $this->assertSame(120000, $revenue['net']);
        $this->assertSame(60000, $revenue['average']);
        $this->assertSame(80000, $this->analytics()->outstanding());
        $this->assertSame(120000, $this->analytics()->revenueSeries('daily', CarbonImmutable::parse('2026-11-10'))['10 Nov']);
        $this->assertCount(30, $this->analytics()->revenueSeries('daily', CarbonImmutable::parse('2026-11-10')));
    }

    public function test_status_treatment_and_therapist_figures(): void
    {
        $this->completedAndPaid('09:00');
        $cancelled = $this->bookingAt($this->branch, $this->treatment, '2026-11-10 11:00', $this->therapist);
        $cancelled->forceFill(['status' => BookingStatus::Cancelled])->save();

        $analytics = $this->analytics();
        $this->assertSame(1, $analytics->statusCounts()['completed']);
        $this->assertSame(1, $analytics->statusCounts()['cancelled']);

        $row = $analytics->treatments()->firstWhere('name', 'Pijat 60');
        $this->assertSame([2, 1, 1, 100000], [(int) $row->booked, (int) $row->completed, (int) $row->lost, (int) $row->value]);

        $ayu = $analytics->therapists()->firstWhere('name', 'Ayu');
        $this->assertSame(12.5, $ayu->utilization);
        $this->assertSame(1, $ayu->lost);
    }

    public function test_customer_metrics_mark_insufficient_data(): void
    {
        $returning = $this->completedAndPaid('09:00');
        $earlier = $this->bookingAt($this->branch, $this->treatment, '2026-11-10 13:00', $this->therapist);
        $earlier->forceFill(['customer_id' => $returning->customer_id, 'status' => BookingStatus::Completed, 'starts_at' => '2026-10-01 10:00', 'ends_at' => '2026-10-01 11:00'])->save();
        $this->completedAndPaid('11:00');

        $customers = $this->analytics()->customers();

        $this->assertSame(2, $customers['visitors']);
        $this->assertSame(1, $customers['new']);
        $this->assertSame(1, $customers['returning']);
        $this->assertNull($customers['retention']);
        $this->assertNull($customers['clv']);
        $this->assertSame([], array_values(array_filter($this->analytics()->insights(), fn ($i) => str_contains($i, 'Jam paling ramai'))));
    }

    public function test_decline_insight_needs_enough_previous_data(): void
    {
        foreach (range(0, 5) as $i) {
            $b = $this->bookingAt($this->branch, $this->treatment, '2026-11-10 '.(9 + $i).':00', $this->therapist);
            $b->forceFill(['starts_at' => "2026-11-09 1{$i}:00", 'ends_at' => "2026-11-09 1{$i}:30"])->save();
        }
        $this->bookingAt($this->branch, $this->treatment, '2026-11-10 09:00', $this->therapist);

        $insights = $this->analytics()->insights();

        $this->assertContains('Booking Pijat 60 turun dari 6 menjadi 1 dibanding periode sebelumnya.', $insights);
    }

    public function test_reports_are_branch_scoped_and_bi_is_super_admin_only(): void
    {
        $this->completedAndPaid('09:00');
        $other = Branch::factory()->create(['name' => 'Cabang Lain']);
        $foreign = $this->bookingAt($other, $this->treatment, '2026-11-10 10:00');
        $foreign->forceFill(['status' => BookingStatus::Completed])->save();
        $foreign->recordPayment($foreign->total_price, $this->cash, (string) Str::uuid(), $this->admin);

        $this->actingAs($this->admin);
        $this->get(route('admin.reports', ['from' => '2026-11-10', 'to' => '2026-11-10']))->assertOk()->assertSee('100.000')->assertDontSee('200.000');
        $this->get(route('admin.reports', ['branch' => $other->id]))->assertSessionHasErrors('branch');
        $this->get(route('admin.reports', ['from' => '2025-01-01', 'to' => '2026-11-10']))->assertSessionHasErrors('to');
        $this->get(route('admin.insights'))->assertForbidden();
        $this->get(route('admin.dashboard'))->assertOk()->assertSee('100.000');

        $this->actingAs(User::factory()->superAdmin()->create())
            ->get(route('admin.insights', ['from' => '2026-11-10', 'to' => '2026-11-10']))
            ->assertOk()->assertSee('Cabang Lain')->assertSee('200.000');
    }
}
