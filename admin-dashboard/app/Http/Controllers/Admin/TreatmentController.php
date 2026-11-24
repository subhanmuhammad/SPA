<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ServiceType;
use App\Http\Controllers\Controller;
use App\Http\Requests\TreatmentRequest;
use App\Models\AuditLog;
use App\Models\Treatment;
use App\Models\TreatmentCategory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class TreatmentController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'category' => ['nullable', 'integer'],
        ]);

        $treatments = Treatment::query()
            ->with('category:id,name')
            ->when($filters['q'] ?? null, fn ($q, $term) => $q->where('name', 'like', '%'.addcslashes($term, '%_\\').'%'))
            ->when($filters['category'] ?? null, fn ($q, $id) => $q->where('treatment_category_id', $id))
            ->orderBy('treatment_category_id')
            ->orderBy('sort_order')
            ->paginate(25)
            ->withQueryString();

        return view('admin.treatments.index', [
            'treatments' => $treatments,
            'categories' => TreatmentCategory::orderBy('sort_order')->pluck('name', 'id'),
        ]);
    }

    public function create(): View
    {
        return $this->form(new Treatment(['is_active' => true, 'service_type' => ServiceType::InStore]));
    }

    public function store(TreatmentRequest $request): RedirectResponse
    {
        $treatment = DB::transaction(function () use ($request) {
            $treatment = new Treatment($request->treatmentData());
            if ($request->hasFile('photo')) {
                $treatment->photo_path = $request->file('photo')->store('treatments', 'public');
            }
            $treatment->save();
            $treatment->items()->createMany($request->packageItems());
            $treatment->branches()->attach($request->user()->accessibleBranches()->pluck('branches.id'));
            AuditLog::record('treatment.created', $treatment, $treatment->only('name', 'price', 'duration_minutes'));

            return $treatment;
        });

        return redirect()->route('admin.treatments.edit', $treatment)->with('status', 'Layanan ditambahkan.');
    }

    public function edit(Request $request, Treatment $treatment): View
    {
        return $this->form($treatment->load('items'), $request);
    }

    public function update(TreatmentRequest $request, Treatment $treatment): RedirectResponse
    {
        DB::transaction(function () use ($request, $treatment) {
            $treatment->fill($request->treatmentData());
            $oldPhoto = $treatment->photo_path;
            if ($request->hasFile('photo')) {
                $treatment->photo_path = $request->file('photo')->store('treatments', 'public');
            } elseif ($request->validated('remove_photo')) {
                $treatment->photo_path = null;
            }

            $changes = collect($treatment->getDirty())->except('updated_at')
                ->map(fn ($new, $key) => ['from' => $treatment->getOriginal($key), 'to' => $new]);
            $treatment->save();

            $treatment->items()->delete();
            $treatment->items()->createMany($request->packageItems());

            if ($oldPhoto && $oldPhoto !== $treatment->photo_path) {
                Storage::disk('public')->delete($oldPhoto);
            }
            AuditLog::record('treatment.updated', $treatment, $changes->all());
        });

        return redirect()->route('admin.treatments.edit', $treatment)->with('status', 'Layanan disimpan.');
    }

    private function form(Treatment $treatment, ?Request $request = null): View
    {
        $branches = $treatment->exists
            ? ($request ?? request())->user()->accessibleBranches()->orderBy('name')->get(['branches.id', 'branches.name'])
            : collect();

        return view('admin.treatments.form', [
            'treatment' => $treatment,
            'categories' => TreatmentCategory::orderBy('sort_order')->pluck('name', 'id'),
            'components' => Treatment::whereKeyNot($treatment->id)->orderBy('name')->pluck('name', 'id'),
            'branches' => $branches,
            'branchConfig' => $treatment->exists
                ? $treatment->branches()->whereIn('branches.id', $branches->pluck('id'))->get()->keyBy('id')
                : collect(),
        ]);
    }
}
