<?php

namespace Tests\Feature;

use App\Enums\BookingStatus;
use App\Enums\ServiceType;
use App\Models\Booking;
use App\Models\Branch;
use App\Models\Customer;
use App\Models\HomecareServiceArea;
use App\Models\Therapist;
use App\Models\Treatment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminOperationsTest extends TestCase
{
    use RefreshDatabase;

    private Branch $branch;

    private User $admin;

    private Treatment $treatment;

    private Therapist $therapist;

    protected function setUp(): void
    {
        parent::setUp();
        $this->branch = Branch::factory()->create();
        $this->admin = User::factory()->create();
        $this->admin->branches()->attach($this->branch);
        $this->treatment = Treatment::factory()->create(['price' => 115000, 'duration_minutes' => 60]);
        $this->treatment->branches()->attach($this->branch);
        $this->therapist = Therapist::factory()->at($this->branch)->workingEveryDay('08:00', '20:00')->create();
        $this->therapist->treatments()->attach($this->treatment);
        $this->actingAs($this->admin);
    }

    private function manual(array $overrides = [])
    {
        return $this->post(route('admin.bookings.store'), [
            'branch_id' => $this->branch->id,
            'treatment_id' => $this->treatment->id,
            'date' => now()->addDay()->toDateString(),
            'time' => '10:00',
            'name' => 'Walk In',
            'phone' => '081211112222',
            ...$overrides,
        ]);
    }

    public function test_admin_creates_confirmed_manual_booking_with_server_price(): void
    {
        $this->manual(['total_price' => 1])->assertSessionHasNoErrors();

        $booking = Booking::sole();
        $this->assertSame(BookingStatus::Confirmed, $booking->status);
        $this->assertSame('admin', $booking->source);
        $this->assertSame(115000, $booking->total_price);
    }

    public function test_manual_booking_respects_branch_and_conflicts(): void
    {
        $other = Branch::factory()->create();
        $this->manual(['branch_id' => $other->id])->assertSessionHasErrors('branch_id');

        $this->manual();
        $this->manual(['phone' => '081233334444', 'time' => '10:30'])->assertSessionHasErrors('time');
        $this->assertSame(1, Booking::count());
    }

    public function test_manual_home_care_needs_area_and_address(): void
    {
        $homeCare = Treatment::factory()->create(['service_type' => ServiceType::HomeCare, 'duration_minutes' => 60]);
        $homeCare->branches()->attach($this->branch);
        $this->therapist->treatments()->attach($homeCare);
        $area = HomecareServiceArea::factory()->for($this->branch)->create(['travel_fee' => 10000]);

        $this->manual(['treatment_id' => $homeCare->id])->assertSessionHasErrors('area_id');
        $this->manual(['treatment_id' => $homeCare->id, 'area_id' => $area->id])->assertSessionHasErrors('address');
        $this->manual(['treatment_id' => $homeCare->id, 'area_id' => $area->id, 'address' => 'Jl. A'])->assertSessionHasNoErrors();
        $this->assertSame(10000, Booking::sole()->travel_fee);
    }

    public function test_change_treatment_resnapshots_price_and_duration(): void
    {
        $booking = $this->bookingAt($this->branch, $this->treatment, '+1 day 10:00', $this->therapist);
        $longer = Treatment::factory()->create(['name' => 'Pijat 120', 'price' => 190000, 'duration_minutes' => 120]);
        $longer->branches()->attach($this->branch, ['price' => 200000]);
        $this->therapist->treatments()->attach($longer);

        $this->put(route('admin.bookings.treatment', $booking), ['treatment_id' => $longer->id])->assertSessionHasNoErrors();

        $booking->refresh();
        $this->assertSame(200000, $booking->total_price);
        $this->assertSame(120, (int) $booking->starts_at->diffInMinutes($booking->ends_at));
        $this->assertSame('Pijat 120', $booking->items()->sole()->name);
        $this->assertDatabaseHas('audit_logs', ['action' => 'booking.treatment_changed']);
    }

    public function test_change_treatment_rejected_without_competent_therapist_or_after_completion(): void
    {
        $booking = $this->bookingAt($this->branch, $this->treatment, '+1 day 10:00', $this->therapist);
        $unknown = Treatment::factory()->create();
        $unknown->branches()->attach($this->branch);

        $this->put(route('admin.bookings.treatment', $booking), ['treatment_id' => $unknown->id])->assertSessionHasErrors('treatment_id');

        $booking->forceFill(['status' => BookingStatus::Completed])->save();
        $this->therapist->treatments()->attach($unknown);
        $this->put(route('admin.bookings.treatment', $booking), ['treatment_id' => $unknown->id])->assertSessionHasErrors('treatment_id');
    }

    public function test_admin_only_sees_customers_of_own_branch(): void
    {
        $mine = $this->bookingAt($this->branch, $this->treatment);
        $foreign = $this->bookingAt(Branch::factory()->create(), $this->treatment);
        Customer::find($foreign->customer_id)->update(['name' => 'Orang Lain']);

        $this->get(route('admin.customers.index'))->assertOk()->assertSee($mine->customer_phone)->assertDontSee('Orang Lain');
        $this->get(route('admin.customers.show', $mine->customer_id))->assertOk();
        $this->get(route('admin.customers.show', $foreign->customer_id))->assertForbidden();
        $this->put(route('admin.customers.update', $foreign->customer_id), ['name' => 'X', 'phone' => '081299990000'])->assertForbidden();
    }

    public function test_customer_update_is_validated_and_audited(): void
    {
        $booking = $this->bookingAt($this->branch, $this->treatment);
        $other = Customer::create(['name' => 'Lain', 'phone' => '081277778888']);

        $this->put(route('admin.customers.update', $booking->customer_id), ['name' => 'Budi S', 'phone' => '+62 812-7777-8888'])->assertSessionHasErrors('phone');
        $this->put(route('admin.customers.update', $booking->customer_id), ['name' => 'Budi S', 'phone' => '081200001111', 'email' => 'budi@contoh.id'])->assertSessionHasNoErrors();

        $this->assertSame('Budi S', Customer::find($booking->customer_id)->name);
        $this->assertDatabaseHas('audit_logs', ['action' => 'customer.updated']);
        $this->assertNotNull($other);
    }
}
