@props(['sub', 'title', 'align' => 'center'])

<div {{ $attributes->class(['section-title mb-5', 'text-start' => $align === 'start']) }}>
    @if ($align === 'start')
        <p class="sub-title h5 pe-3 mb-0">{{ $sub }}</p>
    @else
        <div class="sub-style"><p class="sub-title h5 px-3 mb-0">{{ $sub }}</p></div>
    @endif
    <h2 class="display-5 mb-4">{{ $title }}</h2>
    @if ($slot->isNotEmpty())
        <p class="mb-0">{{ $slot }}</p>
    @endif
</div>
