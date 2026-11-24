<?php

namespace Tests\Feature;

use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Models\Branch;
use App\Models\Therapist;
use App\Models\Treatment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class BookingTest extends TestCase
{
    use RefreshDatabase;

    private Branch $branch;

    private Treatment $treatment;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2026-11-10 08:00:00');
        $this->branch = Branch::factory()->create(['opening_time' => '09:00', 'closing_time' => '21:00']);
        $this->treatment = Treatment::factory()->create(['name' => 'Pijat 60', 'duration_minutes' => 60, 'price' => 115000]);
        $this->treatment->branches()->attach($this->branch);
    }

    private function therapist(?array $skills = null): Therapist
    {
        $therapist = Therapist::factory()->at($this->branch)->workingEveryDay()->create();
        $therapist->treatments()->attach($skills ?? [$this->treatment->id]);

        return $therapist;
    }

    private function book(array $overrides = [])
    {
        return $this->post(route('bookings.store'), [
            'cabang' => $this->branch->slug,
            'tipe' => 'in_store',
            'layanan' => $this->treatment->slug,
            'tanggal' => '2026-11-10',
            'jam' => '10:00',
            'name' => 'Siti',
            'phone' => '0812 3456 7890',
            ...$overrides,
        ]);
    }

    public function test_every_booking_step_renders(): void
    {
        $this->therapist();
        $steps = ['tipe' => 'in_store', 'layanan' => $this->treatment->slug, 'tanggal' => '2026-11-10', 'jam' => '10:00'];

        $query = [];
        $this->get(route('bookings.create'))->assertOk();
        foreach ($steps as $key => $value) {
            $query[$key] = $value;
            $this->get(route('bookings.create', $query))->assertOk();
        }
        $this->get(route('bookings.create', $query))->assertSee('Konfirmasi Booking');
    }

    public function test_available_times_follow_schedule_and_duration(): void
    {
        $this->therapist();

        $this->get(route('bookings.create', ['cabang' => $this->branch->slug, 'tipe' => 'in_store', 'layanan' => $this->treatment->slug, 'tanggal' => '2026-11-10']))
            ->assertOk()
            ->assertSee('09:00')->assertSee('16:00')->assertDontSee('16:30');
    }

    public function test_customer_books_with_price_snapshot_and_can_track(): void
    {
        $this->therapist();

        $response = $this->book(['total_price' => 1, 'price' => 1]);

        $booking = Booking::sole();
        $response->assertRedirectContains(route('bookings.show', $booking->code));
        $this->assertSame(BookingStatus::Pending, $booking->status);
        $this->assertSame(115000, $booking->total_price);
        $this->assertSame('08123456789'.'0', $booking->customer_phone);
        $this->assertSame('2026-11-10 11:00:00', $booking->ends_at->toDateTimeString());
        $this->assertDatabaseHas('booking_items', ['booking_id' => $booking->id, 'name' => 'Pijat 60', 'price' => 115000, 'duration_minutes' => 60]);

        $this->treatment->update(['price' => 200000, 'name' => 'Pijat Baru']);
        $this->assertSame(115000, $booking->fresh()->total_price);
        $this->get(route('bookings.show', $booking->code))->assertOk()->assertSee('Pijat 60')->assertSee('115.000');
    }

    public function test_same_therapist_cannot_be_double_booked(): void
    {
        $therapist = $this->therapist();

        $this->book(['terapis' => $therapist->id])->assertSessionHasNoErrors();
        $this->book(['terapis' => $therapist->id, 'jam' => '10:30', 'phone' => '081300000000'])->assertSessionHasErrors('time');

        $this->assertSame(1, Booking::count());
    }

    public function test_any_therapist_spreads_load_and_stops_when_full(): void
    {
        $a = $this->therapist();
        $b = $this->therapist();

        $this->book()->assertSessionHasNoErrors();
        $this->book(['phone' => '081300000001'])->assertSessionHasNoErrors();
        $this->book(['phone' => '081300000002'])->assertSessionHasErrors('time');

        $this->assertEqualsCanonicalizing([$a->id, $b->id], Booking::pluck('therapist_id')->all());
    }

    public function test_cancelled_booking_frees_the_slot(): void
    {
        $therapist = $this->therapist();
        $this->book();
        Booking::sole()->forceFill(['status' => BookingStatus::Cancelled])->save();

        $this->book(['phone' => '081300000003'])->assertSessionHasNoErrors();
        $this->assertSame(2, $therapist->bookings()->count());
    }

    public function test_buffer_between_bookings_is_respected(): void
    {
        config(['booking.buffer_minutes' => 15]);
        $this->therapist();
        $this->book();

        $this->book(['jam' => '11:00', 'phone' => '081300000004'])->assertSessionHasErrors('time');
        $this->book(['jam' => '11:30', 'phone' => '081300000004'])->assertSessionHasNoErrors();
    }

    public function test_leave_outside_hours_and_past_times_are_rejected(): void
    {
        $therapist = $this->therapist();

        $this->book(['jam' => '07:30'])->assertSessionHasErrors('time');
        $this->book(['jam' => '16:30'])->assertSessionHasErrors('time');
        Carbon::setTestNow('2026-11-10 12:00:00');
        $this->book(['jam' => '10:00'])->assertSessionHasErrors('time');

        $therapist->leaves()->create(['date' => '2026-11-11']);
        $this->book(['tanggal' => '2026-11-11'])->assertSessionHasErrors('time');

        $this->assertSame(0, Booking::count());
    }

    public function test_package_needs_therapist_competent_in_every_component(): void
    {
        $sauna = Treatment::factory()->create(['name' => 'Sauna']);
        $package = Treatment::factory()->create(['name' => 'Paket Uji', 'duration_minutes' => 90, 'price' => 180000, 'normal_price' => 200000]);
        $package->items()->createMany([
            ['name' => 'Pijat', 'treatment_id' => $this->treatment->id],
            ['name' => 'Sauna', 'treatment_id' => $sauna->id],
            ['name' => 'Keretek', 'treatment_id' => null],
        ]);
        $package->branches()->attach($this->branch);

        $partial = $this->therapist([$this->treatment->id]);
        $this->book(['layanan' => $package->slug, 'terapis' => $partial->id])->assertSessionHasErrors('time');

        $full = $this->therapist([$this->treatment->id, $sauna->id]);
        $this->book(['layanan' => $package->slug])->assertSessionHasNoErrors();

        $booking = Booking::sole();
        $this->assertSame($full->id, $booking->therapist_id);
        $this->assertSame(['Pijat', 'Sauna', 'Keretek'], $booking->items->first()->components);
        $this->assertSame(200000, $booking->items->first()->normal_price);
    }

    public function test_service_without_duration_is_not_bookable(): void
    {
        $this->treatment->update(['duration_minutes' => null]);
        $this->therapist();

        $this->book()->assertNotFound();
    }

    public function test_therapist_from_another_branch_cannot_be_requested(): void
    {
        $this->therapist();
        $foreign = Therapist::factory()->at(Branch::factory()->create())->workingEveryDay()->create();
        $foreign->treatments()->attach($this->treatment);

        $this->book(['terapis' => $foreign->id])->assertSessionHasErrors('time');
    }

    public function test_tracking_requires_session_token_or_code_and_phone(): void
    {
        $this->therapist();
        $this->book();
        $booking = Booking::sole();

        $this->flushSession();
        $this->get(route('bookings.show', $booking->code))->assertNotFound();
        $this->get(route('bookings.show', [$booking->code, 'token' => 'salah']))->assertNotFound();

        $this->post(route('bookings.lookup.submit'), ['code' => $booking->code, 'phone' => '081399999999'])->assertSessionHasErrors('code');
        $this->post(route('bookings.lookup.submit'), ['code' => strtolower($booking->code), 'phone' => '+62 812-3456-7890'])
            ->assertRedirect(route('bookings.show', $booking->code));
        $this->get(route('bookings.show', $booking->code))->assertOk()->assertSee('Ditampilkan setelah booking dikonfirmasi');
    }

    public function test_lookup_is_rate_limited(): void
    {
        foreach (range(1, 10) as $_) {
            $this->post(route('bookings.lookup.submit'), ['code' => 'ZNXXXXXX', 'phone' => '0812']);
        }

        $this->post(route('bookings.lookup.submit'), ['code' => 'ZNXXXXXX', 'phone' => '0812'])->assertTooManyRequests();
    }

    public function test_customer_can_request_change_on_open_booking(): void
    {
        $this->therapist();
        $this->book();
        $booking = Booking::sole();

        $this->post(route('bookings.request-change', $booking->code), ['type' => 'cancel', 'message' => 'Ada acara'])->assertRedirect();

        $this->assertSame('Pembatalan: Ada acara', $booking->fresh()->change_request);
        $this->assertSame(BookingStatus::Pending, $booking->fresh()->status);
    }
}
