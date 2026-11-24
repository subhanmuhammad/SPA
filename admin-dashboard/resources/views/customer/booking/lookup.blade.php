<x-layouts.customer title="Cek Booking" header="Cek Booking">
    <section class="container py-5" style="max-width: 520px;">
        <p class="text-center mb-4">Masukkan kode booking dan nomor HP yang dipakai saat booking.</p>
        <form method="POST" action="{{ route('bookings.lookup.submit') }}" class="bg-light rounded p-4">
            @csrf
            <x-zen.field name="code" label="Kode booking" required maxlength="12" autocapitalize="characters" placeholder="ZNXXXXXX" />
            <x-zen.field name="phone" label="Nomor HP" type="tel" required maxlength="20" autocomplete="tel" />
            <button class="btn btn-primary rounded-pill text-white w-100 py-3">Cek status</button>
        </form>
    </section>
</x-layouts.customer>
