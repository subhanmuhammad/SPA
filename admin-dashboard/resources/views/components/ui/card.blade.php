@props(['title' => null, 'description' => null])

<div {{ $attributes->class('flex flex-col gap-6 overflow-hidden rounded-xl bg-card py-6 text-sm text-card-foreground shadow-xs ring-1 ring-foreground/10') }}>
    @if ($title)
        <div class="grid gap-1 px-6">
            <div class="text-base leading-normal font-medium">{{ $title }}</div>
            @if ($description)
                <div class="text-muted-foreground">{{ $description }}</div>
            @endif
        </div>
    @endif
    <div class="px-6">{{ $slot }}</div>
</div>
