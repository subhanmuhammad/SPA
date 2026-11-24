<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\AnalyticsRequest;
use Illuminate\View\View;

class ReportController extends Controller
{
    public function __invoke(AnalyticsRequest $request): View
    {
        $analytics = $request->analytics();
        return view('admin.reports', [
            'analytics' => $analytics,
            'branches' => $request->branches(),
            'statuses' => $analytics->statusCounts(),
            'types' => $analytics->serviceTypeCounts(),
            'revenue' => $analytics->revenue(),
            'methods' => $analytics->byMethod(),
            'treatments' => $analytics->treatments(),
            'therapists' => $analytics->therapists(),
            'customers' => $analytics->customers(),
            'homeCare' => $analytics->homeCare(),
            'lost' => $analytics->lostBookings(),
        ]);
    }
}
