<x-guest-layout>
    <form method="POST" action="{{ route('password.store') }}">
        @csrf

        <!-- Password Reset Token -->
        <input type="hidden" name="token" value="{{ $request->route('token') }}">

        <!-- Email Address -->
        <x-floating-input id="email" name="email" label="Correo electrónico" type="email"
            :value="old('email', $request->email)" :required="true" autofocus autocomplete="username" />

        <!-- Password -->
        <div class="mt-4">
            <x-floating-input id="password" name="password" label="Contraseña" type="password"
                :required="true" autocomplete="new-password" />
        </div>

        <!-- Confirm Password -->
        <div class="mt-4">
            <x-floating-input id="password_confirmation" name="password_confirmation" label="Confirmar contraseña"
                type="password" :required="true" autocomplete="new-password" />
        </div>

        <div class="flex items-center justify-end mt-4">
            <x-primary-button>
                {{ __('Reset Password') }}
            </x-primary-button>
        </div>
    </form>
</x-guest-layout>
