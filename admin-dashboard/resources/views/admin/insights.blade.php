@php($td = 'px-2 py-2.5 text-right tabular-nums')

<x-layouts.app title="Business Intelligence">
    <div class="mb-4">
        <h2 class="text-2xl font-semibold">Business Intelligence</h2>
        <p class="text-muted-foreground">Semua angka dihitung dari data booking dan pembayaran aktual. Metrik yang datanya belum cukup ditandai.</p>
    </div>

    <x-period-filter :analytics="$analytics" :branches="$branches" />

    <div class="space-y-6">
        <x-ui.card title="Insight" description="Hanya muncul bila datanya memenuhi ambang minimum.">
            @forelse ($insights as $insight)
                <p class="border-b py-2 last:border-0">{{ $insight }}</p>
            @empty
                <p class="text-muted-foreground">Belum ada insight: data pada periode ini belum cukup.</p>
            @endforelse
        </x-ui.card>

        <section aria-labelledby="bi-revenue" class="space-y-4">
            <h3 id="bi-revenue" class="text-lg font-semibold">Pendapatan</h3>
            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-5">
                <x-stat label="Bersih periode ini"><x-rupiah :amount="$revenue['net']" /></x-stat>
                <x-stat :label="'Bersih tahun '.now()->year"><x-rupiah :amount="$yearly" /></x-stat>
                <x-stat label="Refund"><x-rupiah :amount="$revenue['refunded']" /></x-stat>
                <x-stat label="Rata-rata transaksi">@if ($revenue['average'] !== null)<x-rupiah :amount="$revenue['average']" />@else - @endif</x-stat>
                <x-stat label="Booking dibayar">{{ $revenue['paid_bookings'] }}</x-stat>
            </div>
            <x-bar-chart id="bi-revenue-chart" title="Pendapatan bersih" :data="$series">
                <x-slot:actions><x-grain-switch :grain="$grain" /></x-slot:actions>
            </x-bar-chart>
            <x-data-table title="Per cabang" :headers="['Cabang', 'Perawatan selesai', 'Pendapatan bersih']" :rows="$byBranch">
                @foreach ($byBranch as $row)
                    <tr class="border-b last:border-0"><td class="px-6 py-2.5">{{ $row->name }}</td><td class="{{ $td }}">{{ $row->completed }}</td><td class="px-6 py-2.5 text-right tabular-nums"><x-rupiah :amount="$row->net" /></td></tr>
                @endforeach
            </x-data-table>
        </section>

        <section aria-labelledby="bi-customers">
            <h3 id="bi-customers" class="mb-3 text-lg font-semibold">Pelanggan</h3>
            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <x-stat label="Total pelanggan">{{ $customers['total'] }}</x-stat>
                <x-stat label="Pelanggan baru">{{ $customers['new'] }}</x-stat>
                <x-stat label="Pelanggan berulang">{{ $customers['returning'] }}</x-stat>
                <x-stat label="Frekuensi kunjungan" hint="Kunjungan selesai per pelanggan yang berkunjung">{{ $customers['visitors'] ? number_format($customers['visits'] / $customers['visitors'], 1, ',', '.') : '-' }}</x-stat>
                <x-stat label="Retention rate" :hint="$customers['retention'] === null ? 'Data belum cukup (butuh ≥ 5 pelanggan di periode sebelumnya)' : 'Pelanggan periode sebelumnya yang kembali'">{{ $customers['retention'] === null ? '-' : $customers['retention'].'%' }}</x-stat>
                <x-stat label="Tidak aktif > 90 hari">{{ $customers['inactive'] }}</x-stat>
                <x-stat label="Customer Lifetime Value" :hint="$customers['clv'] === null ? 'Data belum cukup (butuh ≥ 10 pelanggan yang pernah membayar)' : 'Rata-rata total bayar per pelanggan'">@if ($customers['clv'] !== null)<x-rupiah :amount="$customers['clv']" />@else - @endif</x-stat>
            </div>
        </section>

        <section aria-labelledby="bi-treatments" class="space-y-4">
            <h3 id="bi-treatments" class="text-lg font-semibold">Layanan</h3>
            <div class="grid gap-4 sm:grid-cols-2">
                <x-stat label="Paket promo" :hint="'Nilai selesai '.\Illuminate\Support\Number::currency($promo['promo']->sum('value'), in: 'IDR', locale: 'id', precision: 0)">{{ $promo['promo']->sum('booked') }} booking</x-stat>
                <x-stat label="Layanan reguler" :hint="'Nilai selesai '.\Illuminate\Support\Number::currency($promo['regular']->sum('value'), in: 'IDR', locale: 'id', precision: 0)">{{ $promo['regular']->sum('booked') }} booking</x-stat>
            </div>
            <x-data-table title="Perbandingan layanan" description="Diurutkan dari booking terbanyak. Laba tidak dihitung karena data biaya belum tersedia." :headers="['Layanan', 'Dibooking', 'Selesai', 'Nilai selesai']" :rows="$treatments">
                @foreach ($treatments as $row)
                    <tr class="border-b last:border-0">
                        <td class="px-6 py-2.5">{{ $row->name }} @if ($row->is_promo)<x-ui.badge variant="outline">Promo</x-ui.badge>@endif</td>
                        <td class="{{ $td }}">{{ $row->booked }}</td><td class="{{ $td }}">{{ $row->completed }}</td>
                        <td class="px-6 py-2.5 text-right tabular-nums"><x-rupiah :amount="$row->value" /></td>
                    </tr>
                @endforeach
            </x-data-table>
        </section>

        <x-data-table title="Terapis" :headers="['Terapis', 'Perawatan selesai', 'Pelanggan dilayani', 'Batal / tidak datang', 'Utilisasi']" :rows="$therapists">
            @foreach ($therapists as $row)
                <tr class="border-b last:border-0">
                    <td class="px-6 py-2.5">{{ $row->name }}</td>
                    <td class="{{ $td }}">{{ $row->completed }}</td><td class="{{ $td }}">{{ $row->customers }}</td>
                    <td class="{{ $td }}">{{ $row->lost }}{{ $row->completed + $row->lost ? ' ('.round(100 * $row->lost / ($row->completed + $row->lost)).'%)' : '' }}</td>
                    <td class="px-6 py-2.5 text-right tabular-nums">{{ $row->utilization === null ? '-' : $row->utilization.'%' }}</td>
                </tr>
            @endforeach
        </x-data-table>

        <section aria-labelledby="bi-bookings" class="space-y-4">
            <h3 id="bi-bookings" class="text-lg font-semibold">Booking</h3>
            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-5">
                <x-stat label="Total booking">{{ array_sum($statuses) }}</x-stat>
                <x-stat label="Berhasil (selesai)">{{ $statuses['completed'] }}</x-stat>
                <x-stat label="Dibatalkan">{{ $statuses['cancelled'] }}</x-stat>
                <x-stat label="Tidak datang">{{ $statuses['no_show'] }}</x-stat>
                <x-stat label="Home Care · In-Store">{{ $types['home_care'] }} · {{ $types['in_store'] }}</x-stat>
            </div>
            @if ($hours)
                <x-bar-chart id="bi-hours" title="Booking per jam mulai" :data="$hours" unit="booking" />
            @endif
        </section>
    </div>
</x-layouts.app>
