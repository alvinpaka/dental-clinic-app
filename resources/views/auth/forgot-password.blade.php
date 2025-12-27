<x-guest-layout>
  <div class="min-h-screen transition-colors duration-300 flex dark:bg-gray-950 bg-gradient-to-br from-blue-50 via-white to-cyan-50">
    <!-- Left Side - Branding & Info (mirrors Login page) -->
    <div class="hidden lg:flex lg:w-1/2 bg-gradient-to-br from-blue-600 via-cyan-600 to-teal-600 p-12 flex-col justify-between relative overflow-hidden">
      <!-- Background Pattern -->
      <div class="absolute inset-0 opacity-10">
        <div class="absolute inset-0" style="background-image: repeating-linear-gradient(0deg, transparent, transparent 39px, white 39px, white 40px), repeating-linear-gradient(90deg, transparent, transparent 39px, white 39px, white 40px);"></div>
      </div>

      <div class="relative z-10">
        <!-- Logo -->
        <a href="{{ url('/') }}" class="flex items-center space-x-3 mb-12">
          <div class="flex items-center justify-center w-10 h-10 bg-blue-100 dark:bg-white/5 rounded-lg backdrop-blur-sm">
            <i class="fas fa-tooth text-xl text-blue-600 dark:text-slate-200"></i>
          </div>
          <div>
            <span class="text-2xl font-bold text-white">Vintech Solutions</span>
            <p class="text-xs text-blue-100">You Smile We Smile</p>
          </div>
        </a>

        <!-- Feature Highlights -->
        <div class="space-y-8">
          <div>
            <h2 class="text-4xl font-bold text-white mb-4">Reset Access</h2>
            <p class="text-blue-100 text-lg">We’ll email you a secure link to reset your password.</p>
          </div>

          <div class="space-y-6">
            <div class="flex items-start space-x-4">
              <div class="w-12 h-12 rounded-lg bg-white/20 backdrop-blur-sm flex items-center justify-center flex-shrink-0">
                <i class="fas fa-shield-alt text-white text-xl"></i>
              </div>
              <div>
                <h3 class="text-white font-semibold text-lg mb-1">Secure</h3>
                <p class="text-blue-100">Reset links expire and are single-use.</p>
              </div>
            </div>

            <div class="flex items-start space-x-4">
              <div class="w-12 h-12 rounded-lg bg-white/20 backdrop-blur-sm flex items-center justify-center flex-shrink-0">
                <i class="fas fa-envelope-open-text text-white text-xl"></i>
              </div>
              <div>
                <h3 class="text-white font-semibold text-lg mb-1">Fast</h3>
                <p class="text-blue-100">Receive the reset email in seconds.</p>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Stats -->
      <!-- <div class="relative z-10 grid grid-cols-3 gap-4">
        <div class="text-center p-4 rounded-lg bg-white/10 backdrop-blur-sm">
          <div class="text-3xl font-bold text-white">500+</div>
          <div class="text-blue-100 text-sm">Clinics</div>
        </div>
        <div class="text-center p-4 rounded-lg bg-white/10 backdrop-blur-sm">
          <div class="text-3xl font-bold text-white">50k+</div>
          <div class="text-blue-100 text-sm">Patients</div>
        </div>
        <div class="text-center p-4 rounded-lg bg-white/10 backdrop-blur-sm">
          <div class="text-3xl font-bold text-white">98%</div>
          <div class="text-blue-100 text-sm">Satisfaction</div>
        </div>
      </div> -->
    </div>

    <!-- Right Side - Forgot Password Form -->
    <div class="w-full lg:w-1/2 flex items-center justify-center p-8">
      <div class="w-full max-w-md">
        <!-- Mobile Logo -->
        <div class="lg:hidden mb-8 text-center">
          <a href="{{ url('/') }}" class="inline-flex items-center space-x-3">
            <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-blue-600 to-cyan-600 flex items-center justify-center shadow-lg">
              <i class="fas fa-sparkles text-white text-xl"></i>
            </div>
            <span class="text-3xl font-bold bg-gradient-to-r from-blue-600 to-cyan-600 bg-clip-text text-transparent">Vintech Solutions</span>
          </a>
        </div>

        <div class="border-0 shadow-2xl bg-white/80 dark:bg-gray-900/80 backdrop-blur-sm rounded-xl">
          <div class="space-y-1 pb-6 text-center pt-8">
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white">Forgot Password</h1>
            <p class="text-base text-gray-600 dark:text-gray-400">Enter your email to receive a reset link</p>
          </div>

          <div class="px-6 pb-8">
            <!-- Session Status -->
            <x-auth-session-status class="mb-6" :status="session('status')" />

            <form method="POST" action="{{ route('password.email') }}" class="space-y-6">
              @csrf

              <!-- Email Address -->
              <div class="space-y-2">
                <x-input-label for="email" :value="__('Email')" class="text-gray-700 dark:text-gray-300" />
                <x-text-input id="email" class="block mt-1 w-full h-12 bg-white dark:bg-gray-800 border-gray-300 dark:border-gray-700" type="email" name="email" :value="old('email')" required autofocus />
                <x-input-error :messages="$errors->get('email')" class="mt-2" />
              </div>

              <x-primary-button class="w-full h-12 justify-center">{{ __('Email Password Reset Link') }}</x-primary-button>
            </form>
          </div>
        </div>

        <div class="mt-6 text-center">
          <a href="{{ route('login') }}" class="text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-200 inline-flex items-center text-sm">
            <i class="fas fa-arrow-left mr-2"></i>
            Back to login
          </a>
        </div>
      </div>
    </div>
  </div>
</x-guest-layout>
