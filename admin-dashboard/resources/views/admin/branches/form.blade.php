@php($title = $branch->exists ? $branch->name : 'Tambah Cabang')

<x-layouts.app :title="$title">
    <div class="mb-6">
        <a href="{{ route('admin.branches.index') }}" class="text-sm text-muted-foreground hover:text-foreground">&larr; Cabang</a>
        <h2 class="mt-1 text-2xl font-semibold">{{ $title }}</h2>
    </div>

    <x-ui.card title="Data cabang" description="Data ini tampil di website pelanggan. Cabang nonaktif tidak bisa dibooking." class="max-w-xl">
        <form method="POST" action="{{ $branch->exists ? route('admin.branches.update', $branch) : route('admin.branches.store') }}" class="space-y-4">
            @csrf
            @if ($branch->exists)
                @method('PUT')
            @endif
            <div class="grid gap-2"><x-ui.label for="name">Nama cabang</x-ui.label><x-ui.input name="name" :value="old('name', $branch->name)" required maxlength="100" /></div>
            <div class="grid gap-2"><x-ui.label for="address">Alamat</x-ui.label><x-ui.input name="address" :value="old('address', $branch->address)" maxlength="255" /></div>
            <div class="grid gap-2"><x-ui.label for="phone">Telepon</x-ui.label><x-ui.input name="phone" type="tel" :value="old('phone', $branch->phone)" maxlength="30" /></div>
            <div class="grid grid-cols-2 gap-4">
                <div class="grid gap-2"><x-ui.label for="opening_time">Jam buka</x-ui.label><x-ui.input name="opening_time" type="time" :value="old('opening_time', substr($branch->opening_time, 0, 5))" required /></div>
                <div class="grid gap-2"><x-ui.label for="closing_time">Jam tutup</x-ui.label><x-ui.input name="closing_time" type="time" :value="old('closing_time', substr($branch->closing_time, 0, 5))" required /></div>
            </div>
            <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="is_active" value="1" class="size-4 accent-primary" @checked(old('is_active', $branch->is_active))> Aktif</label>
            <x-ui.button>Simpan</x-ui.button>
        </form>
    </x-ui.card>
</x-layouts.app>
