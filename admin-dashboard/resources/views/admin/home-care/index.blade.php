<x-layouts.app title="Home Care">
    <div class="mb-6 flex flex-wrap items-center justify-between gap-4">
        <div>
            <h2 class="text-2xl font-semibold">Home Care</h2>
            <p class="text-muted-foreground">Area layanan, biaya transport, dan waktu tempuh per cabang. Home Care hanya bisa dibooking di area yang aktif.</p>
        </div>
        <x-ui.button variant="outline" :href="route('admin.bookings.index', ['type' => 'home_care'])"><i data-lucide="calendar-check"></i> Booking Home Care</x-ui.button>
    </div>

    @if ($errors->any())
        <div role="alert" class="mb-6 rounded-lg border border-destructive/30 bg-destructive/5 px-4 py-3 text-sm text-destructive">{{ $errors->first() }}</div>
    @endif

    <div class="space-y-6">
        @forelse ($branches as $branch)
            <x-ui.card :title="$branch->name" description="Waktu tempuh dihitung pergi dan pulang, jadi jadwal terapis ikut terblokir selama perjalanan.">
                <div class="space-y-6">
                    <form method="POST" action="{{ route('admin.home-care.limit', $branch) }}" class="flex flex-wrap items-end gap-2">
                        @csrf
                        @method('PUT')
                        <div class="grid gap-2">
                            <x-ui.label for="limit-{{ $branch->id }}">Maks. Home Care per terapis per hari</x-ui.label>
                            <x-ui.input name="home_care_max_per_therapist" id="limit-{{ $branch->id }}" type="number" min="1" max="20" :value="$branch->home_care_max_per_therapist" placeholder="Tanpa batas" class="max-w-40" />
                        </div>
                        <x-ui.button variant="outline">Simpan batas</x-ui.button>
                    </form>

                    <div class="-mx-6 overflow-x-auto border-t">
                        <div class="min-w-160 divide-y text-sm">
                            <div class="grid grid-cols-[minmax(10rem,1fr)_9rem_7rem_2rem_6rem] items-center gap-2 px-6 py-2 font-medium" aria-hidden="true">
                                <span>Area</span><span>Transport (Rp)</span><span>Tempuh (menit)</span><span>Aktif</span><span></span>
                            </div>
                            @foreach ($branch->homecareAreas as $area)
                                <form method="POST" action="{{ route('admin.home-care.areas.update', $area) }}" class="grid grid-cols-[minmax(10rem,1fr)_9rem_7rem_2rem_6rem] items-center gap-2 px-6 py-2">
                                    @csrf
                                    @method('PUT')
                                    <x-ui.input name="name" id="area-{{ $area->id }}-name" :value="$area->name" required maxlength="100" aria-label="Nama area" />
                                    <x-ui.input name="travel_fee" id="area-{{ $area->id }}-fee" type="number" min="0" step="1000" :value="$area->travel_fee" required aria-label="Biaya transport {{ $area->name }}" />
                                    <x-ui.input name="travel_minutes" id="area-{{ $area->id }}-minutes" type="number" min="0" max="240" step="5" :value="$area->travel_minutes" required aria-label="Waktu tempuh {{ $area->name }}" />
                                    <input type="checkbox" name="is_active" value="1" class="size-4 accent-primary" aria-label="Aktif {{ $area->name }}" @checked($area->is_active)>
                                    <x-ui.button variant="outline" size="sm">Simpan</x-ui.button>
                                </form>
                            @endforeach
                            <form method="POST" action="{{ route('admin.home-care.areas.store') }}" class="grid grid-cols-[minmax(10rem,1fr)_9rem_7rem_2rem_6rem] items-center gap-2 px-6 py-2">
                                @csrf
                                <input type="hidden" name="branch_id" value="{{ $branch->id }}">
                                <input type="hidden" name="is_active" value="1">
                                <x-ui.input name="name" id="new-area-{{ $branch->id }}" required maxlength="100" placeholder="Nama area baru, mis. kecamatan" aria-label="Nama area baru" />
                                <x-ui.input name="travel_fee" id="new-fee-{{ $branch->id }}" type="number" min="0" step="1000" value="0" required aria-label="Biaya transport" />
                                <x-ui.input name="travel_minutes" id="new-minutes-{{ $branch->id }}" type="number" min="0" max="240" step="5" value="0" required aria-label="Waktu tempuh" />
                                <span></span>
                                <x-ui.button size="sm"><i data-lucide="plus"></i> Tambah</x-ui.button>
                            </form>
                        </div>
                    </div>
                </div>
            </x-ui.card>
        @empty
            <p class="text-muted-foreground">Anda belum memiliki akses cabang.</p>
        @endforelse
    </div>
</x-layouts.app>
