<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\AnalyticsRequest;
use Carbon\CarbonImmutable;
use Illuminate\View\View;

class InsightController extends Controller
{
    public function __invoke(AnalyticsRequest $request): View
    {
        $analytics = $request->analytics();
        $treatments = $analytics->treatments();

        return view('admin.insights', [
            'analytics' => $analytics,
            'branches' => $request->branches(),
            'grain' => $request->grain(),
            'series' => $analytics->revenueSeries($request->grain(), CarbonImmutable::parse($analytics->to)),
            'yearly' => $analytics->payments(CarbonImmutable::now()->startOfYear(), CarbonImmutable::now()->endOfYear())
                ->selectRaw("COALESCE(SUM(CASE WHEN type = 'payment' THEN amount ELSE -amount END), 0) as net")->value('net'),
            'revenue' => $analytics->revenue(),
            'byBranch' => $analytics->byBranch(),
            'customers' => $analytics->customers(),
            'treatments' => $treatments,
            'promo' => [
                'promo' => $treatments->where('is_promo', 1),
                'regular' => $treatments->where('is_promo', 0),
            ],
            'therapists' => $analytics->therapists(),
            'statuses' => $analytics->statusCounts(),
            'types' => $analytics->serviceTypeCounts(),
            'hours' => $analytics->peakHours(),
            'insights' => $analytics->insights(),
        ]);
    }
}
