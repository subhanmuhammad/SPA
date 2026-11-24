@php
    $title = $treatment->exists ? $treatment->name : 'Tambah Layanan';
    $items = old('items', $treatment->items->map->only('name', 'treatment_id')->all());
    $itemRows = max(count($items) + 2, 4);
@endphp

<x-layouts.app :title="$title">
    <div class="mb-6">
        <a href="{{ route('admin.treatments.index') }}" class="text-sm text-muted-foreground hover:text-foreground">&larr; Layanan & Paket</a>
        <h2 class="mt-1 text-2xl font-semibold">{{ $title }}</h2>
    </div>

    <form method="POST" enctype="multipart/form-data"
        action="{{ $treatment->exists ? route('admin.treatments.update', $treatment) : route('admin.treatments.store') }}"
        class="grid gap-6 lg:grid-cols-2 lg:items-start">
        @csrf
        @if ($treatment->exists)
            @method('PUT')
        @endif

        <x-ui.card title="Data layanan" description="Harga dan durasi di sini adalah nilai standar. Tiap cabang bisa punya harga atau durasi sendiri.">
            <div class="space-y-4">
                <div class="grid gap-2">
                    <x-ui.label for="name">Nama layanan</x-ui.label>
                    <x-ui.input name="name" :value="old('name', $treatment->name)" required maxlength="150" />
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div class="grid gap-2">
                        <x-ui.label for="treatment_category_id">Kategori</x-ui.label>
                        <x-ui.select name="treatment_category_id" :options="$categories" :selected="$treatment->treatment_category_id" />
                    </div>
                    <div class="grid gap-2">
                        <x-ui.label for="service_type">Jenis layanan</x-ui.label>
                        <x-ui.select name="service_type" :options="\App\Enums\ServiceType::options()" :selected="$treatment->service_type" />
                    </div>
                </div>

                <div class="grid gap-2">
                    <x-ui.label for="description">Deskripsi</x-ui.label>
                    <x-ui.textarea name="description" maxlength="2000">{{ old('description', $treatment->description) }}</x-ui.textarea>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div class="grid gap-2">
                        <x-ui.label for="duration_minutes">Durasi (menit)</x-ui.label>
                        <x-ui.input name="duration_minutes" type="number" min="5" max="600" step="5" inputmode="numeric" :value="old('duration_minutes', $treatment->duration_minutes)" placeholder="Belum diatur" />
                    </div>
                    <div class="grid gap-2">
                        <x-ui.label for="price">Harga (Rp)</x-ui.label>
                        <x-ui.input name="price" type="number" min="0" step="1000" inputmode="numeric" :value="old('price', $treatment->price)" required />
                    </div>
                </div>

                <label class="flex items-center gap-2 text-sm">
                    <input type="checkbox" name="is_active" value="1" class="size-4 accent-primary" @checked(old('is_active', $treatment->is_active))>
                    Aktif (tampil di website dan bisa dibooking)
                </label>
            </div>
        </x-ui.card>

        <div class="grid gap-6">
            <x-ui.card title="Promo" description="Isi harga normal untuk menampilkan harga coret dan penghematan. Kosongkan periode bila promo berlaku terus.">
                <div class="space-y-4">
                    <div class="grid gap-2">
                        <x-ui.label for="normal_price">Harga normal (Rp)</x-ui.label>
                        <x-ui.input name="normal_price" type="number" min="0" step="1000" inputmode="numeric" :value="old('normal_price', $treatment->normal_price)" />
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div class="grid gap-2">
                            <x-ui.label for="promo_starts_on">Mulai</x-ui.label>
                            <x-ui.input name="promo_starts_on" type="date" :value="old('promo_starts_on', $treatment->promo_starts_on?->toDateString())" />
                        </div>
                        <div class="grid gap-2">
                            <x-ui.label for="promo_ends_on">Berakhir</x-ui.label>
                            <x-ui.input name="promo_ends_on" type="date" :value="old('promo_ends_on', $treatment->promo_ends_on?->toDateString())" />
                        </div>
                    </div>
                </div>
            </x-ui.card>

            <x-ui.card title="Komponen paket" description="Untuk paket saja. Hubungkan komponen ke layanan agar kompetensi terapis bisa dicek saat booking.">
                <div class="space-y-2">
                    @for ($i = 0; $i < $itemRows; $i++)
                        <div class="grid grid-cols-2 gap-2">
                            <x-ui.input name="items[{{ $i }}][name]" id="items-{{ $i }}-name" :value="$items[$i]['name'] ?? ''" maxlength="100" placeholder="Nama komponen" aria-label="Nama komponen {{ $i + 1 }}" />
                            <x-ui.select name="items[{{ $i }}][treatment_id]" id="items-{{ $i }}-treatment" :options="['' => 'Belum dihubungkan'] + $components->all()" :selected="$items[$i]['treatment_id'] ?? ''" aria-label="Layanan komponen {{ $i + 1 }}" />
                        </div>
                        @error("items.$i.name") <p class="text-sm text-destructive">{{ $message }}</p> @enderror
                        @error("items.$i.treatment_id") <p class="text-sm text-destructive">{{ $message }}</p> @enderror
                    @endfor
                </div>
            </x-ui.card>

            <x-ui.card title="Foto" description="JPG, PNG, atau WebP, maksimal 2 MB.">
                <div class="space-y-4">
                    @if ($treatment->photoUrl())
                        <img src="{{ $treatment->photoUrl() }}" alt="Foto {{ $treatment->name }}" class="aspect-video w-full rounded-lg object-cover">
                        <label class="flex items-center gap-2 text-sm">
                            <input type="checkbox" name="remove_photo" value="1" class="size-4 accent-primary"> Hapus foto
                        </label>
                    @endif
                    <x-ui.input name="photo" type="file" accept="image/jpeg,image/png,image/webp" />
                </div>
            </x-ui.card>

            <div><x-ui.button>Simpan</x-ui.button></div>
        </div>
    </form>

    @if ($treatment->exists && $branches->isNotEmpty())
        <x-ui.card title="Pengaturan per cabang" description="Kosongkan harga atau durasi untuk memakai nilai standar." class="mt-6">
            <form method="POST" action="{{ route('admin.treatments.branches.update', $treatment) }}" class="space-y-4">
                @csrf
                @method('PUT')
                <div class="-mx-6 overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="border-b">
                            <tr class="text-left">
                                <th class="h-10 px-6 font-medium">Cabang</th>
                                <th class="h-10 px-2 font-medium">Tersedia</th>
                                <th class="h-10 px-2 font-medium">Harga cabang (Rp)</th>
                                <th class="h-10 px-6 font-medium">Durasi cabang (menit)</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($branches as $branch)
                                @php($pivot = $branchConfig->get($branch->id)?->pivot)
                                <tr class="border-b last:border-0">
                                    <td class="px-6 py-3 font-medium">{{ $branch->name }}</td>
                                    <td class="px-2 py-3">
                                        <input type="checkbox" name="branches[{{ $branch->id }}][is_available]" value="1" class="size-4 accent-primary"
                                            aria-label="Tersedia di {{ $branch->name }}" @checked(old("branches.{$branch->id}.is_available", $pivot?->is_available))>
                                    </td>
                                    <td class="px-2 py-3">
                                        <x-ui.input name="branches[{{ $branch->id }}][price]" id="branch-{{ $branch->id }}-price" type="number" min="0" step="1000"
                                            :value="old('branches.'.$branch->id.'.price', $pivot?->price)" :placeholder="$treatment->price" aria-label="Harga di {{ $branch->name }}" class="max-w-40" />
                                    </td>
                                    <td class="px-6 py-3">
                                        <x-ui.input name="branches[{{ $branch->id }}][duration_minutes]" id="branch-{{ $branch->id }}-duration" type="number" min="5" max="600" step="5"
                                            :value="old('branches.'.$branch->id.'.duration_minutes', $pivot?->duration_minutes)" :placeholder="$treatment->duration_minutes" aria-label="Durasi di {{ $branch->name }}" class="max-w-32" />
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @if ($errors->has('branches.*'))
                    <p class="text-sm text-destructive">{{ $errors->first('branches.*') }}</p>
                @endif
                <x-ui.button variant="outline">Simpan pengaturan cabang</x-ui.button>
            </form>
        </x-ui.card>
    @endif
</x-layouts.app>
