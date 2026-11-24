@props(['variant' => 'default'])

<span {{ $attributes->class([
    'inline-flex h-5 items-center rounded-4xl px-2 text-xs font-medium whitespace-nowrap',
    'bg-primary text-primary-foreground' => $variant === 'default',
    'bg-secondary text-secondary-foreground' => $variant === 'secondary',
    'border border-border text-foreground' => $variant === 'outline',
]) }}>{{ $slot }}</span>
