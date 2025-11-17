<script setup>
import PageTemplate from './PageTemplate.vue';
import { DocumentTextIcon, UserCircleIcon, ShieldCheckIcon, ExclamationTriangleIcon, ScaleIcon, ClockIcon, EnvelopeIcon } from '@heroicons/vue/24/outline';

const terms = [
  {
    icon: DocumentTextIcon,
    title: '1. Introduction',
    content: 'Welcome to DentalPro. These Terms of Service ("Terms") govern your access to and use of our website and services (collectively, the "Service"). By accessing or using the Service, you agree to be bound by these Terms.'
  },
  {
    icon: UserCircleIcon,
    title: '2. Accounts',
    content: 'When you create an account with us, you must provide accurate and complete information. You are responsible for maintaining the confidentiality of your account credentials and for all activities that occur under your account.'
  },
  {
    icon: ShieldCheckIcon,
    title: '3. Use of Service',
    content: 'You agree to use the Service only for lawful purposes and in accordance with these Terms. You agree not to use the Service in any way that violates any applicable law or regulation, to transmit any malicious code or harmful content, to interfere with or disrupt the integrity or performance of the Service, or to attempt to gain unauthorized access to the Service or its related systems.'
  },
  {
    icon: ExclamationTriangleIcon,
    title: '4. Intellectual Property',
    content: 'The Service and its original content, features, and functionality are and will remain the exclusive property of DentalPro and its licensors. The Service is protected by copyright, trademark, and other laws of both the United States and foreign countries.'
  },
  {
    icon: ScaleIcon,
    title: '5. Limitation of Liability',
    content: 'In no event shall DentalPro, nor its directors, employees, partners, agents, suppliers, or affiliates, be liable for any indirect, incidental, special, consequential or punitive damages, including without limitation, loss of profits, data, use, goodwill, or other intangible losses, resulting from your access to or use of or inability to access or use the Service.'
  },
  {
    icon: ClockIcon,
    title: '6. Changes to Terms',
    content: 'We reserve the right to modify or replace these Terms at any time. We will provide notice of any changes by posting the new Terms on this page and updating the "Last updated" date.'
  }
];
</script>

<template>
  <PageTemplate
    title="Terms of Service"
    description="Last updated: November 17, 2025"
  >
    <div class="bg-white dark:bg-gray-900 rounded-2xl shadow-xl overflow-hidden">
      <!-- Hero Section -->
      <div class="bg-gradient-to-br from-blue-600 to-cyan-600 px-6 py-16 sm:py-20 lg:px-8">
        <div class="mx-auto max-w-3xl text-center">
          <h2 class="text-3xl font-bold tracking-tight text-white sm:text-4xl">
            Terms of Service
          </h2>
          <p class="mx-auto mt-6 max-w-2xl text-lg leading-8 text-blue-100">
            Please read these terms carefully before using our service. By accessing or using our service, you agree to be bound by these terms.
          </p>
        </div>
      </div>

      <!-- Terms Content -->
      <div class="mx-auto max-w-4xl px-6 py-16 sm:py-20 lg:px-8">
        <div class="prose dark:prose-invert prose-lg max-w-none">
          <div v-for="(term, index) in terms" :key="index" class="mb-12 last:mb-0">
            <div class="flex items-start">
              <div class="flex-shrink-0 bg-blue-100 dark:bg-blue-900/50 p-2 rounded-lg">
                <component :is="term.icon" class="h-6 w-6 text-blue-600 dark:text-blue-400" aria-hidden="true" />
              </div>
              <div class="ml-4">
                <h3 class="text-xl font-semibold text-gray-900 dark:text-white">{{ term.title }}</h3>
                <p class="mt-2 text-gray-600 dark:text-gray-400">{{ term.content }}</p>
              </div>
            </div>
          </div>
        </div>

        <div class="mt-16 border-t border-gray-200 dark:border-gray-700 pt-12">
          <div class="text-center">
            <h3 class="text-lg font-medium text-gray-900 dark:text-white">Governing Law</h3>
            <p class="mt-2 text-gray-600 dark:text-gray-400">
              These Terms shall be governed and construed in accordance with the laws of the State of California, United States, without regard to its conflict of law provisions.
            </p>
          </div>

          <div class="mt-10 bg-blue-50 dark:bg-blue-900/20 rounded-xl p-6">
            <div class="flex">
              <div class="flex-shrink-0">
                <EnvelopeIcon class="h-6 w-6 text-blue-500 dark:text-blue-400" aria-hidden="true" />
              </div>
              <div class="ml-3">
                <h3 class="text-sm font-medium text-blue-800 dark:text-blue-200">Questions?</h3>
                <div class="mt-2 text-sm text-blue-700 dark:text-blue-400">
                  <p>If you have any questions about these Terms, please contact us at <a href="mailto:legal@dentalpro.com" class="font-medium text-blue-600 hover:text-blue-500 dark:text-blue-300 dark:hover:text-blue-400">legal@dentalpro.com</a>.</p>
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
