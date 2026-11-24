<?php

namespace App\Http\Controllers\Admin;

use App\Enums\BookingStatus;
use App\Http\Controllers\Controller;
use App\Models\Booking;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class BookingStatusController extends Controller
{
    public function update(Request $request, Booking $booking): RedirectResponse
    {
        $data = $request->validate([
            'status' => ['required', Rule::enum(BookingStatus::class)],
            'note' => ['nullable', 'string', 'max:255'],
        ]);

        $status = BookingStatus::from($data['status']);
        $booking->moveTo($status, $request->user(), $data['note'] ?? null);

        return back()->with('status', "Status booking menjadi {$status->label()}.");
    }
}
