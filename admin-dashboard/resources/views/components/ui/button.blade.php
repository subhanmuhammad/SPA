@props(['variant' => 'default', 'size' => 'default', 'href' => null])

@php
    $classes = 'inline-flex shrink-0 items-center justify-center gap-1.5 rounded-md border border-transparent text-sm font-medium whitespace-nowrap transition-all outline-none select-none focus-visible:border-ring focus-visible:ring-3 focus-visible:ring-ring/50 active:translate-y-px disabled:pointer-events-none disabled:opacity-50 '
        .match ($variant) {
            'outline' => 'border-border bg-background shadow-xs hover:bg-muted hover:text-foreground',
            'secondary' => 'bg-secondary text-secondary-foreground hover:bg-secondary/80',
            'ghost' => 'hover:bg-muted hover:text-foreground',
            'destructive' => 'bg-destructive/10 text-destructive hover:bg-destructive/20',
            default => 'bg-primary text-primary-foreground hover:bg-primary/80',
        }.' '
        .match ($size) {
            'sm' => 'h-8 px-2.5',
            'lg' => 'h-10 px-4',
            'icon' => 'size-9',
            default => 'h-9 px-3',
        };
@endphp

@if ($href)
    <a href="{{ $href }}" {{ $attributes->class($classes) }}>{{ $slot }}</a>
@else
    <button {{ $attributes->merge(['type' => 'submit'])->class($classes) }}>{{ $slot }}</button>
@endif
