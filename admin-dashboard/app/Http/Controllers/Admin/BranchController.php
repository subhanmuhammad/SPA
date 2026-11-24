<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Branch;
use App\Models\Treatment;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\View\View;

class BranchController extends Controller
{
    public function index(): View
    {
        return view('admin.branches.index', ['branches' => Branch::withCount(['users', 'treatments'])->orderBy('name')->get()]);
    }

    public function create(): View
    {
        return view('admin.branches.form', ['branch' => new Branch]);
    }

    public function store(Request $request): RedirectResponse
    {
        $branch = DB::transaction(function () use ($request) {
            $branch = new Branch($this->validated($request));
            $base = Str::slug($branch->name);
            $slug = $base;
            for ($i = 2; Branch::withTrashed()->where('slug', $slug)->exists(); $i++) {
                $slug = "{$base}-{$i}";
            }
            $branch->slug = $slug;
            $branch->save();
            $branch->treatments()->attach(Treatment::where('is_active', true)->pluck('id'));
            AuditLog::record('branch.created', $branch, $branch->only('name', 'address', 'opening_time', 'closing_time'), $branch->id);

            return $branch;
        });

        return redirect()->route('admin.branches.edit', $branch)->with('status', 'Cabang dibuat. Semua layanan aktif tersedia di cabang ini; atur harga cabang dari menu Layanan & Paket.');
    }

    public function edit(Branch $branch): View
    {
        return view('admin.branches.form', ['branch' => $branch]);
    }

    public function update(Request $request, Branch $branch): RedirectResponse
    {
        $branch->fill($this->validated($request));
        $changes = collect($branch->getDirty())->except('updated_at')->map(fn ($new, $key) => ['from' => $branch->getOriginal($key), 'to' => $new]);
        $branch->save();
        AuditLog::record('branch.updated', $branch, $changes->all(), $branch->id);

        return redirect()->route('admin.branches.edit', $branch)->with('status', 'Cabang disimpan.');
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'address' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:30'],
            'opening_time' => ['required', 'date_format:H:i'],
            'closing_time' => ['required', 'date_format:H:i', 'after:opening_time'],
        ], [], ['name' => 'nama cabang', 'address' => 'alamat', 'phone' => 'telepon', 'opening_time' => 'jam buka', 'closing_time' => 'jam tutup'])
            + ['is_active' => $request->boolean('is_active')];
    }
}
