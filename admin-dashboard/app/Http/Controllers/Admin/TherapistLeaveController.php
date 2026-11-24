<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Therapist;
use App\Models\TherapistLeave;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class TherapistLeaveController extends Controller
{
    public function store(Request $request, Therapist $therapist): RedirectResponse
    {
        $data = $request->validate([
            'date' => ['required', 'date', 'after_or_equal:today', Rule::unique('therapist_leaves')->where('therapist_id', $therapist->id)],
            'reason' => ['nullable', 'string', 'max:150'],
        ], [], ['date' => 'tanggal libur']);

        $leave = $therapist->leaves()->create($data);
        AuditLog::record('therapist.leave_added', $therapist, ['date' => $leave->date->toDateString()]);
        $clash = $therapist->blockingBookings()->whereDate('starts_at', $leave->date)->count();
        return back()->with('status', $clash
            ? "Libur ditambahkan. Perhatian: ada {$clash} booking aktif terapis ini di tanggal tersebut yang perlu dijadwalkan ulang."
            : 'Libur ditambahkan.');
    }

    public function destroy(Therapist $therapist, TherapistLeave $leave): RedirectResponse
    {
        $leave->delete();
        AuditLog::record('therapist.leave_removed', $therapist, ['date' => $leave->date->toDateString()]);
        return back()->with('status', 'Libur dihapus.');
    }
}
