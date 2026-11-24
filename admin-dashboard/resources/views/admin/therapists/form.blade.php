@php
    $title = $therapist->exists ? $therapist->name : 'Tambah Terapis';
    $schedules = $therapist->exists ? $therapist->schedules->keyBy('day_of_week') : collect();
    $selectedSkills = array_map('intval', old('treatments', $skills));
@endphp

<x-layouts.app :title="$title">
    <div class="mb-6">
        <a href="{{ route('admin.therapists.index') }}" class="text-sm text-muted-foreground hover:text-foreground">&larr; Terapis</a>
        <h2 class="mt-1 text-2xl font-semibold">{{ $title }}</h2>
    </div>

    <form method="POST" action="{{ $therapist->exists ? route('admin.therapists.update', $therapist) : route('admin.therapists.store') }}" class="grid gap-6 lg:grid-cols-2 lg:items-start">
        @csrf
        @if ($therapist->exists)
            @method('PUT')
        @endif

        <div class="grid gap-6">
            <x-ui.card title="Data terapis">
                <div class="space-y-4">
                    <div class="grid gap-2">
                        <x-ui.label for="name">Nama</x-ui.label>
                        <x-ui.input name="name" :value="old('name', $therapist->name)" required maxlength="100" />
                    </div>
                    <div class="grid gap-2">
                        <x-ui.label for="branch_id">Cabang</x-ui.label>
                        <x-ui.select name="branch_id" :options="$branches" :selected="$therapist->branch_id" />
                    </div>
                    <div class="grid gap-2">
                        <x-ui.label for="phone">Nomor HP</x-ui.label>
                        <x-ui.input name="phone" type="tel" :value="old('phone', $therapist->phone)" maxlength="20" />
                    </div>
                    <div class="grid gap-2">
                        <x-ui.label for="specialization">Spesialisasi</x-ui.label>
                        <x-ui.input name="specialization" :value="old('specialization', $therapist->specialization)" maxlength="255" placeholder="Mis. pijat tradisional, perawatan wajah" />
                    </div>
                    <label class="flex items-center gap-2 text-sm">
                        <input type="checkbox" name="is_active" value="1" class="size-4 accent-primary" @checked(old('is_active', $therapist->is_active))>
                        Aktif (bisa menerima booking)
                    </label>
                </div>
            </x-ui.card>

            <x-ui.card title="Jadwal kerja mingguan" description="Booking hanya bisa diambil di dalam jam kerja dan jam buka cabang.">
                <div class="space-y-2">
                    @foreach (\App\Models\TherapistSchedule::DAYS as $day => $label)
                        @php($row = $schedules->get($day))
                        <div class="grid grid-cols-[7rem_1fr_1fr] items-center gap-2">
                            <label class="flex items-center gap-2 text-sm">
                                <input type="hidden" name="schedules[{{ $day }}][active]" value="0">
                                <input type="checkbox" name="schedules[{{ $day }}][active]" value="1" class="size-4 accent-primary" @checked(old("schedules.$day.active", (bool) $row))>
                                {{ $label }}
                            </label>
                            <x-ui.input type="time" name="schedules[{{ $day }}][start_time]" id="schedule-{{ $day }}-start" :value="old('schedules.'.$day.'.start_time', $row ? substr($row->start_time, 0, 5) : '09:00')" aria-label="Jam mulai {{ $label }}" />
                            <x-ui.input type="time" name="schedules[{{ $day }}][end_time]" id="schedule-{{ $day }}-end" :value="old('schedules.'.$day.'.end_time', $row ? substr($row->end_time, 0, 5) : '17:00')" aria-label="Jam selesai {{ $label }}" />
                        </div>
                        @error("schedules.$day.end_time") <p class="text-sm text-destructive">{{ $message }}</p> @enderror
                        @error("schedules.$day.start_time") <p class="text-sm text-destructive">{{ $message }}</p> @enderror
                    @endforeach
                </div>
            </x-ui.card>
        </div>

        <div class="grid gap-6">
            <x-ui.card title="Layanan yang dikuasai" description="Untuk paket, terapis harus menguasai semua komponen paket yang sudah dihubungkan ke layanan.">
                <div class="space-y-4">
                    @foreach ($treatments->groupBy('category.name') as $category => $items)
                        <fieldset>
                            <legend class="mb-2 font-medium">{{ $category }}</legend>
                            <div class="grid gap-1 sm:grid-cols-2">
                                @foreach ($items as $treatment)
                                    <label class="flex items-center gap-2 text-sm">
                                        <input type="checkbox" name="treatments[]" value="{{ $treatment->id }}" class="size-4 accent-primary" @checked(in_array($treatment->id, $selectedSkills, true))>
                                        {{ $treatment->name }}
                                    </label>
                                @endforeach
                            </div>
                        </fieldset>
                    @endforeach
                </div>
            </x-ui.card>
            <div><x-ui.button>Simpan</x-ui.button></div>
        </div>
    </form>

    @if ($therapist->exists)
        <x-ui.card title="Hari libur" description="Terapis tidak menerima booking di tanggal libur." class="mt-6 lg:max-w-[calc(50%-0.75rem)]">
            <div class="space-y-4">
                @forelse ($therapist->leaves as $leave)
                    <div class="flex items-center justify-between gap-4 rounded-md border px-3 py-2">
                        <span>{{ $leave->date->translatedFormat('l, j F Y') }} <span class="text-muted-foreground">{{ $leave->reason }}</span></span>
                        <form method="POST" action="{{ route('admin.therapists.leaves.destroy', [$therapist, $leave]) }}">
                            @csrf
                            @method('DELETE')
                            <x-ui.button variant="ghost" size="icon" aria-label="Hapus libur {{ $leave->date->toDateString() }}"><i data-lucide="trash-2"></i></x-ui.button>
                        </form>
                    </div>
                @empty
                    <p class="text-muted-foreground">Tidak ada libur terjadwal.</p>
                @endforelse

                <form method="POST" action="{{ route('admin.therapists.leaves.store', $therapist) }}" class="grid gap-2 border-t pt-4 sm:grid-cols-[auto_1fr_auto]">
                    @csrf
                    <x-ui.input type="date" name="date" :min="today()->toDateString()" required aria-label="Tanggal libur" />
                    <x-ui.input name="reason" maxlength="150" placeholder="Keterangan (opsional)" aria-label="Keterangan libur" />
                    <x-ui.button variant="outline"><i data-lucide="plus"></i> Tambah</x-ui.button>
                </form>
            </div>
        </x-ui.card>
    @endif
</x-layouts.app>
