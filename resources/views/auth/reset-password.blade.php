<x-guest-layout>
    <div class="min-h-screen flex items-center justify-center bg-gradient-to-br from-blue-50 via-white to-cyan-50 dark:bg-gray-950 py-10 px-4">
        <div class="w-full max-w-md">
            <div class="mb-6 text-center">
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Reset your password</h1>
                <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">Create a new password for your account.</p>
            </div>

            <div class="bg-white/90 dark:bg-gray-900/80 border border-gray-200 dark:border-gray-800 rounded-xl shadow-2xl backdrop-blur-sm p-6">
                <form method="POST" action="{{ route('password.store') }}" class="space-y-4">
                    @csrf

                    <!-- Password Reset Token -->
                    <input type="hidden" name="token" value="{{ $request->route('token') }}">

                    <!-- Email Address -->
                    <div>
                        <x-input-label for="email" :value="__('Email')" class="text-gray-700 dark:text-gray-300" />
                        <x-text-input id="email" class="block mt-1 w-full bg-white dark:bg-gray-800 border-gray-300 dark:border-gray-700" type="email" name="email" :value="old('email', $request->email)" required autocomplete="username" />
                        <x-input-error :messages="$errors->get('email')" class="mt-2" />
                    </div>

                    <!-- Password -->
                    <div>
                        <x-input-label for="password" :value="__('Password')" class="text-gray-700 dark:text-gray-300" />
                        <x-text-input id="password" class="block mt-1 w-full bg-white dark:bg-gray-800 border-gray-300 dark:border-gray-700" type="password" name="password" required autocomplete="new-password" />
                        <x-input-error :messages="$errors->get('password')" class="mt-2" />
                    </div>

                    <!-- Confirm Password -->
                    <div>
                        <x-input-label for="password_confirmation" :value="__('Confirm Password')" class="text-gray-700 dark:text-gray-300" />
                        <x-text-input id="password_confirmation" class="block mt-1 w-full bg-white dark:bg-gray-800 border-gray-300 dark:border-gray-700" type="password" name="password_confirmation" required autocomplete="new-password" />
                        <x-input-error :messages="$errors->get('password_confirmation')" class="mt-2" />
                    </div>

                    <div class="pt-2">
                        <x-primary-button class="w-full justify-center h-11">
                            {{ __('Reset Password') }}
                        </x-primary-button>
                    </div>
                </form>
            </div>

            <div class="mt-6 text-center">
                <a href="{{ route('login') }}" class="text-sm text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-gray-200 inline-flex items-center">
                    <i class="fas fa-arrow-left mr-2"></i>
                    Back to login
                </a>
            </div>
        </div>
    </div>
</x-guest-layout>
