<?php

namespace App\Support;

use App\Enums\ServiceType;
use App\Models\Branch;
use App\Models\HomecareServiceArea;
use App\Models\Therapist;
use App\Models\Treatment;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class BookingSlots
{
    public function __construct(private Branch $branch, private Treatment $treatment, private int $travelMinutes = 0) {}

    public function competentTherapists(): Builder
    {
        $skills = $this->treatment->requiredSkillIds();
        return Therapist::query()
            ->where('branch_id', $this->branch->id)
            ->where('is_active', true)
            ->whereHas('treatments', fn (Builder $q) => $q->whereIn('treatments.id', $skills), '=', count($skills));
    }

    public function times(CarbonInterface $date, ?int $therapistId = null): array
    {
        $day = CarbonImmutable::parse($date->toDateString());
        $therapists = $this->load($day, $therapistId);
        if ($therapists->isEmpty()) {
            return [];
        }

        $close = $day->setTimeFromTimeString($this->branch->closing_time);
        $times = [];
        for ($start = $day->setTimeFromTimeString($this->branch->opening_time); $start->addMinutes($this->treatment->duration_minutes) <= $close; $start = $start->addMinutes(config('booking.slot_interval_minutes'))) {
            if ($start->isFuture() && $this->free($therapists, $start)->isNotEmpty()) {
                $times[] = $start->format('H:i');
            }
        }

        return $times;
    }

    public function freeTherapists(CarbonInterface $start, ?int $therapistId = null, ?int $ignoreBookingId = null, bool $lock = false): Collection
    {
        $start = CarbonImmutable::parse($start);
        $end = $start->addMinutes($this->treatment->duration_minutes);
        $day = $start->startOfDay();
        if ($start->isPast()
            || $start < $day->setTimeFromTimeString($this->branch->opening_time)
            || $end > $day->setTimeFromTimeString($this->branch->closing_time)) {
            return collect();
        }

        return $this->free($this->load($day, $therapistId, $ignoreBookingId, $lock), $start);
    }

    private function load(CarbonImmutable $day, ?int $therapistId, ?int $ignoreBookingId = null, bool $lock = false): Collection
    {
        $reach = config('booking.buffer_minutes') + HomecareServiceArea::MAX_TRAVEL_MINUTES * 2;

        return $this->competentTherapists()
            ->when($therapistId, fn (Builder $q) => $q->whereKey($therapistId))
            ->when($lock, fn (Builder $q) => $q->orderBy('id')->lockForUpdate())
            ->with([
                'schedules' => fn ($q) => $q->where('day_of_week', $day->dayOfWeek),
                'leaves' => fn ($q) => $q->whereDate('date', $day)->when($lock, fn ($q) => $q->sharedLock()),
                'blockingBookings' => fn ($q) => $q
                    ->where('starts_at', '<', $day->addDay()->addMinutes($reach))
                    ->where('ends_at', '>', $day->subMinutes($reach))
                    ->when($ignoreBookingId, fn ($q) => $q->whereKeyNot($ignoreBookingId))
                    ->when($lock, fn ($q) => $q->lockForUpdate()),
            ])
            ->get();
    }

    private function free(Collection $therapists, CarbonImmutable $start): Collection
    {
        $buffer = config('booking.buffer_minutes');
        $from = $start->subMinutes($this->travelMinutes);
        $until = $start->addMinutes($this->treatment->duration_minutes + $this->travelMinutes);
        $homeCareLimit = $this->treatment->service_type === ServiceType::HomeCare ? $this->branch->home_care_max_per_therapist : null;

        return $therapists->filter(function (Therapist $therapist) use ($start, $from, $until, $buffer, $homeCareLimit) {
            $shift = $therapist->schedules->first();
            if (! $shift || $therapist->leaves->isNotEmpty()
                || $from < $start->setTimeFromTimeString($shift->start_time)
                || $until > $start->setTimeFromTimeString($shift->end_time)) {
                return false;
            }

            if ($homeCareLimit !== null && $therapist->blockingBookings
                ->filter(fn ($b) => $b->service_type === ServiceType::HomeCare && $b->starts_at->isSameDay($start))
                ->count() >= $homeCareLimit) {
                return false;
            }

            return $therapist->blockingBookings->doesntContain(
                fn ($booking) => $from < $booking->ends_at->copy()->addMinutes($booking->travel_minutes + $buffer)
                    && $until->addMinutes($buffer) > $booking->starts_at->copy()->subMinutes($booking->travel_minutes)
            );
        })->values();
    }
}
