<script setup lang="ts">
import { Head, useForm, usePage, router } from '@inertiajs/vue3';
import { Button } from '@/Components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/Components/ui/card';
import { Badge } from '@/Components/ui/badge';
import AppLayout from '@/Layouts/AppLayout.vue';
import { ArrowLeft, Download, Printer, CreditCard, AlertCircle } from 'lucide-vue-next';
import { formatUGX } from '@/Composables/useCurrency';
import { route } from 'ziggy-js';
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogDescription, DialogFooter } from '@/Components/ui/dialog';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/Components/ui/select';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { ref, computed, nextTick, watch } from 'vue';

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
  paid_at?: string;
  paid_total?: number | string;
  balance?: number | string;
  payments?: Array<{
    id: number;
    amount: number | string;
    method?: string | null;
    received_at?: string | null;
    reference?: string | null;
    notes?: string | null;
  }>;
}

interface Refund {
  id: number;
  invoice_id: number;
  payment_id: number;
  amount: number | string;
  reason?: string | null;
  notes?: string | null;
  refunded_at?: string | null;
  refunded_by?: number | null;
  refunded_by_user?: { name: string } | null;
}

interface Props {
  invoice: Invoice;
  refunds?: Refund[];
}

const props = defineProps<Props>();

const downloadPDF = () => {
  if (props.invoice.pdf_path) {
    window.open(props.invoice.pdf_path, '_blank');
  }
};

// Helper function to get medicine name from prescription
const getMedicineName = (prescription) => {
  if (!prescription) return 'Prescription';
  
  // Check for nested medicine object first
  if (prescription.medicine && prescription.medicine.name) {
    return prescription.medicine.name;
  }
  
  // Then check for direct properties
  return prescription.medicine_name || prescription.medication || 'Prescription';
};

const printInvoice = () => {
  // Create a new window for printing
  const printWindow = window.open('', '_blank');
  if (!printWindow) return;
  
  // Get the current date for the printout
  const printDate = new Date().toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'long',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  });

  // Generate the HTML for printing
  let printContent = `
    <!DOCTYPE html>
    <html>
    <head>
      <meta charset="utf-8" />
      <title>Invoice #${props.invoice.id} - ${props.invoice.patient.name}</title>
      <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
      <style>
        @page { margin: 0.5cm; }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { 
          font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, sans-serif;
          color: #111827;
          line-height: 1.4;
          font-size: 11px;
          -webkit-print-color-adjust: exact !important;
          print-color-adjust: exact !important;
          margin: 0;
          padding: 15px;
        }
        .header { 
          display: flex;
          justify-content: space-between;
          align-items: flex-start;
          margin-bottom: 15px;
          padding-bottom: 10px;
          border-bottom: 1px solid #e5e7eb;
        }
        .logo h1 {
          color: #1e40af;
          font-size: 18px;
          font-weight: 700;
          margin: 0 0 3px 0;
        }
        .muted { 
          color: #6b7280;
          font-size: 11px;
        }
        .section {
          margin-bottom: 15px;
          page-break-inside: avoid;
        }
        .section-title {
          font-size: 12px;
          font-weight: 600;
          color: #1e40af;
          margin-bottom: 8px;
          padding-bottom: 4px;
          border-bottom: 1px solid #3b82f6;
          text-transform: uppercase;
          letter-spacing: 0.3px;
        }
        .box { 
          background: #f8fafc;
          border: 1px solid #e5e7eb;
          border-radius: 6px;
          padding: 12px;
          margin-bottom: 10px;
        }
        .grid { 
          display: grid;
          grid-template-columns: 1fr 1fr;
          gap: 10px;
          margin-bottom: 10px;
        }
        .detail-item {
          margin-bottom: 6px;
          display: flex;
          align-items: flex-start;
        }
        .detail-label {
          font-weight: 600;
          min-width: 120px;
          color: #4b5563;
        }
        .detail-value {
          flex: 1;
        }
        .amount-due {
          font-size: 14px;
          font-weight: 600;
          color: #1e40af;
          margin: 10px 0;
        }
        .status-badge {
          display: inline-block;
          padding: 2px 6px;
          border-radius: 4px;
          font-size: 10px;
          font-weight: 600;
          text-transform: uppercase;
          letter-spacing: 0.5px;
        }
        .status-paid { background-color: #dcfce7; color: #166534; }
        .status-pending { background-color: #fef3c7; color: #92400e; }
        .status-overdue { background-color: #fee2e2; color: #991b1b; }
        table {
          width: 100%;
          border-collapse: collapse;
          margin: 10px 0;
          font-size: 11px;
        }
        th {
          text-align: left;
          background-color: #f3f4f6;
          padding: 6px 8px;
          font-weight: 600;
          color: #4b5563;
          border: 1px solid #e5e7eb;
        }
        td {
          padding: 6px 8px;
          border: 1px solid #e5e7eb;
          vertical-align: top;
        }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .font-bold { font-weight: 600; }
        .mt-4 { margin-top: 1rem; }
        .mb-2 { margin-bottom: 0.5rem; }
        .w-full { width: 100%; }
      </style>
    </head>
    <body>
      <div class="header">
        <div class="logo">
          <h1>Victoria Dental Lounge</h1>
          <div class="muted">Dental Care Excellence</div>
        </div>
        <div class="text-right">
          <div class="font-bold">INVOICE</div>
          <div class="muted">#${props.invoice.id}</div>
          <div class="muted">${printDate}</div>
        </div>
      </div>

      <div class="grid">
        <div class="box">
          <div class="section-title">Patient Information</div>
          <div class="detail-item">
            <span class="detail-label">Patient Name:</span>
            <span class="detail-value">${props.invoice.patient.name}</span>
          </div>
          <div class="detail-item">
            <span class="detail-label">Email:</span>
            <span class="detail-value">${props.invoice.patient.email || 'N/A'}</span>
          </div>
        </div>
        <div class="box">
          <div class="section-title">Invoice Details</div>
          <div class="detail-item">
            <span class="detail-label">Status:</span>
            <span class="status-badge status-${props.invoice.status.toLowerCase()}">
              ${props.invoice.status.toUpperCase()}
            </span>
          </div>
          <div class="detail-item">
            <span class="detail-label">Due Date:</span>
            <span class="detail-value">${new Date(props.invoice.due_date).toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' })}</span>
          </div>
        </div>
      </div>

      <div class="section">
        <div class="section-title">Invoice Items</div>
        <table>
          <thead>
            <tr>
              <th>Description</th>
              <th class="text-right">Amount</th>
            </tr>
          </thead>
          <tbody>`;

  // Add treatment if exists
  if (props.invoice.treatment) {
    // Add treatment row
    printContent += `
            <tr>
              <td>${props.invoice.treatment.procedure || 'Treatment'}</td>
              <td class="text-right">${formatUGX(Number(props.invoice.treatment.cost || 0))}</td>
            </tr>`;

    // Add all prescriptions from the treatment
    if (props.invoice.treatment.prescriptions && props.invoice.treatment.prescriptions.length > 0) {
      console.log('Treatment Prescriptions:', JSON.stringify(props.invoice.treatment.prescriptions, null, 2));
      props.invoice.treatment.prescriptions.forEach(prescription => {
        console.log('Prescription data:', JSON.stringify(prescription, null, 2));
        printContent += `
            <tr>
              <td style="padding-left: 20px;">
                <div>${getMedicineName(prescription)} ${prescription.dosage ? `- ${prescription.dosage}mg` : ''}</div>
                <div class="muted" style="font-size: 9px;">
                  ${[prescription.frequency, prescription.duration].filter(Boolean).join(' • ')}
                </div>
              </td>
              <td class="text-right">${formatUGX(Number(prescription.prescription_amount || 0))}</td>
            </tr>`;
      });
    }
  }

  // Add standalone prescription if it exists and is not part of a treatment
  if (props.invoice.prescription && !props.invoice.treatment_id) {
    console.log('Standalone Prescription:', JSON.stringify(props.invoice.prescription, null, 2));
    printContent += `
            <tr>
              <td>
                <div>${getMedicineName(props.invoice.prescription)} ${props.invoice.prescription.dosage ? `- ${props.invoice.prescription.dosage}mg` : ''}</div>
                <div class="muted" style="font-size: 9px;">
                  ${[props.invoice.prescription.frequency, props.invoice.prescription.duration].filter(Boolean).join(' • ')}
                </div>
              </td>
              <td class="text-right">${formatUGX(Number(props.invoice.prescription.amount || 0))}</td>
            </tr>`;
  }

  // Add total
  printContent += `
            <tr>
              <td class="font-bold">Total Amount</td>
              <td class="text-right font-bold">${formatUGX(Number(props.invoice.amount))}</td>
            </tr>
          </tbody>
        </table>
      </div>`;

  // Add payments section if payments exist
  if (props.invoice.payments && props.invoice.payments.length > 0) {
    printContent += `
      <div class="section">
        <div class="section-title">Payment History</div>
        <table>
          <thead>
            <tr>
              <th>Date</th>
              <th>Method</th>
              <th>Reference</th>
              <th class="text-right">Amount</th>
            </tr>
          </thead>
          <tbody>`;

    props.invoice.payments.forEach(payment => {
      const paymentDate = payment.received_at || payment.created_at;
      printContent += `
            <tr>
              <td>${new Date(paymentDate).toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' })}</td>
              <td>${payment.method || 'N/A'}</td>
              <td>${payment.reference || 'N/A'}</td>
              <td class="text-right">${formatUGX(Number(payment.amount))}</td>
            </tr>`;
    });

    printContent += `
          </tbody>
        </table>
      </div>`;
  }

  // Add footer
  printContent += `
      <div class="mt-4" style="margin-top: 30px; padding-top: 15px; border-top: 1px solid #e5e7eb;">
        <div class="text-center muted" style="font-size: 10px;">
          Thank you for choosing Victoria Dental Lounge
        </div>
      </div>
    </body>
    </html>`;

  // Write the content and trigger print
  printWindow.document.open();
  printWindow.document.write(printContent);
  printWindow.document.close();
  
  // Wait for content to load before printing
  printWindow.onload = function() {
    setTimeout(() => {
      printWindow.print();
      printWindow.onafterprint = function() {
        printWindow.close();
      };
    }, 500);
  };
};

// Refund UI state
// Payment modal state
const isPaymentOpen = ref(false);
const paymentForm = useForm({
  amount: 0,
  method: '',
  received_at: new Date().toISOString().slice(0, 10),
  reference: '',
  notes: '',
});

const paymentAmountInput = ref<{ focus: () => void } | null>(null);

// Refund UI state
const isRefundOpen = ref(false);
const refundingPayment = ref<any | null>(null);
const refundForm = useForm({ amount: 0, reason: '', notes: '' });

// Cash drawer dialogs
const showCashSessionDialog = ref(false);
const showErrorDialog = ref(false);

const page = usePage<any>();
const canRefund = computed(() => {
  const roles = (page?.props?.auth?.user?.roles || []).map((r: any) => r?.name ?? r);
  return Array.isArray(roles) && (roles.includes('admin') || roles.includes('receptionist'));
});

// Build a map of refunded totals per payment
const refundedByPaymentId = computed<Record<number, number>>(() => {
  const map: Record<number, number> = {};
  (props.refunds || []).forEach((r: any) => {
    const pid = Number(r.payment_id);
    map[pid] = (map[pid] || 0) + Number(r.amount || 0);
  });
  return map;
});

const openRefund = (payment: any) => {
  refundingPayment.value = payment;
  refundForm.amount = Math.min(Number(payment.amount) - (Number(payment.refunded_amount) || 0), Number(payment.amount));
  refundForm.reason = '';
  refundForm.notes = '';
  isRefundOpen.value = true;
};

const submitRefund = () => {
  if (!refundingPayment.value) return;
  if (Number(refundForm.amount) <= 0) {
    alert('Amount must be greater than zero.');
    return;
  }
  
  // Calculate the remaining amount after this refund
  const payment = props.invoice.payments?.find(p => p.id === refundingPayment.value?.id);
  if (!payment) return;
  
  const currentRefunded = refundedByPaymentId.value[payment.id] || 0;
  const newRefunded = currentRefunded + Number(refundForm.amount);
  
  if (newRefunded > Number(payment.amount)) {
    alert('Refund amount cannot exceed the payment amount.');
    return;
  }
  
  refundForm.post(route('invoices.payments.refund', [props.invoice.id, payment.id]), {
    preserveScroll: true,
    onSuccess: () => {
      // Update the UI immediately
      if (payment) {
        // The server will handle the actual refund logic
        // We'll still reload to ensure data consistency
        router.reload();
      }
      isRefundOpen.value = false;
      refundingPayment.value = null;
    },
  });
};

const openPayment = () => {
  paymentForm.reset();
  paymentForm.amount = Number(props.invoice.balance) || Math.max(props.invoice.amount, 0);
  paymentForm.received_at = new Date().toISOString().slice(0, 10);
  isPaymentOpen.value = true;
  nextTick(() => {
    paymentAmountInput.value?.focus();
  });
};

const navigateToCashDrawer = () => {
  window.location.href = route('cash-drawer.index');
};

const submitPayment = async () => {
  if (paymentForm.amount <= 0) {
    alert('Amount must be greater than zero.');
    return;
  }
  const maxBalance = Number(props.invoice.balance || 0);
  if (Number(paymentForm.amount) > maxBalance + 0.0001) {
    alert(`Amount cannot exceed current balance of ${formatUGX(maxBalance)}`);
    return;
  }
  
  // If method is cash, ensure an active cash session
  if (paymentForm.method === 'cash') {
    try {
      const res = await fetch(route('cash-drawer.active'), { 
        headers: { 
          'X-Requested-With': 'XMLHttpRequest',
          'Accept': 'application/json'
        } 
      });
      
      if (!res.ok) throw new Error('Failed to verify cash drawer status');
      
      const data = await res.json();
      if (!data?.active) {
        showCashSessionDialog.value = true;
        // Wait for the dialog to be closed
        await new Promise<void>((resolve) => {
          const unwatch = watch(showCashSessionDialog, (newVal) => {
            if (!newVal) {
              unwatch();
              resolve();
            }
          });
        });
        return; // Stop execution if dialog was shown
      }
    } catch (e) {
      console.error('Error checking cash drawer status:', e);
      showErrorDialog.value = true;
      return;
    }
  }
  
  // Proceed with payment submission
  paymentForm.post(route('invoices.payments.store', props.invoice.id), {
    preserveScroll: true,
    onSuccess: () => {
      isPaymentOpen.value = false;
      router.reload();
    },
  });
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
            <Printer class="h-4 w-4 mr-2" />
            Print
          </Button>
          <Button v-if="!props.invoice.payments || props.invoice.payments.length === 0" 
                  @click="openPayment" 
                  variant="default" 
                  class="bg-green-600 hover:bg-green-700 text-white">
            <CreditCard class="h-4 w-4 mr-2" />
            Record Payment
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
            <label class="text-sm font-medium text-gray-500">Paid</label>
            <p class="text-sm text-green-600">{{ formatUGX(Number(props.invoice.paid_total || 0)) }}</p>
          </div>
          <div>
            <label class="text-sm font-medium text-gray-500">Balance</label>
            <p :class="['text-sm', Number(props.invoice.balance || 0) > 0 ? 'text-amber-600' : 'text-green-600']">{{ formatUGX(Number(props.invoice.balance || 0)) }}</p>
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
              <div v-if="(props.refunds && props.refunds.length) && Number(props.invoice.paid_total || 0) < Number(props.invoice.amount || 0)" class="mt-2">
                <Badge class="px-2 py-0.5 text-xs font-semibold bg-blue-100 text-blue-700 border border-blue-200">
                  Partially Refunded
                </Badge>
              </div>
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

      <!-- Refunds History -->
      <Card v-if="props.refunds && props.refunds.length" class="mt-6">
        <CardHeader>
          <CardTitle>Refunds</CardTitle>
          <CardDescription>History of refunds issued for this invoice</CardDescription>
        </CardHeader>
        <CardContent>
          <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-800">
              <thead>
                <tr>
                  <th class="px-4 py-2 text-left text-xs font-semibold text-gray-600 dark:text-gray-300">Date</th>
                  <th class="px-4 py-2 text-left text-xs font-semibold text-gray-600 dark:text-gray-300">Payment</th>
                  <th class="px-4 py-2 text-left text-xs font-semibold text-gray-600 dark:text-gray-300">Amount</th>
                  <th class="px-4 py-2 text-left text-xs font-semibold text-gray-600 dark:text-gray-300">Reason</th>
                  <th class="px-4 py-2 text-left text-xs font-semibold text-gray-600 dark:text-gray-300">Cashier</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                <tr v-for="r in props.refunds" :key="r.id">
                  <td class="px-4 py-2 text-gray-700 dark:text-gray-300">{{ r.refunded_at ? new Date(r.refunded_at).toLocaleString() : '—' }}</td>
                  <td class="px-4 py-2 text-gray-700 dark:text-gray-300">#{{ r.payment_id }}</td>
                  <td class="px-4 py-2 text-red-600">{{ formatUGX(Number(r.amount || 0)) }}</td>
                  <td class="px-4 py-2 text-gray-700 dark:text-gray-300">{{ r.reason || r.notes || '—' }}</td>
                  <td class="px-4 py-2 text-gray-700 dark:text-gray-300">{{ (r as any).refunded_by?.name || (r as any).refundedBy?.name || '—' }}</td>
                </tr>
              </tbody>
            </table>
          </div>
        </CardContent>
      </Card>

      <!-- Payments History -->
      <Card class="mt-6">
        <CardHeader>
          <CardTitle>Payments</CardTitle>
          <CardDescription>Recorded payments for this invoice</CardDescription>
        </CardHeader>
        <CardContent>
          <div v-if="props.invoice.payments && props.invoice.payments.length" class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-800">
              <thead>
                <tr>
                  <th class="px-4 py-2 text-left text-xs font-semibold text-gray-600 dark:text-gray-300">Date</th>
                  <th class="px-4 py-2 text-left text-xs font-semibold text-gray-600 dark:text-gray-300">Method</th>
                  <th class="px-4 py-2 text-left text-xs font-semibold text-gray-600 dark:text-gray-300">Reference</th>
                  <th class="px-4 py-2 text-left text-xs font-semibold text-gray-600 dark:text-gray-300">Amount</th>
                  <th class="px-4 py-2 text-left text-xs font-semibold text-gray-600 dark:text-gray-300">Notes</th>
                  <th class="px-4 py-2 text-left text-xs font-semibold text-gray-600 dark:text-gray-300">Receipt</th>
                  <!-- <th class="px-4 py-2 text-left text-xs font-semibold text-gray-600 dark:text-gray-300">Actions</th> -->
                </tr>
              </thead>
              <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                <tr v-for="p in props.invoice.payments" :key="p.id">
                  <td class="px-4 py-2 text-gray-700 dark:text-gray-300">{{ p.received_at ? new Date(p.received_at).toLocaleDateString() : '—' }}</td>
                  <td class="px-4 py-2 text-gray-700 dark:text-gray-300">{{ p.method || '—' }}</td>
                  <td class="px-4 py-2 text-gray-700 dark:text-gray-300">{{ p.reference || '—' }}</td>
                  <td class="px-4 py-2">
                    <div class="flex flex-col">
                      <div :class="{ 'text-green-600': Number(p.amount || 0) > 0, 'text-gray-500': Number(p.amount || 0) <= 0 }">
                        {{ formatUGX(Number(p.amount || 0)) }}
                      </div>
                      <div v-if="refundedByPaymentId[Number(p.id)]" class="text-xs text-gray-500">
                        <div>Refunded: {{ formatUGX(refundedByPaymentId[Number(p.id)]) }}</div>
                        <div>Remaining: {{ formatUGX(Math.max(0, Number(p.amount || 0) - refundedByPaymentId[Number(p.id)])) }}</div>
                      </div>
                    </div>
                  </td>
                  <td class="px-4 py-2 text-gray-700 dark:text-gray-300">{{ p.notes || '—' }}</td>
                  <td class="px-4 py-2">
                    <a :href="route('invoices.payments.receipt', [props.invoice.id, p.id])" class="text-blue-600 hover:underline">Receipt</a>
                  </td>
                  <!-- <td class="px-4 py-2">
                    <Button v-if="canRefund" size="sm" @click="openRefund(p)" class="text-white bg-red-600 hover:bg-red-700 dark:bg-red-700 dark:hover:bg-red-800">
                      Refund
                    </Button>
                  </td> -->
                </tr>
              </tbody>
            </table>
          </div>
          <div v-else class="text-sm text-gray-600 dark:text-gray-400">No payments recorded yet.</div>
        </CardContent>
      </Card>

      <!-- Refund Modal -->
      <Dialog v-model:open="isRefundOpen">
        <DialogContent class="max-w-md">
          <DialogHeader>
            <DialogTitle>Refund Payment</DialogTitle>
            <DialogDescription>Issue a refund for this payment.</DialogDescription>
          </DialogHeader>
          <div class="space-y-3">
            <div>
              <Label>Amount (UGX)</Label>
              <Input v-model.number="refundForm.amount" type="number" step="0.01" min="0.01" required />
            </div>
            <div>
              <Label>Reason</Label>
              <Input v-model="refundForm.reason" type="text" placeholder="Reason for refund" />
            </div>
            <div>
              <Label>Notes</Label>
              <Input v-model="refundForm.notes" type="text" placeholder="Additional notes (optional)" />
            </div>
          </div>
          <DialogFooter>
            <Button variant="outline" @click="isRefundOpen = false">Cancel</Button>
            <Button @click="submitRefund" class="text-white bg-red-600 hover:bg-red-700 dark:bg-red-700 dark:hover:bg-red-800">
              Refund
            </Button>
          </DialogFooter>
        </DialogContent>
      </Dialog>

      <!-- Cash Session Required Dialog -->
      <Dialog :open="showCashSessionDialog" @update:open="(val) => {
        showCashSessionDialog = val;
        if (!val) isPaymentOpen = false;
      }">
        <DialogContent class="sm:max-w-md z-[1000] transition-all duration-300 ease-in-out" :class="{ 'scale-105': showCashSessionDialog }">
          <DialogHeader>
            <DialogTitle class="text-lg font-semibold flex items-center gap-2">
              <AlertCircle class="h-5 w-5 text-amber-500" />
              Cash Session Required
            </DialogTitle>
            <DialogDescription class="pt-2">
              You need to open a cash drawer session before processing cash payments.
            </DialogDescription>
          </DialogHeader>
          <div class="flex justify-end gap-2 pt-4">
            <Button variant="outline" @click="showCashSessionDialog = false">
              Cancel
            </Button>
            <Button 
              @click="navigateToCashDrawer"
              class="bg-amber-600 hover:bg-amber-700 text-white"
            >
              Open Cash Drawer
            </Button>
          </div>
        </DialogContent>
      </Dialog>

      <!-- Error Dialog -->
      <Dialog :open="showErrorDialog" @update:open="showErrorDialog = $event">
        <DialogContent class="sm:max-w-md">
          <DialogHeader>
            <DialogTitle class="text-lg font-semibold flex items-center gap-2">
              <AlertCircle class="h-5 w-5 text-red-500" />
              Unable to Verify Cash Drawer
            </DialogTitle>
            <DialogDescription class="pt-2">
              There was an error verifying the cash drawer status. Please open a cash session first.
            </DialogDescription>
          </DialogHeader>
          <div class="flex justify-end gap-2 pt-4">
            <Button @click="showErrorDialog = false">
              Close
            </Button>
          </div>
        </DialogContent>
      </Dialog>

      <!-- Record Payment Modal -->
      <Dialog :open="isPaymentOpen" @update:open="(value) => isPaymentOpen = value">
        <DialogContent class="max-w-md transition-opacity duration-300" :class="{ 'opacity-50': showCashSessionDialog }">
          <DialogHeader>
            <DialogTitle class="text-2xl font-bold text-gray-900 dark:text-white">Record Payment</DialogTitle>
            <DialogDescription>Add a payment to this invoice</DialogDescription>
          </DialogHeader>
          <form @submit.prevent="submitPayment" class="space-y-4">
            <div>
              <Label>Amount (UGX)</Label>
              <Input
                ref="paymentAmountInput"
                v-model.number="paymentForm.amount"
                type="number"
                step="0.01"
                min="0.01"
                required
              />
              <p v-if="props.invoice.balance" class="text-xs text-gray-500 mt-1">Balance: {{ formatUGX(Number(props.invoice.balance || 0)) }}</p>
            </div>
            <div class="grid grid-cols-2 gap-3">
              <div>
                <Label>Method</Label>
                <Select v-model="paymentForm.method">
                  <SelectTrigger class="h-10">
                    <SelectValue placeholder="Select method" />
                  </SelectTrigger>
                  <SelectContent>
                    <SelectItem value="cash">Cash</SelectItem>
                    <SelectItem value="card">Card</SelectItem>
                    <SelectItem value="mobile_money">Mobile Money</SelectItem>
                    <SelectItem value="bank_transfer">Bank Transfer</SelectItem>
                  </SelectContent>
                </Select>
              </div>
              <div>
                <Label>Date</Label>
                <Input v-model="paymentForm.received_at" type="date" />
              </div>
            </div>
            <div>
              <Label>Reference</Label>
              <Input v-model="paymentForm.reference" placeholder="Txn / receipt no." />
            </div>
            <div>
              <Label>Notes</Label>
              <Input v-model="paymentForm.notes" placeholder="Optional notes" />
            </div>
            <DialogFooter>
              <Button type="button" variant="outline" @click="isPaymentOpen = false">Cancel</Button>
              <Button type="submit" :disabled="paymentForm.processing" class="bg-blue-600 hover:bg-blue-700">
                <i v-if="paymentForm.processing" class="fas fa-spinner fa-spin mr-2"></i>
                <i v-else class="fas fa-check mr-2"></i>
                {{ paymentForm.processing ? 'Saving...' : 'Save Payment' }}
              </Button>
            </DialogFooter>
          </form>
        </DialogContent>
      </Dialog>
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
