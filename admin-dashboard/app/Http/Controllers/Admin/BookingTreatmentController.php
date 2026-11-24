<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Treatment;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class BookingTreatmentController extends Controller
{
    public function update(Request $request, Booking $booking): RedirectResponse
    {
        $data = $request->validate(['treatment_id' => ['required', 'integer']], [], ['treatment_id' => 'layanan']);
        $treatment = Treatment::offeredAt($booking->branch)
            ->with(['category:id,name', 'items:id,package_id,treatment_id,name'])
            ->whereRaw('COALESCE(branch_treatments.duration_minutes, treatments.duration_minutes) IS NOT NULL')
            ->find($data['treatment_id'])
            ?? throw ValidationException::withMessages(['treatment_id' => 'Layanan tidak tersedia di cabang ini.']);

        $booking->changeTreatment($treatment, $request->user());
        return back()->with('status', "Layanan diganti menjadi {$treatment->name}.");
    }
}
