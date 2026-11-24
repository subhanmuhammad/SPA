<?php

namespace Tests\Feature;

use App\Enums\BookingStatus;
use App\Enums\PaymentStatus;
use App\Models\Booking;
use App\Models\Branch;
use App\Models\Payment;
use App\Models\PaymentMethod;
use App\Models\Treatment;
use App\Models\User;
use Database\Seeders\PaymentMethodSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Tests\TestCase;

class PaymentTest extends TestCase
{
    use RefreshDatabase;

    private Branch $branch;

    private User $admin;

    private Booking $booking;

    private PaymentMethod $cash;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(PaymentMethodSeeder::class);
        $this->cash = PaymentMethod::where('name', 'Cash')->first();
        $this->branch = Branch::factory()->create();
        $this->admin = User::factory()->create();
        $this->admin->branches()->attach($this->branch);
        $this->booking = $this->bookingAt($this->branch, Treatment::factory()->create(['price' => 150000]));
        $this->actingAs($this->admin);
    }

    private function complete(): void
    {
        $this->booking->forceFill(['status' => BookingStatus::Completed])->save();
    }

    private function pay(int $amount, ?string $key = null)
    {
        return $this->post(route('admin.bookings.payments.store', $this->booking), [
            'payment_method_id' => $this->cash->id,
            'amount' => $amount,
            'idempotency_key' => $key ?? (string) Str::uuid(),
        ]);
    }

    public function test_payment_only_after_treatment_completed(): void
    {
        $this->pay(150000)->assertSessionHasErrors('amount');
        $this->assertSame(0, Payment::count());
    }

    public function test_full_payment_marks_paid_and_creates_receipt(): void
    {
        $this->complete();

        $this->pay(150000)->assertSessionHasNoErrors();

        $booking = $this->booking->fresh();
        $this->assertSame(PaymentStatus::Paid, $booking->payment_status);
        $this->assertSame(150000, $booking->paid_amount);
        $this->assertMatchesRegularExpression('/^ZN-\d{8}-\d{6}$/', Payment::sole()->receipt_number);
        $this->assertDatabaseHas('audit_logs', ['action' => 'payment.payment', 'subject_id' => $booking->id]);
        $this->get(route('admin.bookings.receipt', $booking))->assertOk()->assertSee('KUITANSI')->assertSee('Lunas');
    }

    public function test_partial_payments_then_paid_and_overpayment_rejected(): void
    {
        $this->complete();

        $this->pay(100000);
        $this->assertSame(PaymentStatus::PartiallyPaid, $this->booking->fresh()->payment_status);

        $this->pay(60000)->assertSessionHasErrors('amount');
        $this->pay(50000)->assertSessionHasNoErrors();
        $this->assertSame(PaymentStatus::Paid, $this->booking->fresh()->payment_status);
    }

    public function test_paid_at_keeps_application_time_after_updates(): void
    {
        $this->complete();
        Carbon::setTestNow('2030-01-15 10:00:00');

        $this->pay(150000);
        Payment::sole()->update(['note' => 'Diperbarui']);

        $this->assertSame('2030-01-15 10:00:00', Payment::sole()->paid_at->toDateTimeString());
    }

    public function test_double_submit_records_payment_once(): void
    {
        $this->complete();
        $key = (string) Str::uuid();

        $this->pay(50000, $key);
        $this->pay(50000, $key);

        $this->assertSame(1, Payment::count());
        $this->assertSame(50000, $this->booking->fresh()->paid_amount);
    }

    public function test_inactive_method_is_rejected(): void
    {
        $this->complete();
        $this->cash->update(['is_active' => false]);

        $this->pay(150000)->assertSessionHasErrors('payment_method_id');
    }

    public function test_refund_is_capped_needs_reason_and_updates_status(): void
    {
        $this->complete();
        $this->pay(150000);
        $refund = fn (array $data) => $this->post(route('admin.bookings.refunds.store', $this->booking), [
            'payment_method_id' => $this->cash->id, 'idempotency_key' => (string) Str::uuid(), ...$data,
        ]);

        $refund(['amount' => 50000])->assertSessionHasErrors('reason');
        $refund(['amount' => 200000, 'reason' => 'Salah input'])->assertSessionHasErrors('amount');
        $refund(['amount' => 50000, 'reason' => 'Salah input'])->assertSessionHasNoErrors();
        $this->assertSame(PaymentStatus::PartiallyPaid, $this->booking->fresh()->payment_status);

        $refund(['amount' => 100000, 'reason' => 'Pelanggan komplain'])->assertSessionHasNoErrors();
        $this->assertSame(PaymentStatus::Refunded, $this->booking->fresh()->payment_status);
        $this->assertSame(0, $this->booking->fresh()->paid_amount);
        $this->assertDatabaseHas('audit_logs', ['action' => 'payment.refund']);
    }

    public function test_other_branch_admin_cannot_pay_refund_or_print(): void
    {
        $this->complete();
        $outsider = User::factory()->create();
        $outsider->branches()->attach(Branch::factory()->create());
        $this->actingAs($outsider);

        $this->pay(150000)->assertForbidden();
        $this->post(route('admin.bookings.refunds.store', $this->booking), ['payment_method_id' => $this->cash->id, 'amount' => 1, 'reason' => 'x', 'idempotency_key' => (string) Str::uuid()])->assertForbidden();
        $this->get(route('admin.bookings.receipt', $this->booking))->assertForbidden();
        $this->get(route('admin.payments.index'))->assertOk()->assertDontSee($this->booking->code);
    }

    public function test_transactions_page_totals(): void
    {
        $this->complete();
        $this->pay(150000);

        $this->get(route('admin.payments.index'))->assertOk()->assertSee($this->booking->code)->assertSee('150.000');
    }

    public function test_customer_receipt_requires_tracking_access(): void
    {
        $this->complete();
        $this->pay(150000);
        auth()->logout();
        $this->flushSession();

        $this->get(route('bookings.receipt', $this->booking->code))->assertNotFound();
        $this->post(route('bookings.lookup.submit'), ['code' => $this->booking->code, 'phone' => $this->booking->customer_phone]);
        $this->get(route('bookings.receipt', $this->booking->code))->assertOk()->assertSee('Lunas');
    }

    public function test_only_super_admin_manages_payment_methods(): void
    {
        $this->get(route('admin.payment-methods.index'))->assertForbidden();

        $this->actingAs(User::factory()->superAdmin()->create())
            ->post(route('admin.payment-methods.store'), ['name' => 'Debit'])->assertSessionHasNoErrors();
        $this->assertDatabaseHas('payment_methods', ['name' => 'Debit', 'is_active' => true]);
    }
}
