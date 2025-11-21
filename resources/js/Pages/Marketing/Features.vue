<script setup>
import PageTemplate from './PageTemplate.vue';
</script>

<template>
  <PageTemplate
    title="Powerful Features for Modern Dental Practices"
    description="Discover how Victoria Dental Lounge's comprehensive suite of tools can transform your dental practice management."
  >
    <div class="bg-white dark:bg-gray-900 rounded-lg shadow-lg p-6">
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <!-- Feature 1 -->
        <div class="p-6 bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700">
          <div class="w-12 h-12 bg-blue-100 dark:bg-blue-900/30 rounded-lg flex items-center justify-center mb-4">
            <i class="fas fa-calendar-check text-blue-600 dark:text-blue-400 text-xl"></i>
          </div>
          <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">Appointment Management</h3>
          <p class="text-gray-600 dark:text-gray-400">
            Streamline your scheduling with our intuitive calendar and automated reminders.
          </p>
        </div>

        <!-- Feature 2 -->
        <div class="p-6 bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700">
          <div class="w-12 h-12 bg-green-100 dark:bg-green-900/30 rounded-lg flex items-center justify-center mb-4">
            <i class="fas fa-user-md text-green-600 dark:text-green-400 text-xl"></i>
          </div>
          <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">Patient Records</h3>
          <p class="text-gray-600 dark:text-gray-400">
            Secure, cloud-based patient records accessible from anywhere, anytime.
          </p>
        </div>

        <!-- Feature 3 -->
        <div class="p-6 bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700">
          <div class="w-12 h-12 bg-purple-100 dark:bg-purple-900/30 rounded-lg flex items-center justify-center mb-4">
            <i class="fas fa-chart-line text-purple-600 dark:text-purple-400 text-xl"></i>
          </div>
          <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">Analytics Dashboard</h3>
          <p class="text-gray-600 dark:text-gray-400">
            Make data-driven decisions with real-time practice analytics and reports.
          </p>
        </div>
      </div>
    </div>
  </PageTemplate>
</template>
