<script setup>
import { Head } from '@inertiajs/vue3';
import { useThemeStore } from '@/Stores/theme';
import { onMounted } from 'vue';
import FloatingHeader from '@/Components/FloatingHeader.vue';
import Footer from '@/Components/Footer.vue';

const themeStore = useThemeStore();

onMounted(() => {
  themeStore.initTheme();
});

defineProps({
  title: {
    type: String,
    required: true,
  },
  description: {
    type: String,
    default: 'DentalPro - Streamlining dental practices with cutting-edge technology',
  },
});
</script>

<template>
  <div :class="['min-h-screen flex flex-col', themeStore.isDark ? 'dark bg-gray-950' : 'bg-gradient-to-br from-blue-50 via-white to-cyan-50']">
    <Head>
      <title>{{ title }}</title>
      <meta name="description" :content="description">
    </Head>

    <!-- Floating Header -->
    <FloatingHeader />

    <!-- Page Content -->
    <main class="flex-grow">
      <slot />
    </main>

    <!-- Footer -->
    <Footer />
  </div>
</template>
