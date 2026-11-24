<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Payment;
use App\Models\PaymentMethod;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class PaymentController extends Controller
{
    public function index(Request $request): View
    {
        $branches = $request->user()->accessibleBranches()->orderBy('name')->pluck('name', 'branches.id');
        $filters = $request->validate([
            'from' => ['nullable', 'date_format:Y-m-d'],
            'to' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:from'],
            'method' => ['nullable', 'integer'],
            'branch' => ['nullable', 'integer', Rule::in($branches->keys())],
        ]);
        $from = $filters['from'] ?? today()->toDateString();
        $to = $filters['to'] ?? $from;
        $query = Payment::query()
            ->whereIn('branch_id', isset($filters['branch']) ? [$filters['branch']] : $branches->keys())
            ->whereBetween('paid_at', [$from.' 00:00:00', $to.' 23:59:59'])
            ->when($filters['method'] ?? null, fn ($q, $id) => $q->where('payment_method_id', $id));

        $totals = (clone $query)->selectRaw("COALESCE(SUM(CASE WHEN type = 'payment' THEN amount ELSE 0 END), 0) as received, COALESCE(SUM(CASE WHEN type = 'refund' THEN amount ELSE 0 END), 0) as refunded")->first();
        return view('admin.payments.index', [
            'payments' => $query->with(['booking:id,code,customer_name', 'method:id,name', 'user:id,name'])->latest('paid_at')->paginate(50)->withQueryString(),
            'totals' => $totals,
            'from' => $from,
            'to' => $to,
            'branches' => $branches,
            'methods' => PaymentMethod::orderBy('sort_order')->pluck('name', 'id'),
        ]);
    }

    public function store(Request $request, Booking $booking): RedirectResponse
    {
        $data = $request->validate([
            'payment_method_id' => ['required', 'integer', Rule::exists('payment_methods', 'id')->where('is_active', true)],
            'amount' => ['required', 'integer', 'min:1', 'max:100000000'],
            'reference' => ['nullable', 'string', 'max:100'],
            'note' => ['nullable', 'string', 'max:255'],
            'idempotency_key' => ['required', 'uuid'],
        ], [], ['payment_method_id' => 'metode pembayaran', 'amount' => 'nominal', 'reference' => 'nomor referensi']);

        $payment = $booking->recordPayment(
            $data['amount'], PaymentMethod::findOrFail($data['payment_method_id']), $data['idempotency_key'],
            $request->user(), $data['reference'] ?? null, $data['note'] ?? null,
        );

        return redirect()->route('admin.bookings.show', $booking)->with('status', "Pembayaran {$payment->receipt_number} tercatat.");
    }

    public function refund(Request $request, Booking $booking): RedirectResponse
    {
        $data = $request->validate([
            'payment_method_id' => ['required', 'integer', Rule::exists('payment_methods', 'id')],
            'amount' => ['required', 'integer', 'min:1', 'max:100000000'],
            'reason' => ['required', 'string', 'max:255'],
            'idempotency_key' => ['required', 'uuid'],
        ], [], ['payment_method_id' => 'metode refund', 'amount' => 'nominal refund', 'reason' => 'alasan refund']);

        $booking->refund($data['amount'], PaymentMethod::findOrFail($data['payment_method_id']), $data['idempotency_key'], $request->user(), $data['reason']);
        return redirect()->route('admin.bookings.show', $booking)->with('status', 'Refund tercatat.');
    }

    public function receipt(Booking $booking): View
    {
        return view('receipt', ['booking' => $booking->load(['branch', 'items', 'payments.method', 'therapist:id,name'])]);
    }
}
