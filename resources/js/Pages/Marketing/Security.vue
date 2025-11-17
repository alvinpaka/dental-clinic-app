<script setup>
import PageTemplate from './PageTemplate.vue';
import { ShieldCheckIcon, LockClosedIcon, ServerIcon, KeyIcon, BellAlertIcon, UserGroupIcon } from '@heroicons/vue/24/outline';

const securityFeatures = [
  {
    icon: ShieldCheckIcon,
    title: 'Data Protection',
    description: 'We implement industry-standard security measures to protect your information from unauthorized access, alteration, disclosure, or destruction.'
  },
  {
    icon: LockClosedIcon,
    title: 'Encryption',
    description: 'All data is encrypted in transit with TLS 1.2+ and at rest using AES-256 encryption, ensuring your information remains private and secure.'
  },
  {
    icon: ServerIcon,
    title: 'Secure Infrastructure',
    description: 'Our cloud-based infrastructure features multiple security layers, including firewalls, DDoS protection, and regular security audits.'
  },
  {
    icon: KeyIcon,
    title: 'Access Control',
    description: 'Strict authentication and role-based access controls ensure only authorized personnel can access sensitive data.'
  },
  {
    icon: BellAlertIcon,
    title: 'Monitoring & Response',
    description: '24/7 monitoring and automated alerting help us detect and respond to potential security threats in real-time.'
  },
  {
    icon: UserGroupIcon,
    title: 'Compliance',
    description: 'We maintain compliance with major regulations including GDPR and HIPAA, with regular third-party security assessments.'
  }
];
</script>

<template>
  <PageTemplate
    title="Security First"
    description="Your trust is our top priority. We implement enterprise-grade security measures to protect your data."
  >
    <div class="bg-white dark:bg-gray-900 rounded-2xl shadow-xl overflow-hidden">
      <!-- Hero Section -->
      <div class="bg-gradient-to-br from-blue-600 to-cyan-600 px-6 py-16 sm:py-20 lg:px-8">
        <div class="mx-auto max-w-3xl text-center">
          <h2 class="text-3xl font-bold tracking-tight text-white sm:text-4xl">
            Enterprise-Grade Security
          </h2>
          <p class="mx-auto mt-6 max-w-2xl text-lg leading-8 text-blue-100">
            We take a proactive approach to security, implementing multiple layers of protection to safeguard your data.
          </p>
        </div>
      </div>

      <!-- Security Features Grid -->
      <div class="mx-auto max-w-7xl px-6 lg:px-8 py-16 sm:py-20">
        <div class="grid grid-cols-1 gap-x-8 gap-y-16 md:grid-cols-2 lg:grid-cols-3">
          <div v-for="(feature, index) in securityFeatures" :key="index" class="group relative bg-white dark:bg-gray-800 p-6 rounded-xl shadow-sm hover:shadow-md transition-shadow">
            <div class="absolute -top-6 left-6 flex h-14 w-14 items-center justify-center rounded-xl bg-gradient-to-br from-blue-600 to-cyan-600 text-white shadow-lg">
              <component :is="feature.icon" class="h-7 w-7" aria-hidden="true" />
            </div>
            <h3 class="mt-8 text-lg font-semibold text-gray-900 dark:text-white">{{ feature.title }}</h3>
            <p class="mt-4 text-base text-gray-600 dark:text-gray-400">{{ feature.description }}</p>
          </div>
        </div>
      </div>

      <!-- Additional Security Info -->
      <div class="bg-gray-50 dark:bg-gray-800 px-6 py-12 sm:py-16">
        <div class="mx-auto max-w-4xl">
          <div class="text-center">
            <h2 class="text-2xl font-bold tracking-tight text-gray-900 dark:text-white sm:text-3xl">
              Your Role in Security
            </h2>
            <p class="mx-auto mt-4 max-w-2xl text-gray-600 dark:text-gray-400">
              Security is a shared responsibility. Here's how you can help keep your data secure:
            </p>
          </div>
          
          <div class="mt-12">
            <div class="grid grid-cols-1 gap-8 sm:grid-cols-2 lg:grid-cols-4">
              <div v-for="(tip, index) in [
                'Use strong, unique passwords',
                'Enable two-factor authentication',
                'Keep software updated',
                'Be cautious of phishing attempts'
              ]" :key="index" class="flex items-start">
                <div class="flex-shrink-0">
                  <div class="flex h-6 w-6 items-center justify-center rounded-full bg-blue-100 text-blue-600 dark:bg-blue-900/50 dark:text-blue-400">
                    <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 20 20">
                      <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                    </svg>
                  </div>
                </div>
                <p class="ml-3 text-sm text-gray-700 dark:text-gray-300">{{ tip }}</p>
              </div>
            </div>
          </div>

          <div class="mt-16 border-t border-gray-200 dark:border-gray-700 pt-12 text-center">
            <h3 class="text-lg font-medium text-gray-900 dark:text-white">Have security concerns?</h3>
            <p class="mt-2 text-base text-gray-600 dark:text-gray-400">
              Our security team is available 24/7 to address any questions or concerns.
            </p>
            <div class="mt-6">
              <a href="/contact" class="text-base font-medium text-blue-600 hover:text-blue-500 dark:text-cyan-400 dark:hover:text-cyan-300">
                Contact our security team
                <span aria-hidden="true"> &rarr;</span>
              </a>
            </div>
          </div>
        </div>
      </div>

      <div class="bg-white dark:bg-gray-900 px-6 py-4 border-t border-gray-200 dark:border-gray-800">
        <p class="text-center text-sm text-gray-500 dark:text-gray-400">
          Last updated: November 17, 2025
        </p>
      </div>
    </div>
  </PageTemplate>
</template>
