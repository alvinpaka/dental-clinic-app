<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3'
import { ref, onMounted } from 'vue'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/Components/ui/card'
import { Button } from '@/Components/ui/button'
import { Input } from '@/Components/ui/input'
import { Label } from '@/Components/ui/label'
import { Alert, AlertDescription } from '@/Components/ui/alert'
import { useThemeStore } from '@/Stores/theme'

// Props from controller
const props = defineProps<{ status?: string }>()

const form = useForm({
  name: '',
  email: '',
  phone: '',
  company: '',
  preferred_date: '',
  message: ''
})

const submitting = ref(false)

const themeStore = useThemeStore()
onMounted(() => {
  themeStore.initTheme()
})

function submit() {
  submitting.value = true
  form.post(route('demo.store'), {
    preserveScroll: true,
    onFinish: () => {
      submitting.value = false
    }
  })
}
</script>

<template>
  <Head title="Schedule a Demo" />
  <div :class="['min-h-screen transition-colors duration-300 flex', themeStore.isDark ? 'dark bg-gray-950' : 'bg-gradient-to-br from-blue-50 via-white to-cyan-50']">
    <!-- Left Branding Panel -->
    <div class="hidden lg:flex lg:w-1/2 bg-gradient-to-br from-blue-600 via-cyan-600 to-teal-600 p-12 flex-col justify-between relative overflow-hidden">
      <div class="absolute inset-0 opacity-10">
        <div class="absolute inset-0" style="background-image: repeating-linear-gradient(0deg, transparent, transparent 39px, white 39px, white 40px), repeating-linear-gradient(90deg, transparent, transparent 39px, white 39px, white 40px);"></div>
      </div>

      <div class="relative z-10">
        <div class="space-y-8">
          <div>
            <h2 class="text-4xl font-bold text-white mb-4">Schedule a Live Demo</h2>
            <p class="text-blue-100 text-lg">See how Victoria Dental Lounge streamlines your daily operations in a 20–30 minute walkthrough.</p>
          </div>

          <div class="space-y-6">
            <div class="flex items-start space-x-4">
              <div class="w-12 h-12 rounded-lg bg-white/20 backdrop-blur-sm flex items-center justify-center flex-shrink-0">
                <i class="fas fa-bolt text-white text-xl"></i>
              </div>
              <div>
                <h3 class="text-white font-semibold text-lg mb-1">Quick & tailored</h3>
                <p class="text-blue-100">We focus on the features that matter to your clinic.</p>
              </div>
            </div>
            <div class="flex items-start space-x-4">
              <div class="w-12 h-12 rounded-lg bg-white/20 backdrop-blur-sm flex items-center justify-center flex-shrink-0">
                <i class="fas fa-user-shield text-white text-xl"></i>
              </div>
              <div>
                <h3 class="text-white font-semibold text-lg mb-1">Secure by design</h3>
                <p class="text-blue-100">HIPAA-friendly patterns and best practices.</p>
              </div>
            </div>
          </div>
        </div>
      </div>

      <div class="relative z-10 grid grid-cols-3 gap-4">
        <div class="text-center p-4 rounded-lg bg-white/10 backdrop-blur-sm">
          <div class="text-3xl font-bold text-white">500+</div>
          <div class="text-blue-100 text-sm">Clinics</div>
        </div>
        <div class="text-center p-4 rounded-lg bg-white/10 backdrop-blur-sm">
          <div class="text-3xl font-bold text-white">50k+</div>
          <div class="text-blue-100 text-sm">Patients</div>
        </div>
        <div class="text-center p-4 rounded-lg bg-white/10 backdrop-blur-sm">
          <div class="text-3xl font-bold text-white">98%</div>
          <div class="text-blue-100 text-sm">Satisfaction</div>
        </div>
      </div>
    </div>

    <!-- Right Form Panel -->
    <div class="w-full lg:w-1/2 flex items-center justify-center p-8">
      <div class="w-full max-w-xl relative">
        <!-- Theme Toggle -->
        <div class="flex justify-end mb-4">
          <Button variant="ghost" size="icon" @click="themeStore.toggleDarkMode" class="rounded-full">
            <i :class="['fas', themeStore.isDark ? 'fa-sun text-yellow-300' : 'fa-moon']"></i>
          </Button>
        </div>
        <Card class="border-0 shadow-2xl bg-white/80 dark:bg-gray-900/80 backdrop-blur-sm">
          <CardHeader>
            <CardTitle class="text-3xl font-bold text-gray-900 dark:text-white">Schedule a Demo</CardTitle>
            <CardDescription class="text-base text-gray-600 dark:text-gray-400">Fill in your details and we’ll reach out shortly.</CardDescription>
          </CardHeader>
          <CardContent>
            <Alert v-if="props.status" class="mb-6 bg-green-50 dark:bg-green-900/20 border-green-200 dark:border-green-800">
              <i class="fas fa-check-circle text-green-600 dark:text-green-400 mr-2"></i>
              <AlertDescription class="text-green-800 dark:text-green-300">{{ props.status }}</AlertDescription>
            </Alert>

            <form @submit.prevent="submit" class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div class="md:col-span-1">
                <Label for="name" class="text-gray-700 dark:text-gray-300">Full Name</Label>
                <Input id="name" v-model="form.name" type="text" required class="h-12 mt-1 bg-white dark:bg-gray-800 border-gray-300 dark:border-gray-700" />
                <p v-if="form.errors.name" class="text-sm text-red-600 dark:text-red-400 mt-1">{{ form.errors.name }}</p>
              </div>
              <div class="md:col-span-1">
                <Label for="email" class="text-gray-700 dark:text-gray-300">Email</Label>
                <Input id="email" v-model="form.email" type="email" required class="h-12 mt-1 bg-white dark:bg-gray-800 border-gray-300 dark:border-gray-700" />
                <p v-if="form.errors.email" class="text-sm text-red-600 dark:text-red-400 mt-1">{{ form.errors.email }}</p>
              </div>
              <div class="md:col-span-1">
                <Label for="phone" class="text-gray-700 dark:text-gray-300">Phone</Label>
                <Input id="phone" v-model="form.phone" type="text" class="h-12 mt-1 bg-white dark:bg-gray-800 border-gray-300 dark:border-gray-700" />
                <p v-if="form.errors.phone" class="text-sm text-red-600 dark:text-red-400 mt-1">{{ form.errors.phone }}</p>
              </div>
              <div class="md:col-span-1">
                <Label for="company" class="text-gray-700 dark:text-gray-300">Clinic/Company</Label>
                <Input id="company" v-model="form.company" type="text" class="h-12 mt-1 bg-white dark:bg-gray-800 border-gray-300 dark:border-gray-700" />
                <p v-if="form.errors.company" class="text-sm text-red-600 dark:text-red-400 mt-1">{{ form.errors.company }}</p>
              </div>
              <div class="md:col-span-1">
                <Label for="preferred_date" class="text-gray-700 dark:text-gray-300">Preferred Date</Label>
                <Input id="preferred_date" v-model="form.preferred_date" type="date" class="h-12 mt-1 bg-white dark:bg-gray-800 border-gray-300 dark:border-gray-700" />
                <p v-if="form.errors.preferred_date" class="text-sm text-red-600 dark:text-red-400 mt-1">{{ form.errors.preferred_date }}</p>
              </div>
              <div class="md:col-span-2">
                <Label for="message" class="text-gray-700 dark:text-gray-300">Notes</Label>
                <textarea id="message" v-model="form.message" rows="4" class="w-full mt-1 rounded-md border bg-white dark:bg-gray-800 border-gray-300 dark:border-gray-700 px-3 py-2 text-sm"></textarea>
                <p v-if="form.errors.message" class="text-sm text-red-600 dark:text-red-400 mt-1">{{ form.errors.message }}</p>
              </div>

              <div class="md:col-span-2">
                <Button type="submit" class="w-full h-12 bg-gradient-to-r from-blue-600 to-cyan-600 hover:from-blue-700 hover:to-cyan-700 text-white shadow-lg hover:shadow-xl transition-all duration-300 text-base font-semibold" :disabled="submitting || form.processing">
                  <i v-if="submitting || form.processing" class="fas fa-spinner fa-spin mr-2"></i>
                  <i v-else class="fas fa-calendar-check mr-2"></i>
                  {{ (submitting || form.processing) ? 'Submitting...' : 'Schedule Demo' }}
                </Button>

                <!-- Back to Home -->
                <div class="mt-6 text-center">
                  <a
                    href="/"
                    class="text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-200 inline-flex items-center text-sm"
                  >
                    <i class="fas fa-arrow-left mr-2"></i>
                    Back to homepage
                  </a>
                </div>
              </div>
            </form>
          </CardContent>
        </Card>
      </div>
    </div>
  </div>
</template>
