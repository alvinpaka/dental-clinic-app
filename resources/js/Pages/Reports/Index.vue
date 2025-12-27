<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import { Line, Bar, Doughnut } from 'vue-chartjs';
import {
  Chart as ChartJS,
  CategoryScale,
  LinearScale,
  PointElement,
  LineElement,
  BarElement,
  ArcElement,
  Title,
  Tooltip,
  Legend,
  Filler,
} from 'chart.js';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/Components/ui/card';
import { Button } from '@/Components/ui/button';
import { 
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/Components/ui/select';
import AppLayout from '@/Layouts/AppLayout.vue';
import { TrendingUp, TrendingDown, DollarSign, Users, Activity, Percent } from 'lucide-vue-next';

ChartJS.register(
  CategoryScale,
  LinearScale,
  PointElement,
  LineElement,
  BarElement,
  ArcElement,
  Title,
  Tooltip,
  Legend,
  Filler
);

interface FinancialData {
  period: string;
  total: number;
}

interface TreatmentTrend {
  procedure: string;
  count: number;
}

interface Stats {
  totalRevenue: number;
  totalInventoryCosts: number;
  totalOperationalExpenses: number;
  totalExpenses: number;
  netProfit: number;
  profitMargin: number;
  totalPatients: number;
  totalTreatments: number;
}

interface Props {
  revenueData: FinancialData[];
  inventoryCosts: FinancialData[];
  operationalExpenses: FinancialData[];
  treatmentTrends: TreatmentTrend[];
  stats: Stats;
  currentPeriod: string;
}

const props = defineProps<Props>();

const selectedPeriod = ref(props.currentPeriod);

const changePeriod = (period: string) => {
  selectedPeriod.value = period;
  router.get(route('reports.index'), { period }, {
    preserveState: true,
    preserveScroll: true,
  });
};

// Revenue vs Expenses Line Chart
const financialChartData = computed(() => ({
  labels: props.revenueData.map(d => d.period),
  datasets: [
    {
      label: 'Revenue',
      data: props.revenueData.map(d => d.total),
      borderColor: '#10B981',
      backgroundColor: 'rgba(16, 185, 129, 0.1)',
      tension: 0.4,
      fill: true,
    },
    {
      label: 'Inventory Costs',
      data: props.inventoryCosts.map(d => d.total),
      borderColor: '#F59E0B',
      backgroundColor: 'rgba(245, 158, 11, 0.1)',
      tension: 0.4,
      fill: false,
    },
    {
      label: 'Operational Expenses',
      data: props.operationalExpenses.map(d => d.total),
      borderColor: '#EF4444',
      backgroundColor: 'rgba(239, 68, 68, 0.1)',
      tension: 0.4,
      fill: false,
    },
  ],
}));

const financialChartOptions = ref({
  responsive: true,
  maintainAspectRatio: false,
  interaction: {
    mode: 'index',
    intersect: false,
  },
  plugins: {
    legend: {
      position: 'top',
    },
    title: {
      display: false,
    },
  },
  scales: {
    y: {
      beginAtZero: true,
      ticks: {
        callback: function(value: number) {
          return 'UGX' + value.toLocaleString();
        }
      }
    },
  },
});

// Treatment Trends Doughnut Chart
const treatmentChartData = computed(() => ({
  labels: props.treatmentTrends.map(t => t.procedure),
  datasets: [
    {
      data: props.treatmentTrends.map(t => t.count),
      backgroundColor: [
        '#3B82F6',
        '#10B981',
        '#F59E0B',
        '#EF4444',
        '#8B5CF6',
        '#EC4899',
        '#14B8A6',
        '#F97316',
        '#6366F1',
        '#06B6D4',
      ],
      borderWidth: 2,
      borderColor: '#fff',
    },
  ],
}));

const treatmentChartOptions = ref({
  responsive: true,
  maintainAspectRatio: false,
  plugins: {
    legend: {
      position: 'right',
    },
  },
});

const formatCurrency = (amount: number) => {
  const whole = Math.round(amount);
  return `UGX ${whole.toLocaleString('en-US')}`;
};

</script>

<template>
  <AppLayout title="Reports">
    <Head title="Reports" />
    <div class="px-4 py-8 space-y-6">
      <!-- Header -->
      <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
        <div>
          <h1 class="text-3xl font-bold text-[#045c4b] dark:text-white">Financial Reports</h1>
          <p class="text-gray-600 dark:text-gray-400 mt-1">Track revenue, expenses, and business performance</p>
        </div>
        
        <!-- Period Selector -->
        <Select v-model="selectedPeriod" @update:model-value="changePeriod">
          <SelectTrigger class="w-[180px]">
            <SelectValue placeholder="Select Period" />
          </SelectTrigger>
          <SelectContent>
            <SelectItem value="daily">Daily (30 days)</SelectItem>
            <SelectItem value="weekly">Weekly (12 weeks)</SelectItem>
            <SelectItem value="monthly">Monthly (12 months)</SelectItem>
            <SelectItem value="annually">Annually (5 years)</SelectItem>
          </SelectContent>
        </Select>
      </div>

      <!-- Stats Cards -->
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        <!-- Total Revenue -->
        <Card>
          <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle class="text-sm font-medium">Total Revenue</CardTitle>
            <DollarSign class="h-4 w-4 text-green-600" />
          </CardHeader>
          <CardContent>
            <div class="text-2xl font-bold text-green-600">{{ formatCurrency(stats.totalRevenue) }}</div>
            <p class="text-xs text-gray-600 dark:text-gray-400 mt-1">
              <TrendingUp class="inline h-3 w-3" /> Income from paid invoices
            </p>
          </CardContent>
        </Card>

        <!-- Total Inventory Costs -->
        <Card>
          <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle class="text-sm font-medium">Inventory Costs</CardTitle>
            <TrendingDown class="h-4 w-4 text-amber-600" />
          </CardHeader>
          <CardContent>
            <div class="text-2xl font-bold text-amber-600">{{ formatCurrency(stats.totalInventoryCosts) }}</div>
            <p class="text-xs text-gray-600 dark:text-gray-400 mt-1">
              Cost of dental supplies & equipment
            </p>
          </CardContent>
        </Card>

        <!-- Total Operational Expenses -->
        <Card>
          <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle class="text-sm font-medium">Operational Expenses</CardTitle>
            <TrendingDown class="h-4 w-4 text-red-600" />
          </CardHeader>
          <CardContent>
            <div class="text-2xl font-bold text-red-600">{{ formatCurrency(stats.totalOperationalExpenses) }}</div>
            <p class="text-xs text-gray-600 dark:text-gray-400 mt-1">
              Business overhead costs
            </p>
          </CardContent>
        </Card>

        <!-- Total Expenses (Combined) -->
        <Card>
          <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle class="text-sm font-medium">Total Expenses</CardTitle>
            <TrendingDown class="h-4 w-4 text-red-600" />
          </CardHeader>
          <CardContent>
            <div class="text-2xl font-bold text-red-600">{{ formatCurrency(stats.totalExpenses) }}</div>
            <p class="text-xs text-gray-600 dark:text-gray-400 mt-1">
              Inventory + operational costs
            </p>
          </CardContent>
        </Card>

        <!-- Net Profit -->
        <Card>
          <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle class="text-sm font-medium">Net Profit</CardTitle>
            <Activity class="h-4 w-4" :class="stats.netProfit >= 0 ? 'text-green-600' : 'text-red-600'" />
          </CardHeader>
          <CardContent>
            <div class="text-2xl font-bold" :class="stats.netProfit >= 0 ? 'text-green-600' : 'text-red-600'">
              {{ formatCurrency(stats.netProfit) }}
            </div>
            <p class="text-xs text-gray-600 dark:text-gray-400 mt-1">
              Revenue minus total expenses
            </p>
          </CardContent>
        </Card>

        <!-- Profit Margin -->
        <Card>
          <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle class="text-sm font-medium">Profit Margin</CardTitle>
            <Percent class="h-4 w-4 text-blue-600" />
          </CardHeader>
          <CardContent>
            <div class="text-2xl font-bold text-blue-600">{{ stats.profitMargin }}%</div>
            <p class="text-xs text-gray-600 dark:text-gray-400 mt-1">
              Profitability ratio
            </p>
          </CardContent>
        </Card>

        <!-- Total Patients -->
        <Card>
          <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle class="text-sm font-medium">Total Patients</CardTitle>
            <Users class="h-4 w-4 text-purple-600" />
          </CardHeader>
          <CardContent>
            <div class="text-2xl font-bold text-purple-600">{{ stats.totalPatients }}</div>
            <p class="text-xs text-gray-600 dark:text-gray-400 mt-1">
              Unique patients served
            </p>
          </CardContent>
        </Card>

        <!-- Total Treatments -->
        <Card>
          <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle class="text-sm font-medium">Total Treatments</CardTitle>
            <Activity class="h-4 w-4 text-cyan-600" />
          </CardHeader>
          <CardContent>
            <div class="text-2xl font-bold text-cyan-600">{{ stats.totalTreatments }}</div>
            <p class="text-xs text-gray-600 dark:text-gray-400 mt-1">
              Procedures completed
            </p>
          </CardContent>
        </Card>
      </div>

      <!-- Charts -->
      <div class="grid lg:grid-cols-3 gap-6">
        <!-- Revenue vs Expenses Trend -->
        <Card class="lg:col-span-2">
          <CardHeader>
            <CardTitle>Revenue vs Inventory Costs vs Operational Expenses</CardTitle>
            <CardDescription>Financial breakdown showing revenue, inventory costs, and operational expenses over time</CardDescription>
          </CardHeader>
          <CardContent>
            <div class="h-[400px]">
              <Line :data="financialChartData" :options="financialChartOptions" />
            </div>
          </CardContent>
        </Card>

        <!-- Treatment Distribution -->
        <Card>
          <CardHeader>
            <CardTitle>Treatment Distribution</CardTitle>
            <CardDescription>Most common procedures</CardDescription>
          </CardHeader>
          <CardContent>
            <div class="h-[400px]">
              <Doughnut :data="treatmentChartData" :options="treatmentChartOptions" />
            </div>
          </CardContent>
        </Card>
      </div>
    </div>
  </AppLayout>
</template>