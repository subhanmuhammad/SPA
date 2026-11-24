<x-layouts.app title="Terapis">
    <div class="mb-6 flex flex-wrap items-center justify-between gap-4">
        <div>
            <h2 class="text-2xl font-semibold">Terapis</h2>
            <p class="text-muted-foreground">Kompetensi layanan, jadwal kerja, dan hari libur.</p>
        </div>
        <x-ui.button :href="route('admin.therapists.create')"><i data-lucide="plus"></i> Tambah Terapis</x-ui.button>
    </div>

    <x-ui.card class="py-0">
        @if ($therapists->isEmpty())
            <p class="py-10 text-center text-muted-foreground">Belum ada terapis. Booking online baru bisa dilakukan setelah ada terapis dengan jadwal kerja.</p>
        @else
            <div class="-mx-6 overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="border-b">
                        <tr class="text-left">
                            <th class="h-10 px-6 font-medium">Nama</th>
                            <th class="h-10 px-2 font-medium">Cabang</th>
                            <th class="h-10 px-2 font-medium">Hari kerja</th>
                            <th class="h-10 px-2 font-medium">Layanan dikuasai</th>
                            <th class="h-10 px-2 font-medium">Status</th>
                            <th class="h-10 px-6"><span class="sr-only">Aksi</span></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($therapists as $therapist)
                            <tr class="border-b last:border-0 hover:bg-muted/50">
                                <td class="px-6 py-3">
                                    <span class="block font-medium">{{ $therapist->name }}</span>
                                    <span class="text-muted-foreground">{{ $therapist->specialization }}</span>
                                </td>
                                <td class="px-2 py-3">{{ $therapist->branch->name }}</td>
                                <td class="px-2 py-3">{{ $therapist->schedules->map(fn ($s) => \Illuminate\Support\Str::substr(\App\Models\TherapistSchedule::DAYS[$s->day_of_week], 0, 3))->join(', ') ?: '-' }}</td>
                                <td class="px-2 py-3">{{ $therapist->treatments_count }}</td>
                                <td class="px-2 py-3"><x-ui.badge :variant="$therapist->is_active ? 'default' : 'secondary'">{{ $therapist->is_active ? 'Aktif' : 'Nonaktif' }}</x-ui.badge></td>
                                <td class="px-6 py-3 text-right"><x-ui.button variant="outline" size="sm" :href="route('admin.therapists.edit', $therapist)">Atur</x-ui.button></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </x-ui.card>
    <div class="mt-4">{{ $therapists->links() }}</div>
</x-layouts.app>
