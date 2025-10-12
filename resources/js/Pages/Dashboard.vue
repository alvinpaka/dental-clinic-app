<script setup lang="ts">
import { ref, computed } from 'vue';
import { Bar } from 'vue-chartjs';
import {
  Chart as ChartJS,
  CategoryScale,
  LinearScale,
  BarElement,
  Title,
  Tooltip,
  Legend,
} from 'chart.js';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/Components/ui/card';
import { Button } from '@/Components/ui/button';
import { Badge } from '@/Components/ui/badge';
import { Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

ChartJS.register(CategoryScale, LinearScale, BarElement, Title, Tooltip, Legend);

interface Stats {
  total_patients: number;
  upcoming_appointments: number;
  monthly_revenue: number;
  low_stock_items: number;
}

interface User {
  id: number;
  name: string;
  email: string;
}

interface Auth {
  user: User;
}

const props = defineProps<{
  auth: Auth;
  stats: Stats;
}>();

const currentTime = ref(new Date());

const greeting = computed(() => {
  const hour = currentTime.value.getHours();
  if (hour < 12) return 'Good morning';
  if (hour < 18) return 'Good afternoon';
  return 'Good evening';
});

const chartData = ref({
  labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'],
  datasets: [
    {
      label: 'Revenue (UGX)',
      data: [6500, 5900, 8000, 8100, 5600, 8500],
      backgroundColor: [
        'rgba(59, 130, 246, 0.8)',
        'rgba(16, 185, 129, 0.8)',
        'rgba(245, 158, 11, 0.8)',
        'rgba(239, 68, 68, 0.8)',
        'rgba(139, 92, 246, 0.8)',
        'rgba(236, 72, 153, 0.8)',
      ],
      borderColor: [
        'rgb(59, 130, 246)',
        'rgb(16, 185, 129)',
        'rgb(245, 158, 11)',
        'rgb(239, 68, 68)',
        'rgb(139, 92, 246)',
        'rgb(236, 72, 153)',
      ],
      borderWidth: 1,
    },
  ],
});

const chartOptions = ref({
  responsive: true,
  maintainAspectRatio: false,
  plugins: {
    legend: {
      position: 'top' as const,
      labels: {
        usePointStyle: true,
        padding: 20,
      },
    },
    tooltip: {
      backgroundColor: 'rgba(0, 0, 0, 0.8)',
      titleColor: 'white',
      bodyColor: 'white',
      borderColor: 'rgba(59, 130, 246, 0.5)',
      borderWidth: 1,
    },
  },
  scales: {
    y: {
      beginAtZero: true,
      ticks: {
        callback: function(value: any) {
          return 'UGX' + value.toLocaleString();
        },
      },
    },
  },
});

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
    value: `UGX${props.stats.monthly_revenue.toLocaleString()}`,
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
              <h1 class="text-4xl font-bold bg-gradient-to-r from-blue-600 to-cyan-600 bg-clip-text text-transparent mb-2">
                {{ greeting }}, {{ auth.user.name }}!
              </h1>
              <p class="text-gray-600 dark:text-gray-400 text-lg">
                Welcome back to your dental practice dashboard. Here's what's happening today.
              </p>
            </div>
            <div class="hidden md:block">
              <Badge class="bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400 border-0 px-4 py-2">
                <i class="fas fa-circle text-green-500 mr-2"></i>
                All Systems Operational
              </Badge>
            </div>
          </div>
        </div>

        <!-- Stats Cards -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
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
          <!-- Revenue Chart -->
          <div class="lg:col-span-2">
            <Card class="border-0 shadow-xl bg-white dark:bg-gray-900">
              <CardHeader class="pb-4">
                <div class="flex items-center justify-between">
                  <div>
                    <CardTitle class="text-2xl text-gray-900 dark:text-white">Revenue Overview</CardTitle>
                    <CardDescription class="text-gray-600 dark:text-gray-400">
                      Monthly revenue performance for the last 6 months
                    </CardDescription>
                  </div>
                  <Badge class="bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400">
                    <i class="fas fa-arrow-up mr-1"></i>
                    +12% from last month
                  </Badge>
                </div>
              </CardHeader>
              <CardContent>
                <div class="h-80">
                  <Bar :data="chartData" :options="chartOptions" />
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
                <div class="space-y-4">
                  <div class="flex items-start space-x-3">
                    <div class="w-8 h-8 rounded-full bg-blue-100 dark:bg-blue-900/30 flex items-center justify-center flex-shrink-0">
                      <i class="fas fa-user-plus text-blue-600 dark:text-blue-400"></i>
                    </div>
                    <div class="flex-1 min-w-0">
                      <p class="text-sm font-medium text-gray-900 dark:text-white">New patient registered</p>
                      <p class="text-xs text-gray-500 dark:text-gray-400">Sarah Johnson - 2 hours ago</p>
                    </div>
                  </div>

                  <div class="flex items-start space-x-3">
                    <div class="w-8 h-8 rounded-full bg-green-100 dark:bg-green-900/30 flex items-center justify-center flex-shrink-0">
                      <i class="fas fa-calendar-check text-green-600 dark:text-green-400"></i>
                    </div>
                    <div class="flex-1 min-w-0">
                      <p class="text-sm font-medium text-gray-900 dark:text-white">Appointment completed</p>
                      <p class="text-xs text-gray-500 dark:text-gray-400">Mike Chen - Dental cleaning - 4 hours ago</p>
                    </div>
                  </div>

                  <div class="flex items-start space-x-3">
                    <div class="w-8 h-8 rounded-full bg-purple-100 dark:bg-purple-900/30 flex items-center justify-center flex-shrink-0">
                      <i class="fa-solid fa-money-bill-1 text-purple-600 dark:text-purple-400"></i>
                    </div>
                    <div class="flex-1 min-w-0">
                      <p class="text-sm font-medium text-gray-900 dark:text-white">Payment received</p>
                      <p class="text-xs text-gray-500 dark:text-gray-400">UGX250 - Invoice #1234 - 6 hours ago</p>
                    </div>
                  </div>
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