<script setup>
import PageTemplate from './PageTemplate.vue';
import { ShieldCheckIcon, DocumentTextIcon, LockClosedIcon, UserCircleIcon, Cog8ToothIcon, EnvelopeIcon } from '@heroicons/vue/24/outline';

const privacySections = [
  {
    icon: DocumentTextIcon,
    title: 'Information We Collect',
    content: 'When you visit our website, we collect certain information about your device, including information about your web browser, IP address, time zone, and cookies. We also collect information about the individual web pages or products that you view, what websites or search terms referred you to the Site, and information about how you interact with the Site.'
  },
  {
    icon: LockClosedIcon,
    title: 'How We Use Your Information',
    content: 'We use the information we collect for various purposes, including to:',
    items: [
      'Provide, operate, and maintain our website',
      'Improve, personalize, and expand our website',
      'Understand and analyze how you use our website',
      'Develop new products, services, features, and functionality',
      'Communicate with you for customer service and support',
      'Send you marketing communications',
      'Find and prevent fraud'
    ]
  },
  {
    icon: ShieldCheckIcon,
    title: 'Data Protection',
    content: 'We implement appropriate technical and organizational measures to protect your personal information against unauthorized or unlawful processing, accidental loss, destruction, or damage. We limit access to your personal information to those employees, agents, contractors, and other third parties who have a business need to know.'
  },
  {
    icon: UserCircleIcon,
    title: 'Your Rights',
    content: 'Depending on your location, you may have certain rights regarding your personal information, including:',
    items: [
      'The right to access your personal information',
      'The right to correct or update your information',
      'The right to request deletion of your information',
      'The right to restrict or object to processing',
      'The right to data portability',
      'The right to withdraw consent'
    ]
  },
  {
    icon: Cog8ToothIcon,
    title: 'Cookies and Tracking',
    content: 'We use cookies and similar tracking technologies to track activity on our website and hold certain information. You can instruct your browser to refuse all cookies or to indicate when a cookie is being sent. However, if you do not accept cookies, you may not be able to use some portions of our service.'
  }
];
</script>

<template>
  <PageTemplate
    title="Privacy Policy"
    description="Last updated: November 17, 2025"
  >
    <div class="bg-white dark:bg-gray-900 rounded-2xl shadow-xl overflow-hidden">
      <!-- Hero Section -->
      <div class="bg-gradient-to-br from-blue-600 to-cyan-600 px-6 py-16 sm:py-20 lg:px-8">
        <div class="mx-auto max-w-3xl text-center">
          <h2 class="text-3xl font-bold tracking-tight text-white sm:text-4xl">
            Your Privacy Matters
          </h2>
          <p class="mx-auto mt-6 max-w-2xl text-lg leading-8 text-blue-100">
            We are committed to protecting your personal information and your right to privacy. This Privacy Policy explains how we collect, use, and safeguard your information.
          </p>
        </div>
      </div>

      <!-- Privacy Content -->
      <div class="mx-auto max-w-4xl px-6 py-16 sm:py-20 lg:px-8">
        <div class="prose dark:prose-invert prose-lg max-w-none">
          <p class="text-gray-600 dark:text-gray-400">
            At DentalPro, we take your privacy seriously. This Privacy Policy describes how we collect, use, and share your personal information when you use our website and services.
          </p>
          
          <div v-for="(section, index) in privacySections" :key="index" class="mt-12 first:mt-0">
            <div class="flex items-start">
              <div class="flex-shrink-0 bg-blue-100 dark:bg-blue-900/50 p-2 rounded-lg">
                <component :is="section.icon" class="h-6 w-6 text-blue-600 dark:text-blue-400" aria-hidden="true" />
              </div>
              <div class="ml-4">
                <h3 class="text-xl font-semibold text-gray-900 dark:text-white">{{ section.title }}</h3>
                <p class="mt-2 text-gray-600 dark:text-gray-400">{{ section.content }}</p>
                <ul v-if="section.items" class="mt-4 space-y-2">
                  <li v-for="(item, itemIndex) in section.items" :key="itemIndex" class="flex items-start">
                    <svg class="h-5 w-5 text-blue-500 dark:text-blue-400 mt-0.5 mr-2 flex-shrink-0" viewBox="0 0 20 20" fill="currentColor">
                      <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                    </svg>
                    <span class="text-gray-600 dark:text-gray-400">{{ item }}</span>
                  </li>
                </ul>
              </div>
            </div>
          </div>
        </div>

        <div class="mt-16 border-t border-gray-200 dark:border-gray-700 pt-12">
          <div class="bg-blue-50 dark:bg-blue-900/20 rounded-xl p-6">
            <div class="flex">
              <div class="flex-shrink-0">
                <EnvelopeIcon class="h-6 w-6 text-blue-500 dark:text-blue-400" aria-hidden="true" />
              </div>
              <div class="ml-3">
                <h3 class="text-sm font-medium text-blue-800 dark:text-blue-200">Questions about our privacy practices?</h3>
                <div class="mt-2 text-sm text-blue-700 dark:text-blue-400">
                  <p>If you have any questions about this Privacy Policy, please contact us at <a href="mailto:privacy@dentalpro.com" class="font-medium text-blue-600 hover:text-blue-500 dark:text-blue-300 dark:hover:text-blue-400">privacy@dentalpro.com</a>.</p>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <div class="bg-gray-50 dark:bg-gray-800 px-6 py-4 border-t border-gray-200 dark:border-gray-700">
        <p class="text-center text-sm text-gray-500 dark:text-gray-400">
          Last updated: November 17, 2025
        </p>
      </div>
    </div>
  </PageTemplate>
</template>
