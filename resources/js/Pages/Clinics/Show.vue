<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/Components/ui/card';
import { Badge } from '@/Components/ui/badge';
import { Button } from '@/Components/ui/button';
import { Label } from '@/Components/ui/label';
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuTrigger } from '@/components/ui/dropdown-menu';
import { Building2, Users, Calendar, Mail, Phone, MapPin, Settings, CreditCard, Edit, ArrowLeft, Shield, Activity, MoreVertical } from 'lucide-vue-next';
interface User {
  id: number;
  name: string;
  email: string;
  roles: Array<{
    name: string;
    display_name: string;
  }>;
}
interface Clinic {
  id: number;
  name: string;
  email: string;
  phone: string;
  address: string;
  is_active: boolean;
  subscription_status: string;
  users_count: number;
  created_at: string;
  users: User[];
}
const props = defineProps<{
  clinic: Clinic;
}>();

const page = usePage();
const currentUser = computed(() => page.props.auth.user);

const isCurrentUserClinic = computed(() => {
  return currentUser.value?.clinic_id === props.clinic.id;
});
const formatDate = (dateString: string) => {
  return new Date(dateString).toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'long',
    day: 'numeric'
  });
};
const subscriptionStatus = computed(() => {
  const status = props.clinic.subscription_status;
  switch (status) {
    case 'active':
      return { class: 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400', text: 'Active' };
    case 'trial':
      return { class: 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400', text: 'Trial' };
    case 'expired':
      return { class: 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-400', text: 'Expired' };
    case 'cancelled':
      return { class: 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400', text: 'Cancelled' };
    default:
      return { class: 'bg-gray-100 text-gray-700 dark:bg-gray-900/30 dark:text-gray-400', text: status };
  }
});
const formatRoleName = (roleName: string) => {
  const roleMap: Record<string, string> = {
    'admin': 'Administrator',
    'dentist': 'Dentist',
    'assistant': 'Assistant',
    'receptionist': 'Receptionist',
    'super-admin': 'Super Admin'
  };
  return roleMap[roleName] || roleName;
};
</script>
<template>
  <Head :title="`${clinic.name} - Clinic Details`" />
  <AppLayout :title="`${clinic.name} - Clinic Details`">
    <template #header>
      <div class="flex justify-between items-center">
        <div>
          <h1 class="text-4xl font-bold bg-gradient-to-r from-blue-600 to-cyan-600 bg-clip-text text-transparent mb-2">
            {{ clinic.name }}
          </h1>
          <p class="text-gray-600 dark:text-gray-400 text-lg">
            Clinic overview and management
          </p>
        </div>
        <div class="flex items-center gap-3">
          <DropdownMenu>
            <DropdownMenuTrigger as-child>
              <Button variant="outline" size="sm">
                <MoreVertical class="h-4 w-4" />
              </Button>
            </DropdownMenuTrigger>
            <DropdownMenuContent align="end">
              <DropdownMenuItem @click="$inertia.visit(route('clinics.billing', clinic.id))">
                <CreditCard class="mr-2 h-4 w-4" />
                Billing
              </DropdownMenuItem>
              <DropdownMenuItem @click="$inertia.visit(route('clinics.settings', clinic.id))">
                <Settings class="mr-2 h-4 w-4" />
                Settings
              </DropdownMenuItem>
              <DropdownMenuItem @click="$inertia.visit(route('clinics.edit', clinic.id))">
                <Edit class="mr-2 h-4 w-4" />
                Edit Clinic
              </DropdownMenuItem>
            </DropdownMenuContent>
          </DropdownMenu>
        </div>
      </div>
    </template>
    <div class="py-6">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Status Cards -->
        <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4 mb-6">
          <Card class="bg-white dark:bg-gray-900 border-0 shadow-lg">
            <CardContent class="p-6">
              <div class="flex items-center">
                <div class="p-3 bg-blue-100 dark:bg-blue-900/30 rounded-lg">
                  <Users class="h-6 w-6 text-blue-600 dark:text-blue-400" />
                </div>
                <div class="ml-5">
                  <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Total Users</p>
                  <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ clinic.users_count || 0 }}</p>
                </div>
              </div>
            </CardContent>
          </Card>
          <Card class="bg-white dark:bg-gray-900 border-0 shadow-lg">
            <CardContent class="p-6">
              <div class="flex items-center">
                <div class="p-3 rounded-lg" :class="subscriptionStatus.class">
                  <Activity class="h-6 w-6" :class="subscriptionStatus.class.includes('green') ? 'text-green-600 dark:text-green-400' : subscriptionStatus.class.includes('blue') ? 'text-blue-600 dark:text-blue-400' : subscriptionStatus.class.includes('yellow') ? 'text-yellow-600 dark:text-yellow-400' : 'text-red-600 dark:text-red-400'" />
                </div>
                <div class="ml-5">
                  <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Subscription</p>
                  <Badge :variant="clinic.subscription_status === 'active' ? 'default' : 'secondary'" :class="subscriptionStatus.class">
                    {{ subscriptionStatus.text }}
                  </Badge>
                </div>
              </div>
            </CardContent>
          </Card>
          <Card class="bg-white dark:bg-gray-900 border-0 shadow-lg">
            <CardContent class="p-6">
              <div class="flex items-center">
                <div class="p-3 rounded-lg" :class="clinic.is_active ? 'bg-green-100 dark:bg-green-900/30' : 'bg-red-100 dark:bg-red-900/30'">
                  <Shield class="h-6 w-6" :class="clinic.is_active ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400'" />
                </div>
                <div class="ml-5">
                  <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Account Status</p>
                  <Badge :variant="clinic.is_active ? 'default' : 'secondary'" :class="clinic.is_active ? 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400' : 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400'">
                    {{ clinic.is_active ? 'Active' : 'Inactive' }}
                  </Badge>
                </div>
              </div>
            </CardContent>
          </Card>
          <Card class="bg-white dark:bg-gray-900 border-0 shadow-lg">
            <CardContent class="p-6">
              <div class="flex items-center">
                <div class="p-3 bg-gray-100 dark:bg-gray-800 rounded-lg">
                  <Calendar class="h-6 w-6 text-gray-600 dark:text-gray-400" />
                </div>
                <div class="ml-5">
                  <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Created</p>
                  <p class="text-sm font-medium text-gray-900 dark:text-white">{{ formatDate(clinic.created_at) }}</p>
                </div>
              </div>
            </CardContent>
          </Card>
        </div>
        <!-- Main Content -->
        <Card class="bg-white dark:bg-gray-900 border-0 shadow-xl">
          <CardHeader>
            <div class="flex items-center gap-3">
              <Building2 class="h-6 w-6 text-gray-400" />
              <CardTitle class="text-2xl text-gray-900 dark:text-white">Clinic Information</CardTitle>
            </div>
            <CardDescription class="text-gray-600 dark:text-gray-400">
              Complete clinic details and configuration
            </CardDescription>
          </CardHeader>
          <CardContent>
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
              <!-- Contact Information -->
              <div class="space-y-4">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white flex items-center gap-2">
                  <Mail class="h-5 w-5 text-gray-400" />
                  Contact Information
                </h3>
                <div class="space-y-4">
                  <div class="flex items-start gap-3">
                    <MapPin class="h-4 w-4 text-gray-400 mt-0.5" />
                    <div class="flex-1">
                      <Label class="text-sm font-medium">Address</Label>
                      <p class="text-sm mt-1 text-gray-700 dark:text-gray-300">{{ clinic.address }}</p>
                    </div>
                  </div>
                  <div class="flex items-start gap-3">
                    <Mail class="h-4 w-4 text-gray-400 mt-0.5" />
                    <div class="flex-1">
                      <Label class="text-sm font-medium">Email</Label>
                      <p class="text-sm mt-1 text-gray-700 dark:text-gray-300">{{ clinic.email }}</p>
                    </div>
                  </div>
                  <div class="flex items-start gap-3">
                    <Phone class="h-4 w-4 text-gray-400 mt-0.5" />
                    <div class="flex-1">
                      <Label class="text-sm font-medium">Phone</Label>
                      <p class="text-sm mt-1 text-gray-700 dark:text-gray-300">{{ clinic.phone }}</p>
                    </div>
                  </div>
                </div>
              </div>
              <!-- Users Section -->
              <div class="space-y-4">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white flex items-center gap-2">
                  <Users class="h-5 w-5 text-gray-400" />
                  Team Members ({{ clinic.users.length }})
                </h3>
                <div class="space-y-2 max-h-64 overflow-y-auto">
                  <div v-for="user in clinic.users" :key="user.id" class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-800 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors">
                    <div class="flex items-center gap-3">
                      <div class="h-8 w-8 bg-blue-100 dark:bg-blue-900/30 rounded-full flex items-center justify-center">
                        <span class="text-xs font-medium text-blue-600 dark:text-blue-400">{{ user.name.charAt(0).toUpperCase() }}</span>
                      </div>
                      <div>
                        <p class="text-sm font-medium text-gray-900 dark:text-white">{{ user.name }}</p>
                        <p class="text-xs text-gray-600 dark:text-gray-400">{{ user.email }}</p>
                      </div>
                    </div>
                    <div class="flex gap-1">
                      <Badge v-for="role in user.roles" :key="role.name" variant="secondary" class="bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400 text-xs">
                        {{ formatRoleName(role.name) }}
                      </Badge>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </CardContent>
        </Card>
        <!-- Back Button -->
        <div class="mt-6">
          <Button @click="$inertia.visit(route('clinics.index'))" variant="outline" class="text-gray-600 dark:text-gray-400 border-gray-300 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-800">
            <ArrowLeft class="w-4 h-4 mr-2" />
            Back to Clinics
          </Button>
        </div>
      </div>
    </div>
  </AppLayout>
</template>
