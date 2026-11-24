<?php

namespace Tests\Feature;

use App\Enums\ServiceType;
use App\Models\Booking;
use App\Models\Branch;
use App\Models\HomecareServiceArea;
use App\Models\Therapist;
use App\Models\Treatment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class HomeCareTest extends TestCase
{
    use RefreshDatabase;

    private Branch $branch;

    private Treatment $homeCare;

    private Treatment $inStore;

    private HomecareServiceArea $area;

    private Therapist $therapist;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2026-11-10 07:00:00');
        $this->branch = Branch::factory()->create(['opening_time' => '08:00', 'closing_time' => '21:00']);
        $this->homeCare = Treatment::factory()->create(['service_type' => ServiceType::HomeCare, 'duration_minutes' => 90, 'price' => 160000]);
        $this->inStore = Treatment::factory()->create(['duration_minutes' => 60]);
        $this->branch->treatments()->attach([$this->homeCare->id, $this->inStore->id]);
        $this->area = HomecareServiceArea::factory()->for($this->branch)->create(['travel_fee' => 20000, 'travel_minutes' => 30]);
        $this->therapist = Therapist::factory()->at($this->branch)->workingEveryDay('09:00', '17:00')->create();
        $this->therapist->treatments()->attach([$this->homeCare->id, $this->inStore->id]);
    }

    private function book(array $overrides = [])
    {
        return $this->post(route('bookings.store'), [
            'cabang' => $this->branch->slug,
            'tipe' => 'home_care',
            'area' => $this->area->id,
            'layanan' => $this->homeCare->slug,
            'tanggal' => '2026-11-10',
            'jam' => '10:00',
            'name' => 'Rina',
            'phone' => '081234567890',
            'address' => 'Jl. Contoh No. 1',
            ...$overrides,
        ]);
    }

    public function test_home_care_needs_address_and_area(): void
    {
        $this->book(['address' => ''])->assertSessionHasErrors('address');
        $this->book(['area' => ''])->assertSessionHasErrors('area');
        $this->assertSame(0, Booking::count());
    }

    public function test_area_from_other_branch_or_inactive_is_rejected(): void
    {
        $foreign = HomecareServiceArea::factory()->for(Branch::factory())->create();
        $this->book(['area' => $foreign->id])->assertNotFound();

        $this->area->update(['is_active' => false]);
        $this->book()->assertNotFound();
    }

    public function test_travel_fee_is_snapshotted_into_total(): void
    {
        $this->book(['location_url' => 'https://maps.app.goo.gl/abc'])->assertSessionHasNoErrors();

        $booking = Booking::sole();
        $this->assertSame(180000, $booking->total_price);
        $this->assertSame(20000, $booking->travel_fee);
        $this->assertSame(30, $booking->travel_minutes);
        $this->assertSame($this->area->name, $booking->area_name);

        $this->area->update(['travel_fee' => 50000]);
        $this->assertSame(180000, $booking->fresh()->total_price);
    }

    public function test_location_link_must_be_https(): void
    {
        $this->book(['location_url' => 'javascript:alert(1)'])->assertSessionHasErrors('location_url');
    }

    public function test_travel_time_blocks_therapist_before_and_after(): void
    {
        $this->book()->assertSessionHasNoErrors();

        $inStore = ['tipe' => 'in_store', 'area' => null, 'layanan' => $this->inStore->slug, 'address' => null, 'phone' => '081300000000'];
        $this->book([...$inStore, 'jam' => '09:00'])->assertSessionHasErrors('time');
        $this->book([...$inStore, 'jam' => '11:30'])->assertSessionHasErrors('time');
        $this->book([...$inStore, 'jam' => '12:00'])->assertSessionHasNoErrors();
    }

    public function test_travel_must_fit_inside_therapist_shift(): void
    {
        $this->book(['jam' => '09:00'])->assertSessionHasErrors('time');
        $this->book(['jam' => '09:30'])->assertSessionHasNoErrors();
    }

    public function test_daily_home_care_limit_per_therapist(): void
    {
        $this->branch->update(['home_care_max_per_therapist' => 1]);

        $this->book()->assertSessionHasNoErrors();
        $this->book(['jam' => '14:00', 'phone' => '081300000001'])->assertSessionHasErrors('time');
        $this->book(['tanggal' => '2026-11-11', 'phone' => '081300000001'])->assertSessionHasNoErrors();
    }

    public function test_area_step_appears_and_hides_home_care_without_areas(): void
    {
        $this->get(route('bookings.create', ['tipe' => 'home_care']))->assertSee($this->area->name)->assertSee('Transport');
        $this->get(route('bookings.create', ['tipe' => 'home_care', 'area' => $this->area->id, 'layanan' => $this->homeCare->slug, 'tanggal' => '2026-11-10']))
            ->assertOk()->assertDontSee('value="09:00"', false)->assertSee('value="09:30"', false);

        $this->area->delete();
        $this->get(route('bookings.create', ['tipe' => 'home_care']))->assertSee('Home Care belum tersedia');
    }

    public function test_admin_manages_areas_only_in_own_branch(): void
    {
        $admin = User::factory()->create();
        $admin->branches()->attach($this->branch);
        $other = Branch::factory()->create();
        $foreignArea = HomecareServiceArea::factory()->for($other)->create();
        $this->actingAs($admin);

        $this->post(route('admin.home-care.areas.store'), ['branch_id' => $this->branch->id, 'name' => 'Palu Barat', 'travel_fee' => 15000, 'travel_minutes' => 20])
            ->assertRedirect()->assertSessionHasNoErrors();
        $this->post(route('admin.home-care.areas.store'), ['branch_id' => $other->id, 'name' => 'X', 'travel_fee' => 0, 'travel_minutes' => 0])->assertForbidden();
        $this->put(route('admin.home-care.areas.update', $foreignArea), ['name' => 'X', 'travel_fee' => 1, 'travel_minutes' => 1])->assertForbidden();
        $this->put(route('admin.home-care.limit', $other), ['home_care_max_per_therapist' => 1])->assertForbidden();
        $this->put(route('admin.home-care.areas.update', $this->area), ['name' => $this->area->name, 'travel_fee' => 25000, 'travel_minutes' => 300])
            ->assertSessionHasErrors('travel_minutes');

        $this->assertDatabaseHas('homecare_service_areas', ['branch_id' => $this->branch->id, 'name' => 'Palu Barat', 'travel_fee' => 15000]);
        $this->assertDatabaseHas('audit_logs', ['action' => 'home_care.area_created']);
    }

    public function test_admin_sets_departure_and_customer_sees_arrival(): void
    {
        $this->book();
        $booking = Booking::sole();
        $admin = User::factory()->superAdmin()->create();

        $this->actingAs($admin)->put(route('admin.bookings.departure', $booking), ['departure_time' => '10:30'])->assertSessionHasErrors('departure_time');
        $this->actingAs($admin)->put(route('admin.bookings.departure', $booking), ['departure_time' => '09:25'])->assertSessionHasNoErrors();

        $this->assertSame('2026-11-10 09:55:00', $booking->fresh()->estimatedArrivalAt()->toDateTimeString());
        $this->get(route('bookings.show', $booking->code))->assertSee('Perkiraan tiba')->assertSee('09:55');
    }
}
