<x-layouts.app title="Pelanggan">
    <div class="mb-6">
        <h2 class="text-2xl font-semibold">Pelanggan</h2>
        <p class="text-muted-foreground">Pelanggan yang pernah booking di cabang Anda.</p>
    </div>

    <form method="GET" class="mb-4 flex flex-wrap gap-2" role="search">
        <x-ui.input name="q" :value="request('q')" placeholder="Nama atau nomor HP" class="max-w-xs" aria-label="Cari pelanggan" />
        <x-ui.button variant="outline"><i data-lucide="search"></i> Cari</x-ui.button>
    </form>

    <x-ui.card class="py-0">
        @if ($customers->isEmpty())
            <p class="py-10 text-center text-muted-foreground">Belum ada pelanggan.</p>
        @else
            <div class="-mx-6 overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="border-b">
                        <tr class="text-left">
                            <th class="h-10 px-6 font-medium">Nama</th>
                            <th class="h-10 px-2 font-medium">Nomor HP</th>
                            <th class="h-10 px-2 font-medium">Kunjungan selesai</th>
                            <th class="h-10 px-2 font-medium">Kunjungan terakhir</th>
                            <th class="h-10 px-2 font-medium">Total dibayar</th>
                            <th class="h-10 px-6"><span class="sr-only">Aksi</span></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($customers as $customer)
                            <tr class="border-b last:border-0 hover:bg-muted/50">
                                <td class="px-6 py-3 font-medium">{{ $customer->name }}</td>
                                <td class="px-2 py-3">{{ $customer->phone }}</td>
                                <td class="px-2 py-3">{{ $customer->visits_count }}</td>
                                <td class="px-2 py-3">{{ $customer->last_visit ? \Illuminate\Support\Carbon::parse($customer->last_visit)->translatedFormat('j M Y') : '-' }}</td>
                                <td class="px-2 py-3 whitespace-nowrap"><x-rupiah :amount="$customer->spent ?? 0" /></td>
                                <td class="px-6 py-3 text-right"><x-ui.button variant="outline" size="sm" :href="route('admin.customers.show', $customer)">Detail</x-ui.button></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </x-ui.card>
    <div class="mt-4">{{ $customers->links() }}</div>
</x-layouts.app>
