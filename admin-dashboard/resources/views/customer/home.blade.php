<x-layouts.customer>
    <section class="hero">
        <div class="container py-5 text-center text-white">
            <p class="text-uppercase fw-bold text-secondary mb-4" style="letter-spacing: 3px;">Modern Massage &amp; Wellness</p>
            <h1 class="display-2 text-white mb-4">Relaks • Segar • Percaya Diri</h1>
            <p class="fs-5 mb-5 mx-auto" style="max-width: 640px;">Pijat dan perawatan tubuh di spa kami atau langsung di rumah Anda dengan layanan Home Care.</p>
            <div class="d-flex flex-wrap justify-content-center gap-3">
                <a class="btn btn-primary rounded-pill text-white py-3 px-5" href="{{ route('bookings.create') }}">Booking Sekarang</a>
                <a class="btn btn-light rounded-pill py-3 px-5" href="#layanan">Lihat Layanan</a>
            </div>
        </div>
    </section>

    @if ($branches->count() > 1)
        <div class="container pt-5">
            <form method="GET" class="d-flex flex-wrap align-items-center gap-3">
                <label for="cabang" class="fw-semibold">Harga untuk cabang</label>
                <select id="cabang" name="cabang" data-auto-submit class="form-select w-auto">
                    @foreach ($branches as $option)
                        <option value="{{ $option->slug }}" @selected($option->is($branch))>{{ $option->name }}</option>
                    @endforeach
                </select>
                <noscript><button class="btn btn-primary text-white">Tampilkan</button></noscript>
            </form>
        </div>
    @endif

    @if ($promos->isNotEmpty())
        <section id="promo" class="container-fluid service py-5">
            <div class="container py-5">
                <x-section-title :sub="$promoCategory->tagline" :title="$promoCategory->name">{{ $promoCategory->description }}</x-section-title>
                <div class="row g-4 justify-content-center">
                    @foreach ($promos as $treatment)
                        <div class="col-md-6 col-lg-4 col-xl-3"><x-treatment-card :treatment="$treatment" :branch="$branch" /></div>
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    <section class="container-fluid about bg-light py-5">
        <div class="container py-5">
            <div class="row g-5 align-items-center">
                <div class="col-lg-5">
                    <img src="/images/site/about.jpg" class="img-fluid rounded w-100" style="aspect-ratio: 4 / 3; object-fit: cover;" alt="Pijat batu panas di atas meja pijat dengan handuk putih">
                </div>
                <div class="col-lg-7">
                    <x-section-title sub="Tentang Zenova" title="Waktunya Tubuh dan Pikiran Beristirahat" align="start">
                        Zenova Spa menghadirkan pijat dan perawatan tubuh untuk membantu tubuh lebih rileks, pikiran lebih tenang, dan kulit tampak lebih sehat.
                    </x-section-title>
                    <ul class="list-unstyled mb-4">
                        <li class="mb-2"><i class="bi bi-check-circle-fill text-primary me-2"></i>Datang ke spa atau pilih Home Care di lokasi Anda.</li>
                        <li class="mb-2"><i class="bi bi-check-circle-fill text-primary me-2"></i>Pilih terapis sendiri atau biarkan kami pilihkan yang tersedia.</li>
                        <li class="mb-2"><i class="bi bi-check-circle-fill text-primary me-2"></i>Pembayaran dilakukan setelah perawatan selesai.</li>
                    </ul>
                    <a href="{{ route('bookings.create') }}" class="btn btn-primary rounded-pill text-white py-3 px-5">Booking Sekarang</a>
                </div>
            </div>
        </div>
    </section>

    <section id="layanan" class="container-fluid service py-5">
        <div class="container py-5">
            <x-section-title sub="Layanan Kami" title="Pijat & Perawatan Tubuh">Harga untuk {{ $branch?->name ?? 'cabang kami' }}.</x-section-title>
            @forelse ($catalog as [$category, $items])
                <h3 class="h4 mt-5 mb-4 text-center">{{ $category->name }}</h3>
                <div class="row g-4 justify-content-center">
                    @foreach ($items as $treatment)
                        <div class="col-md-6 col-lg-4 col-xl-3"><x-treatment-card :treatment="$treatment" :branch="$branch" /></div>
                    @endforeach
                </div>
            @empty
                <p class="text-center">Belum ada layanan yang tersedia.</p>
            @endforelse
        </div>
    </section>

    @if ($homeCare->isNotEmpty())
        <section id="home-care" class="container-fluid home-care py-5">
            <div class="container py-5">
                <div class="row g-5 align-items-center mb-5">
                    <div class="col-lg-6">
                        <x-section-title sub="Home Care" title="Perawatan Spa di Rumah Anda" align="start" class="mb-4">
                            Nikmati pijat dan perawatan tubuh tanpa perlu keluar rumah. Terapis kami datang langsung ke lokasi Anda.
                        </x-section-title>
                        <div class="d-grid gap-3 mb-4">
                            <div class="d-flex gap-3 align-items-start">
                                <span class="home-care-icon"><i class="bi bi-house-heart"></i></span>
                                <div><p class="fw-semibold text-dark mb-0">Terapis datang ke tempat Anda</p><p class="small mb-0">Cukup isi alamat lengkap saat booking.</p></div>
                            </div>
                            <div class="d-flex gap-3 align-items-start">
                                <span class="home-care-icon"><i class="bi bi-geo-alt"></i></span>
                                <div><p class="fw-semibold text-dark mb-0">Pilih area layanan</p><p class="small mb-0">Biaya transport, bila ada, langsung terlihat sebelum konfirmasi.</p></div>
                            </div>
                            <div class="d-flex gap-3 align-items-start">
                                <span class="home-care-icon"><i class="bi bi-wallet2"></i></span>
                                <div><p class="fw-semibold text-dark mb-0">Bayar setelah perawatan</p><p class="small mb-0">Tidak ada pembayaran di muka.</p></div>
                            </div>
                        </div>
                        <a href="{{ route('bookings.create', ['tipe' => 'home_care']) }}" class="btn btn-primary rounded-pill text-white py-3 px-5">Booking Home Care</a>
                    </div>
                    <div class="col-lg-6">
                        <img src="/images/site/home-care.jpg" class="img-fluid rounded-4 w-100 shadow-sm" style="aspect-ratio: 5 / 4; object-fit: cover;" alt="Botol minyak esensial dengan daun rosemary">
                    </div>
                </div>

                <div class="row g-4 justify-content-center">
                    @foreach ($homeCare as $treatment)
                        <div class="col-sm-6 col-lg-4">
                            <a href="{{ route('treatments.show', ['slug' => $treatment->slug, 'cabang' => $branch->slug]) }}" class="home-care-card d-flex gap-3 h-100 bg-white rounded-4 p-4">
                                <span class="home-care-icon flex-shrink-0"><i class="bi bi-flower1"></i></span>
                                <span class="d-flex flex-column flex-grow-1">
                                    <span class="h5 mb-1">{{ $treatment->name }}</span>
                                    <span class="small mb-3">
                                        @if ($treatment->description){{ $treatment->description }}@elseif ($treatment->items->isNotEmpty()){{ $treatment->items->pluck('name')->join(', ', ' & ') }}@endif
                                    </span>
                                    <span class="mt-auto d-flex justify-content-between align-items-baseline gap-2">
                                        <span class="price"><x-rupiah :amount="$treatment->price" /></span>
                                        @if ($treatment->duration_minutes)<span class="small"><i class="bi bi-clock me-1"></i>{{ $treatment->duration_minutes }} menit</span>@endif
                                    </span>
                                </span>
                            </a>
                        </div>
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    @if ($therapists->isNotEmpty())
        <section class="container-fluid py-5">
            <div class="container py-5">
                <x-section-title sub="Tim Kami" title="Terapis Zenova" />
                <div class="row g-4 justify-content-center">
                    @foreach ($therapists as $therapist)
                        <div class="col-sm-6 col-lg-3">
                            <div class="bg-light rounded p-4 text-center h-100">
                                <span class="d-inline-flex align-items-center justify-content-center rounded-circle bg-dark text-white h4 mb-3" style="width: 64px; height: 64px;" aria-hidden="true">{{ mb_substr($therapist->name, 0, 1) }}</span>
                                <h3 class="h5 mb-1">{{ $therapist->name }}</h3>
                                @if ($therapist->specialization)<p class="mb-0 small">{{ $therapist->specialization }}</p>@endif
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    <section id="lokasi" class="container-fluid bg-light py-5">
        <div class="container py-5">
            <x-section-title sub="Kunjungi Kami" title="Lokasi Cabang" />
            <div class="row g-4 justify-content-center">
                @forelse ($branches as $item)
                    <div class="col-md-6 col-lg-4">
                        <div class="bg-white rounded p-4 h-100 shadow-sm">
                            <h3 class="h5 mb-3">{{ $item->name }}</h3>
                            <p class="mb-2"><i class="bi bi-geo-alt-fill text-primary me-2"></i><span class="visually-hidden">Alamat: </span>{{ $item->address ?? '-' }}</p>
                            <p class="mb-2"><i class="bi bi-clock-fill text-primary me-2"></i><span class="visually-hidden">Jam buka: </span>{{ substr($item->opening_time, 0, 5) }}–{{ substr($item->closing_time, 0, 5) }}</p>
                            <p class="mb-0"><i class="bi bi-telephone-fill text-primary me-2"></i><span class="visually-hidden">Telepon: </span>{{ $item->phone ?? '-' }}</p>
                        </div>
                    </div>
                @empty
                    <p class="text-center">Belum ada cabang aktif.</p>
                @endforelse
            </div>
        </div>
    </section>

    <section class="container-fluid cta-banner py-5">
        <div class="container py-5 text-center text-white">
            <h2 class="display-5 text-white mb-4">Siap untuk relaks?</h2>
            <p class="fs-5 mb-4">Pilih layanan, jadwal, dan terapis dalam beberapa langkah.</p>
            <a href="{{ route('bookings.create') }}" class="btn btn-primary rounded-pill text-white py-3 px-5">Booking Sekarang</a>
        </div>
    </section>
</x-layouts.customer>
