<script setup>
import PageTemplate from './PageTemplate.vue';
import { LightBulbIcon, HeartIcon, ShieldCheckIcon, UserGroupIcon, ChartBarIcon, ClockIcon, EnvelopeIcon } from '@heroicons/vue/24/outline';

const stats = [
  { label: 'Dental Practices Served', value: '500+' },
  { label: 'Active Users', value: '10,000+' },
  { label: 'Appointments Managed', value: '1M+' },
  { label: 'Customer Satisfaction', value: '99%' },
];

const values = [
  {
    name: 'Excellence',
    description: 'We are committed to delivering best-in-class solutions that exceed expectations.',
    icon: ChartBarIcon,
  },
  {
    name: 'Innovation',
    description: 'We continuously evolve our platform to meet the changing needs of modern dental practices.',
    icon: LightBulbIcon,
  },
  {
    name: 'Integrity',
    description: 'We believe in transparency, honesty, and doing what\'s right for our customers.',
    icon: ShieldCheckIcon,
  },
  {
    name: 'Compassion',
    description: 'We care deeply about the success of dental professionals and their patients.',
    icon: HeartIcon,
  },
];

const team = [
  {
    name: 'Dr. Sarah Johnson',
    role: 'Founder & CEO',
    imageUrl:
      'https://images.unsplash.com/photo-1573496358961-3cde308acf8d?ixlib=rb-4.0.3&ixid=MnwxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8&auto=format&fit=crop&w=880&q=80',
    bio: 'Board-certified dentist with 15+ years of clinical experience and a passion for healthcare technology.',
  },
  {
    name: 'Michael Chen',
    role: 'CTO',
    imageUrl:
      'https://images.unsplash.com/photo-1560250097-0b93528c311a?ixlib=rb-4.0.3&ixid=MnwxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8&auto=format&fit=crop&w=687&q=80',
    bio: 'Technology leader with expertise in building scalable healthcare platforms and enterprise software.',
  },
  {
    name: 'Emily Rodriguez',
    role: 'Head of Product',
    imageUrl:
      'https://images.unsplash.com/photo-1573496359142-b8d87734a5a2?ixlib=rb-4.0.3&ixid=MnwxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8&auto=format&fit=crop&w=688&q=80',
    bio: 'Product strategist focused on creating intuitive user experiences for healthcare professionals.',
  },
];
</script>

<template>
  <PageTemplate
    title="About Vintech Solutions"
    description="Transforming dental practices with innovative technology solutions."
  >
    <div class="bg-white dark:bg-gray-900 rounded-2xl shadow-xl overflow-hidden">
      <!-- Hero Section -->
      <div class="bg-gradient-to-br from-[#045c4b] to-[#0a8c74] px-6 py-16 sm:py-20 lg:px-8">
        <div class="mx-auto max-w-3xl text-center">
          <h2 class="text-3xl font-bold tracking-tight text-white sm:text-4xl">
            Revolutionizing Dental Practice Management
          </h2>
          <p class="mx-auto mt-6 max-w-2xl text-lg leading-8 text-blue-100">
            Founded in 2023, Vintech Solutions was born out of a simple idea: to make dental practice management simpler, more efficient, and more effective.
          </p>
        </div>
      </div>

      <!-- Main Content -->
      <div class="mx-auto max-w-7xl px-6 py-16 sm:py-20 lg:px-8">
        <!-- Our Story -->
        <div class="mx-auto max-w-3xl">
          <div class="prose dark:prose-invert prose-lg max-w-none">
            <h2 class="text-2xl font-bold tracking-tight text-gray-900 dark:text-white sm:text-3xl">
              Our Story
            </h2>
            <p class="text-gray-600 dark:text-gray-400">
              Vintech Solutions was founded by a team of dental professionals and technology experts who saw an opportunity to transform how dental practices operate. After years of experiencing the challenges of outdated practice management systems first-hand, we set out to build a solution that truly meets the needs of modern dental practices.
            </p>
            <p class="text-gray-600 dark:text-gray-400">
              Today, Vintech Solutions serves hundreds of dental practices across the country, helping them streamline operations, improve patient care, and grow their businesses. Our platform is built on the latest technology and designed with input from dental professionals at every step.
            </p>
          </div>

          <!-- Stats -->
          <div class="mt-16 grid grid-cols-1 gap-8 sm:grid-cols-2 lg:grid-cols-4">
            <div v-for="stat in stats" :key="stat.label" class="bg-gray-50 dark:bg-gray-800/50 p-6 rounded-2xl text-center">
              <p class="text-3xl font-bold tracking-tight text-blue-600 dark:text-blue-400">{{ stat.value }}</p>
              <p class="mt-2 text-sm font-medium text-gray-600 dark:text-gray-400">{{ stat.label }}</p>
            </div>
          </div>
        </div>

        <!-- Our Mission -->
        <div class="mx-auto mt-24 max-w-3xl">
          <div class="rounded-2xl bg-blue-50 dark:bg-blue-900/20 p-8 sm:p-10">
            <div class="flex items-start">
              <div class="flex-shrink-0 bg-blue-100 dark:bg-blue-900/50 p-2 rounded-lg">
                <LightBulbIcon class="h-6 w-6 text-blue-600 dark:text-blue-400" />
              </div>
              <div class="ml-4">
                <h2 class="text-2xl font-bold tracking-tight text-gray-900 dark:text-white sm:text-3xl">Our Mission</h2>
                <p class="mt-4 text-gray-600 dark:text-gray-300">
                  We're on a mission to empower dental professionals with intuitive software that streamlines operations, enhances patient care, and drives practice growth. We believe that with the right tools, dental professionals can focus on what truly matters – their patients.
                </p>
              </div>
            </div>
          </div>
        </div>

        <!-- Our Values -->
        <div class="mx-auto mt-24 max-w-3xl">
          <div class="text-center">
            <h2 class="text-2xl font-bold tracking-tight text-gray-900 dark:text-white sm:text-3xl">
              Our Values
            </h2>
            <p class="mt-4 text-gray-600 dark:text-gray-400">
              These principles guide everything we do at Vintech Solutions
            </p>
          </div>

          <div class="mt-12 grid grid-cols-1 gap-8 sm:grid-cols-2">
            <div v-for="value in values" :key="value.name" class="flex">
              <div class="flex-shrink-0">
                <a href="/contact" class="mt-8 inline-flex items-center justify-center rounded-md border border-transparent bg-white px-5 py-3 text-base font-medium text-[#045c4b] hover:bg-white/90 transition-colors sm:px-10">
                Contact Us
                <svg class="ml-3 -mr-1 h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                  <path fill-rule="evenodd" d="M10.293 5.293a1 1 0 011.414 0l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414-1.414L12.586 11H5a1 1 0 110-2h7.586l-2.293-2.293a1 1 0 010-1.414z" clip-rule="evenodd" />
                </svg>
              </a>
              </div>
              <div class="ml-4">
                <h3 class="text-lg font-medium text-gray-900 dark:text-white">{{ value.name }}</h3>
                <p class="mt-1 text-gray-600 dark:text-gray-400">{{ value.description }}</p>
              </div>
            </div>
          </div>
        </div>

        <!-- Team -->
        <!-- <div class="mx-auto mt-24 max-w-3xl">
          <div class="text-center">
            <h2 class="text-2xl font-bold tracking-tight text-gray-900 dark:text-white sm:text-3xl">
              Meet Our Leadership
            </h2>
            <p class="mt-4 text-gray-600 dark:text-gray-400">
              The passionate team behind Vintech Solutions
            </p>
          </div>

          <div class="mt-12 grid grid-cols-1 gap-8 sm:grid-cols-2 lg:grid-cols-3">
            <div v-for="person in team" :key="person.name" class="pt-6">
              <div class="flow-root rounded-2xl bg-gray-50 dark:bg-gray-800/50 px-6 pb-8">
                <div class="-mt-6">
                  <div class="relative">
                    <img class="mx-auto h-24 w-24 rounded-full object-cover shadow-lg" :src="person.imageUrl" alt="">
                    <span class="absolute -bottom-2 -right-2 flex h-8 w-8 items-center justify-center rounded-full bg-blue-600 p-1.5 text-white">
                      <UserGroupIcon class="h-5 w-5" aria-hidden="true" />
                    </span>
                  </div>
                  <h3 class="mt-8 text-lg font-semibold leading-8 tracking-tight text-gray-900 dark:text-white">{{ person.name }}</h3>
                  <p class="text-base leading-7 text-blue-600 dark:text-blue-400">{{ person.role }}</p>
                  <p class="mt-4 text-sm leading-6 text-gray-600 dark:text-gray-400">{{ person.bio }}</p>
                </div>
              </div>
            </div>
          </div>
        </div> -->

        <!-- CTA -->
        <div class="mt-24 text-center">
          <h2 class="text-2xl font-bold tracking-tight text-gray-900 dark:text-white sm:text-3xl">Ready to transform your practice?</h2>
          <p class="mx-auto mt-4 max-w-2xl text-gray-600 dark:text-gray-400">
            Join thousands of dental professionals who trust Vintech Solutions to power their practice.
          </p>
          <div class="mt-6">
            <a href="/contact" class="inline-flex items-center rounded-md bg-[#045c4b] px-6 py-3 text-base font-medium text-white shadow-sm hover:bg-[#0a8c74]/80 focus:outline-none focus:ring-2 focus:ring-[#045c4b] focus:ring-offset-2">
              Get in touch
            </a>
          </div>
        </div>
      </div>
    </div>
  </PageTemplate>
</template>
