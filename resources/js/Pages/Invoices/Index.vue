<script setup lang="ts">
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import { ref, computed, onMounted, watch, nextTick } from 'vue';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/Components/ui/card';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { Badge } from '@/Components/ui/badge';
import { Button } from '@/Components/ui/button';
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogDescription, DialogFooter } from '@/Components/ui/dialog';
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuTrigger } from '@/Components/ui/dropdown-menu';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/Components/ui/select';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/Components/ui/tabs';
import { Receipt, Plus, FileText, CreditCard, Calendar, Search, MoreVertical, Eye, Download } from 'lucide-vue-next';
import AppLayout from '@/Layouts/AppLayout.vue';

interface Patient {
  id: number;
  name: string;
  email: string;
  treatments?: Treatment[];
  prescriptions?: Prescription[];
}

interface Prescription {
  id: number;
  medication?: string | null;
  dosage?: string | null;
  frequency?: string | null;
  amount?: number | null;
  medicine?: { medicine_name?: string | null };
  medicine_name?: string | null;
}

interface Treatment {
  id: number;
  procedure: string;
  cost: number;
  prescription_amount?: number;
}

interface Invoice {
  id: number;
  patient?: { name: string; id: number };
  amount: number;
  due_date: string;
  status: 'paid' | 'pending' | 'overdue' | 'cancelled';
  pdf_path?: string;
  treatment?: { procedure: string; id: number };
  prescription?: Prescription;
  created_at?: string;
  paid_at?: string;
}

interface InvoiceItemOption {
  value: string;
  type: 'treatment' | 'prescription';
  title: string;
  subtitle?: string;
  treatmentCost?: number;
  prescriptionCost?: number;
}

interface Prefill {
  patient_id: number;
  treatment_id: number | null;
  prescription_id: number | null;
  amount: number;
  due_date: string;
}

interface Props {
  invoices: {
    data: Invoice[];
    links: any[];
  };
  patients: Patient[];
  stats?: {
    total_invoices: number;
    total_revenue: number;
    pending_amount: number;
    overdue_count: number;
  };
  prefill?: Prefill | null;
}

const props = defineProps<Props>();

const searchQuery = ref('');
const statusFilter = ref('all');
const selectedPatient = ref<Patient | null>(null);
const activeTab = ref('grid');
const selectedPrescriptionId = ref<number | null>(null);
const selectedInvoiceItem = ref<string | null>(null);

// Filtered invoices
const filteredInvoices = computed(() => {
  let invoices = [...(props.invoices?.data || [])];

  // Search filter
  if (searchQuery.value) {
    invoices = invoices.filter(invoice =>
      (invoice.patient?.name || '').toLowerCase().includes(searchQuery.value.toLowerCase()) ||
      invoice.id.toString().includes(searchQuery.value) ||
      (invoice.treatment?.procedure.toLowerCase().includes(searchQuery.value.toLowerCase())) ||
      (invoice.prescription?.medication.toLowerCase().includes(searchQuery.value.toLowerCase()))
    );
  }

  // Status filter
  if (statusFilter.value && statusFilter.value !== 'all') {
    invoices = invoices.filter(invoice => invoice.status === statusFilter.value);
  }

  return invoices.sort((a, b) => new Date(b.created_at || 0).getTime() - new Date(a.created_at || 0).getTime());
});

// Forms
const createForm = useForm({
  patient_id: null as number | null,
  treatment_id: null as number | null,
  prescription_id: null as number | null,
  amount: 0,
  due_date: '',
  notes: '',
});

const editForm = useForm({
  patient_id: null as number | null,
  treatment_id: null as number | null,
  prescription_id: null as number | null,
  amount: '',
  due_date: '',
  status: '',
  notes: '',
});

// Modal states
const isCreateOpen = ref(false);
const isEditOpen = ref(false);
const isDeleteOpen = ref(false);
const editingInvoice = ref<Invoice | null>(null);

// Computed properties
const availableTreatments = computed(() => {
  return selectedPatient.value?.treatments || [];
});

const availablePrescriptions = computed(() => {
  return selectedPatient.value?.prescriptions || [];
});

const invoiceItems = computed<InvoiceItemOption[]>(() => {
  if (!selectedPatient.value) return [];

  const items: InvoiceItemOption[] = [];

  (selectedPatient.value.treatments || []).forEach((treatment) => {
    items.push({
      value: `treatment-${treatment.id}`,
      type: 'treatment',
      title: treatment.procedure,
      treatmentCost: Number(treatment.cost) || 0,
      prescriptionCost: Number(treatment.prescription_amount) || 0,
    });
  });

  (selectedPatient.value.prescriptions || []).forEach((prescription) => {
    const prescriptionTitle = prescription.medication
      || prescription.medicine?.medicine_name
      || prescription.medicine_name
      || `Prescription #${prescription.id}`;

    const subtitleParts = [prescription.dosage, prescription.frequency].filter((part): part is string => !!part);
    items.push({
      value: `prescription-${prescription.id}`,
      type: 'prescription',
      title: prescriptionTitle,
      subtitle: subtitleParts.join(' • '),
      prescriptionCost: Number(prescription.amount) || 0,
    });
  });

  return items;
});

const selectedTreatmentOption = computed(() => {
  if (!selectedInvoiceItem.value || !selectedPatient.value) return null;
  if (!selectedInvoiceItem.value.startsWith('treatment-')) return null;
  const id = Number(selectedInvoiceItem.value.split('-')[1]);
  return selectedPatient.value.treatments?.find(t => t.id === id) || null;
});

const selectedPrescriptionOption = computed(() => {
  if (!selectedInvoiceItem.value || !selectedPatient.value) return null;
  if (!selectedInvoiceItem.value.startsWith('prescription-')) return null;
  const id = Number(selectedInvoiceItem.value.split('-')[1]);
  return selectedPatient.value.prescriptions?.find(p => p.id === id) || null;
});

// Get prescriptions that could be related to the selected treatment
const suggestedPrescriptions = computed(() => {
  if (!createForm.treatment_id || !selectedPatient.value) return [];

  const selectedTreatment = selectedPatient.value.treatments?.find(t => t.id === createForm.treatment_id);
  if (!selectedTreatment) return [];

  // For now, return all prescriptions - in a real app, you might filter by treatment type
  // or have a relationship between treatments and common prescriptions
  return selectedPatient.value.prescriptions || [];
});

const calculatedAmount = computed(() => {
  let total = 0;

  if (createForm.treatment_id && selectedPatient.value) {
    const treatment = selectedPatient.value.treatments?.find(t => t.id === createForm.treatment_id);
    if (treatment) {
      const treatmentCost = Number(treatment.cost) || 0;
      const prescriptionCost = Number(treatment.prescription_amount) || 0;
      total += treatmentCost + prescriptionCost;
    }
  } else if (selectedPrescriptionId.value && selectedPatient.value && !createForm.treatment_id) {
    const prescription = selectedPatient.value.prescriptions?.find(p => p.id === selectedPrescriptionId.value);
    if (prescription) {
      total += Number(prescription.amount) || 0;
    }
  }

  return Math.round(total);
});

const formattedAmountDisplay = computed(() => {
  const amount = Number(createForm.amount) || 0;
  return amount > 0 ? amount.toLocaleString('en-US', { minimumFractionDigits: 0 }) : '0';
});

const totalRevenue = computed(() => {
  return (props.invoices?.data || [])
    .filter(invoice => invoice.status === 'paid')
    .reduce((sum, invoice) => sum + invoice.amount, 0);
});

const pendingAmount = computed(() => {
  return (props.invoices?.data || [])
    .filter(invoice => invoice.status === 'pending')
    .reduce((sum, invoice) => sum + invoice.amount, 0);
});

// Helper functions
const getStatusColor = (status: string) => {
  switch (status) {
    case 'paid': return '#10B981'; // green
    case 'pending': return '#F59E0B'; // amber
    case 'overdue': return '#EF4444'; // red
    case 'cancelled': return '#6B7280'; // gray
    default: return '#6B7280';
  }
};

const getStatusBadgeVariant = (status: string) => {
  switch (status) {
    case 'paid': return 'default';
    case 'pending': return 'secondary';
    case 'overdue': return 'destructive';
    case 'cancelled': return 'outline';
    default: return 'outline';
  }
};

const formatDate = (dateString: string) => {
  return new Date(dateString).toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
  });
};

const isOverdue = (dueDate: string, status: string) => {
  if (status === 'paid' || status === 'cancelled') return false;
  return new Date(dueDate) < new Date();
};

const formatCurrency = (amount: number) => {
  const whole = Math.round(amount);
  return `UGX ${whole.toLocaleString('en-US')}`;
};

// Event handlers
const openCreate = () => {
  createForm.reset();
  selectedPatient.value = null;
  selectedPrescriptionId.value = null;
  selectedInvoiceItem.value = null;
  createForm.treatment_id = null;
  (createForm as any).prescription_id = null;
  isCreateOpen.value = true;
};

const openView = (invoice: Invoice) => {
  // Navigate to invoice details page
  router.visit(route('invoices.show', invoice.id));
};

const openEdit = (invoice: Invoice) => {
  editingInvoice.value = invoice;
  selectedPatient.value = props.patients.find(p => p.id === (invoice.patient?.id || 0)) || null;

  editForm.patient_id = invoice.patient?.id || null;
  editForm.treatment_id = invoice.treatment?.id || null;
  editForm.prescription_id = (invoice as any).prescription?.id || null;
  editForm.amount = invoice.amount.toString();
  editForm.due_date = invoice.due_date.split('T')[0];
  editForm.status = invoice.status;
  editForm.notes = '';

  isEditOpen.value = true;
};

const openDelete = (invoice: Invoice) => {
  editingInvoice.value = invoice;
  isDeleteOpen.value = true;
};

const submitCreate = () => {
  if (!createForm.patient_id || !createForm.amount || !createForm.due_date) {
    alert('Please fill in all required fields');
    return;
  }

  if (!selectedInvoiceItem.value) {
    alert('Please select a treatment or prescription to invoice');
    return;
  }

  const formData = {
    ...createForm,
    prescription_ids: selectedPrescriptionId.value ? [selectedPrescriptionId.value] : null,
  };

  createForm.post(route('invoices.store'), {
    data: formData,
    onSuccess: () => {
      createForm.reset();
      selectedPrescriptionId.value = null;
      selectedInvoiceItem.value = null;
      selectedPatient.value = null;
      isCreateOpen.value = false;
      router.visit(route('invoices.index'));
    },
  });
};

const submitEdit = () => {
  if (!editForm.patient_id || !editForm.amount || !editForm.due_date) {
    alert('Please fill in all required fields');
    return;
  }

  if (editingInvoice.value) {
    editForm.put(route('invoices.update', editingInvoice.value.id), {
      onSuccess: () => {
        editForm.reset();
        isEditOpen.value = false;
        editingInvoice.value = null;
      },
    });
  }
};

const confirmDelete = () => {
  if (editingInvoice.value) {
    router.delete(route('invoices.destroy', editingInvoice.value.id), {
      onSuccess: () => {
        isDeleteOpen.value = false;
        editingInvoice.value = null;
      },
    });
  }
};

const downloadPDF = (invoice: Invoice) => {
  if (invoice.pdf_path) {
    window.open(invoice.pdf_path, '_blank');
  }
};

const markAsPaid = (invoice: Invoice) => {
  router.put(route('invoices.update', invoice.id), {
    data: {
      status: 'paid',
      patient_id: invoice.patient?.id,
      amount: invoice.amount,
      due_date: invoice.due_date.split('T')[0],
    },
    onSuccess: () => {
      // Refresh the page or update the invoice status
      window.location.reload();
    },
  });
};

onMounted(() => {
  if (props.prefill) {
    // Preselect patient and treatment or prescription
    selectedPatient.value = props.patients.find(p => p.id === props.prefill!.patient_id) || null;
    createForm.patient_id = props.prefill.patient_id;
    createForm.treatment_id = props.prefill.treatment_id;

    // Handle prescription prefill - could be multiple prescriptions
    const prescriptionPrefill = (props.prefill as any).prescription_id;
    if (Array.isArray(prescriptionPrefill) && prescriptionPrefill.length > 0) {
      selectedPrescriptionId.value = prescriptionPrefill[0];
    } else if (prescriptionPrefill) {
      selectedPrescriptionId.value = prescriptionPrefill;
    }

    createForm.due_date = props.prefill.due_date;

    // Set amount after selections are made so calculatedAmount works
    nextTick(() => {
      if (props.prefill?.treatment_id) {
        selectedInvoiceItem.value = `treatment-${props.prefill.treatment_id}`;
      } else if (selectedPrescriptionId.value) {
        selectedInvoiceItem.value = `prescription-${selectedPrescriptionId.value}`;
      }
      createForm.amount = calculatedAmount.value;
    });

    isCreateOpen.value = true;
  }
});

watch(() => createForm.treatment_id, (newValue) => {
  if (newValue) {
    selectedPrescriptionId.value = null;
    (createForm as any).prescription_id = null;
  }
  createForm.amount = calculatedAmount.value;
});

watch(selectedPrescriptionId, (newValue) => {
  if (newValue) {
    createForm.treatment_id = null;
    (createForm as any).prescription_id = newValue;
  }
  if (!newValue) {
    (createForm as any).prescription_id = null;
  }
  createForm.amount = calculatedAmount.value;
});

watch(selectedInvoiceItem, (value) => {
  if (!value) {
    createForm.treatment_id = null;
    selectedPrescriptionId.value = null;
    (createForm as any).prescription_id = null;
    createForm.amount = calculatedAmount.value;
    return;
  }

  const [type, id] = value.split('-');
  if (type === 'treatment') {
    createForm.treatment_id = Number(id);
  } else if (type === 'prescription') {
    selectedPrescriptionId.value = Number(id);
  }
});

watch(() => createForm.patient_id, () => {
  createForm.treatment_id = null;
  selectedPrescriptionId.value = null;
  selectedInvoiceItem.value = null;
  (createForm as any).prescription_id = null;
  createForm.amount = calculatedAmount.value;
});

// Return all reactive variables and functions for template access
// Note: In Vue 3 <script setup>, this is not needed as all top-level bindings are automatically exposed
</script>

<template>
  <AppLayout title="Invoices">
    <Head title="Invoices" />

    <div class="min-h-screen bg-gradient-to-br from-blue-50 via-white to-cyan-50 dark:from-gray-900 dark:via-gray-800 dark:to-blue-900">
      <div class="container mx-auto px-4 py-8">
        <!-- Header Section -->
        <div class="mb-8">
          <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div>
              <h1 class="text-4xl font-bold bg-gradient-to-r from-blue-600 to-cyan-600 bg-clip-text text-transparent mb-2">
                Invoice Management
              </h1>
              <p class="text-gray-600 dark:text-gray-400 text-lg">
                Create, manage, and track patient invoices and payments
              </p>
            </div>

            <div class="flex items-center gap-3">
              <Badge variant="secondary" class="bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400">
                <Receipt class="w-4 h-4 mr-1" />
                {{ (props.invoices?.data || []).length }} Total Invoices
              </Badge>

              <Button @click="openCreate" class="bg-gradient-to-r from-blue-600 to-cyan-600 hover:from-blue-700 hover:to-cyan-700 text-white shadow-lg hover:shadow-xl transition-all duration-300">
                <Plus class="w-4 h-4 mr-2" />
                Create Invoice
              </Button>
            </div>
          </div>
        </div>

        <!-- Stats Overview -->
        <div v-if="props.stats" class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
          <Card class="border-0 shadow-xl hover:shadow-2xl transition-all duration-300 hover:-translate-y-1 bg-gradient-to-br from-green-50 to-green-100 dark:from-green-900/20 dark:to-green-800/20">
            <CardContent class="p-6">
              <div class="flex items-center justify-between">
                <div>
                  <p class="text-sm font-medium text-green-700 dark:text-green-300 mb-1">Total Revenue</p>
                  <p class="text-3xl font-bold text-green-900 dark:text-green-100 mb-1">{{ formatCurrency(totalRevenue) }}</p>
                  <p class="text-xs text-green-600 dark:text-green-400">From paid invoices</p>
                </div>
                <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-green-500 to-green-600 flex items-center justify-center shadow-lg">
                  <Receipt class="w-6 h-6 text-white" />
                </div>
              </div>
            </CardContent>
          </Card>

          <Card class="border-0 shadow-xl hover:shadow-2xl transition-all duration-300 hover:-translate-y-1 bg-gradient-to-br from-blue-50 to-blue-100 dark:from-blue-900/20 dark:to-blue-800/20">
            <CardContent class="p-6">
              <div class="flex items-center justify-between">
                <div>
                  <p class="text-sm font-medium text-blue-700 dark:text-blue-300 mb-1">Total Invoices</p>
                  <p class="text-3xl font-bold text-blue-900 dark:text-blue-100 mb-1">{{ props.stats.total_invoices }}</p>
                  <p class="text-xs text-blue-600 dark:text-blue-400">All time</p>
                </div>
                <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-blue-500 to-blue-600 flex items-center justify-center shadow-lg">
                  <FileText class="w-6 h-6 text-white" />
                </div>
              </div>
            </CardContent>
          </Card>

          <Card class="border-0 shadow-xl hover:shadow-2xl transition-all duration-300 hover:-translate-y-1 bg-gradient-to-br from-amber-50 to-amber-100 dark:from-amber-900/20 dark:to-amber-800/20">
            <CardContent class="p-6">
              <div class="flex items-center justify-between">
                <div>
                  <p class="text-sm font-medium text-amber-700 dark:text-amber-300 mb-1">Pending Amount</p>
                  <p class="text-3xl font-bold text-amber-900 dark:text-amber-100 mb-1">{{ formatCurrency(pendingAmount) }}</p>
                  <p class="text-xs text-amber-600 dark:text-amber-400">Awaiting payment</p>
                </div>
                <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-amber-500 to-amber-600 flex items-center justify-center shadow-lg">
                  <CreditCard class="w-6 h-6 text-white" />
                </div>
              </div>
            </CardContent>
          </Card>

          <Card class="border-0 shadow-xl hover:shadow-2xl transition-all duration-300 hover:-translate-y-1 bg-gradient-to-br from-red-50 to-red-100 dark:from-red-900/20 dark:to-red-800/20">
            <CardContent class="p-6">
              <div class="flex items-center justify-between">
                <div>
                  <p class="text-sm font-medium text-red-700 dark:text-red-300 mb-1">Overdue</p>
                  <p class="text-3xl font-bold text-red-900 dark:text-red-100 mb-1">{{ props.stats.overdue_count }}</p>
                  <p class="text-xs text-red-600 dark:text-red-400">Past due date</p>
                </div>
                <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-red-500 to-red-600 flex items-center justify-center shadow-lg">
                  <Calendar class="w-6 h-6 text-white" />
                </div>
              </div>
            </CardContent>
          </Card>
        </div>

        <!-- Main Content -->
        <Card class="border-0 shadow-xl bg-white dark:bg-gray-900">
          <CardHeader class="pb-4">
            <div>
              <CardTitle class="text-2xl text-gray-900 dark:text-white">Invoice Management</CardTitle>
              <CardDescription class="text-gray-600 dark:text-gray-400">
                View and manage all patient invoices
              </CardDescription>
            </div>
          </CardHeader>

          <CardContent>
            <Tabs v-model="activeTab" class="w-full">
              <TabsList class="grid w-full grid-cols-2">
                <TabsTrigger value="grid">Grid View</TabsTrigger>
                <TabsTrigger value="list">List View</TabsTrigger>
              </TabsList>

              <!-- Grid View -->
              <TabsContent value="grid" class="mt-0">
                <div class="space-y-6">
                  <!-- Search and Filters -->
                  <div class="flex flex-col md:flex-row gap-4 items-center">
                    <div class="relative flex-1">
                      <Search class="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400 w-4 h-4" />
                      <Input
                        v-model="searchQuery"
                        placeholder="Search invoices by patient name, invoice ID, or treatment..."
                        class="pl-10 h-12 bg-gray-50 dark:bg-gray-800 border-gray-200 dark:border-gray-700"
                      />
                    </div>

                    <Select v-model="statusFilter">
                      <SelectTrigger class="w-48 h-12">
                        <SelectValue placeholder="Filter by status" />
                      </SelectTrigger>
                      <SelectContent>
                        <SelectItem value="all">All Statuses</SelectItem>
                        <SelectItem value="paid">Paid</SelectItem>
                        <SelectItem value="pending">Pending</SelectItem>
                        <SelectItem value="overdue">Overdue</SelectItem>
                        <SelectItem value="cancelled">Cancelled</SelectItem>
                      </SelectContent>
                    </Select>
                  </div>

                  <!-- Invoices Grid -->
                  <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <Card
                      v-for="(invoice, index) in filteredInvoices"
                      :key="invoice.id"
                      :class="[
                        'border-0 shadow-xl hover:shadow-2xl transition-all duration-300 hover:-translate-y-2 bg-white dark:bg-gray-900 group',
                        isOverdue(invoice.due_date, invoice.status) && invoice.status !== 'paid' ? 'ring-2 ring-red-200 dark:ring-red-800' : ''
                      ]"
                    >
                      <CardHeader class="pb-4">
                        <div class="flex items-start justify-between">
                          <div class="flex items-center space-x-3">
                            <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-blue-500 to-cyan-500 flex items-center justify-center shadow-lg">
                              <Receipt class="w-6 h-6 text-white" />
                            </div>
                            <div>
                              <CardTitle class="text-lg text-gray-900 dark:text-white group-hover:text-blue-600 dark:group-hover:text-cyan-400 transition-colors">
                                Invoice #{{ invoice.id }}
                              </CardTitle>
                              <CardDescription class="text-gray-600 dark:text-gray-400">
                                {{ invoice.patient?.name || 'Unknown Patient' }}
                              </CardDescription>
                            </div>
                          </div>

                          <div class="flex items-center space-x-2">
                            <Badge :variant="getStatusBadgeVariant(invoice.status)" class="text-xs">
                              {{ invoice.status }}
                            </Badge>

                            <DropdownMenu>
                              <DropdownMenuTrigger as-child>
                                <Button variant="ghost" size="sm" class="h-8 w-8 p-0 opacity-0 group-hover:opacity-100 transition-opacity">
                                  <MoreVertical class="h-4 w-4" />
                                </Button>
                              </DropdownMenuTrigger>
                              <DropdownMenuContent align="end">
                                <DropdownMenuItem as-child>
                                  <Link :href="route('invoices.show', invoice.id)" class="flex items-center">
                                    <Eye class="w-4 h-4 mr-2" />
                                    View Details
                                  </Link>
                                </DropdownMenuItem>
                                <DropdownMenuItem @click="downloadPDF(invoice)" v-if="invoice.pdf_path">
                                  <Download class="w-4 h-4 mr-2" />
                                  Download PDF
                                </DropdownMenuItem>
                                <DropdownMenuItem @click="openEdit(invoice)">
                                  <i class="fas fa-edit mr-2"></i>
                                  Edit Invoice
                                </DropdownMenuItem>
                                <DropdownMenuItem @click="markAsPaid(invoice)" v-if="invoice.status !== 'paid'">
                                  <CreditCard class="w-4 h-4 mr-2" />
                                  Mark as Paid
                                </DropdownMenuItem>
                                <DropdownMenuItem @click="openDelete(invoice)" class="text-red-600">
                                  <i class="fas fa-trash mr-2"></i>
                                  Delete Invoice
                                </DropdownMenuItem>
                              </DropdownMenuContent>
                            </DropdownMenu>
                          </div>
                        </div>
                      </CardHeader>

                      <CardContent class="space-y-4">
                        <div class="grid grid-cols-2 gap-4 text-sm">
                          <div class="flex items-center space-x-2">
                            <Receipt class="w-4 h-4 text-gray-400" />
                            <span class="text-gray-600 dark:text-gray-400 font-semibold">{{ formatCurrency(invoice.amount) }}</span>
                          </div>
                          <div class="flex items-center space-x-2">
                            <Calendar class="w-4 h-4 text-gray-400" />
                            <span class="text-gray-600 dark:text-gray-400">Due {{ formatDate(invoice.due_date) }}</span>
                          </div>
                        </div>

                        <div v-if="invoice.treatment" class="flex items-start space-x-2 text-sm">
                          <i class="fas fa-tooth text-gray-400 mt-0.5"></i>
                          <span class="text-gray-600 dark:text-gray-400">{{ invoice.treatment.procedure }}</span>
                        </div>

                        <div v-if="invoice.prescription && invoice.prescription.medication" class="flex items-start space-x-2 text-sm">
                          <i class="fas fa-pills text-gray-400 mt-0.5"></i>
                          <span class="text-gray-600 dark:text-gray-400">{{ invoice.prescription.medication }}</span>
                        </div>

                        <div v-if="isOverdue(invoice.due_date, invoice.status) && invoice.status !== 'paid'" class="flex items-center space-x-2 p-2 bg-red-50 dark:bg-red-900/20 rounded-lg border border-red-200 dark:border-red-800">
                          <i class="fas fa-exclamation-triangle text-red-600 text-sm"></i>
                          <span class="text-red-700 dark:text-red-300 text-sm font-medium">Overdue</span>
                        </div>

                        <div class="flex justify-between items-center pt-4 border-t border-gray-100 dark:border-gray-800">
                          <Button variant="outline" size="sm" as-child>
                            <Link :href="route('invoices.show', invoice.id)">
                              <Eye class="w-4 h-4 mr-2" />
                              View
                            </Link>
                          </Button>
                          <div class="flex space-x-2">
                            <Button v-if="invoice.pdf_path" variant="outline" size="sm" @click="downloadPDF(invoice)">
                              <Download class="w-4 h-4" />
                            </Button>
                            <Button
                              v-if="invoice.status !== 'paid'"
                              size="sm"
                              @click="markAsPaid(invoice)"
                              class="bg-green-600 hover:bg-green-700"
                            >
                              <CreditCard class="w-4 h-4" />
                            </Button>
                          </div>
                        </div>
                      </CardContent>
                    </Card>

                    <!-- Empty State -->
                    <div v-if="filteredInvoices.length === 0" class="col-span-full">
                      <Card class="border-0 shadow-xl bg-white dark:bg-gray-900">
                        <CardContent class="p-12 text-center">
                          <div class="w-24 h-24 mx-auto mb-6 rounded-full bg-gradient-to-br from-gray-100 to-gray-200 dark:from-gray-800 dark:to-gray-700 flex items-center justify-center">
                            <Receipt class="w-12 h-12 text-gray-400" />
                          </div>
                          <h3 class="text-xl font-semibold text-gray-900 dark:text-white mb-2">
                            {{ searchQuery || statusFilter !== 'all' ? 'No invoices found' : 'No invoices yet' }}
                          </h3>
                          <p class="text-gray-600 dark:text-gray-400 mb-6">
                            {{ searchQuery || statusFilter !== 'all' ? 'Try adjusting your search criteria' : 'Get started by creating your first invoice' }}
                          </p>
                          <Button v-if="!searchQuery && statusFilter === 'all'" @click="openCreate" class="bg-gradient-to-r from-blue-600 to-cyan-600 hover:from-blue-700 hover:to-cyan-700">
                            <Plus class="w-4 h-4 mr-2" />
                            Create First Invoice
                          </Button>
                          <Button v-else @click="searchQuery = ''; statusFilter = 'all'" variant="outline">
                            Clear Filters
                          </Button>
                        </CardContent>
                      </Card>
                    </div>
                  </div>
                </div>
              </TabsContent>

              <!-- List View -->
              <TabsContent value="list" class="mt-0">
                <div class="space-y-4">
                  <!-- Search and Filters -->
                  <div class="flex flex-col md:flex-row gap-4 items-center">
                    <div class="relative flex-1">
                      <Search class="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400 w-4 h-4" />
                      <Input
                        v-model="searchQuery"
                        placeholder="Search invoices..."
                        class="pl-10 h-12 bg-gray-50 dark:bg-gray-800 border-gray-200 dark:border-gray-700"
                      />
                    </div>

                    <Select v-model="statusFilter">
                      <SelectTrigger class="w-48 h-12">
                        <SelectValue placeholder="Filter by status" />
                      </SelectTrigger>
                      <SelectContent>
                        <SelectItem value="all">All Statuses</SelectItem>
                        <SelectItem value="paid">Paid</SelectItem>
                        <SelectItem value="pending">Pending</SelectItem>
                        <SelectItem value="overdue">Overdue</SelectItem>
                        <SelectItem value="cancelled">Cancelled</SelectItem>
                      </SelectContent>
                    </Select>
                  </div>

                  <!-- Invoices List -->
                  <div class="space-y-3 max-h-96 overflow-y-auto">
                    <Card
                      v-for="(invoice, index) in filteredInvoices"
                      :key="invoice.id"
                      class="border hover:shadow-md transition-shadow cursor-pointer group"
                      @click="openEdit(invoice)"
                    >
                      <CardContent class="p-4">
                        <div class="flex items-center justify-between">
                          <div class="flex items-center space-x-3">
                            <div
                              class="w-3 h-3 rounded-full flex-shrink-0"
                              :style="{ backgroundColor: getStatusColor(invoice.status) }"
                            ></div>
                            <div>
                              <h4 class="font-medium text-gray-900 dark:text-white group-hover:text-blue-600 transition-colors">
                                {{ invoice.patient?.name || 'Unknown' }} - Invoice #{{ invoice.id }}
                              </h4>
                              <p class="text-sm text-gray-600 dark:text-gray-400">
                                {{ formatCurrency(invoice.amount) }} • Due {{ formatDate(invoice.due_date) }}
                                <span v-if="invoice.treatment"> • {{ invoice.treatment.procedure }}</span>
                                <span v-if="invoice.prescription && invoice.prescription.medication"> • {{ invoice.prescription.medication }}</span>
                              </p>
                            </div>
                          </div>

                          <div class="flex items-center space-x-2">
                            <Badge :variant="getStatusBadgeVariant(invoice.status)" class="text-xs">
                              {{ invoice.status }}
                            </Badge>

                            <DropdownMenu>
                              <DropdownMenuTrigger as-child @click.stop>
                                <Button variant="ghost" size="sm" class="h-8 w-8 p-0">
                                  <MoreVertical class="h-4 w-4" />
                                </Button>
                              </DropdownMenuTrigger>
                              <DropdownMenuContent align="end">
                                <DropdownMenuItem @click.stop="openView(invoice)">
                                  <Eye class="w-4 h-4 mr-2" />
                                  View Details
                                </DropdownMenuItem>
                                <DropdownMenuItem @click.stop="downloadPDF(invoice)" v-if="invoice.pdf_path">
                                  <Download class="w-4 h-4 mr-2" />
                                  Download PDF
                                </DropdownMenuItem>
                                <DropdownMenuItem @click.stop="openEdit(invoice)">
                                  <i class="fas fa-edit mr-2"></i>
                                  Edit
                                </DropdownMenuItem>
                                <DropdownMenuItem @click.stop="markAsPaid(invoice)" v-if="invoice.status !== 'paid'">
                                  <CreditCard class="w-4 h-4 mr-2" />
                                  Mark as Paid
                                </DropdownMenuItem>
                                <DropdownMenuItem @click.stop="openDelete(invoice)" class="text-red-600">
                                  <i class="fas fa-trash mr-2"></i>
                                  Delete
                                </DropdownMenuItem>
                              </DropdownMenuContent>
                            </DropdownMenu>
                          </div>
                        </div>
                      </CardContent>
                    </Card>

                    <div v-if="filteredInvoices.length === 0" class="text-center py-8">
                      <Receipt class="w-12 h-12 mx-auto mb-4 text-gray-400" />
                      <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-2">No invoices found</h3>
                      <p class="text-gray-600 dark:text-gray-400">Try adjusting your search criteria or create a new invoice.</p>
                    </div>
                  </div>
                </div>
              </TabsContent>
            </Tabs>
          </CardContent>
        </Card>
      </div>
    </div>

    <!-- Create Invoice Modal -->
    <Dialog :open="isCreateOpen" @update:open="(value) => isCreateOpen = value">
      <DialogContent class="max-w-3xl">
        <DialogHeader>
          <DialogTitle class="text-2xl font-bold bg-gradient-to-r from-blue-600 to-cyan-600 bg-clip-text text-transparent">
            Create New Invoice
          </DialogTitle>
          <DialogDescription class="text-gray-600 dark:text-gray-400">
            Generate an invoice for a patient's treatment or service.
          </DialogDescription>
        </DialogHeader>

        <form @submit.prevent="submitCreate" class="space-y-6">
          <div class="space-y-2">
            <Label for="patient" class="text-gray-700 dark:text-gray-300">Patient</Label>
            <Select v-model="createForm.patient_id" @update:model-value="selectedPatient = props.patients.find(p => p.id === $event) || null">
              <SelectTrigger class="h-12 bg-gray-50 dark:bg-gray-800 border-gray-200 dark:border-gray-700">
                <SelectValue placeholder="Select a patient" />
              </SelectTrigger>
              <SelectContent>
                <SelectItem v-for="patient in props.patients" :key="patient.id" :value="patient.id">
                  {{ patient.name }} ({{ patient.email }})
                </SelectItem>
              </SelectContent>
            </Select>
          </div>

          <div class="space-y-2">
            <Label for="invoice-item" class="text-gray-700 dark:text-gray-300">Invoice Item</Label>
            <Select v-model="selectedInvoiceItem">
              <SelectTrigger class="h-12 bg-gray-50 dark:bg-gray-800 border-gray-200 dark:border-gray-700">
                <SelectValue placeholder="Select a treatment or prescription" />
              </SelectTrigger>
              <SelectContent>
                <template v-if="invoiceItems.length > 0">
                  <SelectItem v-for="item in invoiceItems" :key="item.value" :value="item.value">
                    <div class="flex flex-col text-left space-y-1">
                      <span class="font-medium">{{ item.title }}</span>
                      <span v-if="item.subtitle" class="text-xs text-gray-500 dark:text-gray-400">{{ item.subtitle }}</span>
                      <div class="text-xs text-gray-500 dark:text-gray-400">
                        <template v-if="item.type === 'treatment'">
                          <div>Procedure: {{ formatCurrency(item.treatmentCost || 0) }}</div>
                          <div v-if="(item.prescriptionCost || 0) > 0">Prescription: {{ formatCurrency(item.prescriptionCost || 0) }}</div>
                        </template>
                        <template v-else>
                          <div>Prescription: {{ formatCurrency(item.prescriptionCost || 0) }}</div>
                        </template>
                      </div>
                    </div>
                  </SelectItem>
                </template>
                <SelectItem v-else disabled value="none">No billable items found</SelectItem>
              </SelectContent>
            </Select>
            <div v-if="!selectedPatient" class="text-sm text-gray-500 dark:text-gray-400 p-3 bg-blue-50 dark:bg-blue-900/20 rounded-lg">
              Select a patient to view treatments and prescriptions.
            </div>
            <div v-else-if="invoiceItems.length === 0" class="text-sm text-gray-500 dark:text-gray-400 p-3 bg-gray-50 dark:bg-gray-800 rounded-lg">
              No treatments or prescriptions available for this patient.
            </div>
          </div>

          <div class="grid grid-cols-2 gap-4">
            <div class="space-y-2">
              <Label for="amount" class="text-gray-700 dark:text-gray-300">Amount (UGX) <span class="text-sm text-gray-500">(Auto-calculated)</span></Label>
              <div class="flex items-center h-12 px-4 bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-md text-gray-700 dark:text-gray-200">
                UGX {{ formattedAmountDisplay }}
              </div>
              <input type="hidden" :value="createForm.amount" name="amount" />
              <div v-if="parseFloat(createForm.amount) > 0" class="text-xs text-gray-500 mt-1 space-y-1">
                <div v-if="selectedTreatmentOption">
                  Treatment: {{ formatCurrency(selectedTreatmentOption.cost) }}
                  <div v-if="(selectedTreatmentOption.prescription_amount || 0) > 0">
                    + Prescription: {{ formatCurrency(selectedTreatmentOption.prescription_amount || 0) }}
                  </div>
                </div>
                <div v-else-if="selectedPrescriptionOption">
                  Prescription: {{ formatCurrency(selectedPrescriptionOption.amount || 0) }}
                </div>
              </div>
            </div>

            <div class="space-y-2">
              <Label for="due_date" class="text-gray-700 dark:text-gray-300">Due Date</Label>
              <Input
                id="due_date"
                type="date"
                v-model="createForm.due_date"
                class="h-12 bg-gray-50 dark:bg-gray-800 border-gray-200 dark:border-gray-700"
                required
              />
            </div>
          </div>

          <div class="space-y-2">
            <Label for="notes" class="text-gray-700 dark:text-gray-300">Notes (Optional)</Label>
            <Input
              id="notes"
              v-model="createForm.notes"
              placeholder="Additional invoice details"
              class="h-12 bg-gray-50 dark:bg-gray-800 border-gray-200 dark:border-gray-700"
            />
          </div>

          <DialogFooter class="gap-2">
            <Button type="button" variant="outline" @click="isCreateOpen = false">
              Cancel
            </Button>
            <Button
              type="submit"
              :disabled="createForm.processing"
              class="bg-gradient-to-r from-blue-600 to-cyan-600 hover:from-blue-700 hover:to-cyan-700"
            >
              <i v-if="createForm.processing" class="fas fa-spinner fa-spin mr-2"></i>
              <i v-else class="fas fa-plus mr-2"></i>
              {{ createForm.processing ? 'Creating...' : 'Create Invoice' }}
            </Button>
          </DialogFooter>
        </form>
      </DialogContent>
    </Dialog>

    <!-- Edit Invoice Modal -->
    <Dialog :open="isEditOpen" @update:open="(value) => isEditOpen = value">
      <DialogContent class="max-w-3xl">
        <DialogHeader>
          <DialogTitle class="text-2xl font-bold bg-gradient-to-r from-blue-600 to-cyan-600 bg-clip-text text-transparent">
            Edit Invoice
          </DialogTitle>
          <DialogDescription class="text-gray-600 dark:text-gray-400">
            Update the invoice information below.
          </DialogDescription>
        </DialogHeader>

        <form @submit.prevent="submitEdit" class="space-y-6">
          <div class="space-y-2">
            <Label for="edit-patient" class="text-gray-700 dark:text-gray-300">Patient</Label>
            <Select v-model="editForm.patient_id" @update:model-value="selectedPatient = props.patients.find(p => p.id === $event) || null">
              <SelectTrigger class="h-12 bg-gray-50 dark:bg-gray-800 border-gray-200 dark:border-gray-700">
                <SelectValue placeholder="Select a patient" />
              </SelectTrigger>
              <SelectContent>
                <SelectItem v-for="patient in props.patients" :key="patient.id" :value="patient.id">
                  {{ patient.name }} ({{ patient.email }})
                </SelectItem>
              </SelectContent>
            </Select>
          </div>

          <div v-if="availableTreatments.length > 0" class="space-y-2">
            <Label for="edit-treatment" class="text-gray-700 dark:text-gray-300">Treatment (Optional)</Label>
            <Select v-model="editForm.treatment_id">
              <SelectTrigger class="h-12 bg-gray-50 dark:bg-gray-800 border-gray-200 dark:border-gray-700">
                <SelectValue placeholder="Select a treatment" />
              </SelectTrigger>
              <SelectContent>
                <SelectItem v-for="treatment in availableTreatments" :key="treatment.id" :value="treatment.id">
                  {{ treatment.procedure }} - {{ formatCurrency(treatment.cost) }}
                </SelectItem>
              </SelectContent>
            </Select>
          </div>

          <div class="grid grid-cols-2 gap-4">
            <div class="space-y-2">
              <Label for="edit-amount" class="text-gray-700 dark:text-gray-300">Amount (UGX)</Label>
              <Input
                id="edit-amount"
                type="number"
                v-model="editForm.amount"
                placeholder="0.00"
                step="0.01"
                min="0"
                class="h-12 bg-gray-50 dark:bg-gray-800 border-gray-200 dark:border-gray-700"
                required
              />
            </div>

            <div class="space-y-2">
              <Label for="edit-due_date" class="text-gray-700 dark:text-gray-300">Due Date</Label>
              <Input
                id="edit-due_date"
                type="date"
                v-model="editForm.due_date"
                class="h-12 bg-gray-50 dark:bg-gray-800 border-gray-200 dark:border-gray-700"
                required
              />
            </div>
          </div>

          <div class="space-y-2">
            <Label for="edit-status" class="text-gray-700 dark:text-gray-300">Status</Label>
            <Select v-model="editForm.status">
              <SelectTrigger class="h-12 bg-gray-50 dark:bg-gray-800 border-gray-200 dark:border-gray-700">
                <SelectValue placeholder="Invoice status" />
              </SelectTrigger>
              <SelectContent>
                <SelectItem value="paid">Paid</SelectItem>
                <SelectItem value="pending">Pending</SelectItem>
                <SelectItem value="overdue">Overdue</SelectItem>
                <SelectItem value="cancelled">Cancelled</SelectItem>
              </SelectContent>
            </Select>
          </div>

          <div class="space-y-2">
            <Label for="edit-notes" class="text-gray-700 dark:text-gray-300">Notes (Optional)</Label>
            <Input
              id="edit-notes"
              v-model="editForm.notes"
              placeholder="Additional invoice details"
              class="h-12 bg-gray-50 dark:bg-gray-800 border-gray-200 dark:border-gray-700"
            />
          </div>

          <DialogFooter class="gap-2">
            <Button type="button" variant="outline" @click="isEditOpen = false">
              Cancel
            </Button>
            <Button
              type="submit"
              :disabled="editForm.processing"
              class="bg-gradient-to-r from-blue-600 to-cyan-600 hover:from-blue-700 hover:to-cyan-700"
            >
              <i v-if="editForm.processing" class="fas fa-spinner fa-spin mr-2"></i>
              <i v-else class="fas fa-save mr-2"></i>
              {{ editForm.processing ? 'Saving...' : 'Save Changes' }}
            </Button>
          </DialogFooter>
        </form>
      </DialogContent>
    </Dialog>

    <!-- Delete Modal -->
    <Dialog :open="isDeleteOpen" @update:open="(value) => isDeleteOpen = value">
      <DialogContent class="max-w-md">
        <DialogHeader>
          <DialogTitle class="text-xl font-bold text-red-600">Delete Invoice</DialogTitle>
          <DialogDescription class="text-gray-600 dark:text-gray-400">
            This action cannot be undone. This will permanently delete the invoice record.
          </DialogDescription>
        </DialogHeader>

        <div class="py-4">
          <div class="flex items-center space-x-3 p-4 bg-red-50 dark:bg-red-900/20 rounded-lg border border-red-200 dark:border-red-800">
            <i class="fas fa-exclamation-triangle text-red-600 text-xl"></i>
            <div>
              <p class="font-medium text-red-800 dark:text-red-200">Delete Invoice #{{ editingInvoice?.id }}?</p>
              <p class="text-sm text-red-600 dark:text-red-400">This will remove the invoice permanently.</p>
            </div>
          </div>
        </div>

        <DialogFooter class="gap-2">
          <Button type="button" variant="outline" @click="isDeleteOpen = false">
            Cancel
          </Button>
          <Button
            @click="confirmDelete"
            variant="destructive"
            class="bg-red-600 hover:bg-red-700"
          >
            <i class="fas fa-trash mr-2"></i>
            Delete Invoice
          </Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  </AppLayout>
</template>

<style scoped>
.bg-clip-text {
  -webkit-background-clip: text;
  background-clip: text;
  -webkit-text-fill-color: transparent;
}
</style>