{{-- Contenedor global de toasts. Cualquier script de la página puede llamar
     a window.showToast('Mensaje', 'success' | 'error') para mostrar una
     confirmación flotante que se auto-descarta. Sin librería — Alpine +
     un CustomEvent nativo (ver initApp en resources/js/app.js). --}}
<div x-data="{
        toasts: [],
        add(message, type) {
            const id = Date.now() + Math.random();
            this.toasts.push({ id, message, type });
            setTimeout(() => this.remove(id), 4000);
        },
        remove(id) {
            this.toasts = this.toasts.filter(t => t.id !== id);
        },
     }"
     @toast:show.window="add($event.detail.message, $event.detail.type)"
     class="fixed bottom-6 right-6 z-[100] flex flex-col gap-2 pointer-events-none"
     aria-live="polite" role="status">
    <template x-for="toast in toasts" :key="toast.id">
        <div x-show="true" x-transition:enter="transition ease-out duration-300"
            x-transition:enter-start="opacity-0 translate-y-2 scale-95"
            x-transition:enter-end="opacity-100 translate-y-0 scale-100"
            x-transition:leave="transition ease-in duration-200"
            x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
            class="pointer-events-auto flex items-center gap-3 min-w-[260px] max-w-sm px-4 py-3 rounded-lg shadow-xl text-sm font-medium"
            :class="toast.type === 'error' ? 'bg-red-700 text-white' : 'bg-gray-900 text-white'">
            <span x-show="toast.type === 'error'"><x-fas-circle-exclamation class="text-red-200" aria-hidden="true" /></span>
            <span x-show="toast.type !== 'error'"><x-fas-circle-check class="text-green-400" aria-hidden="true" /></span>
            <span class="flex-1" x-text="toast.message"></span>
            <button type="button" @click="remove(toast.id)" aria-label="Cerrar notificación"
                class="text-white/60 hover:text-white focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-white rounded">
                <x-fas-xmark aria-hidden="true" />
            </button>
        </div>
    </template>
</div>
