<section>
    <header>
        <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">
            {{ __('Update Password') }}
        </h2>

        <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
            {{ __('Ensure your account is using a long, random password to stay secure.') }}
        </p>
    </header>

    <form method="post" action="{{ route('password.update') }}" class="mt-6 space-y-6">
        @csrf
        @method('put')

        <x-floating-input id="update_password_current_password" name="current_password" label="Contraseña actual"
            type="password" autocomplete="current-password" :error="$errors->updatePassword->first('current_password')" />

        <x-floating-input id="update_password_password" name="password" label="Nueva contraseña"
            type="password" autocomplete="new-password" :error="$errors->updatePassword->first('password')" />

        <x-floating-input id="update_password_password_confirmation" name="password_confirmation"
            label="Confirmar contraseña" type="password" autocomplete="new-password"
            :error="$errors->updatePassword->first('password_confirmation')" />

        <div class="flex items-center gap-4">
            <x-primary-button>{{ __('Save') }}</x-primary-button>

            @if (session('status') === 'password-updated')
                <p
                    x-data="{ show: true }"
                    x-show="show"
                    x-transition
                    x-init="setTimeout(() => show = false, 2000)"
                    class="text-sm text-gray-600 dark:text-gray-400"
                >{{ __('Saved.') }}</p>
            @endif
        </div>
    </form>
</section>
