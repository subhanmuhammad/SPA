@php($item = $booking->items->first())

<x-layouts.app :title="'Booking '.$booking->code">
    <div class="mb-6">
        <a href="{{ route('admin.bookings.index') }}" class="text-sm text-muted-foreground hover:text-foreground">&larr; Booking</a>
        <div class="mt-1 flex flex-wrap items-center justify-between gap-3">
            <h2 class="flex flex-wrap items-center gap-3 text-2xl font-semibold">
                {{ $booking->code }}
                <x-ui.badge :variant="$booking->status->isOpen() ? 'default' : 'secondary'">{{ $booking->status->label() }}</x-ui.badge>
                <x-ui.badge variant="outline">{{ $booking->payment_status->label() }}</x-ui.badge>
            </h2>
            <x-ui.button variant="outline" :href="route('admin.bookings.receipt', $booking)" target="_blank"><i data-lucide="printer"></i> Kuitansi</x-ui.button>
        </div>
    </div>

    @if ($errors->any())
        <div role="alert" class="mb-6 rounded-lg border border-destructive/30 bg-destructive/5 px-4 py-3 text-sm text-destructive">{{ $errors->first() }}</div>
    @endif

    @if ($booking->change_request)
        <div class="mb-6 rounded-lg border px-4 py-3 text-sm">
            <span class="font-medium">Permintaan pelanggan</span> ({{ $booking->change_requested_at->diffForHumans() }}): {{ $booking->change_request }}
        </div>
    @endif

    <div class="grid gap-6 lg:grid-cols-2 lg:items-start">
        <x-ui.card title="Detail booking">
            <dl class="grid grid-cols-[auto_1fr] gap-x-6 gap-y-2">
                <dt class="text-muted-foreground">Pelanggan</dt><dd><a href="{{ route('admin.customers.show', $booking->customer_id) }}" class="underline">{{ $booking->customer_name }}</a> · {{ $booking->customer_phone }}@if ($booking->customer->email) · {{ $booking->customer->email }}@endif</dd>
                <dt class="text-muted-foreground">Cabang</dt><dd>{{ $booking->branch->name }}</dd>
                <dt class="text-muted-foreground">Layanan</dt>
                <dd>
                    {{ $item->name }} <x-ui.badge variant="outline">{{ $booking->service_type->label() }}</x-ui.badge>
                    @if ($item->components)
                        <span class="block text-muted-foreground">{{ implode(', ', $item->components) }}</span>
                    @endif
                </dd>
                <dt class="text-muted-foreground">Jadwal</dt><dd>{{ $booking->starts_at->translatedFormat('l, j F Y') }} · {{ $booking->starts_at->format('H:i') }}–{{ $booking->ends_at->format('H:i') }}</dd>
                <dt class="text-muted-foreground">Terapis</dt><dd>{{ $booking->therapist->name }}{{ $booking->therapist_requested ? ' (dipilih pelanggan)' : '' }}</dd>
                <dt class="text-muted-foreground">Total</dt>
                <dd>
                    <x-rupiah :amount="$booking->total_price" />
                    @if ($item->normal_price) <s class="text-muted-foreground"><x-rupiah :amount="$item->normal_price" /></s> @endif
                </dd>
                @if ($booking->address)
                    <dt class="text-muted-foreground">Alamat</dt>
                    <dd>
                        {{ $booking->address }}
                        @if ($booking->address_notes) <span class="block text-muted-foreground">{{ $booking->address_notes }}</span> @endif
                        @if ($booking->location_url) <a href="{{ $booking->location_url }}" target="_blank" rel="noopener noreferrer nofollow" class="block underline">Buka lokasi</a> @endif
                    </dd>
                @endif
                @if ($booking->area_name)
                    <dt class="text-muted-foreground">Area</dt><dd>{{ $booking->area_name }} · tempuh ±{{ $booking->travel_minutes }} menit</dd>
                    <dt class="text-muted-foreground">Transport</dt><dd><x-rupiah :amount="$booking->travel_fee" /> (sudah termasuk di harga)</dd>
                @endif
                @if ($booking->estimatedArrivalAt())
                    <dt class="text-muted-foreground">Berangkat</dt><dd>{{ $booking->departure_at->format('H:i') }} · perkiraan tiba {{ $booking->estimatedArrivalAt()->format('H:i') }}</dd>
                @endif
                @if ($booking->notes)
                    <dt class="text-muted-foreground">Catatan</dt><dd>{{ $booking->notes }}</dd>
                @endif
            </dl>
        </x-ui.card>

        <div class="grid gap-6">
            <x-ui.card title="Pembayaran" :description="'Total '.\Illuminate\Support\Number::currency($booking->total_price, in: 'IDR', locale: 'id', precision: 0).' · dibayar '.\Illuminate\Support\Number::currency($booking->paid_amount, in: 'IDR', locale: 'id', precision: 0).' · sisa '.\Illuminate\Support\Number::currency($booking->balance(), in: 'IDR', locale: 'id', precision: 0)">
                <div class="space-y-4">
                    @if ($booking->payments->isNotEmpty())
                        <ul class="divide-y rounded-lg border">
                            @foreach ($booking->payments as $payment)
                                <li class="flex items-center justify-between gap-4 px-3 py-2">
                                    <span>
                                        <span class="font-medium">{{ $payment->type->label() }}</span> · {{ $payment->method->name }}
                                        <span class="block text-xs text-muted-foreground">{{ $payment->receipt_number }} · {{ $payment->paid_at->translatedFormat('j M Y H:i') }} · {{ $payment->user->name }}@if ($payment->reference) · Ref {{ $payment->reference }}@endif @if ($payment->note) · {{ $payment->note }}@endif</span>
                                    </span>
                                    <span class="whitespace-nowrap">{{ $payment->type === \App\Enums\PaymentType::Refund ? '−' : '' }}<x-rupiah :amount="$payment->amount" /></span>
                                </li>
                            @endforeach
                        </ul>
                    @endif

                    @if ($booking->status === \App\Enums\BookingStatus::Completed && $booking->balance() > 0)
                        <form method="POST" action="{{ route('admin.bookings.payments.store', $booking) }}" class="grid gap-2 sm:grid-cols-2">
                            @csrf
                            <input type="hidden" name="idempotency_key" value="{{ \Illuminate\Support\Str::uuid() }}">
                            <x-ui.select name="payment_method_id" :options="$methods" aria-label="Metode pembayaran" />
                            <x-ui.input name="amount" type="number" min="1" :max="$booking->balance()" :value="old('amount', $booking->balance())" required aria-label="Nominal" />
                            <x-ui.input name="reference" maxlength="100" placeholder="No. referensi transfer/QRIS (opsional)" aria-label="Nomor referensi" />
                            <x-ui.input name="note" maxlength="255" placeholder="Catatan (opsional)" aria-label="Catatan pembayaran" />
                            <x-ui.button class="sm:col-span-2">Catat pembayaran</x-ui.button>
                        </form>
                    @elseif ($booking->balance() > 0)
                        <p class="text-muted-foreground">Pembayaran dicatat setelah status booking Selesai.</p>
                    @endif

                    @if ($booking->paid_amount > 0)
                        <details class="rounded-lg border px-3 py-2">
                            <summary class="cursor-pointer font-medium">Refund / koreksi</summary>
                            <form method="POST" action="{{ route('admin.bookings.refunds.store', $booking) }}" class="mt-3 grid gap-2 sm:grid-cols-2">
                                @csrf
                                <input type="hidden" name="idempotency_key" value="{{ \Illuminate\Support\Str::uuid() }}">
                                <x-ui.select name="payment_method_id" :options="$allMethods" aria-label="Metode refund" />
                                <x-ui.input name="amount" type="number" min="1" :max="$booking->paid_amount" required placeholder="Nominal refund" aria-label="Nominal refund" />
                                <x-ui.input name="reason" maxlength="255" required placeholder="Alasan refund (wajib)" aria-label="Alasan refund" class="sm:col-span-2" />
                                <x-ui.button variant="destructive" class="sm:col-span-2" data-confirm="Catat refund ini? Refund tidak bisa dihapus.">Catat refund</x-ui.button>
                            </form>
                        </details>
                    @endif
                </div>
            </x-ui.card>

            @if ($booking->status->next())
                <x-ui.card title="Ubah status">
                    <form method="POST" action="{{ route('admin.bookings.status', $booking) }}" class="space-y-3">
                        @csrf
                        @method('PUT')
                        <x-ui.input name="note" maxlength="255" placeholder="Catatan (opsional)" aria-label="Catatan perubahan status" />
                        <div class="flex flex-wrap gap-2">
                            @foreach ($booking->status->next() as $next)
                                <x-ui.button name="status" value="{{ $next->value }}" :variant="in_array($next, [\App\Enums\BookingStatus::Cancelled, \App\Enums\BookingStatus::NoShow], true) ? 'destructive' : 'default'">{{ $next->actionLabel() }}</x-ui.button>
                            @endforeach
                        </div>
                    </form>
                </x-ui.card>
            @endif

            @if ($booking->service_type === \App\Enums\ServiceType::HomeCare && $booking->status->blocksSchedule() && $booking->status !== \App\Enums\BookingStatus::Completed)
                <x-ui.card title="Keberangkatan terapis" description="Perkiraan tiba = jam berangkat + waktu tempuh area.">
                    <form method="POST" action="{{ route('admin.bookings.departure', $booking) }}" class="flex flex-wrap gap-2">
                        @csrf
                        @method('PUT')
                        <x-ui.input type="time" name="departure_time" :value="old('departure_time', $booking->departure_at?->format('H:i') ?? $booking->starts_at->copy()->subMinutes($booking->travel_minutes)->format('H:i'))" class="max-w-36" aria-label="Jam berangkat" />
                        <x-ui.button variant="outline">Simpan jam berangkat</x-ui.button>
                    </form>
                </x-ui.card>
            @endif

            @if ($alternatives->isNotEmpty())
                <x-ui.card title="Ganti layanan" description="Harga dan durasi dihitung ulang dari harga cabang saat ini. Jadwal mulai tetap.">
                    <form method="POST" action="{{ route('admin.bookings.treatment', $booking) }}" class="space-y-3">
                        @csrf
                        @method('PUT')
                        <x-ui.select name="treatment_id" :options="$alternatives" :selected="$item->treatment_id" aria-label="Layanan baru" />
                        <x-ui.button variant="outline">Ganti layanan</x-ui.button>
                    </form>
                </x-ui.card>
            @endif

            @if ($booking->status->isOpen())
                <x-ui.card title="Jadwal ulang / ganti terapis" description="Sistem mengecek jadwal kerja, libur, dan booking lain terapis.">
                    <form method="POST" action="{{ route('admin.bookings.reschedule', $booking) }}" class="space-y-3">
                        @csrf
                        @method('PUT')
                        <div class="grid grid-cols-2 gap-2">
                            <x-ui.input type="date" name="date" :value="old('date', $booking->starts_at->toDateString())" :min="today()->toDateString()" required aria-label="Tanggal" />
                            <x-ui.input type="time" name="time" step="1800" :value="old('time', $booking->starts_at->format('H:i'))" required aria-label="Jam" />
                        </div>
                        <x-ui.select name="therapist_id" :options="['' => 'Terapis mana saja yang tersedia'] + $therapists->all()" :selected="$booking->therapist_id" aria-label="Terapis" />
                        <x-ui.button variant="outline">Simpan jadwal</x-ui.button>
                    </form>
                </x-ui.card>
            @endif

            <x-ui.card title="Riwayat status">
                <ol class="space-y-2">
                    @foreach ($booking->histories as $history)
                        <li>
                            <span class="font-medium">{{ $history->to_status->label() }}</span>
                            <span class="text-muted-foreground">· {{ $history->created_at->translatedFormat('j M Y H:i') }} · {{ $history->user?->name ?? 'Pelanggan' }}</span>
                            @if ($history->note) <span class="block text-muted-foreground">{{ $history->note }}</span> @endif
                        </li>
                    @endforeach
                </ol>
            </x-ui.card>
        </div>
    </div>
</x-layouts.app>
