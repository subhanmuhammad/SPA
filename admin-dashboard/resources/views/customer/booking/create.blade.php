@php
    $isHomeCare = $type === \App\Enums\ServiceType::HomeCare;
    $number = 0;
    $legend = function (string $title) use (&$number) {
        $number++;
        return '<span class="step-number">'.$number.'</span>'.e($title);
    };
@endphp

<x-layouts.customer title="Booking" header="Booking Perawatan">
    <section class="container py-5" style="max-width: 860px;">
        <p class="text-center mb-5">Pembayaran dilakukan setelah perawatan selesai.</p>

        <form method="GET" action="{{ route('bookings.create') }}" data-auto-submit>
            @if ($branches->count() > 1)
                <fieldset class="mb-5">
                    <legend class="h4 mb-3">{!! $legend('Pilih cabang') !!}</legend>
                    <div class="row g-2">
                        @foreach ($branches as $option)
                            <div class="col-sm-6"><label class="choice d-flex align-items-center gap-2 h-100"><input class="form-check-input mt-0" type="radio" name="cabang" value="{{ $option->slug }}" @checked($option->is($branch))> {{ $option->name }}</label></div>
                        @endforeach
                    </div>
                </fieldset>
            @elseif ($branch)
                <input type="hidden" name="cabang" value="{{ $branch->slug }}">
            @endif

            @if ($branch)
                <fieldset class="mb-5">
                    <legend class="h4 mb-3">{!! $legend('Jenis layanan') !!}</legend>
                    <div class="row g-2">
                        @foreach (\App\Enums\ServiceType::cases() as $option)
                            <div class="col-sm-6">
                                <label class="choice d-flex align-items-center gap-3 h-100">
                                    <input class="form-check-input mt-0" type="radio" name="tipe" value="{{ $option->value }}" @checked($option === $type)>
                                    <span>
                                        <span class="d-block fw-semibold text-dark">{{ $option->label() }}</span>
                                        <span class="small">{{ $option === \App\Enums\ServiceType::HomeCare ? 'Terapis datang ke lokasi Anda' : 'Datang ke '.$branch->name }}</span>
                                    </span>
                                </label>
                            </div>
                        @endforeach
                    </div>
                </fieldset>
            @endif

            @if ($isHomeCare)
                <fieldset class="mb-5">
                    <legend class="h4 mb-3">{!! $legend('Area lokasi Anda') !!}</legend>
                    @if ($areas->isEmpty())
                        <p>Home Care belum tersedia untuk cabang ini.</p>
                    @endif
                    <div class="row g-2">
                        @foreach ($areas as $option)
                            <div class="col-sm-6">
                                <label class="choice d-flex align-items-center justify-content-between gap-3 h-100">
                                    <span class="d-flex align-items-center gap-2"><input class="form-check-input mt-0" type="radio" name="area" value="{{ $option->id }}" @checked($option->is($area))> <span class="text-dark">{{ $option->name }}</span></span>
                                    <span class="small text-end">@if ($option->travel_fee) Transport <x-rupiah :amount="$option->travel_fee" /> @else Tanpa biaya transport @endif</span>
                                </label>
                            </div>
                        @endforeach
                    </div>
                </fieldset>
            @endif

            @if ($type && (! $isHomeCare || $area))
                <fieldset class="mb-5">
                    <legend class="h4 mb-3">{!! $legend('Pilih layanan atau paket') !!}</legend>
                    @if ($treatments->isEmpty())
                        <p>Belum ada layanan {{ $type->label() }} yang bisa dibooking online di cabang ini.</p>
                    @endif
                    @foreach ($treatments->groupBy('category.name') as $category => $items)
                        <p class="fw-semibold text-primary mt-4 mb-2">{{ $category }}</p>
                        <div class="d-grid gap-2">
                            @foreach ($items as $option)
                                <label class="choice d-flex align-items-center justify-content-between gap-3">
                                    <span class="d-flex align-items-center gap-2"><input class="form-check-input mt-0" type="radio" name="layanan" value="{{ $option->slug }}" @checked($option->is($treatment))> <span class="text-dark">{{ $option->name }}</span></span>
                                    <span class="text-end text-nowrap">
                                        <span class="fw-semibold text-primary"><x-rupiah :amount="$option->price" /></span>
                                        <span class="d-block small">{{ $option->duration_minutes }} menit</span>
                                    </span>
                                </label>
                            @endforeach
                        </div>
                    @endforeach
                </fieldset>
            @endif

            @if ($treatment)
                <fieldset class="mb-5">
                    <legend class="h4 mb-3">{!! $legend('Pilih tanggal') !!}</legend>
                    <select name="tanggal" aria-label="Tanggal" class="form-select py-2" style="max-width: 360px;">
                        <option value="">Pilih tanggal</option>
                        @foreach ($dates as $option)
                            <option value="{{ $option->toDateString() }}" @selected($date && $option->equalTo($date))>{{ $option->translatedFormat('l, j F Y') }}</option>
                        @endforeach
                    </select>
                </fieldset>
            @endif

            @if ($date)
                <fieldset class="mb-5">
                    <legend class="h4 mb-3">{!! $legend('Pilih jam') !!}</legend>
                    @if ($times === [])
                        <p>Tidak ada jadwal kosong di tanggal ini. Silakan pilih tanggal lain.</p>
                    @endif
                    <div class="row g-2">
                        @foreach ($times as $option)
                            <div class="col-4 col-sm-3 col-md-2">
                                <input type="radio" class="btn-check" name="jam" id="jam-{{ $option }}" value="{{ $option }}" autocomplete="off" @checked($option === $time)>
                                <label class="btn btn-outline-primary w-100" for="jam-{{ $option }}">{{ $option }}</label>
                            </div>
                        @endforeach
                    </div>
                </fieldset>
            @endif

            <noscript><button class="btn btn-primary text-white rounded-pill px-5 py-3">Lanjut</button></noscript>
        </form>

        @if ($time)
            <form method="POST" action="{{ route('bookings.store') }}">
                @csrf
                @foreach (array_filter(['cabang' => $branch->slug, 'tipe' => $type->value, 'area' => $area?->id, 'layanan' => $treatment->slug, 'tanggal' => $date->toDateString(), 'jam' => $time]) as $key => $value)
                    <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                @endforeach

                <fieldset class="mb-5">
                    <legend class="h4 mb-3">{!! $legend('Pilih terapis (opsional)') !!}</legend>
                    <div class="row g-2">
                        <div class="col-sm-6"><label class="choice d-flex align-items-center gap-2 h-100"><input class="form-check-input mt-0" type="radio" name="terapis" value="" @checked(! old('terapis'))> Siapa saja yang tersedia</label></div>
                        @foreach ($therapists as $option)
                            <div class="col-sm-6"><label class="choice d-flex align-items-center gap-2 h-100"><input class="form-check-input mt-0" type="radio" name="terapis" value="{{ $option->id }}" @checked((string) old('terapis') === (string) $option->id)> {{ $option->name }}</label></div>
                        @endforeach
                    </div>
                </fieldset>

                <fieldset class="mb-4">
                    <legend class="h4 mb-3">{!! $legend('Data Anda') !!}</legend>
                    <x-zen.field name="name" label="Nama lengkap" required maxlength="100" autocomplete="name" />
                    <x-zen.field name="phone" label="Nomor HP (WhatsApp)" type="tel" required maxlength="16" autocomplete="tel" placeholder="081234567890" />
                    <x-zen.field name="email" label="Email (opsional)" type="email" maxlength="255" autocomplete="email" />
                    <div class="row g-3">
                        <div class="col-sm-6 mb-3">
                            <label for="gender" class="form-label fw-semibold">Jenis kelamin (opsional)</label>
                            <select id="gender" name="gender" class="form-select py-2">
                                <option value="">-</option>
                                <option value="female" @selected(old('gender') === 'female')>Perempuan</option>
                                <option value="male" @selected(old('gender') === 'male')>Laki-laki</option>
                            </select>
                        </div>
                        <div class="col-sm-6"><x-zen.field name="birth_date" label="Tanggal lahir (opsional)" type="date" /></div>
                    </div>
                    @if ($isHomeCare)
                        <x-zen.field name="address" label="Alamat lengkap untuk Home Care" type="textarea" required maxlength="500" autocomplete="street-address" />
                        <x-zen.field name="address_notes" label="Patokan lokasi (opsional)" maxlength="255" />
                        <x-zen.field name="location_url" label="Link Google Maps (opsional)" type="url" maxlength="500" placeholder="https://maps.app.goo.gl/..." hint="Membantu terapis menemukan lokasi Anda." />
                    @endif
                    <x-zen.field name="notes" label="Catatan khusus (opsional)" type="textarea" maxlength="500" />
                </fieldset>

                <div class="bg-light rounded p-4 mb-4">
                    <h2 class="h5 mb-3">Ringkasan</h2>
                    <dl class="row mb-0">
                        <dt class="col-sm-4 fw-normal">Cabang</dt><dd class="col-sm-8 text-dark">{{ $branch->name }}</dd>
                        <dt class="col-sm-4 fw-normal">Layanan</dt><dd class="col-sm-8 text-dark">{{ $treatment->name }} ({{ $type->label() }})</dd>
                        <dt class="col-sm-4 fw-normal">Jadwal</dt><dd class="col-sm-8 text-dark">{{ $date->translatedFormat('l, j F Y') }} · {{ $time }} ({{ $treatment->duration_minutes }} menit)</dd>
                        @if ($area)
                            <dt class="col-sm-4 fw-normal">Area</dt><dd class="col-sm-8 text-dark">{{ $area->name }}</dd>
                            <dt class="col-sm-4 fw-normal">Harga layanan</dt><dd class="col-sm-8 text-dark"><x-rupiah :amount="$treatment->price" /></dd>
                            <dt class="col-sm-4 fw-normal">Transport</dt><dd class="col-sm-8 text-dark"><x-rupiah :amount="$area->travel_fee" /></dd>
                        @endif
                        <dt class="col-sm-4 fw-normal">Total</dt><dd class="col-sm-8 fw-bold text-primary mb-0"><x-rupiah :amount="$treatment->price + ($area->travel_fee ?? 0)" /></dd>
                    </dl>
                    @error('time') <p class="text-danger mt-3 mb-0" role="alert">{{ $message }}</p> @enderror
                    @if ($errors->hasAny(['cabang', 'tipe', 'area', 'layanan', 'tanggal', 'jam', 'terapis']))
                        <p class="text-danger mt-3 mb-0" role="alert">Pilihan jadwal tidak valid. Silakan pilih ulang.</p>
                    @endif
                </div>

                <button class="btn btn-primary rounded-pill text-white w-100 py-3">Konfirmasi Booking</button>
            </form>
        @endif
    </section>
</x-layouts.customer>
