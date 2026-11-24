<x-layouts.app title="Cabang">
    <div class="mb-6 flex flex-wrap items-center justify-between gap-4">
        <div>
            <h2 class="text-2xl font-semibold">Cabang</h2>
            <p class="text-muted-foreground">Harga dan ketersediaan layanan per cabang diatur di menu Layanan & Paket; area Home Care di menu Home Care.</p>
        </div>
        <x-ui.button :href="route('admin.branches.create')"><i data-lucide="plus"></i> Tambah Cabang</x-ui.button>
    </div>

    <x-data-table title="Daftar cabang" :headers="['Cabang', 'Jam buka', 'Layanan', 'Admin', 'Status', '']" :rows="$branches">
        @foreach ($branches as $branch)
            <tr class="border-b last:border-0 hover:bg-muted/50">
                <td class="px-6 py-3"><span class="block font-medium">{{ $branch->name }}</span><span class="text-muted-foreground">{{ $branch->address }}</span></td>
                <td class="px-2 py-3 text-right">{{ substr($branch->opening_time, 0, 5) }}–{{ substr($branch->closing_time, 0, 5) }}</td>
                <td class="px-2 py-3 text-right">{{ $branch->treatments_count }}</td>
                <td class="px-2 py-3 text-right">{{ $branch->users_count }}</td>
                <td class="px-2 py-3 text-right"><x-ui.badge :variant="$branch->is_active ? 'default' : 'secondary'">{{ $branch->is_active ? 'Aktif' : 'Nonaktif' }}</x-ui.badge></td>
                <td class="px-6 py-3 text-right"><x-ui.button variant="outline" size="sm" :href="route('admin.branches.edit', $branch)">Atur</x-ui.button></td>
            </tr>
        @endforeach
    </x-data-table>
</x-layouts.app>
