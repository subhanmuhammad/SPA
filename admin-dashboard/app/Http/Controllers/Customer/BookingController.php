<?php

namespace App\Http\Controllers\Customer;

use App\Enums\ServiceType;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreBookingRequest;
use App\Models\Booking;
use App\Models\Branch;
use App\Models\Customer;
use App\Models\Treatment;
use App\Support\BookingSlots;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class BookingController extends Controller
{
    public function create(Request $request): View
    {
        $branches = Branch::active()->orderBy('id')->get();
        $branch = $branches->count() === 1 ? $branches->first() : $branches->firstWhere('slug', $request->query('cabang'));
        $type = ServiceType::tryFrom((string) $request->query('tipe'));

        $isHomeCare = $type === ServiceType::HomeCare;
        $areas = $branch && $isHomeCare ? $branch->homecareAreas()->where('is_active', true)->get() : collect();
        $area = $areas->firstWhere('id', (int) $request->query('area'));
        $ready = $branch && $type && (! $isHomeCare || $area);

        $treatments = $ready ? $this->bookable($branch, $type)->get() : collect();
        $treatment = $treatments->firstWhere('slug', $request->query('layanan'));

        $dates = collect(range(0, config('booking.max_days_ahead')))->map(fn (int $i) => CarbonImmutable::today()->addDays($i));
        $date = $dates->first(fn ($d) => $d->toDateString() === $request->query('tanggal'));

        $slots = $treatment ? new BookingSlots($branch, $treatment, $area->travel_minutes ?? 0) : null;
        $times = $slots && $date ? $slots->times($date) : [];
        $time = in_array($request->query('jam'), $times, true) ? $request->query('jam') : null;
        $therapists = $time ? $slots->freeTherapists($date->setTimeFromTimeString($time))->sortBy('name') : collect();

        return view('customer.booking.create', compact('branches', 'branch', 'type', 'areas', 'area', 'treatments', 'treatment', 'dates', 'date', 'times', 'time', 'therapists'));
    }

    public function store(StoreBookingRequest $request): RedirectResponse
    {
        $branch = Branch::active()->where('slug', $request->validated('cabang'))->firstOrFail();
        $treatment = $this->bookable($branch, ServiceType::from($request->validated('tipe')))
            ->where('treatments.slug', $request->validated('layanan'))
            ->firstOrFail();
        $start = CarbonImmutable::parse($request->validated('tanggal').' '.$request->validated('jam'));
        $area = $treatment->service_type === ServiceType::HomeCare
            ? $branch->homecareAreas()->where('is_active', true)->findOrFail($request->validated('area'))
            : null;

        $booking = DB::transaction(function () use ($request, $branch, $treatment, $start, $area) {
            $customer = Customer::firstOrCreate(['phone' => $request->phone()], $request->safe()->only(['name', 'email', 'gender', 'birth_date']));

            return Booking::reserve($branch, $treatment, $start, $request->validated('terapis'), $customer, $request->safe()->only(['name', 'notes', 'address', 'address_notes', 'location_url']), $area);
        });

        $request->session()->push('tracked_bookings', $booking->id);
        return redirect()->route('bookings.show', ['code' => $booking->code, 'token' => $booking->plainTrackingToken])
            ->with('status', 'Booking berhasil dibuat. Simpan kode booking dan link halaman ini untuk cek status.');
    }

    private function bookable(Branch $branch, ServiceType $type)
    {
        return Treatment::offeredAt($branch)
            ->where('treatments.service_type', $type)
            ->whereRaw('COALESCE(branch_treatments.duration_minutes, treatments.duration_minutes) IS NOT NULL')
            ->with(['category:id,name', 'items:id,package_id,treatment_id,name']);
    }
}
