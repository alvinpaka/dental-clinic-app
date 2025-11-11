<script setup lang="ts">
import { Head, useForm, usePage, router } from '@inertiajs/vue3';
import { Button } from '@/Components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/Components/ui/card';
import { Badge } from '@/Components/ui/badge';
import AppLayout from '@/Layouts/AppLayout.vue';
import { ArrowLeft, Download, Printer } from 'lucide-vue-next';
import { formatUGX } from '@/Composables/useCurrency';
import { route } from 'ziggy-js';
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogDescription, DialogFooter } from '@/Components/ui/dialog';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { ref, computed } from 'vue';

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

const printInvoice = () => {
  window.print();
};

// Refund UI state
const isRefundOpen = ref(false);
const refundingPayment = ref<any | null>(null);
const refundForm = useForm({ amount: 0, reason: '', notes: '' });

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
  refundForm.amount = 0;
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
  refundForm.post(route('invoices.payments.refund', [props.invoice.id, refundingPayment.value.id]), {
    preserveScroll: true,
    onSuccess: () => {
      isRefundOpen.value = false;
      refundingPayment.value = null;
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
                  <th class="px-4 py-2 text-left text-xs font-semibold text-gray-600 dark:text-gray-300">Actions</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                <tr v-for="p in props.invoice.payments" :key="p.id">
                  <td class="px-4 py-2 text-gray-700 dark:text-gray-300">{{ p.received_at ? new Date(p.received_at).toLocaleDateString() : '—' }}</td>
                  <td class="px-4 py-2 text-gray-700 dark:text-gray-300">{{ p.method || '—' }}</td>
                  <td class="px-4 py-2 text-gray-700 dark:text-gray-300">{{ p.reference || '—' }}</td>
                  <td class="px-4 py-2 text-green-600">
                    <div>{{ formatUGX(Number(p.amount || 0)) }}</div>
                    <div v-if="refundedByPaymentId[Number(p.id)]" class="text-xs text-gray-500">
                      Refunded: {{ formatUGX(refundedByPaymentId[Number(p.id)]) }} · Remaining: {{ formatUGX(Math.max(0, Number(p.amount || 0) - refundedByPaymentId[Number(p.id)])) }}
                    </div>
                  </td>
                  <td class="px-4 py-2 text-gray-700 dark:text-gray-300">{{ p.notes || '—' }}</td>
                  <td class="px-4 py-2">
                    <a :href="route('invoices.payments.receipt', [props.invoice.id, p.id])" class="text-blue-600 hover:underline">Receipt</a>
                  </td>
                  <td class="px-4 py-2">
                    <Button v-if="canRefund" size="sm" variant="outline" @click="openRefund(p)">Refund</Button>
                  </td>
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
            <Button @click="submitRefund">Refund</Button>
          </DialogFooter>
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
