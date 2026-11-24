@props(['title', 'description' => null, 'headers', 'empty' => 'Belum ada data pada periode ini.', 'rows'])

<x-ui.card :title="$title" :description="$description" {{ $attributes->class('py-0 pt-6') }}>
    @if (collect($rows)->isEmpty())
        <p class="pb-6 text-muted-foreground">{{ $empty }}</p>
    @else
        <div class="-mx-6 overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="border-b">
                    <tr class="text-left">
                        @foreach ($headers as $i => $header)
                            <th @class(['h-10 font-medium', 'px-6' => $i === 0 || $loop->last, 'px-2' => $i > 0 && ! $loop->last, 'text-right' => $i > 0])>{{ $header }}</th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>{{ $slot }}</tbody>
            </table>
        </div>
    @endif
</x-ui.card>
