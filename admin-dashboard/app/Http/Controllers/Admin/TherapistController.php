<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\TherapistRequest;
use App\Models\AuditLog;
use App\Models\Therapist;
use App\Models\Treatment;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class TherapistController extends Controller
{
    public function index(Request $request): View
    {
        $branchIds = $request->user()->accessibleBranches()->pluck('branches.id');

        return view('admin.therapists.index', [
            'therapists' => Therapist::whereIn('branch_id', $branchIds)
                ->with(['branch:id,name', 'schedules'])
                ->withCount('treatments')
                ->orderBy('name')
                ->paginate(25),
        ]);
    }

    public function create(Request $request): View
    {
        return $this->form($request, new Therapist(['is_active' => true]));
    }

    public function store(TherapistRequest $request): RedirectResponse
    {
        $therapist = DB::transaction(function () use ($request) {
            $therapist = new Therapist($request->safe()->only(['name', 'phone', 'specialization', 'is_active']));
            $therapist->branch_id = $request->validated('branch_id');
            $therapist->save();
            $this->syncRelations($request, $therapist);
            AuditLog::record('therapist.created', $therapist, ['name' => $therapist->name]);

            return $therapist;
        });

        return redirect()->route('admin.therapists.edit', $therapist)->with('status', 'Terapis ditambahkan.');
    }

    public function edit(Request $request, Therapist $therapist): View
    {
        return $this->form($request, $therapist->load(['schedules', 'leaves' => fn ($q) => $q->where('date', '>=', today())]));
    }

    public function update(TherapistRequest $request, Therapist $therapist): RedirectResponse
    {
        DB::transaction(function () use ($request, $therapist) {
            $before = $therapist->schedules()->get(['day_of_week', 'start_time', 'end_time'])->toArray();
            $therapist->fill($request->safe()->only(['name', 'phone', 'specialization', 'is_active']));
            $therapist->branch_id = $request->validated('branch_id');
            $therapist->save();
            $this->syncRelations($request, $therapist);
            AuditLog::record('therapist.updated', $therapist, [
                'schedules_before' => $before,
                'schedules_after' => $request->scheduleRows(),
                'treatments' => $request->validated('treatments', []),
            ]);
        });

        return redirect()->route('admin.therapists.edit', $therapist)->with('status', 'Data terapis disimpan.');
    }

    private function syncRelations(TherapistRequest $request, Therapist $therapist): void
    {
        $therapist->treatments()->sync($request->validated('treatments', []));
        $therapist->schedules()->delete();
        $therapist->schedules()->createMany($request->scheduleRows());
    }

    private function form(Request $request, Therapist $therapist): View
    {
        return view('admin.therapists.form', [
            'therapist' => $therapist,
            'branches' => $request->user()->accessibleBranches()->orderBy('name')->pluck('name', 'branches.id'),
            'treatments' => Treatment::with('category:id,name')->orderBy('treatment_category_id')->orderBy('sort_order')->get(['id', 'name', 'treatment_category_id']),
            'skills' => $therapist->exists ? $therapist->treatments()->pluck('treatments.id')->all() : [],
        ]);
    }
}
