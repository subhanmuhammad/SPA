<?php

namespace App\Http\Controllers\Admin;

use App\Enums\BookingStatus;
use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Booking;
use App\Models\Customer;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class CustomerController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->validate(['q' => ['nullable', 'string', 'max:100']]);
        $user = $request->user();
        $scope = fn (Builder $q) => $q->forUser($user);
        $customers = Customer::query()
            ->whereHas('bookings', $scope)
            ->withCount(['bookings as visits_count' => fn ($q) => $q->forUser($user)->where('status', 'completed')])
            ->withSum(['bookings as spent' => $scope], 'paid_amount')
            ->withMax(['bookings as last_visit' => fn ($q) => $q->forUser($user)->where('status', 'completed')], 'starts_at')
            ->when($filters['q'] ?? null, function ($q, $term) {
                $like = '%'.addcslashes($term, '%_\\').'%';
                $q->where(fn ($q) => $q->where('name', 'like', $like)->orWhere('phone', 'like', $like));
            })
            ->orderBy('name')
            ->paginate(25)
            ->withQueryString();

        return view('admin.customers.index', ['customers' => $customers]);
    }

    public function show(Request $request, Customer $customer): View
    {
        $this->authorizeCustomer($request->user(), $customer);
        $bookings = $customer->bookings()->forUser($request->user())
            ->with(['branch:id,name', 'items:id,booking_id,name', 'payments.method:id,name'])
            ->latest('starts_at')
            ->get();

        return view('admin.customers.show', [
            'customer' => $customer,
            'bookings' => $bookings,
            'visits' => $bookings->where('status', BookingStatus::Completed)->count(),
            'spent' => $bookings->sum('paid_amount'),
        ]);
    }

    public function update(Request $request, Customer $customer): RedirectResponse
    {
        $this->authorizeCustomer($request->user(), $customer);
        $request->merge(['phone' => Customer::normalizePhone((string) $request->input('phone'))]);
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'phone' => ['required', 'regex:/^08[0-9]{7,12}$/', Rule::unique('customers')->ignore($customer)],
            'email' => ['nullable', 'email', 'max:255'],
            'gender' => ['nullable', Rule::in(['female', 'male'])],
            'birth_date' => ['nullable', 'date', 'before:today'],
        ], ['phone.regex' => 'Nomor HP harus nomor ponsel Indonesia.'], ['name' => 'nama', 'phone' => 'nomor HP', 'birth_date' => 'tanggal lahir']);

        $customer->fill($data);
        $changes = array_keys(collect($customer->getDirty())->except('updated_at')->all());
        $customer->save();
        AuditLog::record('customer.updated', $customer, ['fields' => $changes]);

        return back()->with('status', 'Data pelanggan disimpan.');
    }

    private function authorizeCustomer(User $user, Customer $customer): void
    {
        abort_unless(Booking::forUser($user)->where('customer_id', $customer->id)->exists(), 403);
    }
}
