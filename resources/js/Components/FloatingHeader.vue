<script setup lang="ts">
import { ref, onMounted, onBeforeUnmount } from 'vue';
import { Link } from '@inertiajs/vue3';
import { Button } from '@/Components/ui/button';
import { useThemeStore } from '@/Stores/theme';

const themeStore = useThemeStore();
const isScrolled = ref(false);
const mobileMenuOpen = ref(false);

const handleScroll = () => {
  isScrolled.value = window.scrollY > 10;
};

onMounted(() => {
  window.addEventListener('scroll', handleScroll);
  handleScroll(); // Initialize scroll state
});

onBeforeUnmount(() => {
  window.removeEventListener('scroll', handleScroll);
});

defineExpose({
  mobileMenuOpen
});
</script>

<template>
  <header :class="['fixed top-0 left-0 right-0 z-50 transition-all duration-300', isScrolled ? 'bg-white/80 dark:bg-gray-900/80 backdrop-blur-lg shadow-lg' : 'bg-transparent']">
    <div class="container mx-auto px-4 py-4">
      <div class="flex justify-between items-center">
        <div class="flex items-center space-x-3">
          <!-- Logo/Icon -->
          <Link href="/" class="flex items-center space-x-3">
            <div class="flex items-center justify-center w-10 h-10 bg-[#045c4b]/10 dark:bg-white/5 rounded-lg backdrop-blur-sm">
              <img 
                src="/images/tooth.png" 
                alt="Tooth Logo" 
                class="h-6 w-6" 
                style="filter: invert(40%) sepia(73%) saturate(2000%) hue-rotate(200deg) brightness(90%) contrast(90%);"
              />
            </div>
            <div>
              <span class="text-2xl font-bold text-[#045c4b] dark:text-white">
                  Vintech Solutions
              </span>
              <p class="text-xs text-gray-600 dark:text-slate-300 hidden sm:block">You Smile We Smile</p>
            </div>
          </Link>
        </div>

        <!-- Desktop Navigation -->
        <nav class="hidden md:flex items-center space-x-8">
          <Link href="#features" class="text-gray-700 dark:text-gray-300 hover:text-[#045c4b] dark:hover:text-[#0a8c74] transition-colors">Features</Link>
          <!-- <Link href="#pricing" class="text-gray-700 dark:text-gray-300 hover:text-[#045c4b] dark:hover:text-[#0a8c74] transition-colors">Pricing</Link> -->
          <Link href="#testimonials" class="text-gray-700 dark:text-gray-300 hover:text-[#045c4b] dark:hover:text-[#0a8c74] transition-colors">Testimonials</Link>
        </nav>

        <div class="flex items-center space-x-3">
          <Button variant="ghost" size="icon" @click="themeStore.toggleDarkMode" class="rounded-full">
            <i :class="['fas', themeStore.isDark ? 'fa-sun text-yellow-300' : 'fa-moon text-gray-700']"></i>
          </Button>
          
          <Button 
            variant="ghost" 
            class="hidden md:inline-flex bg-[#045c4b] hover:bg-[#0a8c74] dark:bg-[#045c4b] dark:hover:bg-[#0a8c74] text-white transition-colors duration-200" 
            as-child
          >
            <Link href="/login">Login</Link>
          </Button>
          
          <!-- Signup button (hidden) -->
          <Button v-if="false" class="bg-gradient-to-r from-blue-600 to-cyan-600 hover:from-blue-700 hover:to-cyan-700 text-white shadow-lg hidden md:inline-flex" as-child>
            <Link href="/register" class="flex items-center">
              Get Started
              <i class="fas fa-arrow-right ml-2"></i>
            </Link>
          </Button>

          <Button variant="ghost" size="icon" class="md:hidden text-gray-700 dark:text-gray-300" @click="mobileMenuOpen = !mobileMenuOpen">
            <i :class="['fas', mobileMenuOpen ? 'fa-times' : 'fa-bars']"></i>
          </Button>
        </div>
      </div>

      <!-- Mobile Menu -->
      <div v-if="mobileMenuOpen" class="md:hidden mt-4 py-4 border-t border-gray-200 dark:border-gray-800">
        <nav class="flex flex-col space-y-4">
          <Link href="#features" class="text-gray-700 dark:text-gray-300 hover:text-blue-600">Features</Link>
          <!-- <Link href="#pricing" class="text-gray-700 dark:text-gray-300 hover:text-blue-600">Pricing</Link> -->
          <Link href="#testimonials" class="text-gray-700 dark:text-gray-300 hover:text-blue-600">Testimonials</Link>
          <Button variant="outline" class="w-full" as-child>
            <Link href="/login">Login</Link>
          </Button>
          <!-- Mobile signup button (hidden) -->
          <Button v-if="false" class="w-full bg-gradient-to-r from-blue-600 to-cyan-600" as-child>
            <Link href="/register">Get Started</Link>
          </Button>
        </nav>
      </div>
    </div>
  </header>
</template>
