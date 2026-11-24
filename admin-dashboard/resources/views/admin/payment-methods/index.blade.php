<x-layouts.app title="Metode Pembayaran">
    <div class="mb-6">
        <h2 class="text-2xl font-semibold">Metode Pembayaran</h2>
        <p class="text-muted-foreground">Metode nonaktif tidak bisa dipilih saat mencatat pembayaran, tapi riwayatnya tetap ada.</p>
    </div>

    @if ($errors->any())
        <div role="alert" class="mb-6 rounded-lg border border-destructive/30 bg-destructive/5 px-4 py-3 text-sm text-destructive">{{ $errors->first() }}</div>
    @endif

    <x-ui.card class="max-w-xl">
        <div class="space-y-2">
            @foreach ($methods as $method)
                <form method="POST" action="{{ route('admin.payment-methods.update', $method) }}" class="grid grid-cols-[1fr_auto_auto] items-center gap-3">
                    @csrf
                    @method('PUT')
                    <x-ui.input name="name" id="method-{{ $method->id }}" :value="$method->name" required maxlength="50" aria-label="Nama metode" />
                    <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="is_active" value="1" class="size-4 accent-primary" @checked($method->is_active)> Aktif</label>
                    <x-ui.button variant="outline" size="sm">Simpan</x-ui.button>
                </form>
            @endforeach
            <form method="POST" action="{{ route('admin.payment-methods.store') }}" class="grid grid-cols-[1fr_auto] gap-3 border-t pt-4">
                @csrf
                <x-ui.input name="name" id="new-method" required maxlength="50" placeholder="Metode baru, mis. Debit BCA" aria-label="Nama metode baru" />
                <x-ui.button size="sm"><i data-lucide="plus"></i> Tambah</x-ui.button>
            </form>
        </div>
    </x-ui.card>
</x-layouts.app>
