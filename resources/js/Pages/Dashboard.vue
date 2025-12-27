<script setup lang="ts">
import { ref, computed } from 'vue';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/Components/ui/card';
import { Button } from '@/Components/ui/button';
import { Badge } from '@/Components/ui/badge';
import { Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

interface Stats {
  total_patients: number;
  upcoming_appointments: number;
  monthly_revenue: number;
  low_stock_items: number;
}

interface TodaysAppointment {
  id: number;
  patient_name: string;
  time: string;
  status: string;
  notes: string | null;
}

interface Activity {
  type: string;
  icon: string;
  color: string;
  message: string;
  time: string;
}

interface PendingTasks {
  unpaid_invoices: number;
  pending_appointments: number;
  low_stock_count: number;
}

interface User {
  id: number;
  name: string;
  email: string;
}

interface Auth {
  user: User;
}

interface Props {
  auth: Auth;
  stats: Stats;
  todaysAppointments: TodaysAppointment[];
  recentActivity: Activity[];
  pendingTasks: PendingTasks;
  appointmentStatuses?: any;
}

const props = withDefaults(defineProps<Props>(), {
  appointmentStatuses: () => ({}),
});

const currentTime = ref(new Date());

const formatUGX = (value: number) => {
  const whole = Math.round(value);
  return `UGX ${whole.toLocaleString('en-US')}`;
};

const greeting = computed(() => {
  const hour = currentTime.value.getHours();
  if (hour < 12) return 'Good morning';
  if (hour < 18) return 'Good afternoon';
  return 'Good evening';
});

const getStatusColor = (status: string) => {
  const colors: Record<string, string> = {
    confirmed: 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400',
    pending: 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-400',
    cancelled: 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400',
    completed: 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400',
  };
  return colors[status] || 'bg-gray-100 text-gray-700 dark:bg-gray-900/30 dark:text-gray-400';
};

const getActivityIconColor = (color: string) => {
  const colors: Record<string, string> = {
    blue: 'text-blue-600 bg-blue-100 dark:bg-blue-900/30',
    green: 'text-green-600 bg-green-100 dark:bg-green-900/30',
    purple: 'text-purple-600 bg-purple-100 dark:bg-purple-900/30',
    red: 'text-red-600 bg-red-100 dark:bg-red-900/30',
  };
  return colors[color] || 'text-gray-600 bg-gray-100 dark:bg-gray-900/30';
};

const statCards = [
  {
    title: 'Total Patients',
    value: props.stats.total_patients,
    description: 'Active patients',
    icon: 'fas fa-users',
    color: 'from-blue-500 to-blue-600',
    bgColor: 'bg-blue-50 dark:bg-blue-900/20',
    textColor: 'text-blue-700 dark:text-blue-300',
  },
  {
    title: 'Upcoming Appointments',
    value: props.stats.upcoming_appointments,
    description: 'Next 7 days',
    icon: 'fas fa-calendar-check',
    color: 'from-green-500 to-green-600',
    bgColor: 'bg-green-50 dark:bg-green-900/20',
    textColor: 'text-green-700 dark:text-green-300',
  },
  {
    title: 'Monthly Revenue',
    value: formatUGX(props.stats.monthly_revenue),
    description: 'This month',
    icon: 'fa-solid fa-money-bill-1',
    color: 'from-purple-500 to-purple-600',
    bgColor: 'bg-purple-50 dark:bg-purple-900/20',
    textColor: 'text-purple-700 dark:text-purple-300',
  },
  {
    title: 'Low Stock Alerts',
    value: props.stats.low_stock_items,
    description: 'Items need restocking',
    icon: 'fas fa-exclamation-triangle',
    color: 'from-red-500 to-red-600',
    bgColor: 'bg-red-50 dark:bg-red-900/20',
    textColor: 'text-red-700 dark:text-red-300',
  },
];

const quickActions = [
  {
    title: 'View Patients',
    description: 'Manage patient records',
    icon: 'fas fa-users',
    href: route('patients.index'),
    color: 'from-blue-500 to-cyan-500',
  },
  {
    title: 'View Appointments',
    description: 'Manage appointments',
    icon: 'fas fa-calendar-check',
    href: route('appointments.index'),
    color: 'from-green-500 to-emerald-500',
  },
  {
    title: 'View Invoices',
    description: 'Manage invoices',
    icon: 'fa-solid fa-money-bill-1',
    href: route('invoices.index'),
    color: 'from-purple-500 to-indigo-500',
  },
  {
    title: 'View Treatments',
    description: 'Manage treatments',
    icon: 'fas fa-tooth',
    href: route('treatments.index'),
    color: 'from-orange-500 to-red-500',
  },
];
</script>

<template>
  <AppLayout title="Dashboard">
    <div class="min-h-screen bg-gradient-to-br from-blue-50 via-white to-cyan-50 dark:from-gray-900 dark:via-gray-800 dark:to-blue-900">
      <div class="container mx-auto px-4 py-8">
        <!-- Welcome Section -->
        <div class="mb-8">
          <div class="flex items-center justify-between">
            <div>
              <h1 class="text-4xl font-bold text-[#045c4b] dark:text-white mb-2">
                {{ greeting }}, {{ auth.user.name }}!
              </h1>
              <p class="text-gray-600 dark:text-gray-400 text-lg">
                Welcome back to your dental practice dashboard. Here's what's happening today.
              </p>
            </div>
            <div class="hidden md:block">
              <Badge class="bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400 px-3 py-1 font-medium rounded-md shadow-sm">
                <i class="fas fa-circle text-green-500 mr-2"></i>
                All Systems Operational
              </Badge>
            </div>
          </div>
        </div>

        <!-- Stats Cards -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
          <Card
            v-for="(stat, index) in statCards"
            :key="index"
            :class="['border-0 shadow-xl hover:shadow-2xl transition-all duration-300 hover:-translate-y-1', stat.bgColor]"
          >
            <CardContent class="p-6">
              <div class="flex items-center justify-between">
                <div>
                  <p class="text-sm font-medium text-gray-600 dark:text-gray-400 mb-1">{{ stat.title }}</p>
                  <p class="text-3xl font-bold text-gray-900 dark:text-white mb-1">{{ stat.value }}</p>
                  <p class="text-xs text-gray-500 dark:text-gray-400">{{ stat.description }}</p>
                </div>
                <div :class="['w-12 h-12 rounded-xl bg-gradient-to-br flex items-center justify-center shadow-lg', stat.color]">
                  <i :class="[stat.icon, 'text-white text-lg']"></i>
                </div>
              </div>
            </CardContent>
          </Card>
        </div>

        <!-- Main Content Grid -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
          <!-- Today's Appointments -->
          <div class="lg:col-span-2">
            <Card class="border-0 shadow-xl bg-white dark:bg-gray-900">
              <CardHeader class="pb-4">
                <div class="flex items-center justify-between">
                  <div>
                    <CardTitle class="text-2xl text-gray-900 dark:text-white">Today's Appointments</CardTitle>
                    <CardDescription class="text-gray-600 dark:text-gray-400">
                      Scheduled appointments for today
                    </CardDescription>
                  </div>
                  <Link :href="route('appointments.index')">
                    <Button variant="outline" size="sm">
                      View All
                    </Button>
                  </Link>
                </div>
              </CardHeader>
              <CardContent>
                <div v-if="todaysAppointments.length > 0" class="space-y-4">
                  <div 
                    v-for="appointment in todaysAppointments" 
                    :key="appointment.id"
                    class="flex items-center justify-between p-4 rounded-lg border border-gray-200 dark:border-gray-700 hover:shadow-md transition-shadow"
                  >
                    <div class="flex items-center space-x-4">
                      <div class="w-12 h-12 rounded-full bg-gradient-to-br from-blue-500 to-cyan-500 flex items-center justify-center">
                        <i class="fas fa-user text-white"></i>
                      </div>
                      <div>
                        <h4 class="font-semibold text-gray-900 dark:text-white">{{ appointment.patient_name }}</h4>
                        <p class="text-sm text-gray-500 dark:text-gray-400">{{ appointment.time }}</p>
                        <p v-if="appointment.notes" class="text-xs text-gray-400 dark:text-gray-500 mt-1">{{ appointment.notes }}</p>
                      </div>
                    </div>
                    <Badge :class="getStatusColor(appointment.status)">
                      {{ appointment.status }}
                    </Badge>
                  </div>
                </div>
                <div v-else class="text-center py-12">
                  <i class="fas fa-calendar-times text-6xl text-gray-300 dark:text-gray-600 mb-4"></i>
                  <p class="text-gray-500 dark:text-gray-400">No appointments scheduled for today</p>
                </div>
              </CardContent>
            </Card>

            <!-- Pending Tasks -->
            <Card class="border-0 shadow-xl bg-white dark:bg-gray-900 mt-8">
              <CardHeader>
                <CardTitle class="text-2xl text-gray-900 dark:text-white">Pending Tasks</CardTitle>
                <CardDescription class="text-gray-600 dark:text-gray-400">
                  Items that require your attention
                </CardDescription>
              </CardHeader>
              <CardContent>
                <div class="space-y-4">
                  <Link :href="route('invoices.index')" class="flex items-center justify-between p-4 rounded-lg bg-red-50 dark:bg-red-900/20 hover:shadow-md transition-shadow">
                    <div class="flex items-center space-x-3">
                      <div class="w-10 h-10 rounded-lg bg-red-100 dark:bg-red-900/30 flex items-center justify-center">
                        <i class="fas fa-file-invoice text-red-600 dark:text-red-400"></i>
                      </div>
                      <div>
                        <h4 class="font-semibold text-gray-900 dark:text-white">Unpaid Invoices</h4>
                        <p class="text-sm text-gray-600 dark:text-gray-400">Pending payment</p>
                      </div>
                    </div>
                    <Badge class="bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400">
                      {{ pendingTasks.unpaid_invoices }}
                    </Badge>
                  </Link>

                  <Link :href="route('appointments.index')" class="flex items-center justify-between p-4 rounded-lg bg-yellow-50 dark:bg-yellow-900/20 hover:shadow-md transition-shadow">
                    <div class="flex items-center space-x-3">
                      <div class="w-10 h-10 rounded-lg bg-yellow-100 dark:bg-yellow-900/30 flex items-center justify-center">
                        <i class="fas fa-calendar-alt text-yellow-600 dark:text-yellow-400"></i>
                      </div>
                      <div>
                        <h4 class="font-semibold text-gray-900 dark:text-white">Pending Appointments</h4>
                        <p class="text-sm text-gray-600 dark:text-gray-400">Awaiting confirmation</p>
                      </div>
                    </div>
                    <Badge class="bg-yellow-100 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-400">
                      {{ pendingTasks.pending_appointments }}
                    </Badge>
                  </Link>

                  <Link :href="route('inventory.index')" class="flex items-center justify-between p-4 rounded-lg bg-orange-50 dark:bg-orange-900/20 hover:shadow-md transition-shadow">
                    <div class="flex items-center space-x-3">
                      <div class="w-10 h-10 rounded-lg bg-orange-100 dark:bg-orange-900/30 flex items-center justify-center">
                        <i class="fas fa-boxes text-orange-600 dark:text-orange-400"></i>
                      </div>
                      <div>
                        <h4 class="font-semibold text-gray-900 dark:text-white">Low Stock Items</h4>
                        <p class="text-sm text-gray-600 dark:text-gray-400">Need restocking</p>
                      </div>
                    </div>
                    <Badge class="bg-orange-100 text-orange-700 dark:bg-orange-900/30 dark:text-orange-400">
                      {{ pendingTasks.low_stock_count }}
                    </Badge>
                  </Link>
                </div>
              </CardContent>
            </Card>
          </div>

          <!-- Quick Actions -->
          <div class="space-y-6">
            <Card class="border-0 shadow-xl bg-white dark:bg-gray-900">
              <CardHeader>
                <CardTitle class="text-xl text-gray-900 dark:text-white">Quick Actions</CardTitle>
                <CardDescription class="text-gray-600 dark:text-gray-400">
                  Common tasks to get you started
                </CardDescription>
              </CardHeader>
              <CardContent class="space-y-4">
                <Button
                  v-for="(action, index) in quickActions"
                  :key="index"
                  as-child
                  variant="outline"
                  class="w-full justify-start h-auto p-4 hover:shadow-md transition-all duration-200 group"
                >
                  <Link :href="action.href" class="flex items-center space-x-3">
                    <div :class="['w-10 h-10 rounded-lg bg-gradient-to-br flex items-center justify-center group-hover:scale-110 transition-transform', action.color]">
                      <i :class="[action.icon, 'text-white']"></i>
                    </div>
                    <div class="text-left">
                      <div class="font-medium text-gray-900 dark:text-white">{{ action.title }}</div>
                      <div class="text-sm text-gray-500 dark:text-gray-400">{{ action.description }}</div>
                    </div>
                  </Link>
                </Button>
              </CardContent>
            </Card>

            <!-- Recent Activity -->
            <Card class="border-0 shadow-xl bg-white dark:bg-gray-900">
              <CardHeader>
                <CardTitle class="text-xl text-gray-900 dark:text-white">Recent Activity</CardTitle>
                <CardDescription class="text-gray-600 dark:text-gray-400">
                  Latest updates from your practice
                </CardDescription>
              </CardHeader>
              <CardContent>
                <div v-if="recentActivity.length > 0" class="space-y-4 max-h-96 overflow-y-auto">
                  <div
                    v-for="(activity, index) in recentActivity"
                    :key="index"
                    class="flex items-start space-x-3"
                  >
                    <div :class="['w-8 h-8 rounded-full flex items-center justify-center flex-shrink-0', getActivityIconColor(activity.color)]">
                      <i :class="['fas', activity.icon]"></i>
                    </div>
                    <div class="flex-1 min-w-0">
                      <p class="text-sm font-medium text-gray-900 dark:text-white">{{ activity.message }}</p>
                      <p class="text-xs text-gray-500 dark:text-gray-400">{{ activity.time }}</p>
                    </div>
                  </div>
                </div>
                <div v-else class="text-center py-8">
                  <i class="fas fa-inbox text-4xl text-gray-300 dark:text-gray-600 mb-2"></i>
                  <p class="text-gray-500 dark:text-gray-400 text-sm">No recent activity</p>
                </div>
              </CardContent>
            </Card>
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<style scoped>
.bg-clip-text {
  -webkit-background-clip: text;
  background-clip: text;
  -webkit-text-fill-color: transparent;
}
</style>