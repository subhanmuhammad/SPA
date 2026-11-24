@props(['name', 'label', 'type' => 'text', 'value' => null, 'hint' => null])

<div class="mb-3">
    <label for="{{ $name }}" class="form-label fw-semibold">{{ $label }}</label>
    @if ($type === 'textarea')
        <textarea id="{{ $name }}" name="{{ $name }}" rows="3" @error($name) aria-invalid="true" aria-describedby="{{ $name }}-error" @enderror
            {{ $attributes->class(['form-control', 'is-invalid' => $errors->has($name)]) }}>{{ old($name, $value) }}</textarea>
    @else
        <input id="{{ $name }}" name="{{ $name }}" type="{{ $type }}" value="{{ old($name, $value) }}" @error($name) aria-invalid="true" aria-describedby="{{ $name }}-error" @enderror
            {{ $attributes->class(['form-control py-2', 'is-invalid' => $errors->has($name)]) }}>
    @endif
    @if ($hint)
        <div class="form-text">{{ $hint }}</div>
    @endif
    @error($name)
        <div id="{{ $name }}-error" class="invalid-feedback d-block">{{ $message }}</div>
    @enderror
</div>
