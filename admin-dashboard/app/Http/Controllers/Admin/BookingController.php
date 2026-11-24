<?php

namespace App\Http\Controllers\Admin;

use App\Enums\BookingStatus;
use App\Enums\ServiceType;
use App\Http\Controllers\Controller;
use App\Http\Requests\AdminBookingRequest;
use App\Models\Booking;
use App\Models\Branch;
use App\Models\Customer;
use App\Models\PaymentMethod;
use App\Models\Therapist;
use App\Models\Treatment;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class BookingController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();
        $branches = $user->accessibleBranches()->orderBy('name')->pluck('name', 'branches.id');
        $filters = $request->validate([
            'date' => ['nullable', 'date_format:Y-m-d'],
            'status' => ['nullable', Rule::enum(BookingStatus::class)],
            'branch' => ['nullable', 'integer', Rule::in($branches->keys())],
            'therapist' => ['nullable', 'integer'],
            'q' => ['nullable', 'string', 'max:100'],
            'requests' => ['nullable', 'boolean'],
            'type' => ['nullable', Rule::enum(ServiceType::class)],
        ]);

        $bookings = Booking::forUser($user)
            ->with(['branch:id,name', 'therapist:id,name', 'items:id,booking_id,name'])
            ->when($filters['date'] ?? null, fn ($q, $date) => $q->whereDate('starts_at', $date))
            ->when($filters['status'] ?? null, fn ($q, $status) => $q->where('status', $status))
            ->when($filters['branch'] ?? null, fn ($q, $id) => $q->where('branch_id', $id))
            ->when($filters['therapist'] ?? null, fn ($q, $id) => $q->where('therapist_id', $id))
            ->when($filters['type'] ?? null, fn ($q, $type) => $q->where('service_type', $type))
            ->when($filters['requests'] ?? false, fn ($q) => $q->whereNotNull('change_request'))
            ->when($filters['q'] ?? null, function ($q, $term) {
                $like = '%'.addcslashes($term, '%_\\').'%';
                $q->where(fn ($q) => $q->where('code', $term)->orWhere('customer_name', 'like', $like)->orWhere('customer_phone', 'like', $like));
            })
            ->orderBy('starts_at')
            ->paginate(25)
            ->withQueryString();

        return view('admin.bookings.index', [
            'bookings' => $bookings,
            'branches' => $branches,
            'therapists' => Therapist::whereIn('branch_id', $branches->keys())->orderBy('name')->pluck('name', 'id'),
        ]);
    }

    public function create(Request $request): View
    {
        $branches = $request->user()->accessibleBranches()->active()->with(['homecareAreas' => fn ($q) => $q->where('is_active', true)])->orderBy('name')->get();
        $branchIds = $branches->pluck('id');

        return view('admin.bookings.create', [
            'branches' => $branches,
            'treatments' => Treatment::with('category:id,name')->where('is_active', true)->whereNotNull('duration_minutes')
                ->whereHas('branches', fn ($q) => $q->whereIn('branches.id', $branchIds)->where('is_available', true))
                ->orderBy('treatment_category_id')->orderBy('sort_order')->get(['id', 'name', 'treatment_category_id', 'service_type', 'price', 'duration_minutes']),
            'therapists' => Therapist::whereIn('branch_id', $branchIds)->where('is_active', true)->orderBy('name')->get(['id', 'name', 'branch_id']),
        ]);
    }

    public function store(AdminBookingRequest $request): RedirectResponse
    {
        $branch = Branch::findOrFail($request->validated('branch_id'));
        $treatment = Treatment::offeredAt($branch)
            ->with(['category:id,name', 'items:id,package_id,treatment_id,name'])
            ->whereRaw('COALESCE(branch_treatments.duration_minutes, treatments.duration_minutes) IS NOT NULL')
            ->find($request->validated('treatment_id'))
            ?? throw ValidationException::withMessages(['treatment_id' => 'Layanan tidak tersedia di cabang ini.']);

        $area = null;
        if ($treatment->service_type === ServiceType::HomeCare) {
            $area = $branch->homecareAreas()->where('is_active', true)->find($request->validated('area_id'))
                ?? throw ValidationException::withMessages(['area_id' => 'Pilih area Home Care yang aktif di cabang ini.']);
            if (blank($request->validated('address'))) {
                throw ValidationException::withMessages(['address' => 'Alamat wajib diisi untuk Home Care.']);
            }
        }

        $booking = DB::transaction(function () use ($request, $branch, $treatment, $area) {
            $customer = Customer::firstOrCreate(['phone' => $request->phone()], $request->safe()->only(['name', 'email']));
            $booking = Booking::reserve(
                $branch, $treatment, CarbonImmutable::parse($request->validated('date').' '.$request->validated('time')),
                $request->validated('therapist_id'), $customer, $request->safe()->only(['name', 'notes', 'address', 'address_notes']),
                $area, 'admin',
            );
            $booking->moveTo(BookingStatus::Confirmed, $request->user(), 'Dibuat oleh admin');

            return $booking;
        });

        return redirect()->route('admin.bookings.show', $booking)->with('status', "Booking {$booking->code} dibuat dan dikonfirmasi.");
    }

    public function show(Booking $booking): View
    {
        $booking->load(['branch', 'therapist:id,name', 'items', 'customer:id,email', 'histories.user:id,name', 'payments.method:id,name', 'payments.user:id,name']);

        return view('admin.bookings.show', [
            'booking' => $booking,
            'methods' => PaymentMethod::active()->pluck('name', 'id'),
            'allMethods' => PaymentMethod::orderBy('sort_order')->pluck('name', 'id'),
            'alternatives' => $booking->status->isOpen() && $booking->paid_amount === 0
                ? Treatment::offeredAt($booking->branch)->where('treatments.service_type', $booking->service_type)
                    ->whereRaw('COALESCE(branch_treatments.duration_minutes, treatments.duration_minutes) IS NOT NULL')->get()->pluck('name', 'id')
                : collect(),
            'therapists' => Therapist::where('branch_id', $booking->branch_id)->where('is_active', true)->orderBy('name')->pluck('name', 'id'),
        ]);
    }
}
