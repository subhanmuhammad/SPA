<?php

namespace App\Models;

use App\Enums\BookingStatus;
use App\Enums\PaymentStatus;
use App\Enums\PaymentType;
use App\Enums\ServiceType;
use App\Support\BookingSlots;
use Carbon\CarbonInterface;
use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class Booking extends Model
{
    public ?string $plainTrackingToken = null;

    protected $guarded = ['id'];

    protected $hidden = ['tracking_token_hash'];

    protected function casts(): array
    {
        return [
            'status' => BookingStatus::class,
            'service_type' => ServiceType::class,
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'therapist_requested' => 'boolean',
            'total_price' => 'integer',
            'change_requested_at' => 'datetime',
            'departure_at' => 'datetime',
            'travel_fee' => 'integer',
            'travel_minutes' => 'integer',
            'paid_amount' => 'integer',
            'payment_status' => PaymentStatus::class,
        ];
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function therapist(): BelongsTo
    {
        return $this->belongsTo(Therapist::class)->withTrashed();
    }

    public function homecareArea(): BelongsTo
    {
        return $this->belongsTo(HomecareServiceArea::class, 'homecare_service_area_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(BookingItem::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class)->orderBy('id');
    }

    public function histories(): HasMany
    {
        return $this->hasMany(BookingStatusHistory::class)->latest('id');
    }

    public function scopeBlocking(Builder $query): void
    {
        $query->whereIn('status', BookingStatus::blocking());
    }

    public function scopeForUser(Builder $query, User $user): void
    {
        if (! $user->isSuperAdmin()) {
            $query->whereIn('branch_id', $user->branches()->select('branches.id'));
        }
    }

    public function matchesToken(?string $token): bool
    {
        return is_string($token) && hash_equals($this->tracking_token_hash, hash('sha256', $token));
    }

    public static function reserve(
        Branch $branch,
        Treatment $treatment,
        CarbonInterface $start,
        ?int $therapistId,
        Customer $customer,
        array $details,
        ?HomecareServiceArea $area = null,
        string $source = 'online',
    ): self {
        $isHomeCare = $treatment->service_type === ServiceType::HomeCare;
        if ($isHomeCare !== ($area !== null) || ($area && ($area->branch_id !== $branch->id || ! $area->is_active))) {
            throw ValidationException::withMessages(['area' => 'Area layanan tidak valid untuk layanan ini.']);
        }

        return DB::transaction(function () use ($branch, $treatment, $start, $therapistId, $customer, $details, $area, $source) {
            $free = (new BookingSlots($branch, $treatment, $area->travel_minutes ?? 0))->freeTherapists($start, $therapistId, lock: true);
            if ($free->isEmpty()) {
                throw ValidationException::withMessages(['time' => 'Jadwal ini baru saja terisi. Silakan pilih jam lain.']);
            }
            $therapist = self::leastBusy($free, $start);

            $token = Str::random(40);
            $booking = self::create([
                'code' => self::newCode(),
                'tracking_token_hash' => hash('sha256', $token),
                'branch_id' => $branch->id,
                'customer_id' => $customer->id,
                'therapist_id' => $therapist->id,
                'therapist_requested' => $therapistId !== null,
                'service_type' => $treatment->service_type,
                'homecare_service_area_id' => $area?->id,
                'area_name' => $area?->name,
                'travel_fee' => $area->travel_fee ?? 0,
                'travel_minutes' => $area->travel_minutes ?? 0,
                'starts_at' => $start,
                'ends_at' => $start->copy()->addMinutes($treatment->duration_minutes),
                'status' => BookingStatus::Pending,
                'source' => $source,
                'customer_name' => $details['name'],
                'customer_phone' => $customer->phone,
                'notes' => $details['notes'] ?? null,
                'address' => $details['address'] ?? null,
                'address_notes' => $details['address_notes'] ?? null,
                'location_url' => $details['location_url'] ?? null,
                'total_price' => $treatment->price + ($area->travel_fee ?? 0),
            ]);
            $booking->plainTrackingToken = $token;

            $booking->items()->create([
                'treatment_id' => $treatment->id,
                'name' => $treatment->name,
                'category' => $treatment->category->name,
                'service_type' => $treatment->service_type,
                'duration_minutes' => $treatment->duration_minutes,
                'price' => $treatment->price,
                'normal_price' => $treatment->normal_price,
                'components' => $treatment->items->pluck('name')->all() ?: null,
            ]);
            $booking->histories()->create(['to_status' => BookingStatus::Pending, 'user_id' => auth('web')->id()]);
            AuditLog::record('booking.created', $booking, ['source' => $source, 'therapist_id' => $therapist->id]);

            return $booking;
        });
    }

    public function moveTo(BookingStatus $status, ?User $user, ?string $note = null): void
    {
        DB::transaction(function () use ($status, $user, $note) {
            $locked = self::whereKey($this->id)->lockForUpdate()->firstOrFail();
            if (! $locked->status->canMoveTo($status)) {
                throw ValidationException::withMessages(['status' => "Status {$locked->status->label()} tidak bisa diubah menjadi {$status->label()}."]);
            }
            if (in_array($status, [BookingStatus::CheckedIn, BookingStatus::InProgress, BookingStatus::Completed], true) && $locked->starts_at->isAfter(today()->endOfDay())) {
                throw ValidationException::withMessages(['status' => 'Belum hari booking. Check-in dan perawatan hanya bisa dicatat pada tanggal booking.']);
            }
            if ($status === BookingStatus::NoShow && $locked->starts_at->isFuture()) {
                throw ValidationException::withMessages(['status' => 'Tandai tidak datang setelah jam booking lewat.']);
            }

            $this->histories()->create(['from_status' => $locked->status, 'to_status' => $status, 'user_id' => $user?->id, 'note' => $note]);
            $this->forceFill(['status' => $status, 'change_request' => $status->isOpen() ? $this->change_request : null])->save();
            AuditLog::record('booking.status_changed', $this, ['from' => $locked->status->value, 'to' => $status->value, 'note' => $note]);
        });
    }

    public function reschedule(CarbonInterface $start, ?int $therapistId, User $user): void
    {
        DB::transaction(function () use ($start, $therapistId) {
            $locked = self::whereKey($this->id)->lockForUpdate()->firstOrFail();
            if (! $locked->status->isOpen()) {
                throw ValidationException::withMessages(['date' => 'Hanya booking yang menunggu atau sudah dikonfirmasi yang bisa dijadwalkan ulang.']);
            }

            $item = $this->items()->firstOrFail();
            $treatment = Treatment::withTrashed()->with('items')->findOrFail($item->treatment_id);
            $treatment->duration_minutes = $item->duration_minutes;

            $free = (new BookingSlots($this->branch, $treatment, $this->travel_minutes))->freeTherapists($start, $therapistId, $this->id, lock: true);
            if ($free->isEmpty()) {
                throw ValidationException::withMessages(['time' => 'Tidak ada terapis yang tersedia pada jadwal tersebut.']);
            }
            $therapist = self::leastBusy($free, $start);

            $before = ['starts_at' => $this->starts_at->toDateTimeString(), 'therapist_id' => $this->therapist_id];
            $this->forceFill([
                'starts_at' => $start,
                'ends_at' => $start->copy()->addMinutes($item->duration_minutes),
                'therapist_id' => $therapist->id,
                'departure_at' => null,
                'change_request' => null,
                'change_requested_at' => null,
            ])->save();
            AuditLog::record('booking.rescheduled', $this, [
                'from' => $before,
                'to' => ['starts_at' => $this->starts_at->toDateTimeString(), 'therapist_id' => $therapist->id],
            ]);
        });
    }

    public function balance(): int
    {
        return max(0, $this->total_price - $this->paid_amount);
    }

    public function recordPayment(int $amount, PaymentMethod $method, string $key, User $user, ?string $reference = null, ?string $note = null): Payment
    {
        return $this->ledger(PaymentType::Payment, $amount, $method, $key, $user, $reference, $note, function (self $locked) use ($amount) {
            if ($locked->status !== BookingStatus::Completed) {
                return 'Pembayaran hanya bisa dicatat setelah perawatan selesai.';
            }

            return $amount > $locked->balance() ? 'Nominal melebihi sisa tagihan ('.number_format($locked->balance(), 0, ',', '.').').' : null;
        });
    }

    public function refund(int $amount, PaymentMethod $method, string $key, User $user, string $reason): Payment
    {
        return $this->ledger(PaymentType::Refund, $amount, $method, $key, $user, null, $reason, fn (self $locked) => $amount > $locked->paid_amount
            ? 'Refund melebihi jumlah yang sudah dibayar.'
            : null);
    }

    public function changeTreatment(Treatment $treatment, User $user): void
    {
        DB::transaction(function () use ($treatment) {
            $locked = self::whereKey($this->id)->lockForUpdate()->firstOrFail();
            if (! $locked->status->isOpen() || $locked->paid_amount > 0) {
                throw ValidationException::withMessages(['treatment_id' => 'Layanan hanya bisa diganti pada booking yang menunggu atau sudah dikonfirmasi.']);
            }
            if ($treatment->service_type !== $locked->service_type) {
                throw ValidationException::withMessages(['treatment_id' => 'Jenis layanan (In-Store/Home Care) harus sama.']);
            }

            $slots = new BookingSlots($this->branch, $treatment, $locked->travel_minutes);
            $start = $locked->starts_at;
            $free = $slots->freeTherapists($start, $locked->therapist_id, $this->id, lock: true);
            if ($free->isEmpty()) {
                $free = $slots->freeTherapists($start, null, $this->id, lock: true);
            }
            if ($free->isEmpty()) {
                throw ValidationException::withMessages(['treatment_id' => 'Tidak ada terapis yang kompeten dan kosong untuk layanan ini di jadwal tersebut.']);
            }
            $therapist = $free->firstWhere('id', $locked->therapist_id) ?? self::leastBusy($free, $start);

            $old = $this->items()->first();
            $this->items()->delete();
            $this->items()->create([
                'treatment_id' => $treatment->id,
                'name' => $treatment->name,
                'category' => $treatment->category->name,
                'service_type' => $treatment->service_type,
                'duration_minutes' => $treatment->duration_minutes,
                'price' => $treatment->price,
                'normal_price' => $treatment->normal_price,
                'components' => $treatment->items->pluck('name')->all() ?: null,
            ]);
            $this->forceFill([
                'starts_at' => $start,
                'ends_at' => $start->copy()->addMinutes($treatment->duration_minutes),
                'therapist_id' => $therapist->id,
                'total_price' => $treatment->price + $locked->travel_fee,
            ])->save();
            $this->unsetRelation('items');
            AuditLog::record('booking.treatment_changed', $this, [
                'from' => ['name' => $old?->name, 'price' => $old?->price],
                'to' => ['name' => $treatment->name, 'price' => $treatment->price],
                'therapist_id' => $therapist->id,
            ]);
        });
    }

    private function ledger(PaymentType $type, int $amount, PaymentMethod $method, string $key, User $user, ?string $reference, ?string $note, Closure $guard): Payment
    {
        return DB::transaction(function () use ($type, $amount, $method, $key, $user, $reference, $note, $guard) {
            $locked = self::whereKey($this->id)->lockForUpdate()->firstOrFail();

            $existing = Payment::where('idempotency_key', $key)->first();
            if ($existing) {
                if ($existing->booking_id !== $this->id) {
                    throw ValidationException::withMessages(['amount' => 'Formulir tidak valid. Muat ulang halaman.']);
                }

                return $existing;
            }

            if ($amount < 1 || ($error = $guard($locked))) {
                throw ValidationException::withMessages(['amount' => $error ?? 'Nominal tidak valid.']);
            }

            $payment = $this->payments()->create([
                'branch_id' => $locked->branch_id,
                'payment_method_id' => $method->id,
                'user_id' => $user->id,
                'type' => $type,
                'amount' => $amount,
                'reference' => $reference,
                'note' => $note,
                'idempotency_key' => $key,
                'paid_at' => now(),
            ]);
            $payment->forceFill(['receipt_number' => sprintf('ZN-%s-%06d', now()->format('Ymd'), $payment->id)])->save();

            $paid = $locked->paid_amount + $payment->signedAmount();
            $hasRefund = $type === PaymentType::Refund || $this->payments()->where('type', PaymentType::Refund)->exists();
            $this->forceFill([
                'paid_amount' => $paid,
                'payment_status' => match (true) {
                    $paid >= $locked->total_price => PaymentStatus::Paid,
                    $paid > 0 => PaymentStatus::PartiallyPaid,
                    $hasRefund => PaymentStatus::Refunded,
                    default => PaymentStatus::Unpaid,
                },
            ])->save();

            AuditLog::record("payment.{$type->value}", $this, [
                'payment_id' => $payment->id,
                'amount' => $amount,
                'method' => $method->name,
                'paid_amount' => $paid,
            ]);

            return $payment;
        });
    }

    public function estimatedArrivalAt(): ?CarbonInterface
    {
        return $this->departure_at?->copy()->addMinutes($this->travel_minutes);
    }

    private static function leastBusy(Collection $therapists, CarbonInterface $day): Therapist
    {
        return $therapists->sortBy([
            fn (Therapist $t) => $t->blockingBookings->filter(fn ($b) => $b->starts_at->isSameDay($day))->count(),
            fn (Therapist $t) => $t->id,
        ])->first();
    }

    private static function newCode(): string
    {
        do {
            $code = 'ZN'.Str::upper(substr(str_replace(['0', 'O', '1', 'I', 'L'], '', Str::random(20)), 0, 6));
        } while (strlen($code) !== 8 || self::where('code', $code)->exists());

        return $code;
    }
}
