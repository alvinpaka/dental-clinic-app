<script setup lang="ts">
import { Link, useForm } from '@inertiajs/vue3';
import { Button } from '@/Components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/Components/ui/card';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { Checkbox } from '@/Components/ui/checkbox';
import { Alert, AlertDescription } from '@/Components/ui/alert';
import { ref, onMounted } from 'vue';

defineProps<{
  canResetPassword?: boolean;
  status?: string;
}>();

const isDark = ref(false);
const showPassword = ref(false);

const form = useForm({
  email: '',
  password: '',
  remember: false,
});

const submit = () => {
  form.post(route('login'), {
    onFinish: () => {
      form.reset('password');
    },
  });
};

const toggleTheme = () => {
  isDark.value = !isDark.value;
  document.documentElement.classList.toggle('dark');
};

onMounted(() => {
  if (window.matchMedia('(prefers-color-scheme: dark)').matches) {
    isDark.value = true;
    document.documentElement.classList.add('dark');
  }
});
</script>

<template>
  <div :class="['min-h-screen transition-colors duration-300 flex', isDark ? 'dark bg-gray-950' : 'bg-gradient-to-br from-blue-50 via-white to-cyan-50']">
    <!-- Left Side - Branding & Info -->
    <div class="hidden lg:flex lg:w-1/2 bg-gradient-to-br from-blue-600 via-cyan-600 to-teal-600 p-12 flex-col justify-between relative overflow-hidden">
      <!-- Background Pattern -->
      <div class="absolute inset-0 opacity-10">
        <div class="absolute inset-0" style="background-image: repeating-linear-gradient(0deg, transparent, transparent 39px, white 39px, white 40px), repeating-linear-gradient(90deg, transparent, transparent 39px, white 39px, white 40px);"></div>
      </div>

      <div class="relative z-10">
        <!-- Logo -->
        <Link href="/" class="flex items-center space-x-3 mb-12">
          <div class="w-12 h-12 rounded-xl bg-white/20 backdrop-blur-sm flex items-center justify-center shadow-lg">
            <i class="fas fa-sparkles text-white text-xl"></i>
          </div>
          <span class="text-3xl font-bold text-white">
            Vintech Solutions
          </span>
        </Link>

        <!-- Feature Highlights -->
        <div class="space-y-8">
          <div>
            <h2 class="text-4xl font-bold text-white mb-4">
              Welcome Back!
            </h2>
            <p class="text-blue-100 text-lg">
              Access your practice management dashboard and continue delivering exceptional patient care.
            </p>
          </div>

          <div class="space-y-6">
            <div class="flex items-start space-x-4">
              <div class="w-12 h-12 rounded-lg bg-white/20 backdrop-blur-sm flex items-center justify-center flex-shrink-0">
                <i class="fas fa-calendar-check text-white text-xl"></i>
              </div>
              <div>
                <h3 class="text-white font-semibold text-lg mb-1">Smart Scheduling</h3>
                <p class="text-blue-100">Manage appointments with ease and reduce no-shows.</p>
              </div>
            </div>

            <div class="flex items-start space-x-4">
              <div class="w-12 h-12 rounded-lg bg-white/20 backdrop-blur-sm flex items-center justify-center flex-shrink-0">
                <i class="fas fa-chart-line text-white text-xl"></i>
              </div>
              <div>
                <h3 class="text-white font-semibold text-lg mb-1">Real-time Analytics</h3>
                <p class="text-blue-100">Track your practice performance at a glance.</p>
              </div>
            </div>

            <div class="flex items-start space-x-4">
              <div class="w-12 h-12 rounded-lg bg-white/20 backdrop-blur-sm flex items-center justify-center flex-shrink-0">
                <i class="fas fa-shield-alt text-white text-xl"></i>
              </div>
              <div>
                <h3 class="text-white font-semibold text-lg mb-1">Secure & Compliant</h3>
                <p class="text-blue-100">HIPAA-compliant platform protecting patient data.</p>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Stats -->
      <div class="relative z-10 grid grid-cols-3 gap-4">
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
      </div>
    </div>

    <!-- Right Side - Login Form -->
    <div class="w-full lg:w-1/2 flex items-center justify-center p-8">
      <div class="w-full max-w-md">
        <!-- Mobile Logo -->
        <div class="lg:hidden mb-8 text-center">
          <Link href="/" class="inline-flex items-center space-x-3">
            <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-blue-600 to-cyan-600 flex items-center justify-center shadow-lg">
              <i class="fas fa-sparkles text-white text-xl"></i>
            </div>
            <span class="text-3xl font-bold bg-gradient-to-r from-blue-600 to-cyan-600 bg-clip-text text-transparent">
              Vintech Solutions
            </span>
          </Link>
        </div>

        <!-- Theme Toggle -->
        <div class="flex justify-end mb-4">
          <Button variant="ghost" size="icon" @click="toggleTheme" class="rounded-full">
            <i :class="['fas', isDark ? 'fa-sun text-yellow-300' : 'fa-moon']"></i>
          </Button>
        </div>

        <Card class="border-0 shadow-2xl bg-white/80 dark:bg-gray-900/80 backdrop-blur-sm">
          <CardHeader class="space-y-1 pb-6">
            <CardTitle class="text-3xl font-bold text-gray-900 dark:text-white">
              Sign In
            </CardTitle>
            <CardDescription class="text-base text-gray-600 dark:text-gray-400">
              Enter your credentials to access your account
            </CardDescription>
          </CardHeader>

          <CardContent>
            <!-- Status Message -->
            <Alert v-if="status" class="mb-6 bg-green-50 dark:bg-green-900/20 border-green-200 dark:border-green-800">
              <i class="fas fa-check-circle text-green-600 dark:text-green-400 mr-2"></i>
              <AlertDescription class="text-green-800 dark:text-green-300">
                {{ status }}
              </AlertDescription>
            </Alert>

            <form @submit.prevent="submit" class="space-y-6">
              <!-- Email Field -->
              <div class="space-y-2">
                <Label for="email" class="text-gray-700 dark:text-gray-300">
                  Email Address
                </Label>
                <div class="relative">
                  <i class="fas fa-envelope absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"></i>
                  <Input
                    id="email"
                    v-model="form.email"
                    type="email"
                    placeholder="name@example.com"
                    class="pl-10 h-12 bg-white dark:bg-gray-800 border-gray-300 dark:border-gray-700"
                    :class="{ 'border-red-500 dark:border-red-500': form.errors.email }"
                    required
                    autofocus
                    autocomplete="username"
                  />
                </div>
                <p v-if="form.errors.email" class="text-sm text-red-600 dark:text-red-400 flex items-center mt-1">
                  <i class="fas fa-exclamation-circle mr-1"></i>
                  {{ form.errors.email }}
                </p>
              </div>

              <!-- Password Field -->
              <div class="space-y-2">
                <Label for="password" class="text-gray-700 dark:text-gray-300">
                  Password
                </Label>
                <div class="relative">
                  <i class="fas fa-lock absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"></i>
                  <Input
                    id="password"
                    v-model="form.password"
                    :type="showPassword ? 'text' : 'password'"
                    placeholder="Enter your password"
                    class="pl-10 pr-12 h-12 bg-white dark:bg-gray-800 border-gray-300 dark:border-gray-700"
                    :class="{ 'border-red-500 dark:border-red-500': form.errors.password }"
                    required
                    autocomplete="current-password"
                  />
                  <button
                    type="button"
                    @click="showPassword = !showPassword"
                    class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300"
                  >
                    <i :class="['fas', showPassword ? 'fa-eye-slash' : 'fa-eye']"></i>
                  </button>
                </div>
                <p v-if="form.errors.password" class="text-sm text-red-600 dark:text-red-400 flex items-center mt-1">
                  <i class="fas fa-exclamation-circle mr-1"></i>
                  {{ form.errors.password }}
                </p>
              </div>

              <!-- Remember Me & Forgot Password -->
              <div class="flex items-center justify-between">
                <div class="flex items-center space-x-2">
                  <Checkbox
                    id="remember"
                    v-model:checked="form.remember"
                    class="border-gray-300 dark:border-gray-600"
                  />
                  <Label
                    for="remember"
                    class="text-sm text-gray-600 dark:text-gray-400 cursor-pointer"
                  >
                    Remember me
                  </Label>
                </div>

                <Link
                  v-if="canResetPassword"
                  :href="route('password.request')"
                  class="text-sm text-blue-600 hover:text-blue-700 dark:text-cyan-400 dark:hover:text-cyan-300 font-medium"
                >
                  Forgot password?
                </Link>
              </div>

              <!-- Submit Button -->
              <Button
                type="submit"
                class="w-full h-12 bg-gradient-to-r from-blue-600 to-cyan-600 hover:from-blue-700 hover:to-cyan-700 text-white shadow-lg hover:shadow-xl transition-all duration-300 text-base font-semibold"
                :disabled="form.processing"
              >
                <i v-if="form.processing" class="fas fa-spinner fa-spin mr-2"></i>
                <i v-else class="fas fa-sign-in-alt mr-2"></i>
                {{ form.processing ? 'Signing in...' : 'Sign In' }}
              </Button>

              <!-- Divider -->
              <div class="relative my-6">
                <div class="absolute inset-0 flex items-center">
                  <div class="w-full border-t border-gray-300 dark:border-gray-700"></div>
                </div>
                <div class="relative flex justify-center text-sm">
                  <span class="px-4 bg-white dark:bg-gray-900 text-gray-500 dark:text-gray-400">
                    Don't have an account?
                  </span>
                </div>
              </div>

              <!-- Register Link -->
              <div class="text-center">
                <Link
                  :href="route('register')"
                  class="text-blue-600 hover:text-blue-700 dark:text-cyan-400 dark:hover:text-cyan-300 font-medium inline-flex items-center"
                >
                  Create a free account
                  <i class="fas fa-arrow-right ml-2"></i>
                </Link>
              </div>
            </form>
          </CardContent>
        </Card>

        <!-- Back to Home -->
        <div class="mt-6 text-center">
          <Link
            href="/"
            class="text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-200 inline-flex items-center text-sm"
          >
            <i class="fas fa-arrow-left mr-2"></i>
            Back to homepage
          </Link>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.bg-clip-text {
  -webkit-background-clip: text;
  background-clip: text;
  -webkit-text-fill-color: transparent;
}
</style>