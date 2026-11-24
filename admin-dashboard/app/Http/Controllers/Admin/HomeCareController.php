<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Branch;
use App\Models\HomecareServiceArea;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class HomeCareController extends Controller
{
    public function index(Request $request): View
    {
        return view('admin.home-care.index', [
            'branches' => $request->user()->accessibleBranches()->with('homecareAreas')->orderBy('name')
                ->get(['branches.id', 'branches.name', 'branches.home_care_max_per_therapist']),
        ]);
    }

    public function updateLimit(Request $request, Branch $branch): RedirectResponse
    {
        $this->authorizeBranch($request, $branch->id);
        $data = $request->validate(['home_care_max_per_therapist' => ['nullable', 'integer', 'between:1,20']], [], ['home_care_max_per_therapist' => 'batas Home Care']);
        $before = $branch->home_care_max_per_therapist;
        $branch->forceFill($data)->save();
        AuditLog::record('home_care.limit_updated', $branch, ['from' => $before, 'to' => $branch->home_care_max_per_therapist], $branch->id);
        return back()->with('status', 'Batas Home Care disimpan.');
    }

    public function storeArea(Request $request): RedirectResponse
    {
        $this->authorizeBranch($request, $request->integer('branch_id'));
        $data = $this->validateArea($request);
        $area = new HomecareServiceArea(collect($data)->except('branch_id')->all());
        $area->branch_id = $data['branch_id'];
        $area->save();
        AuditLog::record('home_care.area_created', $area, $area->only('name', 'travel_fee', 'travel_minutes'));
        return back()->with('status', "Area {$area->name} ditambahkan.");
    }

    public function updateArea(Request $request, HomecareServiceArea $area): RedirectResponse
    {
        $this->authorizeBranch($request, $area->branch_id);
        $data = $this->validateArea($request, $area);
        $area->fill($data);
        $changes = collect($area->getDirty())->except('updated_at')->map(fn ($new, $key) => ['from' => $area->getOriginal($key), 'to' => $new]);
        $area->save();
        AuditLog::record('home_care.area_updated', $area, $changes->all());
        return back()->with('status', "Area {$area->name} disimpan.");
    }

    private function validateArea(Request $request, ?HomecareServiceArea $area = null): array
    {
        $branchId = $area?->branch_id ?? $request->integer('branch_id');
        return $request->validate([
            'branch_id' => [$area ? 'prohibited' : 'required', 'integer'],
            'name' => ['required', 'string', 'max:100', Rule::unique('homecare_service_areas')->where('branch_id', $branchId)->ignore($area)],
            'travel_fee' => ['required', 'integer', 'min:0', 'max:10000000'],
            'travel_minutes' => ['required', 'integer', 'min:0', 'max:'.HomecareServiceArea::MAX_TRAVEL_MINUTES],
            'is_active' => ['boolean'],
        ], [], ['name' => 'nama area', 'travel_fee' => 'biaya transport', 'travel_minutes' => 'waktu tempuh']) + ['is_active' => $request->boolean('is_active')];
    }

    private function authorizeBranch(Request $request, int $branchId): void
    {
        abort_unless($request->user()->canAccessBranch($branchId), 403);
    }
}
