<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { Button } from '@/Components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/Components/ui/card';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { Checkbox } from '@/Components/ui/checkbox';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/Components/ui/select';
import { Textarea } from '@/Components/ui/textarea';
import { Badge } from '@/Components/ui/badge';
import { Building2, Mail, Phone, MapPin, ChevronLeft, Save, X, User, Lock } from 'lucide-vue-next';

const form = useForm({
  name: '',
  email: '',
  phone: '',
  address: '',
  is_active: true,
  subscription_status: 'trial',
  // Admin user fields
  admin_name: '',
  admin_email: '',
  admin_password: '',
  admin_password_confirmation: '',
});

const submit = () => {
  form.post(route('clinics.store'));
};
</script>

<template>
  <Head title="Create Clinic" />

  <AppLayout title="Create Clinic">
    <template #header>
      <div class="px-6 py-4">
        <div class="flex justify-between items-center">
          <div class="flex items-center space-x-4">
            <Link
              :href="route('clinics.index')"
              class="inline-flex items-center text-gray-600 dark:text-gray-300 hover:text-gray-900 dark:hover:text-white transition-colors px-2 py-1 rounded-md hover:bg-gray-100 dark:hover:bg-gray-800"
            >
              <ChevronLeft class="w-5 h-5 mr-1" />
              Back to Clinics
            </Link>
            <div class="h-6 w-px bg-gray-300 dark:bg-gray-600"></div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
              Create New Clinic
            </h1>
          </div>
          <Badge variant="outline" class="bg-blue-50 text-blue-700 border-blue-200 dark:bg-blue-900/20 dark:text-blue-400 dark:border-blue-800">
            <Building2 class="w-4 h-4 mr-2" />
            New Clinic
          </Badge>
        </div>
      </div>
    </template>

    <div class="py-6">
      <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
        <!-- Main Form Card -->
        <Card class="border-0 shadow-xl bg-white dark:bg-gray-900">
          <CardHeader class="border-b border-gray-200 dark:border-gray-700">
            <div class="flex items-center justify-between">
              <div>
                <CardTitle class="text-xl text-gray-900 dark:text-white flex items-center">
                  <Building2 class="w-6 h-6 mr-2 text-blue-600 dark:text-blue-400" />
                  Clinic Information
                </CardTitle>
                <CardDescription class="text-gray-600 dark:text-gray-400 mt-2">
                  Fill in the details to create a new dental clinic location and its administrator account
                </CardDescription>
              </div>
              <div class="flex items-center space-x-2">
                <Badge 
                  :variant="form.is_active ? 'default' : 'secondary'"
                  :class="form.is_active 
                    ? 'bg-green-100 text-green-800 border-green-200 dark:bg-green-900/20 dark:text-green-400 dark:border-green-800' 
                    : 'bg-gray-100 text-gray-800 border-gray-200 dark:bg-gray-900/20 dark:text-gray-400 dark:border-gray-800'"
                >
                  {{ form.is_active ? 'Active' : 'Inactive' }}
                </Badge>
                <Badge variant="outline" class="capitalize">
                  {{ form.subscription_status }}
                </Badge>
              </div>
            </div>
          </CardHeader>

          <CardContent class="p-6">
            <form @submit.prevent="submit" class="space-y-8">
              <!-- Basic Information Section -->
              <div class="space-y-6">
                <div class="flex items-center mb-4">
                  <div class="h-px bg-gray-200 dark:bg-gray-700 flex-1"></div>
                  <span class="px-4 text-sm font-medium text-gray-500 dark:text-gray-400">Basic Information</span>
                  <div class="h-px bg-gray-200 dark:bg-gray-700 flex-1"></div>
                </div>

                <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                  <div class="space-y-2">
                    <Label for="name" class="text-sm font-medium text-gray-700 dark:text-gray-300 flex items-center">
                      <Building2 class="w-4 h-4 mr-2" />
                      Clinic Name *
                    </Label>
                    <Input
                      id="name"
                      v-model="form.name"
                      placeholder="Enter clinic name"
                      :class="{ 'border-red-500 focus:border-red-500': form.errors.name }"
                      required
                    />
                    <p v-if="form.errors.name" class="text-sm text-red-600 dark:text-red-400">
                      {{ form.errors.name }}
                    </p>
                  </div>

                  <div class="space-y-2">
                    <Label for="email" class="text-sm font-medium text-gray-700 dark:text-gray-300 flex items-center">
                      <Mail class="w-4 h-4 mr-2" />
                      Email Address *
                    </Label>
                    <Input
                      id="email"
                      type="email"
                      v-model="form.email"
                      placeholder="clinic@example.com"
                      :class="{ 'border-red-500 focus:border-red-500': form.errors.email }"
                      required
                    />
                    <p v-if="form.errors.email" class="text-sm text-red-600 dark:text-red-400">
                      {{ form.errors.email }}
                    </p>
                  </div>

                  <div class="space-y-2">
                    <Label for="phone" class="text-sm font-medium text-gray-700 dark:text-gray-300 flex items-center">
                      <Phone class="w-4 h-4 mr-2" />
                      Phone Number *
                    </Label>
                    <Input
                      id="phone"
                      type="tel"
                      v-model="form.phone"
                      placeholder="+1-555-0123"
                      :class="{ 'border-red-500 focus:border-red-500': form.errors.phone }"
                      required
                    />
                    <p v-if="form.errors.phone" class="text-sm text-red-600 dark:text-red-400">
                      {{ form.errors.phone }}
                    </p>
                  </div>

                  <div class="space-y-2">
                    <Label for="subscription_status" class="text-sm font-medium text-gray-700 dark:text-gray-300">
                      Subscription Status *
                    </Label>
                    <Select v-model="form.subscription_status">
                      <SelectTrigger :class="{ 'border-red-500 focus:border-red-500': form.errors.subscription_status }">
                        <SelectValue placeholder="Select subscription status" />
                      </SelectTrigger>
                      <SelectContent>
                        <SelectItem value="trial">
                          <div class="flex items-center">
                            <Badge variant="outline" class="mr-2 bg-blue-50 text-blue-700 border-blue-200 dark:bg-blue-900/20 dark:text-blue-400 dark:border-blue-800">Trial</Badge>
                            <span>14-day trial period</span>
                          </div>
                        </SelectItem>
                        <SelectItem value="active">
                          <div class="flex items-center">
                            <Badge variant="outline" class="mr-2 bg-green-50 text-green-700 border-green-200 dark:bg-green-900/20 dark:text-green-400 dark:border-green-800">Active</Badge>
                            <span>Active subscription</span>
                          </div>
                        </SelectItem>
                        <SelectItem value="expired">
                          <div class="flex items-center">
                            <Badge variant="outline" class="mr-2 bg-red-50 text-red-700 border-red-200 dark:bg-red-900/20 dark:text-red-400 dark:border-red-800">Expired</Badge>
                            <span>Subscription expired</span>
                          </div>
                        </SelectItem>
                        <SelectItem value="cancelled">
                          <div class="flex items-center">
                            <Badge variant="outline" class="mr-2 bg-gray-50 text-gray-700 border-gray-200 dark:bg-gray-900/20 dark:text-gray-400 dark:border-gray-800">Cancelled</Badge>
                            <span>Subscription cancelled</span>
                          </div>
                        </SelectItem>
                      </SelectContent>
                    </Select>
                    <p v-if="form.errors.subscription_status" class="text-sm text-red-600 dark:text-red-400">
                      {{ form.errors.subscription_status }}
                    </p>
                  </div>
                </div>

                <div class="space-y-2">
                  <Label for="address" class="text-sm font-medium text-gray-700 dark:text-gray-300 flex items-center">
                    <MapPin class="w-4 h-4 mr-2" />
                    Physical Address *
                  </Label>
                  <Textarea
                    id="address"
                    v-model="form.address"
                    placeholder="Enter complete clinic address"
                    rows="3"
                    :class="{ 'border-red-500 focus:border-red-500': form.errors.address }"
                    required
                  />
                  <p v-if="form.errors.address" class="text-sm text-red-600 dark:text-red-400">
                    {{ form.errors.address }}
                  </p>
                </div>

                <!-- Admin User Section -->
                <div class="space-y-6">
                  <div class="flex items-center mb-4">
                    <div class="h-px bg-gray-200 dark:bg-gray-700 flex-1"></div>
                    <span class="px-4 text-sm font-medium text-gray-500 dark:text-gray-400">Admin User Account</span>
                    <div class="h-px bg-gray-200 dark:bg-gray-700 flex-1"></div>
                  </div>

                  <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-4">
                    <div class="flex items-start space-x-3">
                      <User class="w-5 h-5 text-blue-600 dark:text-blue-400 mt-0.5" />
                      <div>
                        <h4 class="text-sm font-medium text-blue-900 dark:text-blue-100">Create Clinic Administrator</h4>
                        <p class="text-xs text-blue-700 dark:text-blue-300 mt-1">
                          This user will have full administrative access to manage this clinic and its staff.
                        </p>
                      </div>
                    </div>
                  </div>

                  <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                    <div class="space-y-2">
                      <Label for="admin_name" class="text-sm font-medium text-gray-700 dark:text-gray-300 flex items-center">
                        <User class="w-4 h-4 mr-2" />
                        Admin Name *
                      </Label>
                      <Input
                        id="admin_name"
                        v-model="form.admin_name"
                        placeholder="Enter administrator's full name"
                        :class="{ 'border-red-500 focus:border-red-500': form.errors.admin_name }"
                        required
                      />
                      <p v-if="form.errors.admin_name" class="text-sm text-red-600 dark:text-red-400">
                        {{ form.errors.admin_name }}
                      </p>
                    </div>

                    <div class="space-y-2">
                      <Label for="admin_email" class="text-sm font-medium text-gray-700 dark:text-gray-300 flex items-center">
                        <Mail class="w-4 h-4 mr-2" />
                        Admin Email *
                      </Label>
                      <Input
                        id="admin_email"
                        type="email"
                        v-model="form.admin_email"
                        placeholder="admin@clinic.com"
                        :class="{ 'border-red-500 focus:border-red-500': form.errors.admin_email }"
                        required
                      />
                      <p v-if="form.errors.admin_email" class="text-sm text-red-600 dark:text-red-400">
                        {{ form.errors.admin_email }}
                      </p>
                    </div>

                    <div class="space-y-2">
                      <Label for="admin_password" class="text-sm font-medium text-gray-700 dark:text-gray-300 flex items-center">
                        <Lock class="w-4 h-4 mr-2" />
                        Password *
                      </Label>
                      <Input
                        id="admin_password"
                        type="password"
                        v-model="form.admin_password"
                        placeholder="Create a strong password"
                        :class="{ 'border-red-500 focus:border-red-500': form.errors.admin_password }"
                        required
                      />
                      <p v-if="form.errors.admin_password" class="text-sm text-red-600 dark:text-red-400">
                        {{ form.errors.admin_password }}
                      </p>
                    </div>

                    <div class="space-y-2">
                      <Label for="admin_password_confirmation" class="text-sm font-medium text-gray-700 dark:text-gray-300 flex items-center">
                        <Lock class="w-4 h-4 mr-2" />
                        Confirm Password *
                      </Label>
                      <Input
                        id="admin_password_confirmation"
                        type="password"
                        v-model="form.admin_password_confirmation"
                        placeholder="Re-enter the password"
                        :class="{ 'border-red-500 focus:border-red-500': form.errors.admin_password_confirmation }"
                        required
                      />
                      <p v-if="form.errors.admin_password_confirmation" class="text-sm text-red-600 dark:text-red-400">
                        {{ form.errors.admin_password_confirmation }}
                      </p>
                    </div>
                  </div>
                </div>

                <div class="flex items-center space-x-3 p-4 bg-gray-50 dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700">
                  <Checkbox 
                    id="is_active" 
                    v-model:checked="form.is_active"
                    class="data-[state=checked]:bg-blue-600 data-[state=checked]:border-blue-600"
                  />
                  <Label for="is_active" class="text-sm font-medium text-gray-900 dark:text-gray-100 cursor-pointer">
                    Activate clinic immediately
                  </Label>
                  <div class="ml-auto">
                    <Badge 
                      :variant="form.is_active ? 'default' : 'secondary'"
                      :class="form.is_active 
                        ? 'bg-green-100 text-green-800 border-green-200 dark:bg-green-900/20 dark:text-green-400 dark:border-green-800' 
                        : 'bg-gray-100 text-gray-800 border-gray-200 dark:bg-gray-900/20 dark:text-gray-400 dark:border-gray-800'"
                    >
                      {{ form.is_active ? 'Will be active' : 'Will be inactive' }}
                    </Badge>
                  </div>
                </div>
              </div>

              <!-- Action Buttons -->
              <div class="flex justify-between items-center pt-6 border-t border-gray-200 dark:border-gray-700">
                <div class="text-sm text-gray-500 dark:text-gray-400">
                  * Required fields
                </div>
                <div class="flex items-center space-x-3">
                  <Link
                    :href="route('clinics.index')"
                    class="inline-flex items-center px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg shadow-sm text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-all duration-200"
                  >
                    <X class="w-4 h-4 mr-2" />
                    Cancel
                  </Link>
                  <Button
                    type="submit"
                    :disabled="form.processing"
                    class="inline-flex items-center px-6 py-2 bg-gradient-to-r from-blue-600 to-cyan-600 hover:from-blue-700 hover:to-cyan-700 text-white shadow-lg hover:shadow-xl transition-all duration-200 disabled:opacity-50 disabled:cursor-not-allowed"
                  >
                    <Save class="w-4 h-4 mr-2" />
                    {{ form.processing ? 'Creating...' : 'Create Clinic' }}
                  </Button>
                </div>
              </div>
            </form>
          </CardContent>
        </Card>
      </div>
    </div>
  </AppLayout>
</template>
