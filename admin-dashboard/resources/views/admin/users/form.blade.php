@php
    $title = $user->exists ? $user->name : 'Tambah Akun';
    $chosen = array_map('intval', old('branches', $selected));
@endphp

<x-layouts.app :title="$title">
    <div class="mb-6">
        <a href="{{ route('admin.users.index') }}" class="text-sm text-muted-foreground hover:text-foreground">&larr; Akun Admin</a>
        <h2 class="mt-1 text-2xl font-semibold">{{ $title }}</h2>
    </div>

    <form method="POST" action="{{ $user->exists ? route('admin.users.update', $user) : route('admin.users.store') }}" class="grid gap-6 lg:grid-cols-2 lg:items-start">
        @csrf
        @if ($user->exists)
            @method('PUT')
        @endif

        <x-ui.card title="Data akun" :description="$user->exists ? 'Kosongkan password bila tidak diganti. Mengganti password, peran, atau menonaktifkan akun akan mengeluarkan semua sesi akun ini.' : null">
            <div class="space-y-4">
                <div class="grid gap-2"><x-ui.label for="name">Nama</x-ui.label><x-ui.input name="name" :value="old('name', $user->name)" required maxlength="100" /></div>
                <div class="grid gap-2"><x-ui.label for="email">Email</x-ui.label><x-ui.input name="email" type="email" :value="old('email', $user->email)" required maxlength="255" autocomplete="off" /></div>
                <div class="grid gap-2"><x-ui.label for="password">Password</x-ui.label><x-ui.input name="password" type="password" autocomplete="new-password" :required="! $user->exists" /></div>
                <div class="grid gap-2"><x-ui.label for="password_confirmation">Ulangi password</x-ui.label><x-ui.input name="password_confirmation" type="password" autocomplete="new-password" /></div>
                <p class="text-xs text-muted-foreground">Minimal 8 karakter, berisi huruf dan angka.</p>
            </div>
        </x-ui.card>

        <x-ui.card title="Peran & akses">
            <div class="space-y-4">
                <div class="grid gap-2">
                    <x-ui.label for="role">Peran</x-ui.label>
                    <x-ui.select name="role" :options="collect(\App\Enums\Role::cases())->mapWithKeys(fn ($r) => [$r->value => $r->label()])" :selected="$user->role ?? \App\Enums\Role::Admin" />
                </div>
                <fieldset>
                    <legend class="mb-2 text-sm font-medium">Akses cabang (untuk peran Admin)</legend>
                    <div class="grid gap-1">
                        @foreach ($branches as $id => $name)
                            <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="branches[]" value="{{ $id }}" class="size-4 accent-primary" @checked(in_array($id, $chosen, true))> {{ $name }}</label>
                        @endforeach
                    </div>
                    @error('branches') <p class="mt-1 text-sm text-destructive">{{ $message }}</p> @enderror
                </fieldset>
                <label class="flex items-center gap-2 text-sm">
                    <input type="checkbox" name="is_active" value="1" class="size-4 accent-primary" @checked(old('is_active', $user->exists ? $user->is_active : true))> Aktif
                </label>
                <x-ui.button>Simpan</x-ui.button>
            </div>
        </x-ui.card>
    </form>
</x-layouts.app>
