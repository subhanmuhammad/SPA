<x-layouts.app :title="$customer->name">
    <div class="mb-6">
        <a href="{{ route('admin.customers.index') }}" class="text-sm text-muted-foreground hover:text-foreground">&larr; Pelanggan</a>
        <h2 class="mt-1 text-2xl font-semibold">{{ $customer->name }}</h2>
    </div>

    <div class="mb-6 grid gap-4 sm:grid-cols-3">
        <x-ui.card title="Kunjungan selesai"><p class="text-2xl font-semibold">{{ $visits }}</p></x-ui.card>
        <x-ui.card title="Total dibayar"><p class="text-2xl font-semibold"><x-rupiah :amount="$spent" /></p></x-ui.card>
        <x-ui.card title="Pelanggan sejak"><p class="text-2xl font-semibold">{{ $customer->created_at->translatedFormat('M Y') }}</p></x-ui.card>
    </div>

    <div class="grid gap-6 lg:grid-cols-[22rem_1fr] lg:items-start">
        <x-ui.card title="Data pelanggan">
            <form method="POST" action="{{ route('admin.customers.update', $customer) }}" class="space-y-4">
                @csrf
                @method('PUT')
                <div class="grid gap-2"><x-ui.label for="name">Nama</x-ui.label><x-ui.input name="name" :value="old('name', $customer->name)" required maxlength="100" /></div>
                <div class="grid gap-2"><x-ui.label for="phone">Nomor HP</x-ui.label><x-ui.input name="phone" type="tel" :value="old('phone', $customer->phone)" required maxlength="16" /></div>
                <div class="grid gap-2"><x-ui.label for="email">Email</x-ui.label><x-ui.input name="email" type="email" :value="old('email', $customer->email)" maxlength="255" /></div>
                <div class="grid gap-2"><x-ui.label for="gender">Jenis kelamin</x-ui.label><x-ui.select name="gender" :options="['' => '-', 'female' => 'Perempuan', 'male' => 'Laki-laki']" :selected="$customer->gender" /></div>
                <div class="grid gap-2"><x-ui.label for="birth_date">Tanggal lahir</x-ui.label><x-ui.input name="birth_date" type="date" :value="old('birth_date', $customer->birth_date?->toDateString())" /></div>
                <x-ui.button>Simpan</x-ui.button>
            </form>
        </x-ui.card>

        <x-ui.card title="Riwayat booking & transaksi" class="py-0 pt-6">
            <div class="-mx-6 overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="border-b">
                        <tr class="text-left">
                            <th class="h-10 px-6 font-medium">Jadwal</th>
                            <th class="h-10 px-2 font-medium">Layanan</th>
                            <th class="h-10 px-2 font-medium">Status</th>
                            <th class="h-10 px-2 font-medium">Pembayaran</th>
                            <th class="h-10 px-6 text-right font-medium">Dibayar</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($bookings as $booking)
                            <tr class="border-b last:border-0 hover:bg-muted/50">
                                <td class="px-6 py-3 whitespace-nowrap"><a href="{{ route('admin.bookings.show', $booking) }}" class="underline">{{ $booking->code }}</a><span class="block text-muted-foreground">{{ $booking->starts_at->translatedFormat('j M Y H:i') }} · {{ $booking->branch->name }}</span></td>
                                <td class="px-2 py-3">{{ $booking->items->first()?->name }}</td>
                                <td class="px-2 py-3">{{ $booking->status->label() }}</td>
                                <td class="px-2 py-3">{{ $booking->payments->map(fn ($p) => $p->method->name)->unique()->join(', ') ?: $booking->payment_status->label() }}</td>
                                <td class="px-6 py-3 text-right whitespace-nowrap"><x-rupiah :amount="$booking->paid_amount" /></td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="px-6 py-6 text-center text-muted-foreground">Belum ada booking.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </x-ui.card>
    </div>
</x-layouts.app>
