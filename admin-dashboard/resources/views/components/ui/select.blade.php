@props(['name', 'options', 'selected' => null])

@php($current = (string) old($name, $selected instanceof \BackedEnum ? $selected->value : $selected))

<select
    name="{{ $name }}"
    id="{{ $attributes->get('id', $name) }}"
    @error($name) aria-invalid="true" aria-describedby="{{ $name }}-error" @enderror
    {{ $attributes->class('h-9 w-full min-w-0 rounded-md border border-input bg-transparent px-2.5 py-1 text-base shadow-xs outline-none focus-visible:border-ring focus-visible:ring-3 focus-visible:ring-ring/50 aria-invalid:border-destructive aria-invalid:ring-3 aria-invalid:ring-destructive/20 md:text-sm') }}
>
    @foreach ($options as $value => $label)
        <option value="{{ $value }}" @selected((string) $value === $current)>{{ $label }}</option>
    @endforeach
</select>
@error($name)
    <p id="{{ $name }}-error" class="text-sm text-destructive">{{ $message }}</p>
@enderror
