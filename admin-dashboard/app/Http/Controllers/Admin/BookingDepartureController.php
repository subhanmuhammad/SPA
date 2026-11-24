<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ServiceType;
use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Booking;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class BookingDepartureController extends Controller
{
    public function update(Request $request, Booking $booking): RedirectResponse
    {
        if ($booking->service_type !== ServiceType::HomeCare || ! $booking->status->blocksSchedule()) {
            throw ValidationException::withMessages(['departure_time' => 'Jam berangkat hanya untuk booking Home Care yang aktif.']);
        }

        $data = $request->validate(['departure_time' => ['nullable', 'date_format:H:i']], [], ['departure_time' => 'jam berangkat']);
        $departure = $data['departure_time'] ? $booking->starts_at->copy()->setTimeFromTimeString($data['departure_time']) : null;
        if ($departure && $departure > $booking->starts_at) {
            throw ValidationException::withMessages(['departure_time' => 'Jam berangkat harus sebelum jam mulai perawatan.']);
        }

        $booking->forceFill(['departure_at' => $departure])->save();
        AuditLog::record('booking.departure_set', $booking, ['departure_at' => $departure?->toDateTimeString()]);

        return back()->with('status', $departure ? 'Jam berangkat disimpan. Perkiraan tiba '.$booking->estimatedArrivalAt()->format('H:i').'.' : 'Jam berangkat dihapus.');
    }
}
