<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Booking;
use App\Models\Customer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class BookingTrackingController extends Controller
{
    public function lookupForm(): View
    {
        return view('customer.booking.lookup');
    }

    public function lookup(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'code' => ['required', 'string', 'max:12'],
            'phone' => ['required', 'string', 'max:20'],
        ]);

        $booking = Booking::where('code', strtoupper(trim($data['code'])))
            ->where('customer_phone', Customer::normalizePhone($data['phone']))
            ->first(['id', 'code']);

        if (! $booking) {
            throw ValidationException::withMessages(['code' => 'Kode booking atau nomor HP tidak cocok.']);
        }

        $request->session()->push('tracked_bookings', $booking->id);
        return redirect()->route('bookings.show', $booking->code);
    }

    public function show(Request $request, string $code): View
    {
        $booking = $this->authorized($request, $code);
        return view('customer.booking.show', [
            'booking' => $booking->load(['branch:id,name,address,phone', 'therapist:id,name', 'items']),
            'token' => $request->query('token'),
        ]);
    }

    public function receipt(Request $request, string $code): View
    {
        $booking = $this->authorized($request, $code);
        abort_if($booking->payments()->doesntExist(), 404);
        return view('receipt', ['booking' => $booking->load(['branch', 'items', 'payments.method', 'therapist:id,name'])]);
    }

    public function requestChange(Request $request, string $code): RedirectResponse
    {
        $booking = $this->authorized($request, $code);
        $data = $request->validate([
            'type' => ['required', Rule::in(['cancel', 'reschedule'])],
            'message' => ['required', 'string', 'max:500'],
        ], [], ['message' => 'keterangan']);

        if (! $booking->status->isOpen()) {
            throw ValidationException::withMessages(['message' => 'Booking ini sudah tidak bisa diubah.']);
        }

        $label = $data['type'] === 'cancel' ? 'Pembatalan' : 'Ubah jadwal';
        $booking->forceFill(['change_request' => "{$label}: {$data['message']}", 'change_requested_at' => now()])->save();
        AuditLog::record('booking.change_requested', $booking, ['type' => $data['type']]);

        return redirect()->route('bookings.show', ['code' => $booking->code, 'token' => $request->input('token')])
            ->with('status', 'Permintaan terkirim. Admin kami akan menghubungi Anda.');
    }

    private function authorized(Request $request, string $code): Booking
    {
        $booking = Booking::where('code', $code)->first();
        $allowed = $booking && (
            in_array($booking->id, $request->session()->get('tracked_bookings', []), true)
            || $booking->matchesToken($request->input('token'))
        );

        abort_unless($allowed, 404);
        return $booking;
    }
}
