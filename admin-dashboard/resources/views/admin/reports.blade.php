@php
    $labels = \App\Enums\BookingStatus::options();
    $td = 'px-2 py-2.5 text-right tabular-nums';
@endphp

<x-layouts.app title="Laporan">
    <div class="mb-4">
        <h2 class="text-2xl font-semibold">Laporan Operasional</h2>
        <p class="text-muted-foreground">Booking dihitung dari jadwal perawatan; pendapatan dari tanggal pembayaran dicatat.</p>
    </div>

    <x-period-filter :analytics="$analytics" :branches="$branches" />

    <div class="space-y-6">
        <section aria-labelledby="r-booking">
            <h3 id="r-booking" class="mb-3 text-lg font-semibold">Booking</h3>
            <div class="grid grid-cols-2 gap-4 sm:grid-cols-4 lg:grid-cols-7">
                @foreach ($statuses as $status => $count)
                    <x-stat :label="$labels[$status]">{{ $count }}</x-stat>
                @endforeach
            </div>
            <div class="mt-4 grid gap-4 sm:grid-cols-2">
                <x-stat label="In-Store (aktif & selesai)">{{ $types['in_store'] }}</x-stat>
                <x-stat label="Home Care (aktif & selesai)">{{ $types['home_care'] }}</x-stat>
            </div>
        </section>

        <section aria-labelledby="r-money" class="space-y-4">
            <h3 id="r-money" class="text-lg font-semibold">Transaksi & Pembayaran</h3>
            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <x-stat label="Diterima"><x-rupiah :amount="$revenue['received']" /></x-stat>
                <x-stat label="Refund"><x-rupiah :amount="$revenue['refunded']" /></x-stat>
                <x-stat label="Pendapatan bersih"><x-rupiah :amount="$revenue['net']" /></x-stat>
                <x-stat label="Rata-rata per transaksi" hint="Pendapatan bersih ÷ booking yang dibayar">
                    @if ($revenue['average'] !== null)<x-rupiah :amount="$revenue['average']" />@else - @endif
                </x-stat>
            </div>
            <x-data-table title="Per metode pembayaran" :headers="['Metode', 'Transaksi', 'Bersih']" :rows="$methods">
                @foreach ($methods as $row)
                    <tr class="border-b last:border-0"><td class="px-6 py-2.5">{{ $row->name }}</td><td class="{{ $td }}">{{ $row->transactions }}</td><td class="px-6 py-2.5 text-right tabular-nums"><x-rupiah :amount="$row->net" /></td></tr>
                @endforeach
            </x-data-table>
        </section>

        <x-data-table title="Layanan" description="Nilai = harga layanan pada booking yang selesai (di luar biaya transport)." :headers="['Layanan', 'Dibooking', 'Selesai', 'Batal / tidak datang', 'Nilai']" :rows="$treatments">
            @foreach ($treatments as $row)
                <tr class="border-b last:border-0">
                    <td class="px-6 py-2.5">{{ $row->name }} @if ($row->is_promo)<x-ui.badge variant="outline">Promo</x-ui.badge>@endif</td>
                    <td class="{{ $td }}">{{ $row->booked }}</td><td class="{{ $td }}">{{ $row->completed }}</td><td class="{{ $td }}">{{ $row->lost }}</td>
                    <td class="px-6 py-2.5 text-right tabular-nums"><x-rupiah :amount="$row->value" /></td>
                </tr>
            @endforeach
        </x-data-table>

        <x-data-table title="Terapis" description="Utilisasi = menit perawatan selesai ÷ menit jadwal kerja (di luar hari libur)." :headers="['Terapis', 'Perawatan selesai', 'Pelanggan dilayani', 'Batal / tidak datang', 'Utilisasi']" :rows="$therapists">
            @foreach ($therapists as $row)
                <tr class="border-b last:border-0">
                    <td class="px-6 py-2.5">{{ $row->name }}</td>
                    <td class="{{ $td }}">{{ $row->completed }}</td><td class="{{ $td }}">{{ $row->customers }}</td><td class="{{ $td }}">{{ $row->lost }}</td>
                    <td class="px-6 py-2.5 text-right tabular-nums">{{ $row->utilization === null ? '-' : $row->utilization.'%' }}</td>
                </tr>
            @endforeach
        </x-data-table>

        <section aria-labelledby="r-customers">
            <h3 id="r-customers" class="mb-3 text-lg font-semibold">Pelanggan</h3>
            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <x-stat label="Pelanggan berkunjung">{{ $customers['visitors'] }}</x-stat>
                <x-stat label="Pelanggan baru" hint="Booking pertamanya di periode ini">{{ $customers['new'] }}</x-stat>
                <x-stat label="Pelanggan berulang" hint="Pernah berkunjung sebelum periode ini">{{ $customers['returning'] }}</x-stat>
                <x-stat label="Kunjungan selesai">{{ $customers['visits'] }}</x-stat>
            </div>
        </section>

        <section aria-labelledby="r-homecare" class="space-y-4">
            <h3 id="r-homecare" class="text-lg font-semibold">Home Care</h3>
            <div class="grid gap-4 sm:grid-cols-3">
                <x-stat label="Booking Home Care">{{ $homeCare['booked'] }}</x-stat>
                <x-stat label="Selesai">{{ $homeCare['completed'] }}</x-stat>
                <x-stat label="Biaya transport (selesai)"><x-rupiah :amount="$homeCare['travel_fees']" /></x-stat>
            </div>
            <x-data-table title="Per area" :headers="['Area', 'Booking', 'Selesai']" :rows="$homeCare['areas']">
                @foreach ($homeCare['areas'] as $row)
                    <tr class="border-b last:border-0"><td class="px-6 py-2.5">{{ $row->area }}</td><td class="{{ $td }}">{{ $row->total }}</td><td class="px-6 py-2.5 text-right tabular-nums">{{ $row->completed }}</td></tr>
                @endforeach
            </x-data-table>
        </section>

        <x-data-table title="Pembatalan & tidak datang" description="Maksimal 100 terbaru pada periode ini." :headers="['Booking', 'Layanan', 'Terapis', 'Status', 'Catatan']" :rows="$lost">
            @foreach ($lost as $booking)
                <tr class="border-b last:border-0">
                    <td class="px-6 py-2.5"><a href="{{ route('admin.bookings.show', $booking) }}" class="underline">{{ $booking->code }}</a> <span class="block text-muted-foreground">{{ $booking->starts_at->translatedFormat('j M Y H:i') }} · {{ $booking->customer_name }}</span></td>
                    <td class="px-2 py-2.5 text-right">{{ $booking->items->first()?->name }}</td>
                    <td class="px-2 py-2.5 text-right">{{ $booking->therapist->name }}</td>
                    <td class="px-2 py-2.5 text-right">{{ $booking->status->label() }}</td>
                    <td class="px-6 py-2.5 text-right text-muted-foreground">{{ $booking->histories->first()?->note ?? '-' }}</td>
                </tr>
            @endforeach
        </x-data-table>
    </div>
</x-layouts.app>
