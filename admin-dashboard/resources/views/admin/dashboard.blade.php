@php($labels = \App\Enums\BookingStatus::options())

<x-layouts.app title="Dashboard">
    <div class="mb-6 flex flex-wrap items-end justify-between gap-4">
        <div>
            <h2 class="text-2xl font-semibold">Hari ini</h2>
            <p class="text-muted-foreground">{{ now()->translatedFormat('l, j F Y') }}</p>
        </div>
        @if ($branches->count() > 1)
            <form method="GET" class="flex gap-2">
                <x-ui.select name="branch" :options="['' => 'Semua cabang'] + $branches->all()" :selected="request('branch')" aria-label="Cabang" data-auto-submit />
                <noscript><x-ui.button variant="outline">Tampilkan</x-ui.button></noscript>
            </form>
        @endif
    </div>

    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <x-stat label="Booking hari ini">{{ array_sum($statuses) }}</x-stat>
        <x-stat label="Pembayaran diterima hari ini"><x-rupiah :amount="$revenueToday['net']" /></x-stat>
        <x-stat label="Belum lunas" hint="Booking selesai yang masih punya sisa tagihan"><x-rupiah :amount="$outstanding" /></x-stat>
        <x-stat label="Pelanggan · Terapis aktif">{{ $customers }} · {{ $therapists }}</x-stat>
    </div>

    <div class="mt-4 grid grid-cols-2 gap-4 sm:grid-cols-4 lg:grid-cols-7">
        @foreach ($statuses as $status => $count)
            <a href="{{ route('admin.bookings.index', ['date' => today()->toDateString(), 'status' => $status]) }}" class="rounded-xl bg-card p-4 ring-1 ring-foreground/10 hover:bg-muted/50">
                <p class="text-xs text-muted-foreground">{{ $labels[$status] }}</p>
                <p class="mt-1 text-xl font-semibold tabular-nums">{{ $count }}</p>
            </a>
        @endforeach
    </div>

    <div class="mt-6 grid gap-6 lg:grid-cols-[2fr_1fr] lg:items-start">
        <x-bar-chart id="revenue" title="Pendapatan bersih" :data="$series">
            <x-slot:actions><x-grain-switch :grain="$grain" /></x-slot:actions>
        </x-bar-chart>

        <x-ui.card title="Home Care berikutnya">
            @forelse ($homeCare as $booking)
                <a href="{{ route('admin.bookings.show', $booking) }}" class="-mx-2 block rounded-md px-2 py-2 hover:bg-muted/50">
                    <span class="font-medium">{{ $booking->starts_at->translatedFormat('D j M, H:i') }}</span> · {{ $booking->customer_name }}
                    <span class="block text-muted-foreground">{{ $booking->area_name }} · {{ $booking->therapist->name }} · {{ $booking->status->label() }}</span>
                </a>
            @empty
                <p class="text-muted-foreground">Tidak ada Home Care terjadwal.</p>
            @endforelse
        </x-ui.card>
    </div>
</x-layouts.app>
