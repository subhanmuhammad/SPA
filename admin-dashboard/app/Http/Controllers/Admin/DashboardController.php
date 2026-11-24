<?php

namespace App\Http\Controllers\Admin;

use App\Enums\BookingStatus;
use App\Enums\ServiceType;
use App\Http\Controllers\Controller;
use App\Http\Requests\AnalyticsRequest;
use App\Models\Booking;
use App\Models\Therapist;
use App\Support\Analytics;
use Carbon\CarbonImmutable;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(AnalyticsRequest $request): View
    {
        $request->validated();
        $today = new Analytics($request->validated('branch') ? collect([(int) $request->validated('branch')]) : $request->branches()->keys(), CarbonImmutable::today(), CarbonImmutable::today());
        return view('admin.dashboard', [
            'branches' => $request->branches(),
            'statuses' => $today->statusCounts(),
            'revenueToday' => $today->revenue(),
            'outstanding' => $today->outstanding(),
            'customers' => Booking::whereIn('branch_id', $today->branchIds)->distinct()->count('customer_id'),
            'therapists' => Therapist::whereIn('branch_id', $today->branchIds)->where('is_active', true)->count(),
            'homeCare' => Booking::whereIn('branch_id', $today->branchIds)->where('service_type', ServiceType::HomeCare)
                ->whereIn('status', BookingStatus::blocking())->where('status', '!=', BookingStatus::Completed)
                ->where('starts_at', '>=', today())->with('therapist:id,name')->orderBy('starts_at')->limit(5)
                ->get(['id', 'code', 'starts_at', 'area_name', 'customer_name', 'therapist_id', 'status']),
            'grain' => $request->grain(),
            'series' => $today->revenueSeries($request->grain(), CarbonImmutable::today()),
        ]);
    }
}
