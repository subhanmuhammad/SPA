<x-layouts.app title="Audit Log">
    <div class="mb-6">
        <h2 class="text-2xl font-semibold">Audit Log</h2>
        <p class="text-muted-foreground">Catatan login, perubahan booking, jadwal, harga, pembayaran, refund, akun, dan konfigurasi. Tidak menyimpan password.</p>
    </div>

    <form method="GET" class="mb-4 flex flex-wrap gap-2">
        <x-ui.select name="action" :options="['' => 'Semua aksi'] + $actions->all()" :selected="request('action')" class="max-w-60" aria-label="Aksi" />
        <x-ui.select name="user" :options="['' => 'Semua pengguna'] + $users->all()" :selected="request('user')" class="max-w-52" aria-label="Pengguna" />
        <x-ui.input name="from" type="date" :value="request('from')" class="max-w-44" aria-label="Dari tanggal" />
        <x-ui.input name="to" type="date" :value="request('to')" class="max-w-44" aria-label="Sampai tanggal" />
        <x-ui.button variant="outline"><i data-lucide="search"></i> Filter</x-ui.button>
    </form>

    <x-data-table title="Aktivitas" :headers="['Waktu', 'Pengguna', 'Aksi', 'Objek', 'Detail']" :rows="$logs">
        @foreach ($logs as $log)
            <tr class="border-b align-top last:border-0">
                <td class="px-6 py-2.5 whitespace-nowrap">{{ $log->created_at->translatedFormat('j M Y H:i:s') }}</td>
                <td class="px-2 py-2.5 text-right">{{ $log->user?->name ?? 'Pelanggan / sistem' }}<span class="block text-xs text-muted-foreground">{{ $log->ip_address }}</span></td>
                <td class="px-2 py-2.5 text-right font-mono text-xs">{{ $log->action }}</td>
                <td class="px-2 py-2.5 text-right">{{ $log->subject_type ? class_basename($log->subject_type).' #'.$log->subject_id : '-' }}</td>
                <td class="px-6 py-2.5 text-right">
                    @if ($log->data)
                        <details>
                            <summary class="cursor-pointer text-muted-foreground">Lihat</summary>
                            <pre class="mt-2 max-w-md overflow-x-auto rounded-md bg-muted p-2 text-left text-xs">{{ json_encode($log->data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) }}</pre>
                        </details>
                    @endif
                </td>
            </tr>
        @endforeach
    </x-data-table>
    <div class="mt-4">{{ $logs->links() }}</div>
</x-layouts.app>
