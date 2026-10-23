@props([
    'name',
    'label',
    'type' => 'text',
    'value' => null,
    'id' => null,
    'required' => false,
    'autocomplete' => null,
    'rows' => 3,
    'error' => null,
])

@php
    // id normalizado (soporta nombres con notación de arreglo: items[0][x])
    $fieldId = $id ?? 'ff_' . str_replace(['[', ']', '.'], ['_', '', '_'], $name);
    $old = old($name, $value);
    // Permite pasar un error explícito (p. ej. de un error bag nombrado como
    // $errors->updatePassword); si no, usa el bag por defecto.
    $error = $error ?? $errors->first($name);

    $control = 'peer w-full py-3 px-3 rounded-lg text-sm bg-white border transition-colors '
        . 'focus:outline-none focus:ring-2 focus:ring-unmsm-azul focus:border-unmsm-azul '
        . ($error ? 'is-invalid border-red-500' : 'border-gray-300');
@endphp

<div class="floating-field">
    @if($type === 'textarea')
        <textarea id="{{ $fieldId }}" name="{{ $name }}" rows="{{ $rows }}" placeholder=" "
            @required($required)
            @if($error) aria-invalid="true" aria-describedby="{{ $fieldId }}-error" @endif
            {{ $attributes->merge(['class' => $control]) }}>{{ $old }}</textarea>
    @elseif($type === 'select')
        <select id="{{ $fieldId }}" name="{{ $name }}"
            @required($required)
            @if($error) aria-invalid="true" aria-describedby="{{ $fieldId }}-error" @endif
            {{ $attributes->merge(['class' => $control]) }}>
            {{ $slot }}
        </select>
    @else
        <input id="{{ $fieldId }}" type="{{ $type }}" name="{{ $name }}" value="{{ $old }}" placeholder=" "
            @required($required)
            @if($autocomplete) autocomplete="{{ $autocomplete }}" @endif
            @if($error) aria-invalid="true" aria-describedby="{{ $fieldId }}-error" @endif
            {{ $attributes->merge(['class' => $control]) }}>
    @endif

    <label for="{{ $fieldId }}">{{ $label }}@if($required)&nbsp;<span class="text-red-500" aria-hidden="true">*</span>@endif</label>

    @if($error)
        <p id="{{ $fieldId }}-error" class="text-xs text-red-600 mt-1.5 flex items-center gap-1">
            <x-fas-circle-exclamation aria-hidden="true" />{{ $error }}
        </p>
    @endif
</div>
