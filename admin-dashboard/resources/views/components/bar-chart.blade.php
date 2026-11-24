@props(['id', 'title', 'data', 'unit' => 'rupiah'])

<figure {{ $attributes->class('rounded-xl bg-card p-5 ring-1 ring-foreground/10') }}>
    <figcaption class="mb-4 flex flex-wrap items-center justify-between gap-2">
        <span class="font-medium">{{ $title }}</span>
        {{ $actions ?? '' }}
    </figcaption>
    <div class="h-64">
        <canvas data-chart="{{ $id }}-data" role="img" aria-label="{{ $title }}. Rincian tersedia di tabel di bawah."></canvas>
    </div>
    <script type="application/json" id="{{ $id }}-data">@json(['labels' => array_keys($data), 'values' => array_values($data), 'unit' => $unit])</script>
    <details class="mt-3 text-sm">
        <summary class="cursor-pointer text-muted-foreground">Lihat sebagai tabel</summary>
        <table class="mt-2 w-full">
            <tbody>
                @foreach ($data as $label => $value)
                    <tr class="border-b last:border-0">
                        <th scope="row" class="py-1 text-left font-normal">{{ $label }}</th>
                        <td class="py-1 text-right tabular-nums">@if ($unit === 'rupiah')<x-rupiah :amount="$value" />@else{{ $value }}@endif</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </details>
    @vite('resources/js/charts.js')
</figure>
