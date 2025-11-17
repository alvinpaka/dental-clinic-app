<script setup>
import PageTemplate from './PageTemplate.vue';
import { BriefcaseIcon, MapPinIcon, ClockIcon, CheckCircleIcon, LightBulbIcon, UserGroupIcon, ShieldCheckIcon, CurrencyDollarIcon, DevicePhoneMobileIcon, GlobeAltIcon, EnvelopeIcon } from '@heroicons/vue/24/outline';

const jobOpenings = [
  {
    id: 1,
    title: 'Frontend Developer',
    type: 'Full-time',
    location: 'Remote',
    department: 'Engineering',
    description: 'We are looking for an experienced Frontend Developer to join our team and help build the next generation of our dental practice management platform.',
    responsibilities: [
      'Develop and maintain responsive web applications using Vue.js',
      'Collaborate with designers to implement pixel-perfect UIs',
      'Write clean, maintainable, and efficient code',
      'Participate in code reviews and team discussions',
      'Optimize applications for maximum speed and scalability'
    ],
    requirements: [
      '3+ years of experience with modern JavaScript frameworks (Vue.js, React, or similar)',
      'Strong understanding of HTML5, CSS3, and responsive design',
      'Experience with state management (Vuex, Pinia, or similar)',
      'Familiarity with RESTful APIs and GraphQL',
      'Experience with testing frameworks (Jest, Cypress, or similar)'
    ]
  },
  {
    id: 2,
    title: 'UX/UI Designer',
    type: 'Full-time',
    location: 'San Francisco, CA',
    department: 'Design',
    description: 'We are seeking a talented UX/UI Designer to create amazing user experiences for our dental practice management software.',
    responsibilities: [
      'Design intuitive and beautiful user interfaces',
      'Create wireframes, prototypes, and high-fidelity mockups',
      'Conduct user research and usability testing',
      'Collaborate with product managers and engineers',
      'Develop and maintain design systems'
    ],
    requirements: [
      '3+ years of experience in UX/UI design',
      'Strong portfolio showcasing your design work',
      'Proficiency in design tools (Figma, Sketch, or similar)',
      'Understanding of user-centered design principles',
      'Experience with design systems and component libraries'
    ]
  },
  {
    id: 3,
    title: 'Customer Success Manager',
    type: 'Full-time',
    location: 'Remote',
    department: 'Customer Success',
    description: 'Join our team to help our dental practice customers succeed with our products and services.',
    responsibilities: [
      'Onboard new customers and ensure successful product adoption',
      'Build and maintain strong client relationships',
      'Gather customer feedback and advocate for their needs',
      'Identify growth opportunities and prevent churn',
      'Collaborate with sales and product teams'
    ],
    requirements: [
      '2+ years of experience in customer success or account management',
      'Excellent communication and interpersonal skills',
      'Experience with CRM software (Salesforce, HubSpot, or similar)',
      'Ability to understand and explain technical concepts',
      'Background in healthcare or SaaS is a plus'
    ]
  }
];

const benefits = [
  { 
    icon: CurrencyDollarIcon, 
    title: 'Competitive Compensation',
    description: 'We offer competitive salaries and equity packages.'
  },
  { 
    icon: ShieldCheckIcon, 
    title: 'Health & Wellness',
    description: 'Comprehensive health, dental, and vision insurance.'
  },
  { 
    icon: ClockIcon, 
    title: 'Flexible Hours',
    description: 'Work when you\'re most productive with our flexible schedule.'
  },
  { 
    icon: GlobeAltIcon, 
    title: 'Remote Work',
    description: 'Work from anywhere with our fully remote team.'
  },
  { 
    icon: LightBulbIcon, 
    title: 'Learning Budget',
    description: 'Annual stipend for professional development.'
  },
  { 
    icon: DevicePhoneMobileIcon, 
    title: 'Tech Stipend',
    description: 'Budget for your home office setup.'
  }
];
</script>

<template>
  <PageTemplate
    title="Join Our Team"
    description="Help us transform dental practice management with innovative technology."
  >
    <div class="bg-white dark:bg-gray-900 rounded-2xl shadow-xl overflow-hidden">
      <!-- Hero Section -->
      <div class="bg-gradient-to-br from-blue-600 to-cyan-600 px-6 py-16 sm:py-20 lg:px-8">
        <div class="mx-auto max-w-3xl text-center">
          <h2 class="text-3xl font-bold tracking-tight text-white sm:text-4xl">
            Build the Future of Dental Technology
          </h2>
          <p class="mx-auto mt-6 max-w-2xl text-lg leading-8 text-blue-100">
            Join our mission to revolutionize how dental practices operate with innovative software solutions.
          </p>
        </div>
      </div>

      <!-- Main Content -->
      <div class="mx-auto max-w-7xl px-6 py-16 sm:py-20 lg:px-8">
        <!-- Current Openings -->
        <div class="mx-auto max-w-4xl">
          <h2 class="text-2xl font-bold tracking-tight text-gray-900 dark:text-white sm:text-3xl">
            Current Openings
          </h2>
          
          <div class="mt-10 space-y-10">
            <div v-for="job in jobOpenings" :key="job.id" class="group relative overflow-hidden rounded-2xl bg-white dark:bg-gray-800/50 p-6 shadow-sm ring-1 ring-gray-900/5 hover:shadow-md transition-all duration-200">
              <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between">
                <div>
                  <h3 class="text-xl font-semibold text-gray-900 dark:text-white">
                    {{ job.title }}
                  </h3>
                  <div class="mt-2 flex flex-wrap items-center gap-x-4 gap-y-2 text-sm">
                    <div class="flex items-center text-gray-600 dark:text-gray-400">
                      <BriefcaseIcon class="mr-1.5 h-5 w-5 flex-shrink-0 text-gray-500" aria-hidden="true" />
                      {{ job.department }}
                    </div>
                    <div class="flex items-center text-gray-600 dark:text-gray-400">
                      <MapPinIcon class="mr-1.5 h-5 w-5 flex-shrink-0 text-gray-500" aria-hidden="true" />
                      {{ job.location }}
                    </div>
                    <div class="flex items-center text-gray-600 dark:text-gray-400">
                      <ClockIcon class="mr-1.5 h-5 w-5 flex-shrink-0 text-gray-500" aria-hidden="true" />
                      {{ job.type }}
                    </div>
                  </div>
                </div>
                <div class="mt-4 sm:mt-0">
                  <a href="#" class="inline-flex items-center rounded-md bg-blue-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                    Apply now
                  </a>
                </div>
              </div>
              
              <div class="mt-6">
                <h4 class="text-sm font-medium text-gray-900 dark:text-white">About the Role</h4>
                <p class="mt-2 text-gray-600 dark:text-gray-400">{{ job.description }}</p>
                
                <div class="mt-6 grid gap-6 sm:grid-cols-2">
                  <div>
                    <h4 class="text-sm font-medium text-gray-900 dark:text-white">Responsibilities</h4>
                    <ul role="list" class="mt-2 space-y-2">
                      <li v-for="(item, index) in job.responsibilities" :key="`resp-${index}`" class="flex">
                        <CheckCircleIcon class="h-5 w-5 flex-shrink-0 text-green-500 mt-0.5 mr-2" aria-hidden="true" />
                        <span class="text-gray-600 dark:text-gray-400">{{ item }}</span>
                      </li>
                    </ul>
                  </div>
                  <div>
                    <h4 class="text-sm font-medium text-gray-900 dark:text-white">Requirements</h4>
                    <ul role="list" class="mt-2 space-y-2">
                      <li v-for="(item, index) in job.requirements" :key="`req-${index}`" class="flex">
                        <CheckCircleIcon class="h-5 w-5 flex-shrink-0 text-green-500 mt-0.5 mr-2" aria-hidden="true" />
                        <span class="text-gray-600 dark:text-gray-400">{{ item }}</span>
                      </li>
                    </ul>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Why Join Us -->
        <div class="mx-auto mt-24 max-w-4xl">
          <div class="text-center">
            <h2 class="text-2xl font-bold tracking-tight text-gray-900 dark:text-white sm:text-3xl">
              Why Join DentalPro?
            </h2>
            <p class="mx-auto mt-4 max-w-2xl text-gray-600 dark:text-gray-400">
              We're building a company where talented people can do their best work and help transform the dental industry.
            </p>
          </div>

          <!-- Benefits Grid -->
          <div class="mt-12 grid grid-cols-1 gap-8 sm:grid-cols-2 lg:grid-cols-3">
            <div v-for="(benefit, index) in benefits" :key="index" class="rounded-2xl bg-gray-50 dark:bg-gray-800/50 p-6">
              <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-blue-100 dark:bg-blue-900/50 text-blue-600 dark:text-blue-400">
                <component :is="benefit.icon" class="h-6 w-6" aria-hidden="true" />
              </div>
              <h3 class="mt-4 text-base font-medium text-gray-900 dark:text-white">{{ benefit.title }}</h3>
              <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">{{ benefit.description }}</p>
            </div>
          </div>

          <!-- Culture Section -->
          <div class="mt-16 rounded-2xl bg-blue-50 dark:bg-blue-900/20 p-8 sm:p-10">
            <div class="md:flex md:items-center md:justify-between">
              <div class="md:flex-1">
                <h2 class="text-2xl font-bold tracking-tight text-gray-900 dark:text-white sm:text-3xl">Our Culture</h2>
                <p class="mt-4 text-gray-600 dark:text-gray-300">
                  At DentalPro, we value collaboration, innovation, and a strong work-life balance. Our team is distributed across different locations, and we embrace remote work while maintaining a strong company culture.
                </p>
                <div class="mt-6 flex items-center">
                  <UserGroupIcon class="h-5 w-5 text-blue-500" aria-hidden="true" />
                  <span class="ml-2 text-sm text-gray-600 dark:text-gray-400">Join our team of 50+ talented individuals</span>
                </div>
              </div>
              <div class="mt-6 md:ml-10 md:mt-0">
                <a href="#" class="inline-flex items-center rounded-md bg-blue-600 px-4 py-3 text-sm font-medium text-white shadow-sm hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                  See Open Positions
                </a>
              </div>
            </div>
          </div>
        </div>

        <!-- CTA Section -->
        <div class="mt-24 text-center">
          <h2 class="text-2xl font-bold tracking-tight text-gray-900 dark:text-white sm:text-3xl">Don't see the perfect role?</h2>
          <p class="mx-auto mt-4 max-w-2xl text-gray-600 dark:text-gray-400">
            We're always looking for talented individuals to join our team. Send us your resume and tell us how you can contribute.
          </p>
          <div class="mt-6">
            <a href="mailto:careers@dentalpro.com" class="inline-flex items-center rounded-md bg-white px-4 py-3 text-sm font-medium text-blue-600 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-500 dark:bg-gray-800 dark:text-blue-400 dark:ring-gray-700 dark:hover:bg-gray-700">
              <EnvelopeIcon class="-ml-0.5 mr-2 h-5 w-5" aria-hidden="true" />
              careers@dentalpro.com
            </a>
          </div>
        </div>
      </div>
    </div>
  </PageTemplate>
</template>
