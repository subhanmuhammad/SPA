<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Treatment;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TreatmentBranchController extends Controller
{
    public function update(Request $request, Treatment $treatment): RedirectResponse
    {
        $branchIds = $request->user()->accessibleBranches()->pluck('branches.id');
        $rules = $branchIds->flatMap(fn (int $id) => [
            "branches.{$id}.is_available" => ['boolean'],
            "branches.{$id}.price" => ['nullable', 'integer', 'min:0', 'max:100000000'],
            "branches.{$id}.duration_minutes" => ['nullable', 'integer', 'between:5,600'],
        ])->all();
        $input = $request->validate($rules, [], ['branches.*.price' => 'harga cabang', 'branches.*.duration_minutes' => 'durasi cabang'])['branches'] ?? [];

        DB::transaction(function () use ($branchIds, $input, $treatment) {
            $sync = $branchIds->mapWithKeys(fn (int $id) => [$id => [
                'is_available' => (bool) ($input[$id]['is_available'] ?? false),
                'price' => $input[$id]['price'] ?? null,
                'duration_minutes' => $input[$id]['duration_minutes'] ?? null,
            ]])->all();

            $treatment->branches()->syncWithoutDetaching($sync);
            AuditLog::record('treatment.branch_config_updated', $treatment, ['branches' => $sync]);
        });

        return redirect()->route('admin.treatments.edit', $treatment)->with('status', 'Pengaturan cabang disimpan.');
    }
}
