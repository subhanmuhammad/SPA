@php
    $treatmentOptions = $treatments->groupBy('category.name')->map(fn ($items) => $items->mapWithKeys(fn ($t) => [$t->id => $t->name.' · '.$t->service_type->label().' · '.$t->duration_minutes.' mnt']));
    $areaOptions = $branches->flatMap(fn ($b) => $b->homecareAreas->mapWithKeys(fn ($a) => [$a->id => ($branches->count() > 1 ? $b->name.' · ' : '').$a->name]));
@endphp

<x-layouts.app title="Booking Manual">
    <div class="mb-6">
        <a href="{{ route('admin.bookings.index') }}" class="text-sm text-muted-foreground hover:text-foreground">&larr; Booking</a>
        <h2 class="mt-1 text-2xl font-semibold">Booking Manual</h2>
        <p class="text-muted-foreground">Untuk pelanggan walk-in atau via telepon. Booking langsung berstatus Dikonfirmasi; harga diambil dari harga cabang.</p>
    </div>

    @if ($errors->any())
        <div role="alert" class="mb-6 rounded-lg border border-destructive/30 bg-destructive/5 px-4 py-3 text-sm text-destructive">{{ $errors->first() }}</div>
    @endif

    <form method="POST" action="{{ route('admin.bookings.store') }}" class="grid gap-6 lg:grid-cols-2 lg:items-start">
        @csrf
        <x-ui.card title="Layanan & jadwal">
            <div class="space-y-4">
                <div class="grid gap-2">
                    <x-ui.label for="branch_id">Cabang</x-ui.label>
                    <x-ui.select name="branch_id" :options="$branches->pluck('name', 'id')" />
                </div>
                <div class="grid gap-2">
                    <x-ui.label for="treatment_id">Layanan</x-ui.label>
                    <select name="treatment_id" id="treatment_id" required class="h-9 w-full rounded-md border border-input bg-transparent px-2.5 text-sm shadow-xs">
                        @foreach ($treatmentOptions as $category => $options)
                            <optgroup label="{{ $category }}">
                                @foreach ($options as $id => $label)
                                    <option value="{{ $id }}" @selected((string) old('treatment_id') === (string) $id)>{{ $label }}</option>
                                @endforeach
                            </optgroup>
                        @endforeach
                    </select>
                </div>
                <div class="grid gap-2">
                    <x-ui.label for="area_id">Area Home Care (khusus Home Care)</x-ui.label>
                    <x-ui.select name="area_id" :options="['' => '-'] + $areaOptions->all()" />
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div class="grid gap-2">
                        <x-ui.label for="date">Tanggal</x-ui.label>
                        <x-ui.input name="date" type="date" :value="old('date', today()->toDateString())" :min="today()->toDateString()" required />
                    </div>
                    <div class="grid gap-2">
                        <x-ui.label for="time">Jam mulai</x-ui.label>
                        <x-ui.input name="time" type="time" :value="old('time')" required />
                    </div>
                </div>
                <div class="grid gap-2">
                    <x-ui.label for="therapist_id">Terapis</x-ui.label>
                    <x-ui.select name="therapist_id" :options="['' => 'Siapa saja yang tersedia'] + $therapists->pluck('name', 'id')->all()" />
                </div>
            </div>
        </x-ui.card>

        <x-ui.card title="Pelanggan" description="Pelanggan lama dikenali dari nomor HP.">
            <div class="space-y-4">
                <div class="grid gap-2">
                    <x-ui.label for="phone">Nomor HP</x-ui.label>
                    <x-ui.input name="phone" type="tel" :value="old('phone')" required maxlength="16" />
                </div>
                <div class="grid gap-2">
                    <x-ui.label for="name">Nama</x-ui.label>
                    <x-ui.input name="name" :value="old('name')" required maxlength="100" />
                </div>
                <div class="grid gap-2">
                    <x-ui.label for="email">Email (opsional)</x-ui.label>
                    <x-ui.input name="email" type="email" :value="old('email')" maxlength="255" />
                </div>
                <div class="grid gap-2">
                    <x-ui.label for="address">Alamat (wajib untuk Home Care)</x-ui.label>
                    <x-ui.textarea name="address" maxlength="500">{{ old('address') }}</x-ui.textarea>
                </div>
                <div class="grid gap-2">
                    <x-ui.label for="notes">Catatan</x-ui.label>
                    <x-ui.textarea name="notes" maxlength="500">{{ old('notes') }}</x-ui.textarea>
                </div>
                <x-ui.button>Buat booking</x-ui.button>
            </div>
        </x-ui.card>
    </form>
</x-layouts.app>
