<script setup>
import { ref } from 'vue';
import PageTemplate from './PageTemplate.vue';
import { CheckIcon, XMarkIcon } from '@heroicons/vue/24/outline';

const plans = [
  {
    name: 'Basic',
    price: { monthly: '$99', annually: '$89' },
    description: 'Perfect for small practices just getting started.',
    features: [
      'Up to 2 providers',
      'Basic scheduling',
      'Patient records',
      'Email support',
      'Basic reporting',
      '1 GB storage'
    ],
    cta: 'Get Started',
    featured: false
  },
  {
    name: 'Professional',
    price: { monthly: '$199', annually: '$179' },
    description: 'Ideal for growing practices with multiple providers.',
    features: [
      'Up to 5 providers',
      'Advanced scheduling',
      'Patient portal',
      'Treatment planning',
      'Priority support',
      '5 GB storage',
      'Custom forms',
      'Billing integration'
    ],
    cta: 'Get Started',
    featured: true
  },
  {
    name: 'Enterprise',
    price: { custom: true },
    description: 'For large practices with complex needs.',
    features: [
      'Unlimited providers',
      'Custom workflows',
      'Dedicated account manager',
      'API access',
      '24/7 support',
      'Unlimited storage',
      'Custom integrations',
      'Onboarding & training'
    ],
    cta: 'Contact Sales',
    featured: false
  }
];

const features = [
  'Number of providers',
  'Patient records',
  'Appointment scheduling',
  'Patient portal',
  'Treatment planning',
  'Billing & invoicing',
  'Custom forms',
  'Storage',
  'Support',
  'API access',
  'Custom integrations'
];

const faqs = [
  {
    question: 'Can I change plans later?',
    answer: 'Yes, you can upgrade or downgrade your plan at any time. Your billing will be prorated accordingly.'
  },
  {
    question: 'Is there a free trial available?',
    answer: 'Yes, we offer a 14-day free trial for all new customers. No credit card required to start.'
  },
  {
    question: 'What payment methods do you accept?',
    answer: 'We accept all major credit cards, PayPal, and bank transfers for annual plans.'
  },
  {
    question: 'How is my data protected?',
    answer: 'We use enterprise-grade security measures including 256-bit encryption, regular backups, and SOC 2 compliance.'
  },
  {
    question: 'Do you offer discounts for non-profits?',
    answer: 'Yes, we offer special pricing for non-profit organizations. Please contact our sales team for more information.'
  },
  {
    question: 'What happens if I exceed my plan limits?',
    answer: 'If you exceed your plan limits, we\'ll notify you and work with you to upgrade to a plan that better fits your needs.'
  }
];

const billingCycle = ref('monthly');
</script>

<template>
  <PageTemplate
    title="Simple, Transparent Pricing"
    description="Choose the perfect plan for your dental practice with our straightforward pricing."
  >
    <div class="bg-white dark:bg-gray-900 rounded-2xl shadow-xl overflow-hidden">
      <!-- Pricing Toggle -->
      <div class="bg-gray-50 dark:bg-gray-800/50 pt-12 pb-6 px-6">
        <div class="max-w-7xl mx-auto">
          <div class="flex justify-center">
            <div class="inline-flex items-center bg-gray-100 dark:bg-gray-700 p-1 rounded-lg">
              <button
                @click="billingCycle = 'monthly'"
                :class="[
                  'px-4 py-2 text-sm font-medium rounded-md',
                  billingCycle === 'monthly'
                    ? 'bg-white dark:bg-gray-600 text-gray-900 dark:text-white shadow-sm'
                    : 'text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200'
                ]"
              >
                Monthly billing
              </button>
              <button
                @click="billingCycle = 'annually'"
                :class="[
                  'px-4 py-2 text-sm font-medium rounded-md',
                  billingCycle === 'annually'
                    ? 'bg-white dark:bg-gray-600 text-gray-900 dark:text-white shadow-sm'
                    : 'text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200'
                ]"
              >
                Annual billing <span class="text-blue-600 dark:text-blue-400 ml-1">(Save 10%)</span>
              </button>
            </div>
          </div>
        </div>
      </div>

      <!-- Pricing Plans -->
      <div class="max-w-7xl mx-auto px-6 py-12">
        <div class="grid grid-cols-1 gap-8 lg:grid-cols-3 lg:gap-6">
          <!-- Plan Cards -->
          <div v-for="(plan, index) in plans" :key="plan.name" 
               :class="[
                 'relative rounded-2xl p-8',
                 plan.featured 
                   ? 'bg-gradient-to-b from-blue-600 to-cyan-600 text-white shadow-2xl shadow-blue-500/20' 
                   : 'bg-white dark:bg-gray-800/50 ring-1 ring-gray-200 dark:ring-gray-700'
               ]">
            <!-- Popular Badge -->
            <div v-if="plan.featured" class="absolute -top-4 left-1/2 -translate-x-1/2">
              <div class="bg-yellow-400 text-gray-900 text-xs font-semibold px-4 py-1.5 rounded-full shadow-lg">
                Most Popular
              </div>
            </div>

            <h3 :class="[
              'text-lg font-semibold',
              plan.featured ? 'text-white' : 'text-gray-900 dark:text-white'
            ]">
              {{ plan.name }}
            </h3>

            <div class="mt-4 flex items-baseline">
              <span v-if="!plan.price.custom" 
                    :class="[
                      'text-4xl font-bold tracking-tight',
                      plan.featured ? 'text-white' : 'text-gray-900 dark:text-white'
                    ]">
                {{ billingCycle === 'monthly' ? plan.price.monthly : plan.price.annually }}
              </span>
              <span v-else 
                    :class="[
                      'text-4xl font-bold tracking-tight',
                      plan.featured ? 'text-white' : 'text-gray-900 dark:text-white'
                    ]">
                Custom
              </span>
              <span v-if="!plan.price.custom" 
                    :class="[
                      'ml-1 text-lg font-normal',
                      plan.featured ? 'text-blue-100' : 'text-gray-500 dark:text-gray-400'
                    ]">
                {{ billingCycle === 'monthly' ? '/month' : '/year' }}
              </span>
            </div>

            <p :class="[
              'mt-2',
              plan.featured ? 'text-blue-100' : 'text-gray-600 dark:text-gray-400'
            ]">
              {{ plan.description }}
            </p>

            <div class="mt-8">
              <a href="#" 
                 :class="[
                   'block w-full px-6 py-3 text-center font-medium rounded-md',
                   plan.featured 
                     ? 'bg-white text-blue-600 hover:bg-gray-50' 
                     : 'bg-blue-600 text-white hover:bg-blue-700'
                 ]">
                {{ plan.cta }}
              </a>
            </div>

            <ul role="list" :class="[
              'mt-8 space-y-3',
              plan.featured ? 'text-blue-100' : 'text-gray-700 dark:text-gray-300'
            ]">
              <li v-for="(feature, featureIdx) in plan.features" :key="featureIdx" class="flex items-start">
                <CheckIcon v-if="plan.features.includes(feature)" 
                          class="h-5 w-5 flex-shrink-0 text-green-500" 
                          aria-hidden="true" />
                <XMarkIcon v-else 
                           class="h-5 w-5 flex-shrink-0 text-gray-400" 
                           aria-hidden="true" />
                <span class="ml-3">{{ feature }}</span>
              </li>
            </ul>
          </div>
        </div>
      </div>

      <!-- Feature Comparison Table -->
      <div class="px-6 pb-12">
        <div class="max-w-7xl mx-auto">
          <h2 class="text-2xl font-bold tracking-tight text-gray-900 dark:text-white sm:text-3xl text-center mb-10">
            Compare all features
          </h2>
          
          <div class="mt-8 flex flex-col">
            <div class="-my-2 -mx-4 overflow-x-auto sm:-mx-6 lg:-mx-8">
              <div class="inline-block min-w-full py-2 align-middle md:px-6 lg:px-8">
                <div class="overflow-hidden shadow ring-1 ring-black ring-opacity-5 md:rounded-lg">
                  <table class="min-w-full divide-y divide-gray-300 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-800">
                      <tr>
                        <th scope="col" class="py-3.5 pl-4 pr-3 text-left text-sm font-semibold text-gray-900 dark:text-white sm:pl-6">
                          Feature
                        </th>
                        <th v-for="plan in plans" :key="plan.name" scope="col" class="px-3 py-3.5 text-center text-sm font-semibold text-gray-900 dark:text-white">
                          {{ plan.name }}
                        </th>
                      </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700 bg-white dark:bg-gray-900">
                      <tr v-for="(feature, index) in features" :key="index" :class="{ 'bg-gray-50 dark:bg-gray-800/50': index % 2 === 0 }">
                        <td class="whitespace-nowrap py-4 pl-4 pr-3 text-sm font-medium text-gray-900 dark:text-white sm:pl-6">
                          {{ feature }}
                        </td>
                        <td v-for="plan in plans" :key="`${plan.name}-${index}`" class="whitespace-nowrap px-3 py-4 text-sm text-center">
                          <CheckIcon v-if="plan.features.some(f => f.toLowerCase().includes(feature.toLowerCase()))" 
                                    class="mx-auto h-5 w-5 text-green-500" 
                                    aria-hidden="true" />
                          <XMarkIcon v-else 
                                     class="mx-auto h-5 w-5 text-gray-400" 
                                     aria-hidden="true" />
                        </td>
                      </tr>
                    </tbody>
                  </table>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- FAQ Section -->
      <div class="bg-gray-50 dark:bg-gray-800/50 py-16 px-6 sm:py-24 lg:px-8">
        <div class="mx-auto max-w-3xl">
          <div class="text-center">
            <h2 class="text-2xl font-bold tracking-tight text-gray-900 dark:text-white sm:text-3xl">
              Frequently asked questions
            </h2>
            <p class="mt-4 text-gray-600 dark:text-gray-400">
              Can't find the answer you're looking for? Reach out to our
              <a href="#" class="font-medium text-blue-600 hover:text-blue-500 dark:text-blue-400 dark:hover:text-blue-300">
                customer support
              </a>
              team.
            </p>
          </div>

          <div class="mt-12">
            <dl class="space-y-10 sm:grid sm:grid-cols-2 sm:gap-x-6 sm:gap-y-10 sm:space-y-0 lg:gap-x-8">
              <div v-for="(faq, index) in faqs" :key="index" class="pt-6">
                <dt class="text-lg font-medium text-gray-900 dark:text-white">
                  {{ faq.question }}
                </dt>
                <dd class="mt-2 text-gray-600 dark:text-gray-400">
                  {{ faq.answer }}
                </dd>
              </div>
            </dl>
          </div>
        </div>
      </div>

      <!-- CTA Section -->
      <div class="bg-white dark:bg-gray-900">
        <div class="mx-auto max-w-7xl py-12 px-6 lg:flex lg:items-center lg:justify-between lg:py-16 lg:px-8">
          <h2 class="text-3xl font-bold tracking-tight text-gray-900 dark:text-white sm:text-4xl">
            <span class="block">Ready to get started?</span>
            <span class="block bg-gradient-to-r from-blue-600 to-cyan-600 bg-clip-text text-transparent">Start your free trial today.</span>
          </h2>
          <div class="mt-8 flex lg:mt-0 lg:flex-shrink-0">
            <div class="inline-flex rounded-md shadow">
              <a href="#" class="inline-flex items-center justify-center rounded-md border border-transparent bg-blue-600 px-5 py-3 text-base font-medium text-white hover:bg-blue-700">
                Get started
              </a>
            </div>
            <div class="ml-3 inline-flex rounded-md shadow">
              <a href="#" class="inline-flex items-center justify-center rounded-md border border-transparent bg-white dark:bg-gray-800 px-5 py-3 text-base font-medium text-blue-600 hover:bg-gray-50 dark:text-blue-400 dark:hover:bg-gray-700">
                Contact sales
              </a>
            </div>
          </div>
        </div>
      </div>
    </div>
  </PageTemplate>
</template>
