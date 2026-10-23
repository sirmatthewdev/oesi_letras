@props([
    'icon' => 'fa-inbox',
    'title' => 'Sin contenido',
    'description' => null,
])

<div {{ $attributes->merge(['class' => 'text-center py-16 px-6']) }}>
    <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-gray-100 mb-4">
        <x-dynamic-component :component="'fas-' . str_replace('fa-', '', $icon)" class="text-2xl text-gray-400" aria-hidden="true" />
    </div>
    <h3 class="text-lg font-bold text-gray-800 mb-1">{{ $title }}</h3>
    @if($description)
        <p class="text-sm text-gray-500 max-w-sm mx-auto">{{ $description }}</p>
    @endif
    {{ $slot ?? '' }}
</div>
