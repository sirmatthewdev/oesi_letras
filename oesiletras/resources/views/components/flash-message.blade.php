@props(['type' => 'success'])

@php
    $message = session($type);
    $styles = [
        'success' => ['bg' => 'bg-green-50', 'border' => 'border-green-200', 'text' => 'text-green-800', 'icon' => 'fa-circle-check', 'iconColor' => 'text-green-600'],
        'error' => ['bg' => 'bg-red-50', 'border' => 'border-red-200', 'text' => 'text-red-800', 'icon' => 'fa-circle-exclamation', 'iconColor' => 'text-red-600'],
    ];
    $s = $styles[$type] ?? $styles['success'];
@endphp

@if($message)
    <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 6000)"
        x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="opacity-0 -translate-y-1"
        x-transition:enter-end="opacity-100 translate-y-0"
        x-transition:leave="transition ease-in duration-200"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        role="status"
        {{ $attributes->merge(['class' => "mb-4 p-3 rounded-lg {$s['bg']} border {$s['border']} {$s['text']} text-sm flex items-start gap-2"]) }}>
        <x-dynamic-component :component="'fas-' . str_replace('fa-', '', $s['icon'])" :class="$s['iconColor'] . ' mt-0.5'" aria-hidden="true" />
        <span class="flex-1">{{ $message }}</span>
        <button type="button" @click="show = false" aria-label="Cerrar mensaje" class="{{ $s['iconColor'] }} hover:opacity-70 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-current rounded">
            <x-fas-xmark aria-hidden="true" />
        </button>
    </div>
@endif
