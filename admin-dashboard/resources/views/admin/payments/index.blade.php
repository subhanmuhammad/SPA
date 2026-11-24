<x-layouts.app title="Transaksi">
    <div class="mb-6">
        <h2 class="text-2xl font-semibold">Transaksi</h2>
        <p class="text-muted-foreground">Pembayaran dan refund yang dicatat admin.</p>
    </div>

    <form method="GET" class="mb-4 flex flex-wrap items-end gap-2">
        <x-ui.input name="from" type="date" :value="$from" class="max-w-44" aria-label="Dari tanggal" />
        <x-ui.input name="to" type="date" :value="$to" class="max-w-44" aria-label="Sampai tanggal" />
        <x-ui.select name="method" :options="['' => 'Semua metode'] + $methods->all()" :selected="request('method')" class="max-w-44" aria-label="Metode" />
        @if ($branches->count() > 1)
            <x-ui.select name="branch" :options="['' => 'Semua cabang'] + $branches->all()" :selected="request('branch')" class="max-w-48" aria-label="Cabang" />
        @endif
        <x-ui.button variant="outline"><i data-lucide="search"></i> Tampilkan</x-ui.button>
    </form>

    <div class="mb-4 grid gap-4 sm:grid-cols-3">
        <x-ui.card title="Diterima"><p class="text-2xl font-semibold"><x-rupiah :amount="$totals->received" /></p></x-ui.card>
        <x-ui.card title="Refund"><p class="text-2xl font-semibold"><x-rupiah :amount="$totals->refunded" /></p></x-ui.card>
        <x-ui.card title="Bersih"><p class="text-2xl font-semibold"><x-rupiah :amount="$totals->received - $totals->refunded" /></p></x-ui.card>
    </div>

    <x-ui.card class="py-0">
        @if ($payments->isEmpty())
            <p class="py-10 text-center text-muted-foreground">Tidak ada transaksi pada periode ini.</p>
        @else
            <div class="-mx-6 overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="border-b">
                        <tr class="text-left">
                            <th class="h-10 px-6 font-medium">Waktu</th>
                            <th class="h-10 px-2 font-medium">No. kuitansi</th>
                            <th class="h-10 px-2 font-medium">Booking</th>
                            <th class="h-10 px-2 font-medium">Metode</th>
                            <th class="h-10 px-2 font-medium">Dicatat oleh</th>
                            <th class="h-10 px-6 text-right font-medium">Nominal</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($payments as $payment)
                            <tr class="border-b last:border-0 hover:bg-muted/50">
                                <td class="px-6 py-3 whitespace-nowrap">{{ $payment->paid_at->translatedFormat('j M Y H:i') }}</td>
                                <td class="px-2 py-3">{{ $payment->receipt_number }}</td>
                                <td class="px-2 py-3"><a href="{{ route('admin.bookings.show', $payment->booking_id) }}" class="underline">{{ $payment->booking->code }}</a> · {{ $payment->booking->customer_name }}</td>
                                <td class="px-2 py-3">{{ $payment->method->name }}</td>
                                <td class="px-2 py-3">{{ $payment->user->name }}</td>
                                <td @class(['px-6 py-3 text-right whitespace-nowrap', 'text-destructive' => $payment->type === \App\Enums\PaymentType::Refund])>
                                    {{ $payment->type === \App\Enums\PaymentType::Refund ? '−' : '' }}<x-rupiah :amount="$payment->amount" />
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </x-ui.card>
    <div class="mt-4">{{ $payments->links() }}</div>
</x-layouts.app>
