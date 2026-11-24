<x-layouts.app title="Layanan & Paket">
    <div class="mb-6 flex flex-wrap items-center justify-between gap-4">
        <div>
            <h2 class="text-2xl font-semibold">Layanan & Paket</h2>
            <p class="text-muted-foreground">Katalog layanan, paket combo, dan paket promo.</p>
        </div>
        <x-ui.button :href="route('admin.treatments.create')"><i data-lucide="plus"></i> Tambah Layanan</x-ui.button>
    </div>

    <form method="GET" class="mb-4 flex flex-wrap gap-2" role="search">
        <x-ui.input name="q" :value="request('q')" placeholder="Cari nama layanan" class="max-w-xs" aria-label="Cari nama layanan" />
        <x-ui.select name="category" :options="['' => 'Semua kategori'] + $categories->all()" :selected="request('category')" class="max-w-52" aria-label="Kategori" />
        <x-ui.button variant="outline"><i data-lucide="search"></i> Cari</x-ui.button>
    </form>

    <x-ui.card class="py-0">
        @if ($treatments->isEmpty())
            <p class="py-10 text-center text-muted-foreground">Tidak ada layanan yang cocok.</p>
        @else
            <div class="-mx-6 overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="border-b">
                        <tr class="text-left">
                            <th class="h-10 px-6 font-medium">Nama</th>
                            <th class="h-10 px-2 font-medium">Kategori</th>
                            <th class="h-10 px-2 font-medium">Jenis</th>
                            <th class="h-10 px-2 font-medium">Durasi</th>
                            <th class="h-10 px-2 font-medium">Harga</th>
                            <th class="h-10 px-2 font-medium">Status</th>
                            <th class="h-10 px-6"><span class="sr-only">Aksi</span></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($treatments as $treatment)
                            <tr class="border-b transition-colors last:border-0 hover:bg-muted/50">
                                <td class="px-6 py-3 font-medium">{{ $treatment->name }}</td>
                                <td class="px-2 py-3">{{ $treatment->category->name }}</td>
                                <td class="px-2 py-3"><x-ui.badge variant="outline">{{ $treatment->service_type->label() }}</x-ui.badge></td>
                                <td class="px-2 py-3 whitespace-nowrap">
                                    @if ($treatment->duration_minutes)
                                        {{ $treatment->duration_minutes }} menit
                                    @else
                                        <span class="text-destructive">Belum diatur</span>
                                    @endif
                                </td>
                                <td class="px-2 py-3 whitespace-nowrap">
                                    @if ($treatment->normal_price)
                                        <s class="block text-xs text-muted-foreground"><x-rupiah :amount="$treatment->normal_price" /></s>
                                    @endif
                                    <x-rupiah :amount="$treatment->price" />
                                </td>
                                <td class="px-2 py-3">
                                    <x-ui.badge :variant="$treatment->is_active ? 'default' : 'secondary'">{{ $treatment->is_active ? 'Aktif' : 'Nonaktif' }}</x-ui.badge>
                                </td>
                                <td class="px-6 py-3 text-right">
                                    <x-ui.button variant="outline" size="sm" :href="route('admin.treatments.edit', $treatment)">Atur</x-ui.button>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </x-ui.card>

    <div class="mt-4">{{ $treatments->links() }}</div>
</x-layouts.app>
