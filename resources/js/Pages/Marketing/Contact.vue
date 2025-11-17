<script setup>
import PageTemplate from './PageTemplate.vue';
</script>

<template>
  <PageTemplate
    title="Contact Us"
    description="Get in touch with our team. We'd love to hear from you!"
  >
    <div class="bg-white dark:bg-gray-900 rounded-lg shadow-lg p-6">
      <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
        <div>
          <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-4">Get in Touch</h2>
          <p class="text-gray-600 dark:text-gray-400 mb-6">
            Have questions about our products or services? Fill out the form and our team will get back to you as soon as possible.
          </p>
          
          <div class="space-y-4">
            <div class="flex items-start">
              <div class="flex-shrink-0 h-10 w-10 rounded-full bg-blue-100 dark:bg-blue-900/30 flex items-center justify-center">
                <i class="fas fa-envelope text-blue-600 dark:text-blue-400"></i>
              </div>
              <div class="ml-4">
                <h3 class="text-sm font-medium text-gray-900 dark:text-white">Email</h3>
                <p class="text-sm text-gray-600 dark:text-gray-400">alvinpaka@gmail.com</p>
              </div>
            </div>
            
            <div class="flex items-start">
              <div class="flex-shrink-0 h-10 w-10 rounded-full bg-blue-100 dark:bg-blue-900/30 flex items-center justify-center">
                <i class="fas fa-phone-alt text-blue-600 dark:text-blue-400"></i>
              </div>
              <div class="ml-4">
                <h3 class="text-sm font-medium text-gray-900 dark:text-white">Phone</h3>
                <p class="text-sm text-gray-600 dark:text-gray-400">+256 (392) 911-652</p>
              </div>
            </div>
            
            <div class="flex items-start">
              <div class="flex-shrink-0 h-10 w-10 rounded-full bg-blue-100 dark:bg-blue-900/30 flex items-center justify-center">
                <i class="fas fa-map-marker-alt text-blue-600 dark:text-blue-400"></i>
              </div>
              <div class="ml-4">
                <h3 class="text-sm font-medium text-gray-900 dark:text-white">Office</h3>
                <p class="text-sm text-gray-600 dark:text-gray-400">
                  Plot 8<br>
                  Hill Road<br>
                  Entebbe, Uganda
                </p>
              </div>
            </div>
          </div>
        </div>
        
        <div class="bg-gray-50 dark:bg-gray-800 p-6 rounded-lg">
          <form class="space-y-4">
            <div>
              <label for="name" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Name</label>
              <input type="text" id="name" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
            </div>
            
            <div>
              <label for="email" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Email</label>
              <input type="email" id="email" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
            </div>
            
            <div>
              <label for="subject" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Subject</label>
              <input type="text" id="subject" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
            </div>
            
            <div>
              <label for="message" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Message</label>
              <textarea id="message" rows="4" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white"></textarea>
            </div>
            
            <div class="pt-2">
              <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-medium py-2 px-4 rounded-md transition-colors">
                Send Message
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </PageTemplate>
</template>
