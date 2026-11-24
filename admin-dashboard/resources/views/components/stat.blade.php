@props(['label', 'hint' => null])

<div {{ $attributes->class('rounded-xl bg-card p-5 ring-1 ring-foreground/10') }}>
    <p class="text-sm text-muted-foreground">{{ $label }}</p>
    <p class="mt-1 text-2xl font-semibold tabular-nums">{{ $slot }}</p>
    @if ($hint)
        <p class="mt-1 text-xs text-muted-foreground">{{ $hint }}</p>
    @endif
</div>
