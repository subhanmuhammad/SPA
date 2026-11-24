@php($item = $booking->items->first())

<x-layouts.base :title="'Kuitansi '.$booking->code" class="bg-muted print:bg-white">
    <main class="mx-auto max-w-xl px-4 py-8 print:p-0">
        <div class="mb-4 flex justify-end gap-2 print:hidden">
            <button type="button" data-print class="inline-flex h-9 items-center gap-1.5 rounded-md bg-primary px-3 text-sm font-medium text-primary-foreground">
                <i data-lucide="printer"></i> Cetak / Simpan PDF
            </button>
        </div>

        <article class="rounded-xl bg-card p-8 text-sm ring-1 ring-foreground/10 print:ring-0">
            <header class="flex items-start justify-between gap-4 border-b pb-4">
                <div class="flex items-center gap-3">
                    <img src="/images/logo.jpg" alt="" class="size-12 rounded-md">
                    <div>
                        <p class="text-base font-semibold">Zenova Spa</p>
                        <p class="text-muted-foreground">{{ $booking->branch->name }}</p>
                        <p class="text-muted-foreground">{{ $booking->branch->address }}</p>
                    </div>
                </div>
                <div class="text-right">
                    <p class="font-semibold">KUITANSI</p>
                    <p class="text-muted-foreground">{{ $booking->code }}</p>
                </div>
            </header>

            <dl class="grid grid-cols-[auto_1fr] gap-x-6 gap-y-1 border-b py-4">
                <dt class="text-muted-foreground">Pelanggan</dt><dd>{{ $booking->customer_name }}</dd>
                <dt class="text-muted-foreground">Tanggal</dt><dd>{{ $booking->starts_at->translatedFormat('j F Y, H:i') }}</dd>
                <dt class="text-muted-foreground">Terapis</dt><dd>{{ $booking->therapist->name }}</dd>
                <dt class="text-muted-foreground">Jenis</dt><dd>{{ $booking->service_type->label() }}</dd>
            </dl>

            <table class="w-full border-b">
                <tbody>
                    <tr>
                        <td class="py-2">
                            {{ $item->name }}
                            @if ($item->components) <span class="block text-xs text-muted-foreground">{{ implode(', ', $item->components) }}</span> @endif
                        </td>
                        <td class="py-2 text-right whitespace-nowrap"><x-rupiah :amount="$item->price" /></td>
                    </tr>
                    @if ($booking->travel_fee)
                        <tr><td class="py-2">Transport Home Care ({{ $booking->area_name }})</td><td class="py-2 text-right"><x-rupiah :amount="$booking->travel_fee" /></td></tr>
                    @endif
                    <tr class="font-semibold"><td class="py-2">Total</td><td class="py-2 text-right"><x-rupiah :amount="$booking->total_price" /></td></tr>
                </tbody>
            </table>

            <table class="w-full border-b">
                <tbody>
                    @forelse ($booking->payments as $payment)
                        <tr>
                            <td class="py-1.5">
                                {{ $payment->type->label() }} · {{ $payment->method->name }}
                                <span class="block text-xs text-muted-foreground">{{ $payment->receipt_number }} · {{ $payment->paid_at->translatedFormat('j M Y H:i') }}</span>
                            </td>
                            <td class="py-1.5 text-right whitespace-nowrap">{{ $payment->type === \App\Enums\PaymentType::Refund ? '−' : '' }}<x-rupiah :amount="$payment->amount" /></td>
                        </tr>
                    @empty
                        <tr><td class="py-2 text-muted-foreground">Belum ada pembayaran.</td></tr>
                    @endforelse
                </tbody>
            </table>

            <dl class="grid grid-cols-[1fr_auto] gap-y-1 pt-4">
                <dt>Dibayar</dt><dd class="text-right"><x-rupiah :amount="$booking->paid_amount" /></dd>
                <dt>Sisa</dt><dd class="text-right"><x-rupiah :amount="$booking->balance()" /></dd>
                <dt class="font-semibold">Status</dt><dd class="text-right font-semibold">{{ $booking->payment_status->label() }}</dd>
            </dl>

            <p class="mt-8 text-center text-xs text-muted-foreground">Terima kasih · Relaks • Segar • Percaya Diri</p>
        </article>
    </main>
</x-layouts.base>
