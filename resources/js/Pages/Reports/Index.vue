<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { ref } from 'vue';
import { Bar, Pie } from 'vue-chartjs';
import {
  Chart as ChartJS,
  CategoryScale,
  LinearScale,
  BarElement,
  ArcElement,
  Title,
  Tooltip,
  Legend,
} from 'chart.js';
import { Card, CardContent, CardHeader, CardTitle } from '@/Components/ui/card';
import AppLayout from '@/Layouts/AppLayout.vue';

ChartJS.register(CategoryScale, LinearScale, BarElement, ArcElement, Title, Tooltip, Legend);

interface RevenueData {
  month: number;
  total: number;
}

interface TreatmentTrend {
  procedure: string;
  count: number;
}

interface Props {
  revenueData: RevenueData[];
  treatmentTrends: TreatmentTrend[];
}

const props = defineProps<Props>();

const barData = ref({
  labels: props.revenueData.map(d => `Month ${d.month}`),
  datasets: [
    {
      label: 'Revenue',
      data: props.revenueData.map(d => d.total),
      backgroundColor: 'hsl(var(--primary))',
    },
  ],
});

const barOptions = ref({
  responsive: true,
  plugins: { legend: { position: 'top' } },
});

const pieData = ref({
  labels: props.treatmentTrends.map(t => t.procedure),
  datasets: [
    {
      data: props.treatmentTrends.map(t => t.count),
      backgroundColor: ['#3B82F6', '#10B981', '#EF4444', '#F59E0B', '#8B5CF6'],
    },
  ],
});

const pieOptions = ref({
  responsive: true,
  plugins: { legend: { position: 'top' } },
});
</script>

<template>
  <AppLayout title="Reports">
    <Head title="Reports" />
    <div class="space-y-6">
      <h1 class="text-3xl font-bold">Analytics & Reports</h1>
      <div class="grid md:grid-cols-2 gap-6">
        <Card>
          <CardHeader>
            <CardTitle>Monthly Revenue</CardTitle>
          </CardHeader>
          <CardContent>
            <Bar :data="barData" :options="barOptions" />
          </CardContent>
        </Card>
        <Card>
          <CardHeader>
            <CardTitle>Treatment Trends</CardTitle>
          </CardHeader>
          <CardContent>
            <Pie :data="pieData" :options="pieOptions" />
          </CardContent>
        </Card>
      </div>
    </div>
  </AppLayout>
</template>