<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { Button } from '@/Components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/Components/ui/card';
import { Badge } from '@/Components/ui/badge';
import AppLayout from '@/Layouts/AppLayout.vue';
import { ArrowLeft, Download } from 'lucide-vue-next';
import { route } from 'ziggy-js';

interface Invoice {
  id: number;
  patient: { name: string; email: string };
  treatment?: { procedure: string; cost: number };
  amount: number;
  status: string;
  due_date: string;
  pdf_path?: string;
  created_at: string;
}

interface Props {
  invoice: Invoice;
}

const props = defineProps<Props>();

const downloadPDF = () => {
  if (props.invoice.pdf_path) {
    window.open(route('invoices.show', { id: 'download', pdf_path: props.invoice.pdf_path }), '_blank');
  }
};
</script>

<template>
  <AppLayout :title="`Invoice #${props.invoice.id}`">
    <Head :title="`Invoice #${props.invoice.id}`" />

    <div class="space-y-6">
      <!-- Header -->
      <div class="flex items-center justify-between">
        <div class="flex items-center space-x-4">
          <Button variant="ghost" @click="$inertia.visit(route('invoices.index'))">
            <ArrowLeft class="mr-2 h-4 w-4" />
            Back to Invoices
          </Button>
          <div>
            <h1 class="text-3xl font-bold">Invoice #{{ props.invoice.id }}</h1>
            <p class="text-gray-600">Created on {{ new Date(props.invoice.created_at).toLocaleDateString() }}</p>
          </div>
        </div>
        <Button v-if="props.invoice.pdf_path" @click="downloadPDF" variant="outline">
          <Download class="mr-2 h-4 w-4" />
          Download PDF
        </Button>
      </div>

      <!-- Invoice Details -->
      <Card>
        <CardHeader>
          <CardTitle>Invoice Details</CardTitle>
          <CardDescription>Invoice information and payment status</CardDescription>
        </CardHeader>
        <CardContent class="grid grid-cols-2 gap-4">
          <div>
            <label class="text-sm font-medium text-gray-500">Patient</label>
            <p class="text-sm">{{ props.invoice.patient.name }} ({{ props.invoice.patient.email }})</p>
          </div>
          <div>
            <label class="text-sm font-medium text-gray-500">Amount</label>
            <p class="text-sm">${{ props.invoice.amount }}</p>
          </div>
          <div>
            <label class="text-sm font-medium text-gray-500">Due Date</label>
            <p class="text-sm">{{ new Date(props.invoice.due_date).toLocaleDateString() }}</p>
          </div>
          <div>
            <label class="text-sm font-medium text-gray-500">Status</label>
            <Badge :variant="props.invoice.status === 'paid' ? 'default' : props.invoice.status === 'overdue' ? 'destructive' : 'secondary'">
              {{ props.invoice.status }}
            </Badge>
          </div>
          <div v-if="props.invoice.treatment" class="col-span-2">
            <label class="text-sm font-medium text-gray-500">Treatment</label>
            <p class="text-sm">{{ props.invoice.treatment.procedure }} - ${{ props.invoice.treatment.cost }}</p>
          </div>
        </CardContent>
      </Card>
    </div>
  </AppLayout>
</template>
