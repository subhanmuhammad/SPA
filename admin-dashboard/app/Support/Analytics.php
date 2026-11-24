<?php

namespace App\Support;

use App\Enums\BookingStatus;
use App\Enums\ServiceType;
use App\Models\Booking;
use App\Models\Payment;
use App\Models\Therapist;
use Carbon\CarbonImmutable;
use Carbon\CarbonPeriod;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class Analytics
{
    private const NET = "COALESCE(SUM(CASE WHEN payments.type = 'payment' THEN payments.amount ELSE -payments.amount END), 0)";

    public readonly CarbonImmutable $from;

    public readonly CarbonImmutable $to;

    private array $memo = [];

    public function __construct(public readonly Collection $branchIds, CarbonImmutable $from, CarbonImmutable $to)
    {
        $this->from = $from->startOfDay();
        $this->to = $to->endOfDay();
    }

    public function bookings(): Builder
    {
        return Booking::query()->whereIn('bookings.branch_id', $this->branchIds)->whereBetween('bookings.starts_at', [$this->from, $this->to]);
    }

    public function payments(?CarbonImmutable $from = null, ?CarbonImmutable $to = null): Builder
    {
        return Payment::query()->whereIn('payments.branch_id', $this->branchIds)->whereBetween('payments.paid_at', [$from ?? $this->from, $to ?? $this->to]);
    }

    public function statusCounts(): array
    {
        $counts = $this->bookings()->selectRaw('status, COUNT(*) as total')->groupBy('status')->pluck('total', 'status');

        return collect(BookingStatus::cases())->mapWithKeys(fn ($s) => [$s->value => (int) ($counts[$s->value] ?? 0)])->all();
    }

    public function serviceTypeCounts(): array
    {
        $counts = $this->bookings()->whereIn('status', BookingStatus::blocking())->selectRaw('service_type, COUNT(*) as total')->groupBy('service_type')->pluck('total', 'service_type');

        return collect(ServiceType::cases())->mapWithKeys(fn ($t) => [$t->value => (int) ($counts[$t->value] ?? 0)])->all();
    }

    public function revenue(): array
    {
        $row = $this->payments()->selectRaw("
            COALESCE(SUM(CASE WHEN type = 'payment' THEN amount ELSE 0 END), 0) as received,
            COALESCE(SUM(CASE WHEN type = 'refund' THEN amount ELSE 0 END), 0) as refunded,
            COUNT(DISTINCT CASE WHEN type = 'payment' THEN booking_id END) as paid_bookings")->first();
        $net = (int) $row->received - (int) $row->refunded;

        return [
            'received' => (int) $row->received,
            'refunded' => (int) $row->refunded,
            'net' => $net,
            'paid_bookings' => (int) $row->paid_bookings,
            'average' => $row->paid_bookings > 0 ? intdiv($net, (int) $row->paid_bookings) : null,
        ];
    }

    public function outstanding(): int
    {
        return (int) Booking::whereIn('branch_id', $this->branchIds)
            ->where('status', BookingStatus::Completed)
            ->whereColumn('paid_amount', '<', 'total_price')
            ->sum(DB::raw('total_price - paid_amount'));
    }

    public function revenueSeries(string $grain, CarbonImmutable $end): array
    {
        [$start, $step, $key, $format, $label] = match ($grain) {
            'weekly' => [$end->startOfWeek()->subWeeks(11), '1 week', 'DATE(paid_at - INTERVAL WEEKDAY(paid_at) DAY)', 'Y-m-d', 'j M'],
            'monthly' => [$end->startOfMonth()->subMonths(11), '1 month', "DATE_FORMAT(paid_at, '%Y-%m')", 'Y-m', 'M Y'],
            default => [$end->subDays(29)->startOfDay(), '1 day', 'DATE(paid_at)', 'Y-m-d', 'j M'],
        };

        $totals = $this->payments($start, $end->endOfDay())->selectRaw("{$key} as bucket, ".self::NET.' as net')->groupBy('bucket')->pluck('net', 'bucket');

        return collect(CarbonPeriod::create($start, $step, $end))
            ->mapWithKeys(fn ($date) => [$date->translatedFormat($label) => (int) ($totals[$date->format($format)] ?? 0)])
            ->all();
    }

    public function byMethod(): Collection
    {
        return $this->payments()->join('payment_methods', 'payment_methods.id', '=', 'payments.payment_method_id')
            ->selectRaw('payment_methods.name, '.self::NET.' as net, COUNT(*) as transactions')
            ->groupBy('payment_methods.name')->orderByDesc('net')->get();
    }

    public function byBranch(): Collection
    {
        return $this->memo['byBranch'] ??= (function () {
            $revenue = $this->payments()->selectRaw('branch_id, '.self::NET.' as net')->groupBy('branch_id')->pluck('net', 'branch_id');
            $bookings = $this->bookings()->where('status', BookingStatus::Completed)->selectRaw('branch_id, COUNT(*) as total')->groupBy('branch_id')->pluck('total', 'branch_id');

            return DB::table('branches')->whereIn('id', $this->branchIds)->orderBy('name')->get(['id', 'name'])
                ->map(fn ($b) => (object) ['name' => $b->name, 'net' => (int) ($revenue[$b->id] ?? 0), 'completed' => (int) ($bookings[$b->id] ?? 0)])
                ->sortByDesc('net')->values();
        })();
    }

    public function treatments(): Collection
    {
        return $this->memo['treatments'] ??= (function () {
            return $this->bookings()->join('booking_items', 'booking_items.booking_id', '=', 'bookings.id')
                ->selectRaw("booking_items.name,
                    MAX(booking_items.normal_price IS NOT NULL) as is_promo,
                    COUNT(*) as booked,
                    SUM(bookings.status = 'completed') as completed,
                    SUM(bookings.status IN ('cancelled', 'no_show')) as lost,
                    COALESCE(SUM(CASE WHEN bookings.status = 'completed' THEN booking_items.price END), 0) as value")
                ->groupBy('booking_items.name')
                ->orderByDesc('booked')
                ->get();
        })();
    }

    public function therapists(): Collection
    {
        return $this->memo['therapists'] ??= (function () {
            $stats = $this->bookings()
                ->selectRaw("therapist_id,
                    SUM(status = 'completed') as completed,
                    COUNT(DISTINCT CASE WHEN status = 'completed' THEN customer_id END) as customers,
                    SUM(status IN ('cancelled', 'no_show')) as lost,
                    COALESCE(SUM(CASE WHEN status = 'completed' THEN TIMESTAMPDIFF(MINUTE, starts_at, ends_at) END), 0) as minutes")
                ->groupBy('therapist_id')->get()->keyBy('therapist_id');

            $days = collect(CarbonPeriod::create($this->from, $this->to->startOfDay()));

            return Therapist::withTrashed()->whereIn('branch_id', $this->branchIds)
                ->with(['schedules', 'leaves' => fn ($q) => $q->whereBetween('date', [$this->from->toDateString(), $this->to->toDateString()])])
                ->orderBy('name')->get(['id', 'name', 'branch_id'])
                ->map(function (Therapist $t) use ($stats, $days) {
                    $leaves = $t->leaves->map(fn ($l) => $l->date->toDateString())->all();
                    $shifts = $t->schedules->keyBy('day_of_week');
                    $scheduled = $days->sum(function ($day) use ($shifts, $leaves) {
                        $shift = $shifts->get($day->dayOfWeek);

                        return $shift && ! in_array($day->toDateString(), $leaves, true)
                            ? (strtotime($shift->end_time) - strtotime($shift->start_time)) / 60
                            : 0;
                    });
                    $s = $stats->get($t->id);

                    return (object) [
                        'name' => $t->name,
                        'completed' => (int) ($s->completed ?? 0),
                        'customers' => (int) ($s->customers ?? 0),
                        'lost' => (int) ($s->lost ?? 0),
                        'utilization' => $scheduled > 0 ? round(100 * ($s->minutes ?? 0) / $scheduled, 1) : null,
                    ];
                })
                ->filter(fn ($row) => $row->completed || $row->lost || $row->utilization !== null)
                ->values();
        })();
    }

    public function customers(): array
    {
        return $this->memo['customers'] ??= (function () {
            $scope = fn () => Booking::whereIn('branch_id', $this->branchIds);
            $visitors = fn (CarbonImmutable $from, CarbonImmutable $to) => $scope()->where('status', BookingStatus::Completed)
                ->whereBetween('starts_at', [$from, $to])->distinct()->pluck('customer_id');

            $current = $visitors($this->from, $this->to);
            $length = $this->from->diffInDays($this->to->startOfDay()) + 1;
            $previous = $visitors($this->from->subDays($length), $this->from->subSecond());
            $returning = $current->isEmpty() ? 0 : $scope()->where('status', BookingStatus::Completed)
                ->where('starts_at', '<', $this->from)->whereIn('customer_id', $current)->distinct()->count('customer_id');

            $lifetime = $scope()->where('paid_amount', '>', 0)->selectRaw('customer_id, SUM(paid_amount) as total')->groupBy('customer_id')->pluck('total');

            return [
                'total' => $scope()->distinct()->count('customer_id'),
                'new' => $scope()->selectRaw('customer_id')->groupBy('customer_id')
                    ->havingRaw('MIN(starts_at) BETWEEN ? AND ?', [$this->from, $this->to])->get()->count(),
                'visitors' => $current->count(),
                'returning' => $returning,
                'visits' => $scope()->where('status', BookingStatus::Completed)->whereBetween('starts_at', [$this->from, $this->to])->count(),
                'retention' => $previous->count() >= 5 ? round(100 * $previous->intersect($current)->count() / $previous->count(), 1) : null,
                'previous_visitors' => $previous->count(),
                'inactive' => $scope()->where('status', BookingStatus::Completed)->selectRaw('customer_id')->groupBy('customer_id')
                    ->havingRaw('MAX(starts_at) < ?', [now()->subDays(90)])->get()->count(),
                'clv' => $lifetime->count() >= 10 ? intdiv((int) $lifetime->sum(), $lifetime->count()) : null,
                'clv_customers' => $lifetime->count(),
            ];
        })();
    }

    public function peakHours(): array
    {
        return $this->memo['peakHours'] ??= (function () {
            $counts = $this->bookings()->whereIn('status', BookingStatus::blocking())
                ->selectRaw('HOUR(starts_at) as hour, COUNT(*) as total')->groupBy('hour')->pluck('total', 'hour');

            if ($counts->isEmpty()) {
                return [];
            }

            return collect(range($counts->keys()->min(), $counts->keys()->max()))
                ->mapWithKeys(fn ($h) => [sprintf('%02d.00', $h) => (int) ($counts[$h] ?? 0)])->all();
        })();
    }

    public function homeCare(): array
    {
        $base = fn () => $this->bookings()->where('service_type', ServiceType::HomeCare);

        return [
            'booked' => $base()->whereIn('status', BookingStatus::blocking())->count(),
            'completed' => $base()->where('status', BookingStatus::Completed)->count(),
            'travel_fees' => (int) $base()->where('status', BookingStatus::Completed)->sum('travel_fee'),
            'areas' => $base()->whereIn('status', BookingStatus::blocking())
                ->selectRaw("COALESCE(area_name, '-') as area, COUNT(*) as total, SUM(status = 'completed') as completed")
                ->groupBy('area')->orderByDesc('total')->get(),
        ];
    }

    public function lostBookings(): Collection
    {
        return $this->bookings()->whereIn('status', [BookingStatus::Cancelled, BookingStatus::NoShow])
            ->with(['branch:id,name', 'therapist:id,name', 'items:id,booking_id,name', 'histories' => fn ($q) => $q->whereIn('to_status', [BookingStatus::Cancelled, BookingStatus::NoShow])])
            ->latest('starts_at')->limit(100)->get();
    }

    public function insights(): array
    {
        $insights = [];
        $length = $this->from->diffInDays($this->to->startOfDay()) + 1;
        $previous = new self($this->branchIds, $this->from->subDays($length), $this->from->subDay());

        $now = $this->treatments()->pluck('booked', 'name');
        foreach ($previous->treatments()->pluck('booked', 'name') as $name => $before) {
            $after = (int) ($now[$name] ?? 0);
            if ($before >= 5 && $after <= $before * 0.7) {
                $insights[] = "Booking {$name} turun dari {$before} menjadi {$after} dibanding periode sebelumnya.";
            }
        }

        $hours = collect($this->peakHours());
        if ($hours->sum() >= 20) {
            $insights[] = "Jam paling ramai: {$hours->sortDesc()->keys()->first()} ({$hours->max()} booking).";
        }

        $branches = $this->byBranch()->where('net', '>', 0);
        if ($branches->count() >= 2) {
            $insights[] = "Cabang dengan pendapatan tertinggi: {$branches->first()->name}.";
        }

        $promo = $this->treatments()->where('is_promo', 1)->sortByDesc('booked')->first();
        if ($promo && $promo->booked >= 5) {
            $insights[] = "Paket promo paling banyak dipakai: {$promo->name} ({$promo->booked} booking).";
        }

        $inactive = $this->customers()['inactive'];
        if ($inactive > 0) {
            $insights[] = "{$inactive} pelanggan tidak berkunjung lebih dari 90 hari.";
        }

        return $insights;
    }
}
