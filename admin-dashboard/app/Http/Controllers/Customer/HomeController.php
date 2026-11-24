<?php

namespace App\Http\Controllers\Customer;

use App\Enums\ServiceType;
use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Treatment;
use App\Models\TreatmentCategory;
use Illuminate\Http\Request;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function __invoke(Request $request): View
    {
        $branches = Branch::active()->orderBy('id')->get(['id', 'name', 'slug', 'address', 'phone', 'opening_time', 'closing_time']);
        $branch = $branches->firstWhere('slug', $request->query('cabang')) ?? $branches->first();

        $treatments = $branch
            ? Treatment::offeredAt($branch)->with(['items:id,package_id,name'])->get()
            : collect();
        $categories = TreatmentCategory::orderBy('sort_order')->get(['id', 'name', 'slug', 'tagline', 'description']);
        $promoCategory = $categories->firstWhere('slug', TreatmentCategory::PROMO);

        return view('customer.home', [
            'therapists' => $branch ? $branch->therapists()->where('is_active', true)->orderBy('name')->get(['id', 'name', 'specialization']) : collect(),
            'branches' => $branches,
            'branch' => $branch,
            'promoCategory' => $promoCategory,
            'promos' => $treatments->where('treatment_category_id', $promoCategory?->id),
            'homeCare' => $treatments->where('service_type', ServiceType::HomeCare),
            'catalog' => $categories
                ->reject(fn ($c) => $c->is($promoCategory))
                ->map(fn ($c) => [$c, $treatments->where('treatment_category_id', $c->id)->where('service_type', ServiceType::InStore)])
                ->filter(fn ($pair) => $pair[1]->isNotEmpty()),
        ]);
    }
}
