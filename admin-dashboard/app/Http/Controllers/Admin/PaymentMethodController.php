<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\PaymentMethod;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class PaymentMethodController extends Controller
{
    public function index(): View
    {
        return view('admin.payment-methods.index', ['methods' => PaymentMethod::orderBy('sort_order')->orderBy('id')->get()]);
    }

    public function store(Request $request): RedirectResponse
    {
        $method = PaymentMethod::create($this->validated($request) + ['sort_order' => PaymentMethod::max('sort_order') + 1]);
        AuditLog::record('payment_method.created', $method, ['name' => $method->name]);
        return back()->with('status', "Metode {$method->name} ditambahkan.");
    }

    public function update(Request $request, PaymentMethod $method): RedirectResponse
    {
        $method->update($this->validated($request, $method));
        AuditLog::record('payment_method.updated', $method, $method->only('name', 'is_active'));
        return back()->with('status', "Metode {$method->name} disimpan.");
    }

    private function validated(Request $request, ?PaymentMethod $method = null): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:50', Rule::unique('payment_methods')->ignore($method)],
        ], [], ['name' => 'nama metode']) + ['is_active' => $request->boolean('is_active', $method === null)];
    }
}
