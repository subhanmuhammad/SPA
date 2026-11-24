<x-layouts.app title="Booking">
    <div class="mb-6 flex flex-wrap items-center justify-between gap-4">
        <div>
            <h2 class="text-2xl font-semibold">Booking</h2>
            <p class="text-muted-foreground">Semua booking In-Store dan Home Care.</p>
        </div>
        <x-ui.button :href="route('admin.bookings.create')"><i data-lucide="plus"></i> Booking Manual</x-ui.button>
    </div>

    <form method="GET" class="mb-4 flex flex-wrap gap-2" role="search">
        <x-ui.input name="q" :value="request('q')" placeholder="Kode, nama, atau HP" class="max-w-56" aria-label="Cari booking" />
        <x-ui.input name="date" type="date" :value="request('date')" class="max-w-44" aria-label="Tanggal" />
        <x-ui.select name="type" :options="['' => 'Semua jenis'] + \App\Enums\ServiceType::options()" :selected="request('type')" class="max-w-40" aria-label="Jenis layanan" />
        <x-ui.select name="status" :options="['' => 'Semua status'] + \App\Enums\BookingStatus::options()" :selected="request('status')" class="max-w-48" aria-label="Status" />
        @if ($branches->count() > 1)
            <x-ui.select name="branch" :options="['' => 'Semua cabang'] + $branches->all()" :selected="request('branch')" class="max-w-48" aria-label="Cabang" />
        @endif
        <x-ui.select name="therapist" :options="['' => 'Semua terapis'] + $therapists->all()" :selected="request('therapist')" class="max-w-48" aria-label="Terapis" />
        <label class="flex items-center gap-2 px-2 text-sm"><input type="checkbox" name="requests" value="1" class="size-4 accent-primary" @checked(request('requests'))> Ada permintaan</label>
        <x-ui.button variant="outline"><i data-lucide="search"></i> Filter</x-ui.button>
    </form>

    <x-ui.card class="py-0">
        @if ($bookings->isEmpty())
            <p class="py-10 text-center text-muted-foreground">Tidak ada booking.</p>
        @else
            <div class="-mx-6 overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="border-b">
                        <tr class="text-left">
                            <th class="h-10 px-6 font-medium">Jadwal</th>
                            <th class="h-10 px-2 font-medium">Pelanggan</th>
                            <th class="h-10 px-2 font-medium">Layanan</th>
                            <th class="h-10 px-2 font-medium">Terapis</th>
                            <th class="h-10 px-2 font-medium">Status</th>
                            <th class="h-10 px-6"><span class="sr-only">Aksi</span></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($bookings as $booking)
                            <tr class="border-b last:border-0 hover:bg-muted/50">
                                <td class="px-6 py-3 whitespace-nowrap">
                                    <span class="block font-medium">{{ $booking->starts_at->translatedFormat('D, j M') }} · {{ $booking->starts_at->format('H:i') }}</span>
                                    <span class="text-muted-foreground">{{ $booking->code }} · {{ $booking->branch->name }}</span>
                                </td>
                                <td class="px-2 py-3">
                                    <span class="block">{{ $booking->customer_name }}</span>
                                    <span class="text-muted-foreground">{{ $booking->customer_phone }}</span>
                                </td>
                                <td class="px-2 py-3">
                                    {{ $booking->items->first()?->name }}
                                    <x-ui.badge variant="outline">{{ $booking->service_type->label() }}</x-ui.badge>
                                </td>
                                <td class="px-2 py-3">{{ $booking->therapist->name }}</td>
                                <td class="px-2 py-3">
                                    <x-ui.badge :variant="$booking->status->isOpen() ? 'default' : 'secondary'">{{ $booking->status->label() }}</x-ui.badge>
                                    @if ($booking->status === \App\Enums\BookingStatus::Completed)
                                        <x-ui.badge variant="outline">{{ $booking->payment_status->label() }}</x-ui.badge>
                                    @endif
                                    @if ($booking->change_request)
                                        <span class="mt-1 block text-xs text-destructive">Ada permintaan</span>
                                    @endif
                                </td>
                                <td class="px-6 py-3 text-right"><x-ui.button variant="outline" size="sm" :href="route('admin.bookings.show', $booking)">Detail</x-ui.button></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </x-ui.card>
    <div class="mt-4">{{ $bookings->links() }}</div>
</x-layouts.app>
