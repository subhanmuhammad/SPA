@props(['grain'])

<nav class="flex gap-1 rounded-lg bg-muted p-1 text-sm" aria-label="Rentang grafik">
    @foreach (['daily' => '30 hari', 'weekly' => '12 minggu', 'monthly' => '12 bulan'] as $value => $label)
        <a href="{{ request()->fullUrlWithQuery(['grain' => $value]) }}" @if ($grain === $value) aria-current="true" @endif
            @class(['rounded-md px-2.5 py-1', 'bg-card font-medium shadow-xs' => $grain === $value, 'text-muted-foreground hover:text-foreground' => $grain !== $value])>{{ $label }}</a>
    @endforeach
</nav>
