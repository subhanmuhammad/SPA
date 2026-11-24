@php($item = $booking->items->first())

<x-layouts.customer :title="'Booking '.$booking->code" header="Status Booking">
    <section class="container py-5" style="max-width: 760px;">
        @if (session('status'))
            <div class="alert alert-success" role="status">{{ session('status') }}</div>
        @endif

        <div class="text-center mb-4">
            <p class="mb-1">Kode booking</p>
            <p class="display-6 fw-bold text-dark mb-2" style="letter-spacing: 3px;">{{ $booking->code }}</p>
            <span class="badge bg-dark rounded-pill px-3 py-2">{{ $booking->status->label() }}</span>
        </div>

        <div class="bg-light rounded p-4">
            <dl class="row mb-0">
                <dt class="col-sm-4 fw-normal">Nama</dt><dd class="col-sm-8 text-dark">{{ $booking->customer_name }}</dd>
                <dt class="col-sm-4 fw-normal">Layanan</dt>
                <dd class="col-sm-8 text-dark">
                    {{ $item->name }} ({{ $booking->service_type->label() }})
                    @if ($item->components)<span class="d-block small text-body">{{ implode(', ', $item->components) }}</span>@endif
                </dd>
                <dt class="col-sm-4 fw-normal">Jadwal</dt><dd class="col-sm-8 text-dark">{{ $booking->starts_at->translatedFormat('l, j F Y') }} · {{ $booking->starts_at->format('H:i') }}–{{ $booking->ends_at->format('H:i') }}</dd>
                <dt class="col-sm-4 fw-normal">Cabang</dt><dd class="col-sm-8 text-dark">{{ $booking->branch->name }}</dd>
                @if ($booking->address)
                    <dt class="col-sm-4 fw-normal">Alamat</dt><dd class="col-sm-8 text-dark">{{ $booking->address }}@if ($booking->area_name)<span class="d-block small text-body">Area {{ $booking->area_name }}</span>@endif</dd>
                @endif
                @if ($booking->estimatedArrivalAt())
                    <dt class="col-sm-4 fw-normal">Perkiraan tiba</dt><dd class="col-sm-8 text-dark">{{ $booking->estimatedArrivalAt()->format('H:i') }}</dd>
                @endif
                @if ($booking->travel_fee)
                    <dt class="col-sm-4 fw-normal">Transport</dt><dd class="col-sm-8 text-dark"><x-rupiah :amount="$booking->travel_fee" /></dd>
                @endif
                <dt class="col-sm-4 fw-normal">Terapis</dt>
                <dd class="col-sm-8 text-dark">{{ $booking->status === \App\Enums\BookingStatus::Pending ? 'Ditampilkan setelah booking dikonfirmasi' : $booking->therapist->name }}</dd>
                <dt class="col-sm-4 fw-normal">Total</dt><dd class="col-sm-8 text-dark"><span class="fw-bold"><x-rupiah :amount="$booking->total_price" /></span> · dibayar setelah perawatan</dd>
                <dt class="col-sm-4 fw-normal">Pembayaran</dt>
                <dd class="col-sm-8 text-dark mb-0">
                    {{ $booking->payment_status->label() }}
                    @if ($booking->paid_amount > 0 || $booking->payment_status === \App\Enums\PaymentStatus::Refunded)
                        · <a href="{{ route('bookings.receipt', ['code' => $booking->code, 'token' => $token]) }}" target="_blank">Lihat kuitansi</a>
                    @endif
                </dd>
            </dl>
        </div>

        @if ($booking->change_request)
            <div class="alert alert-info mt-4">Permintaan Anda sedang diproses: {{ $booking->change_request }}</div>
        @elseif ($booking->status->isOpen())
            <details class="bg-light rounded p-4 mt-4" @if ($errors->any()) open @endif>
                <summary class="fw-semibold text-dark">Ajukan pembatalan atau ubah jadwal</summary>
                <form method="POST" action="{{ route('bookings.request-change', $booking->code) }}" class="mt-3">
                    @csrf
                    <input type="hidden" name="token" value="{{ $token }}">
                    <div class="d-flex gap-4 mb-3">
                        <div class="form-check"><input class="form-check-input" type="radio" name="type" id="type-reschedule" value="reschedule" checked><label class="form-check-label" for="type-reschedule">Ubah jadwal</label></div>
                        <div class="form-check"><input class="form-check-input" type="radio" name="type" id="type-cancel" value="cancel"><label class="form-check-label" for="type-cancel">Batalkan</label></div>
                    </div>
                    <x-zen.field name="message" label="Keterangan (mis. jadwal pengganti yang diinginkan)" type="textarea" required maxlength="500" />
                    <button class="btn btn-primary rounded-pill text-white px-4">Kirim permintaan</button>
                </form>
            </details>
        @endif
    </section>
</x-layouts.customer>
