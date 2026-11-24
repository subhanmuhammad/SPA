<x-layouts.app title="Akun Admin">
    <div class="mb-6 flex flex-wrap items-center justify-between gap-4">
        <div>
            <h2 class="text-2xl font-semibold">Akun Admin</h2>
            <p class="text-muted-foreground">Admin hanya bisa mengakses cabang yang ditetapkan. Super Admin mengakses semua cabang.</p>
        </div>
        <x-ui.button :href="route('admin.users.create')"><i data-lucide="plus"></i> Tambah Akun</x-ui.button>
    </div>

    <x-data-table title="Daftar akun" :headers="['Nama', 'Peran', 'Akses cabang', 'Status', '']" :rows="$users">
        @foreach ($users as $user)
            <tr class="border-b last:border-0 hover:bg-muted/50">
                <td class="px-6 py-3"><span class="block font-medium">{{ $user->name }}</span><span class="text-muted-foreground">{{ $user->email }}</span></td>
                <td class="px-2 py-3 text-right">{{ $user->role->label() }}</td>
                <td class="px-2 py-3 text-right">{{ $user->isSuperAdmin() ? 'Semua cabang' : ($user->branches->pluck('name')->join(', ') ?: '-') }}</td>
                <td class="px-2 py-3 text-right"><x-ui.badge :variant="$user->is_active ? 'default' : 'secondary'">{{ $user->is_active ? 'Aktif' : 'Nonaktif' }}</x-ui.badge></td>
                <td class="px-6 py-3 text-right whitespace-nowrap">
                    <x-ui.button variant="ghost" size="sm" :href="route('admin.audit-logs.index', ['user' => $user->id])">Aktivitas</x-ui.button>
                    <x-ui.button variant="outline" size="sm" :href="route('admin.users.edit', $user)">Atur</x-ui.button>
                </td>
            </tr>
        @endforeach
    </x-data-table>
    <div class="mt-4">{{ $users->links() }}</div>
</x-layouts.app>
