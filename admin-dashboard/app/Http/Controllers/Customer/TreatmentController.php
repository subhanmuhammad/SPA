<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Treatment;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TreatmentController extends Controller
{
    public function __invoke(Request $request, string $slug): View
    {
        $branch = Branch::forCustomer($request->query('cabang')) ?? abort(404);
        $treatment = Treatment::offeredAt($branch)
            ->where('treatments.slug', $slug)
            ->with(['category:id,name', 'items:id,package_id,name'])
            ->firstOrFail();
        return view('customer.treatment', ['treatment' => $treatment, 'branch' => $branch]);
    }
}
