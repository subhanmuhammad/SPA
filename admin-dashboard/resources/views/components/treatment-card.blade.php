@props(['treatment', 'branch'])

<div class="service-item rounded h-100 d-flex flex-column">
    @if ($treatment->photoUrl())
        <div class="service-img rounded-top">
            <img src="{{ $treatment->photoUrl() }}" class="img-fluid rounded-top w-100" style="aspect-ratio: 4 / 3; object-fit: cover;" alt="" loading="lazy">
        </div>
    @endif
    <div @class(['service-content bg-light p-4 flex-grow-1 d-flex', 'rounded-bottom' => $treatment->photoUrl(), 'rounded' => ! $treatment->photoUrl()])>
        <div class="service-content-inner d-flex flex-column w-100">
            <h3 class="h5 mb-3">{{ $treatment->name }}</h3>
            @if ($treatment->items->isNotEmpty())
                <p class="mb-3 text-muted">{{ $treatment->items->pluck('name')->join(', ', ' & ') }}</p>
            @elseif ($treatment->description)
                <p class="mb-3 text-muted">{{ $treatment->description }}</p>
            @endif
            <div class="mt-auto">
                @if ($treatment->normal_price)
                    <del class="d-block text-muted small"><x-rupiah :amount="$treatment->normal_price" /></del>
                @endif
                <div class="d-flex flex-wrap align-items-baseline justify-content-between gap-2">
                    <span class="price"><x-rupiah :amount="$treatment->price" /></span>
                    @if ($treatment->duration_minutes)
                        <span class="text-muted small"><i class="bi bi-clock me-1"></i>{{ $treatment->duration_minutes }} menit</span>
                    @endif
                </div>
                @if ($treatment->savings())
                    <span class="badge saving-badge rounded-pill mt-2">Hemat <x-rupiah :amount="$treatment->savings()" /></span>
                @endif
                <a href="{{ route('treatments.show', ['slug' => $treatment->slug, 'cabang' => $branch->slug]) }}" class="btn btn-primary rounded-pill text-white py-2 px-4 mt-3 d-block">
                    Lihat detail<span class="visually-hidden"> {{ $treatment->name }}</span>
                </a>
            </div>
        </div>
    </div>
</div>
