@props(['title' => null, 'header' => null, 'breadcrumb' => []])

@php
    $main = $siteBranches->first();
    $links = [
        ['label' => 'Beranda', 'href' => route('home'), 'active' => request()->routeIs('home')],
        ['label' => 'Layanan', 'href' => route('home').'#layanan', 'active' => request()->routeIs('treatments.show')],
        ['label' => 'Paket Promo', 'href' => route('home').'#promo', 'active' => false],
        ['label' => 'Home Care', 'href' => route('home').'#home-care', 'active' => false],
        ['label' => 'Cek Booking', 'href' => route('bookings.lookup'), 'active' => request()->routeIs('bookings.lookup', 'bookings.show')],
    ];
@endphp

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="description" content="Zenova Spa — pijat dan perawatan tubuh di spa atau Home Care. Relaks • Segar • Percaya Diri.">
    <link rel="icon" href="/images/logo.jpg" type="image/jpeg">
    <title>{{ $title ? "{$title} · " : '' }}Zenova Spa</title>
    @fonts
    @vite(['resources/css/customer.scss', 'resources/js/customer.js'])
</head>
<body>
    <a href="#konten" class="visually-hidden-focusable position-absolute top-0 start-0 m-2 btn btn-light">Langsung ke konten</a>

    <div class="container-fluid bg-dark px-5 d-none d-lg-block">
        <div class="row gx-0 align-items-center" style="height: 45px;">
            <div class="col-lg-9 d-flex flex-wrap small">
                @if ($main)
                    <span class="text-light me-4"><i class="bi bi-geo-alt-fill text-secondary me-2"></i>{{ $main->address ?? $main->name }}</span>
                    @if ($main->phone)
                        <span class="text-light me-4"><i class="bi bi-telephone-fill text-secondary me-2"></i>{{ $main->phone }}</span>
                    @endif
                    <span class="text-light"><i class="bi bi-clock-fill text-secondary me-2"></i>{{ substr($main->opening_time, 0, 5) }}–{{ substr($main->closing_time, 0, 5) }}</span>
                @endif
            </div>
            <div class="col-lg-3 text-end small text-secondary">Modern Massage &amp; Wellness</div>
        </div>
    </div>

    <div class="container-fluid position-relative p-0">
        <nav class="navbar navbar-expand-lg navbar-light bg-white px-4 px-lg-5 py-3 py-lg-0" aria-label="Menu utama">
            <a href="{{ route('home') }}" class="navbar-brand p-0 d-flex align-items-center gap-3">
                <img src="/images/logo.jpg" alt="">
                <span class="h3 text-dark m-0">Zenova Spa</span>
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarCollapse" aria-controls="navbarCollapse" aria-expanded="false" aria-label="Buka menu">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarCollapse">
                <div class="navbar-nav ms-auto py-0">
                    @foreach ($links as $link)
                        <a href="{{ $link['href'] }}" @class(['nav-item nav-link', 'active' => $link['active']]) @if ($link['active']) aria-current="page" @endif>{{ $link['label'] }}</a>
                    @endforeach
                </div>
                <a href="{{ route('bookings.create') }}" class="btn btn-primary rounded-pill text-white py-2 px-4 flex-wrap flex-sm-shrink-0">Booking Sekarang</a>
            </div>
        </nav>

        @if ($header)
            <div class="container-fluid bg-breadcrumb">
                <div class="container text-center py-5" style="max-width: 900px;">
                    <h1 class="text-white display-4 mb-4">{{ $header }}</h1>
                    <nav aria-label="Breadcrumb">
                        <ol class="breadcrumb justify-content-center mb-0">
                            <li class="breadcrumb-item"><a href="{{ route('home') }}">Beranda</a></li>
                            @foreach ($breadcrumb as $label => $href)
                                <li class="breadcrumb-item"><a href="{{ $href }}">{{ $label }}</a></li>
                            @endforeach
                            <li class="breadcrumb-item active text-secondary" aria-current="page">{{ $header }}</li>
                        </ol>
                    </nav>
                </div>
            </div>
        @endif
    </div>

    <main id="konten">
        {{ $slot }}
    </main>

    <footer class="container-fluid footer py-5">
        <div class="container py-5">
            <div class="row g-5">
                <div class="col-md-6 col-xl-4">
                    <div class="footer-item d-flex flex-column">
                        <h2 class="h4 text-white mb-4">Zenova Spa</h2>
                        <p>Pijat dan perawatan tubuh di spa kami atau langsung di rumah Anda dengan layanan Home Care. Pembayaran dilakukan setelah perawatan selesai.</p>
                        <p class="text-secondary fst-italic mb-0">Relaks • Segar • Percaya Diri</p>
                    </div>
                </div>
                <div class="col-md-6 col-xl-3">
                    <div class="footer-item d-flex flex-column">
                        <h2 class="h4 text-white mb-4">Tautan</h2>
                        @foreach ($links as $link)
                            <a href="{{ $link['href'] }}"><i class="bi bi-chevron-right me-2"></i>{{ $link['label'] }}</a>
                        @endforeach
                        <a href="{{ route('bookings.create') }}"><i class="bi bi-chevron-right me-2"></i>Booking</a>
                    </div>
                </div>
                <div class="col-md-12 col-xl-5">
                    <div class="footer-item d-flex flex-column">
                        <h2 class="h4 text-white mb-4">Lokasi</h2>
                        @forelse ($siteBranches as $branch)
                            <p class="mb-1 text-white">{{ $branch->name }}</p>
                            <p class="mb-1"><i class="bi bi-geo-alt me-2"></i>{{ $branch->address ?? '-' }}</p>
                            @if ($branch->phone)<p class="mb-1"><i class="bi bi-telephone me-2"></i>{{ $branch->phone }}</p>@endif
                            <p class="mb-3"><i class="bi bi-clock me-2"></i>Setiap hari {{ substr($branch->opening_time, 0, 5) }}–{{ substr($branch->closing_time, 0, 5) }}</p>
                        @empty
                            <p>Belum ada cabang aktif.</p>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </footer>

    <div class="container-fluid copyright py-4">
        <div class="container">
            <div class="row g-3 align-items-center">
                <div class="col-12 text-center text-white">&copy; {{ now()->year }} Zenova Spa. Hak cipta dilindungi.</div>
            </div>
        </div>
    </div>

    <a href="#" class="btn btn-primary btn-lg-square rounded-circle back-to-top text-white d-none align-items-center justify-content-center" aria-label="Kembali ke atas"><i class="bi bi-arrow-up"></i></a>
</body>
</html>
