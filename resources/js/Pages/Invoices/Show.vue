<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { Button } from '@/Components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/Components/ui/card';
import { Badge } from '@/Components/ui/badge';
import AppLayout from '@/Layouts/AppLayout.vue';
import { ArrowLeft, Download, Printer } from 'lucide-vue-next';
import { route } from 'ziggy-js';

interface Invoice {
  id: number;
  patient: { name: string; email: string };
  treatment?: { procedure: string; cost: number };
  prescription?: { medication: string; dosage: string; frequency: string };
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
    window.open(props.invoice.pdf_path, '_blank');
  }
};

const printInvoice = () => {
  window.print();
};

const formatUGX = (value: number) => {
  const whole = Math.round(value);
  return `UGX ${whole.toLocaleString('en-US')}`;
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
        <div class="flex space-x-2">
          <Button v-if="props.invoice.pdf_path" @click="downloadPDF" variant="outline">
            <Download class="mr-2 h-4 w-4" />
            Download PDF
          </Button>
          <Button @click="printInvoice" variant="outline">
            <Printer class="mr-2 h-4 w-4" />
            Print
          </Button>
        </div>
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
            <p class="text-sm text-red-600">{{ formatUGX(props.invoice.amount) }}</p>
          </div>
          <div>
            <label class="text-sm font-medium text-gray-500">Due Date</label>
            <p class="text-sm">{{ new Date(props.invoice.due_date).toLocaleDateString() }}</p>
          </div>
          <div>
            <label class="text-sm font-medium text-gray-500 block">Status</label>
            <div class="mt-1">
              <Badge
                :class="{
                  'bg-green-100 text-green-800 border-green-200': props.invoice.status === 'paid',
                  'bg-red-100 text-red-800 border-red-200': props.invoice.status === 'overdue',
                  'bg-yellow-100 text-yellow-800 border-yellow-200': props.invoice.status === 'pending'
                }"
                class="px-3 py-1 text-xs font-medium uppercase tracking-wide border shadow-sm rounded-md"
              >
                {{ props.invoice.status }}
              </Badge>
            </div>
          </div>

          <!-- Treatment Information -->
          <div v-if="props.invoice.treatment" class="col-span-2">
            <label class="text-sm font-medium text-gray-500">Treatment</label>
            <p class="text-sm">{{ props.invoice.treatment.procedure }} - {{ formatUGX(props.invoice.treatment.cost) }}</p>
          </div>

          <!-- Prescription Information -->
          <div v-if="props.invoice.treatment && props.invoice.treatment.prescriptions && props.invoice.treatment.prescriptions.length > 0" class="col-span-2">
            <label class="text-sm font-medium text-gray-500">Prescriptions</label>
            <div v-for="prescription in props.invoice.treatment.prescriptions" :key="prescription.id" class="text-sm">
              <p>{{ prescription.medicine ? prescription.medicine.medicine_name : (prescription.medication || 'N/A') }} - {{ prescription.prescription_amount ? formatUGX(prescription.prescription_amount) : 'N/A' }}</p>
            </div>
          </div>
        </CardContent>
      </Card>
    </div>
  </AppLayout>
</template>

<style>
@media print {
  .print\:hidden {
    display: none !important;
  }

  body {
    font-size: 12px;
  }

  .container {
    max-width: none;
    margin: 0;
    padding: 20px;
  }

  .bg-gradient-to-br,
  .bg-blue-50,
  .bg-white {
    background: white !important;
  }

  .text-gray-600,
  .text-gray-500 {
    color: black !important;
  }

  /* Hide the sidebar and header when printing */
  nav,
  aside {
    display: none !important;
  }

  /* Remove left margin from main content when printing */
  main {
    margin-left: 0 !important;
    padding: 0 !important;
  }

  /* Invoice print styling */
  .space-y-6 {
    margin: 0 !important;
  }

  .flex {
    display: flex !important;
  }

  .items-center {
    align-items: center !important;
  }

  .justify-between {
    justify-content: space-between !important;
  }

  /* Hide buttons and actions when printing */
  button {
    display: none !important;
  }

  /* Make the invoice card fill the page properly */
  .min-h-screen {
    min-height: auto !important;
  }

  /* Ensure proper spacing for printed invoice */
  .p-6 {
    padding: 20px !important;
  }

  /* Make the card look like a proper invoice */
  .border {
    border: 1px solid #000 !important;
  }

  .shadow-xl {
    box-shadow: none !important;
  }

  /* Badge styling for print */
  .badge {
    border: 1px solid currentColor !important;
    background: white !important;
    color: black !important;
    border-radius: 0.375rem !important;
    box-shadow: 0 1px 2px 0 rgb(0 0 0 / 0.05) !important;
  }

  /* Ensure badge colors are preserved in print where possible */
  .bg-green-100 {
    background-color: #f0fdf4 !important;
    color: #166534 !important;
    border-color: #bbf7d0 !important;
  }

  .bg-red-100 {
    background-color: #fef2f2 !important;
    color: #991b1b !important;
    border-color: #fecaca !important;
  }

  .bg-yellow-100 {
    background-color: #fffbeb !important;
    color: #92400e !important;
    border-color: #fef3c7 !important;
  }
}
</style>
