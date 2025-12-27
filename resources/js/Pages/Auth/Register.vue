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
  status?: string;
}>();

const isDark = ref(false);
const showPassword = ref(false);
const showConfirmPassword = ref(false);

const form = useForm({
  name: '',
  email: '',
  password: '',
  password_confirmation: '',
  terms: false,
});

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

const submit = () => {
  form.post(route('register'), {
    onFinish: () => {
      if (!form.hasErrors) {
        form.reset('password', 'password_confirmation');
      }
    },
  });
};
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
          <!-- Logo/Icon -->
          <div class="flex items-center justify-center w-10 h-10 bg-blue-100 dark:bg-white/5 rounded-lg backdrop-blur-sm">
            <i class="fas fa-tooth text-xl text-blue-600 dark:text-slate-200"></i>
          </div>
          <div>
            <span class="text-2xl font-bold text-white">
              Vintech Solutions
            </span>
            <p class="text-xs text-blue-100">You Smile We Smile</p>
          </div>
        </Link>

        <!-- Feature Highlights -->
        <div class="space-y-8">
          <div>
            <h2 class="text-4xl font-bold text-white mb-4">
              Join Vintech Solutions Today
            </h2>
            <p class="text-blue-100 text-lg">
              Start your free trial and transform your dental practice with our comprehensive management platform.
            </p>
          </div>

          <div class="space-y-6">
            <div class="flex items-start space-x-4">
              <div class="w-12 h-12 rounded-lg bg-white/20 backdrop-blur-sm flex items-center justify-center flex-shrink-0">
                <i class="fas fa-rocket text-white text-xl"></i>
              </div>
              <div>
                <h3 class="text-white font-semibold text-lg mb-1">Quick Setup</h3>
                <p class="text-blue-100">Get started in minutes with our easy onboarding process.</p>
              </div>
            </div>

            <div class="flex items-start space-x-4">
              <div class="w-12 h-12 rounded-lg bg-white/20 backdrop-blur-sm flex items-center justify-center flex-shrink-0">
                <i class="fas fa-users text-white text-xl"></i>
              </div>
              <div>
                <h3 class="text-white font-semibold text-lg mb-1">Team Collaboration</h3>
                <p class="text-blue-100">Work seamlessly with your entire dental team.</p>
              </div>
            </div>

            <div class="flex items-start space-x-4">
              <div class="w-12 h-12 rounded-lg bg-white/20 backdrop-blur-sm flex items-center justify-center flex-shrink-0">
                <i class="fas fa-headset text-white text-xl"></i>
              </div>
              <div>
                <h3 class="text-white font-semibold text-lg mb-1">24/7 Support</h3>
                <p class="text-blue-100">Get help whenever you need it with our dedicated support team.</p>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Stats -->
      <div class="relative z-10 grid grid-cols-3 gap-4">
        <div class="text-center p-4 rounded-lg bg-white/10 backdrop-blur-sm">
          <div class="text-3xl font-bold text-white">Free</div>
          <div class="text-blue-100 text-sm">30-Day Trial</div>
        </div>
        <div class="text-center p-4 rounded-lg bg-white/10 backdrop-blur-sm">
          <div class="text-3xl font-bold text-white">No</div>
          <div class="text-blue-100 text-sm">Setup Fees</div>
        </div>
        <div class="text-center p-4 rounded-lg bg-white/10 backdrop-blur-sm">
          <div class="text-3xl font-bold text-white">Cancel</div>
          <div class="text-blue-100 text-sm">Anytime</div>
        </div>
      </div>
    </div>

    <!-- Right Side - Register Form -->
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
              Create Account
            </CardTitle>
            <CardDescription class="text-base text-gray-600 dark:text-gray-400">
              Start your free trial and join thousands of dental professionals
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

            <!-- Validation Error Alert -->
            <Alert v-if="form.hasErrors" class="mb-6 bg-red-50 dark:bg-red-900/20 border-red-200 dark:border-red-800">
              <i class="fas fa-exclamation-triangle text-red-600 dark:text-red-400 mr-2"></i>
              <AlertDescription class="text-red-800 dark:text-red-300">
                <strong>Registration failed.</strong> Please check the errors below and try again.
              </AlertDescription>
            </Alert>

            <form @submit.prevent="submit" class="space-y-6">
              <!-- Name Field -->
              <div class="space-y-2">
                <Label for="name" class="text-gray-700 dark:text-gray-300">
                  Full Name
                </Label>
                <div class="relative">
                  <i class="fas fa-user absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"></i>
                  <Input
                    id="name"
                    v-model="form.name"
                    type="text"
                    placeholder="Dr. John Smith"
                    class="pl-10 h-12 bg-white dark:bg-gray-800 border-gray-300 dark:border-gray-700"
                    :class="{ 'border-red-500 dark:border-red-500': form.errors.name }"
                    required
                    autofocus
                    autocomplete="name"
                  />
                </div>
                <p v-if="form.errors.name" class="text-sm text-red-600 dark:text-red-400 flex items-center mt-1">
                  <i class="fas fa-exclamation-circle mr-1"></i>
                  {{ form.errors.name }}
                </p>
              </div>

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
                    placeholder="Create a strong password"
                    class="pl-10 pr-12 h-12 bg-white dark:bg-gray-800 border-gray-300 dark:border-gray-700"
                    :class="{ 'border-red-500 dark:border-red-500': form.errors.password }"
                    required
                    autocomplete="new-password"
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

              <!-- Confirm Password Field -->
              <div class="space-y-2">
                <Label for="password_confirmation" class="text-gray-700 dark:text-gray-300">
                  Confirm Password
                </Label>
                <div class="relative">
                  <i class="fas fa-lock absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"></i>
                  <Input
                    id="password_confirmation"
                    v-model="form.password_confirmation"
                    :type="showConfirmPassword ? 'text' : 'password'"
                    placeholder="Confirm your password"
                    class="pl-10 pr-12 h-12 bg-white dark:bg-gray-800 border-gray-300 dark:border-gray-700"
                    :class="{ 'border-red-500 dark:border-red-500': form.errors.password_confirmation }"
                    required
                    autocomplete="new-password"
                  />
                  <button
                    type="button"
                    @click="showConfirmPassword = !showConfirmPassword"
                    class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300"
                  >
                    <i :class="['fas', showConfirmPassword ? 'fa-eye-slash' : 'fa-eye']"></i>
                  </button>
                </div>
                <p v-if="form.errors.password_confirmation" class="text-sm text-red-600 dark:text-red-400 flex items-center mt-1">
                  <i class="fas fa-exclamation-circle mr-1"></i>
                  {{ form.errors.password_confirmation }}
                </p>
              </div>

              <!-- Terms & Conditions -->
              <div class="space-y-2">
                <div class="flex items-start space-x-2">
                  <input
                    type="checkbox"
                    id="terms"
                    v-model="form.terms"
                    class="mt-1 h-4 w-4 rounded border-gray-300 dark:border-gray-600 text-blue-600 focus:ring-blue-500 dark:bg-gray-800 dark:focus:ring-blue-600"
                    :class="{ 'border-red-500 dark:border-red-500': form.errors.terms }"
                  />
                  <label
                    for="terms"
                    class="text-sm text-gray-600 dark:text-gray-400 cursor-pointer leading-relaxed"
                  >
                    I agree to the <Link href="/terms" class="text-blue-600 hover:text-blue-700 dark:text-cyan-400 dark:hover:text-cyan-300 underline">Terms of Service</Link>
                    and <Link href="/privacy" class="text-blue-600 hover:text-blue-700 dark:text-cyan-400 dark:hover:text-cyan-300 underline">Privacy Policy</Link>
                  </label>
                </div>
                <p v-if="form.errors.terms" class="text-sm text-red-600 dark:text-red-400 flex items-center">
                  <i class="fas fa-exclamation-circle mr-1"></i>
                  {{ form.errors.terms }}
                </p>
              </div>

              <!-- Submit Button -->
              <Button
                type="submit"
                class="w-full h-12 bg-gradient-to-r from-blue-600 to-cyan-600 hover:from-blue-700 hover:to-cyan-700 text-white shadow-lg hover:shadow-xl transition-all duration-300 text-base font-semibold"
                :disabled="form.processing"
              >
                <i v-if="form.processing" class="fas fa-spinner fa-spin mr-2"></i>
                <i v-else class="fas fa-user-plus mr-2"></i>
                {{ form.processing ? 'Creating account...' : 'Create Account' }}
              </Button>

              <!-- Divider -->
              <div class="relative my-6">
                <div class="absolute inset-0 flex items-center">
                  <div class="w-full border-t border-gray-300 dark:border-gray-700"></div>
                </div>
                <div class="relative flex justify-center text-sm">
                  <span class="px-4 bg-white dark:bg-gray-900 text-gray-500 dark:text-gray-400">
                    Already have an account?
                  </span>
                </div>
              </div>

              <!-- Login Link -->
              <div class="text-center">
                <Link
                  :href="route('login')"
                  class="text-blue-600 hover:text-blue-700 dark:text-cyan-400 dark:hover:text-cyan-300 font-medium inline-flex items-center"
                >
                  Sign in instead
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
