<x-layouts.customer :title="$treatment->name" :header="$treatment->name" :breadcrumb="['Layanan' => route('home', ['cabang' => $branch->slug]).'#layanan']">
    <section class="container py-5">
        <div class="row g-5 justify-content-center">
            @if ($treatment->photoUrl())
                <div class="col-lg-5">
                    <img src="{{ $treatment->photoUrl() }}" alt="Foto {{ $treatment->name }}" class="img-fluid rounded w-100" style="aspect-ratio: 4 / 3; object-fit: cover;">
                </div>
            @endif
            <div class="col-lg-7">
                <p class="sub-title h6 pe-3 mb-3">{{ $treatment->category->name }} · {{ $treatment->service_type->label() }}</p>
                <h2 class="display-6 mb-4">{{ $treatment->name }}</h2>

                @if ($treatment->description)
                    <p class="fs-5">{{ $treatment->description }}</p>
                @endif

                @if ($treatment->items->isNotEmpty())
                    <h3 class="h5 mt-4">Termasuk</h3>
                    <ul class="list-unstyled">
                        @foreach ($treatment->items as $item)
                            <li class="mb-1"><i class="bi bi-check-circle-fill text-primary me-2"></i>{{ $item->name }}</li>
                        @endforeach
                    </ul>
                @endif

                <div class="bg-light rounded p-4 my-4 d-flex flex-wrap align-items-end gap-4">
                    <div>
                        @if ($treatment->normal_price)
                            <del class="d-block text-muted"><x-rupiah :amount="$treatment->normal_price" /></del>
                        @endif
                        <span class="h2 text-primary mb-0"><x-rupiah :amount="$treatment->price" /></span>
                        @if ($treatment->savings())
                            <span class="badge saving-badge rounded-pill ms-2">Hemat <x-rupiah :amount="$treatment->savings()" /></span>
                        @endif
                    </div>
                    @if ($treatment->duration_minutes)
                        <span><i class="bi bi-clock me-1"></i>{{ $treatment->duration_minutes }} menit</span>
                    @endif
                    @if ($treatment->promo_ends_on)
                        <span class="small">Promo berlaku s.d. {{ $treatment->promo_ends_on->translatedFormat('j F Y') }}</span>
                    @endif
                </div>
                <p class="small">Harga untuk {{ $branch->name }}. Pembayaran dilakukan setelah perawatan selesai.</p>

                @if ($treatment->duration_minutes)
                    <a href="{{ route('bookings.create', ['cabang' => $branch->slug, 'tipe' => $treatment->service_type->value, 'layanan' => $treatment->slug]) }}"
                        class="btn btn-primary rounded-pill text-white py-3 px-5">Booking layanan ini</a>
                @endif
            </div>
        </div>
    </section>
</x-layouts.customer>
