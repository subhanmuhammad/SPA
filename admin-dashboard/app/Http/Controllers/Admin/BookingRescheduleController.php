<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class BookingRescheduleController extends Controller
{
    public function update(Request $request, Booking $booking): RedirectResponse
    {
        $data = $request->validate([
            'date' => ['required', 'date_format:Y-m-d', 'after_or_equal:today'],
            'time' => ['required', 'date_format:H:i'],
            'therapist_id' => ['nullable', 'integer'],
        ], [], ['date' => 'tanggal', 'time' => 'jam', 'therapist_id' => 'terapis']);

        $booking->reschedule(CarbonImmutable::parse("{$data['date']} {$data['time']}"), $data['therapist_id'] ?? null, $request->user());

        return back()->with('status', 'Jadwal booking diperbarui.');
    }
}
