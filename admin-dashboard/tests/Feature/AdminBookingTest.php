<?php

namespace Tests\Feature;

use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Models\Branch;
use App\Models\Customer;
use App\Models\Therapist;
use App\Models\Treatment;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class AdminBookingTest extends TestCase
{
    use RefreshDatabase;

    private Branch $branch;

    private User $admin;

    private Treatment $treatment;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2026-11-10 08:00:00');
        $this->branch = Branch::factory()->create();
        $this->admin = User::factory()->create();
        $this->admin->branches()->attach($this->branch);
        $this->treatment = Treatment::factory()->create(['duration_minutes' => 60]);
        $this->treatment->branches()->attach($this->branch);
    }

    private function therapistAt(Branch $branch): Therapist
    {
        $therapist = Therapist::factory()->at($branch)->workingEveryDay()->create();
        $therapist->treatments()->attach($this->treatment);

        return $therapist;
    }

    private function reserveAt(Branch $branch, string $time = '10:00', ?int $therapistId = null): Booking
    {
        $this->treatment->branches()->syncWithoutDetaching([$branch->id]);
        $treatment = Treatment::offeredAt($branch)->with(['category', 'items'])->findOrFail($this->treatment->id);
        $customer = Customer::create(['phone' => '0812'.random_int(1000000, 9999999), 'name' => 'Budi']);

        return Booking::reserve($branch, $treatment, CarbonImmutable::parse("2026-11-10 {$time}"), $therapistId, $customer, ['name' => 'Budi']);
    }

    public function test_admin_walks_booking_through_valid_statuses(): void
    {
        $this->therapistAt($this->branch);
        $booking = $this->reserveAt($this->branch);
        $this->actingAs($this->admin);

        foreach ([BookingStatus::Confirmed, BookingStatus::CheckedIn, BookingStatus::InProgress, BookingStatus::Completed] as $status) {
            $this->put(route('admin.bookings.status', $booking), ['status' => $status->value])->assertSessionHasNoErrors();
        }

        $this->assertSame(BookingStatus::Completed, $booking->fresh()->status);
        $this->assertSame(5, $booking->histories()->count());
        $this->assertDatabaseHas('audit_logs', ['action' => 'booking.status_changed', 'subject_id' => $booking->id]);
    }

    public function test_check_in_only_on_booking_day_and_no_show_after_start(): void
    {
        $this->therapistAt($this->branch);
        $tomorrow = $this->reserveAt($this->branch);
        $tomorrow->forceFill(['starts_at' => '2026-11-11 10:00', 'ends_at' => '2026-11-11 11:00', 'status' => BookingStatus::Confirmed])->save();
        $this->actingAs($this->admin);

        $this->put(route('admin.bookings.status', $tomorrow), ['status' => 'checked_in'])->assertSessionHasErrors('status');
        $this->put(route('admin.bookings.status', $tomorrow), ['status' => 'no_show'])->assertSessionHasErrors('status');

        Carbon::setTestNow('2026-11-11 10:30:00');
        $this->put(route('admin.bookings.status', $tomorrow), ['status' => 'no_show'])->assertSessionHasNoErrors();
    }

    public function test_deactivated_admin_is_logged_out_on_next_request(): void
    {
        $this->actingAs($this->admin);
        $this->admin->forceFill(['is_active' => false])->save();

        $this->get(route('admin.bookings.index'))->assertRedirect(route('login'));
        $this->assertGuest();
    }

    public function test_invalid_status_jump_is_rejected(): void
    {
        $this->therapistAt($this->branch);
        $booking = $this->reserveAt($this->branch);

        $this->actingAs($this->admin)->put(route('admin.bookings.status', $booking), ['status' => 'completed'])->assertSessionHasErrors('status');

        $this->assertSame(BookingStatus::Pending, $booking->fresh()->status);
    }

    public function test_admin_cannot_see_or_change_other_branch_booking(): void
    {
        $other = Branch::factory()->create();
        $this->therapistAt($other);
        $foreign = $this->reserveAt($other);
        $this->actingAs($this->admin);

        $this->get(route('admin.bookings.show', $foreign))->assertForbidden();
        $this->put(route('admin.bookings.status', $foreign), ['status' => 'confirmed'])->assertForbidden();
        $this->put(route('admin.bookings.reschedule', $foreign), ['date' => '2026-11-11', 'time' => '10:00'])->assertForbidden();
        $this->get(route('admin.bookings.index'))->assertOk()->assertDontSee($foreign->code);
    }

    public function test_reschedule_checks_conflicts_and_is_audited(): void
    {
        $a = $this->therapistAt($this->branch);
        $first = $this->reserveAt($this->branch, '10:00', $a->id);
        $second = $this->reserveAt($this->branch, '12:00', $a->id);
        $this->actingAs($this->admin);

        $this->put(route('admin.bookings.reschedule', $second), ['date' => '2026-11-10', 'time' => '10:30', 'therapist_id' => $a->id])
            ->assertSessionHasErrors('time');

        $this->put(route('admin.bookings.reschedule', $second), ['date' => '2026-11-10', 'time' => '12:30', 'therapist_id' => $a->id])
            ->assertSessionHasNoErrors();

        $this->assertSame('2026-11-10 12:30:00', $second->fresh()->starts_at->toDateTimeString());
        $this->assertSame('2026-11-10 10:00:00', $first->fresh()->starts_at->toDateTimeString());
        $this->assertDatabaseHas('audit_logs', ['action' => 'booking.rescheduled', 'subject_id' => $second->id]);
    }

    public function test_super_admin_sees_all_branches(): void
    {
        $other = Branch::factory()->create();
        $this->therapistAt($other);
        $foreign = $this->reserveAt($other);

        $this->actingAs(User::factory()->superAdmin()->create())->get(route('admin.bookings.index'))->assertSee($foreign->code);
    }
}
