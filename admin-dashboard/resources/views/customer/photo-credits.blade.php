<x-layouts.customer title="Kredit Foto" header="Kredit Foto">
    <section class="container py-5" style="max-width: 760px;">
        <p class="mb-4">Foto di website ini berasal dari Wikimedia Commons dan dipakai sesuai lisensinya. Ukuran foto diperkecil dan dipotong agar sesuai tampilan.</p>
        <ul class="list-group">
            @foreach (config('site.photos') as $photo)
                <li class="list-group-item py-3">
                    <a href="{{ $photo['source'] }}" rel="noopener" class="fw-semibold">{{ $photo['title'] }}</a>
                    <span class="d-block small">oleh {{ $photo['author'] }} · <a href="{{ $photo['license_url'] }}" rel="noopener license">{{ $photo['license'] }}</a></span>
                </li>
            @endforeach
        </ul>
    </section>
</x-layouts.customer>
