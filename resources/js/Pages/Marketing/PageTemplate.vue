<script setup>
import MarketingLayout from '@/Layouts/MarketingLayout.vue';

defineProps({
  title: {
    type: String,
    required: true,
  },
  description: {
    type: String,
    default: 'Vintech Solutions - Streamlining dental practices with cutting-edge technology',
  },
});
</script>

<template>
  <MarketingLayout :title="title" :description="description">
    <div class="py-16 lg:py-20">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-16">
          <h1 class="text-4xl font-bold text-gray-900 dark:text-white sm:text-5xl md:text-6xl">
            {{ title }}
          </h1>
          <p class="mt-6 max-w-2xl mx-auto text-lg text-gray-600 dark:text-gray-400 md:mt-8 md:text-xl">
            {{ description }}
          </p>
        </div>

        <div class="mt-16">
          <slot />
        </div>
      </div>
    </div>
  </MarketingLayout>
</template>
