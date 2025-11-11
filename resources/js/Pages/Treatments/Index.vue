<script setup lang="ts">
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import { route } from 'ziggy-js';
import { ref, computed, watch } from 'vue';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/Components/ui/card';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { Badge } from '@/Components/ui/badge';
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogDescription, DialogFooter } from '@/Components/ui/dialog';
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuTrigger } from '@/Components/ui/dropdown-menu';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/Components/ui/select';
import { Plus, Search, MoreVertical, Stethoscope, FileText, Calendar, Upload, Receipt } from 'lucide-vue-next';
import Pagination from '@/Components/ui/Pagination.vue';
import AppLayout from '@/Layouts/AppLayout.vue';

// Interfaces
interface Patient {
  id: number;
  name: string;
  email: string;
}

interface Treatment {
  id: number;
  patient: { id: number; name: string; email?: string };
  procedure: string;
  cost: number;
  notes?: string;
  file_path?: string;
  appointment?: { id: number };
  invoice?: { id: number };
  prescriptions?: Array<{
    id?: number | null;
    medicine_id: number | null;
    dosage: string;
    quantity: number;
    prescription_amount: number;
    medicine?: { medicine_name: string };
  }>;
  created_at?: string;
}

interface DentalMedicine {
  medicine_id: number;
  medicine_name: string;
  category: string;
  dosage_form: string;
  prescription_required: boolean;
}

interface PaginationMeta {
  current_page?: number;
  last_page?: number;
  per_page?: number;
  total?: number;
  from?: number;
  to?: number;
}

interface Props {
  auth: { user: { id: number; name: string } | null };
  treatments: {
    data: Treatment[];
    meta?: PaginationMeta;
  };
  patients: Patient[];
  stats?: {
    total_treatments: number;
    total_revenue: number;
    this_month_treatments: number;
  };
  appointmentTypes: string[];
  medicines: DentalMedicine[];
}

const props = defineProps<Props>();

// State
const searchQuery = ref('');
const filterPatient = ref('all');
const sortBy = ref('created_at');
const sortOrder = ref('desc');
const listViewPerPage = ref((props.treatments?.meta?.per_page ?? 10).toString());
const currentPage = ref(props.treatments?.meta?.current_page ?? 1);

// Computed properties
const totalTreatments = computed(() => props.treatments?.meta?.total ?? props.treatments?.data.length ?? 0);
const lastPage = computed(() => props.treatments?.meta?.last_page ?? 1);

const paginationLinks = computed(() => {
  const links: Array<{ url: string | null; label: string; active: boolean }> = [];
  const current = currentPage.value;
  const last = lastPage.value;

  // Previous link
  links.push({
    url: current > 1 ? '#' : null,
    label: '&laquo; Previous',
    active: current === 1,
  });

  // Page numbers (show current ± 2 pages)
  const startPage = Math.max(1, current - 2);
  const endPage = Math.min(last, current + 2);
  for (let i = startPage; i <= endPage; i++) {
    links.push({
      url: i === current ? null : '#',
      label: i.toString(),
      active: i === current,
    });
  }

  // Next link
  links.push({
    url: current < last ? '#' : null,
    label: 'Next &raquo;',
    active: current === last,
  });

  console.log('Generated Pagination Links:', links); // Debug
  return links;
});

const filteredTreatments = computed(() => {
  let treatments = [...(props.treatments?.data || [])];

  // Apply search filter
  if (searchQuery.value) {
    treatments = treatments.filter(treatment =>
      [treatment.procedure, treatment.patient?.name, treatment.notes]
        .some(field => field?.toLowerCase().includes(searchQuery.value.toLowerCase())) ||
      treatment.prescriptions?.some(pres => pres.medicine_id?.toString().includes(searchQuery.value.toLowerCase()))
    );
  }

  // Apply patient filter
  if (filterPatient.value !== 'all') {
    treatments = treatments.filter(treatment => treatment.patient?.id.toString() === filterPatient.value);
  }

  // Sort treatments
  treatments.sort((a, b) => {
    const key = sortBy.value as keyof Treatment;
    let aValue: any, bValue: any;

    switch (key) {
      case 'procedure':
        aValue = a.procedure.toLowerCase();
        bValue = b.procedure.toLowerCase();
        break;
      case 'cost':
        aValue = a.cost;
        bValue = b.cost;
        break;
      case 'patient_id':
        aValue = a.patient?.name.toLowerCase() || '';
        bValue = b.patient?.name.toLowerCase() || '';
        break;
      case 'created_at':
      default:
        aValue = new Date(a.created_at || 0).getTime();
        bValue = new Date(b.created_at || 0).getTime();
        break;
    }

    return sortOrder.value === 'asc' ? aValue < bValue ? -1 : 1 : aValue < bValue ? 1 : -1;
  });

  return treatments;
});

const paginationSummary = computed(() => {
  const meta = props.treatments?.meta;
  console.log('Pagination Meta:', meta); // Debug
  if (meta) {
    return { from: meta.from ?? 0, to: meta.to ?? 0, total: meta.total ?? totalTreatments.value };
  }
  const count = filteredTreatments.value.length;
  return { from: count ? 1 : 0, to: count, total: totalTreatments.value };
});

// Watchers
watch([searchQuery, filterPatient, sortBy, sortOrder, listViewPerPage], () => {
  currentPage.value = 1; // Reset to page 1 on filter change
  router.post(route('treatments.index'), {
    page: currentPage.value,
    per_page: Number(listViewPerPage.value),
    search: searchQuery.value,
    patient: filterPatient.value,
    sort_by: sortBy.value,
    sort_order: sortOrder.value,
  }, {
    preserveState: true,
    preserveScroll: true,
    replace: true,
  });
});

watch(() => props.treatments?.meta?.per_page, (value) => {
  if (value && value.toString() !== listViewPerPage.value) {
    listViewPerPage.value = value.toString();
  }
});

watch(() => props.treatments?.meta?.current_page, (value) => {
  if (value && value !== currentPage.value) {
    currentPage.value = value;
  }
});

// Pagination navigation
const goToPage = (link: { url: string | null; label: string; active: boolean }) => {
  if (link.active || !link.url) return;

  let newPage = currentPage.value;
  if (link.label.includes('Previous')) {
    newPage = currentPage.value - 1;
  } else if (link.label.includes('Next')) {
    newPage = currentPage.value + 1;
  } else if (!isNaN(parseInt(link.label, 10))) {
    newPage = parseInt(link.label, 10);
  } else {
    return; // Don't proceed if it's not a valid page number
  }

  if (newPage >= 1 && newPage <= lastPage.value) {
    // Use GET instead of POST for pagination
    router.get(route('treatments.index'), {
      page: newPage,
      per_page: Number(listViewPerPage.value),
      search: searchQuery.value,
      patient: filterPatient.value,
      sort_by: sortBy.value,
      sort_order: sortOrder.value,
    }, {
      preserveState: true,
      preserveScroll: true,
      replace: true,
      only: ['treatments', 'filters']
    });
  }
};

// Rest of the script (form handling, modals, etc.)
const createForm = useForm({
  patient_id: null as number | null,
  appointment_id: null as number | null,
  procedure: '',
  cost: 0,
  notes: '',
  file: null as File | null,
  prescriptions: [] as Array<{ id?: number | null; medicine_id: number | null; prescription_amount: number }>,
});

const editForm = useForm({
  patient_id: null as number | null,
  procedure: '',
  cost: '',
  notes: '',
  file: null as File | null,
  prescriptions: [] as Array<{ id?: number | null; medicine_id: number | null; dosage: string; quantity: number; prescription_amount: number }>,
});

const isCreateOpen = ref(false);
const isEditOpen = ref(false);
const isDeleteOpen = ref(false);
const isViewOpen = ref(false);
const editingTreatment = ref<Treatment | null>(null);
const viewingTreatment = ref<Treatment | null>(null);

const openCreate = () => {
  isCreateOpen.value = true;
};

const openEdit = (treatment: Treatment) => {
  if (treatment.invoice) {
    alert('This treatment has an invoice and cannot be edited.');
    return;
  }
  
  editingTreatment.value = treatment;
  
  // Update the form fields directly
  editForm.patient_id = treatment.patient?.id || null;
  editForm.procedure = treatment.procedure;
  editForm.cost = treatment.cost.toString();
  editForm.notes = treatment.notes || '';
  editForm.prescriptions = treatment.prescriptions?.map(pres => ({
    id: pres.id,
    medicine_id: pres.medicine_id,
    dosage: pres.dosage,
    quantity: pres.quantity,
    prescription_amount: pres.prescription_amount,
  })) || [];
  
  isEditOpen.value = true;
};

const openDelete = (treatment: Treatment) => {
  editingTreatment.value = treatment;
  isDeleteOpen.value = true;
};

const openView = (treatment: Treatment) => {
  viewingTreatment.value = treatment;
  isViewOpen.value = true;
};

const createInvoice = (treatment: Treatment) => {
  if (!treatment.id) return;
  router.post(route('treatments.createInvoice', treatment.id), {}, {
    onSuccess: () => router.reload(),
    onError: (errors) => {
      alert('Failed to create invoice. Please try again.');
      console.error(errors);
    },
  });
};

const submitCreate = () => {
  if (!createForm.patient_id || !createForm.procedure || createForm.cost <= 0 || createForm.prescriptions.some(pres => !pres.medicine_id)) {
    alert('Please fill all required fields correctly.');
    return;
  }
  createForm.post(route('treatments.store'), {
    forceFormData: true,
    onSuccess: () => {
      createForm.reset();
      isCreateOpen.value = false;
    },
  });
};

const submitEdit = () => {
  if (!editForm.patient_id || !editForm.procedure || parseFloat(editForm.cost) <= 0 || editForm.prescriptions.some(pres => !pres.medicine_id)) {
    alert('Please fill all required fields correctly.');
    return;
  }
  if (editingTreatment.value) {
    editForm.put(route('treatments.update', editingTreatment.value.id), {
      forceFormData: true,
      onSuccess: () => {
        editForm.reset();
        isEditOpen.value = false;
        editingTreatment.value = null;
      },
    });
  }
};

const confirmDelete = () => {
  if (editingTreatment.value) {
    router.delete(route('treatments.destroy', editingTreatment.value.id), {
      onSuccess: () => {
        isDeleteOpen.value = false;
        editingTreatment.value = null;
      },
    });
  }
};

const getTreatmentIcon = (procedure: string) => {
  const lower = procedure.toLowerCase();
  if (lower.includes('cleaning') || lower.includes('hygiene')) return 'fas fa-toothbrush';
  if (lower.includes('filling') || lower.includes('cavity')) return 'fas fa-fill';
  if (lower.includes('extraction') || lower.includes('removal')) return 'fas fa-tooth';
  if (lower.includes('crown') || lower.includes('bridge')) return 'fas fa-crown';
  if (lower.includes('implant')) return 'fas fa-bolt';
  if (lower.includes('x-ray') || lower.includes('radiograph')) return 'fas fa-x-ray';
  return 'fas fa-stethoscope';
};

const formatUGX = (value: number) => `UGX ${(Number(value) || 0).toLocaleString('en-US')}`;

const calculateTotalCost = (treatment: Treatment) => {
  const baseCost = Number(treatment.cost) || 0;
  const prescriptionsTotal = treatment.prescriptions?.reduce((sum, pres) => sum + (Number(pres.prescription_amount) || 0), 0) ?? 0;
  return baseCost + prescriptionsTotal;
};
</script>

<template>
  <AppLayout title="Treatments">
    <Head title="Treatments" />

    <div class="min-h-screen bg-gray-50 dark:bg-gray-900">
      <div class="container mx-auto px-4 py-8">
        <!-- Header -->
        <div class="mb-8 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
          <div>
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white">Treatment Management</h1>
            <p class="text-gray-600 dark:text-gray-400">Manage dental procedures and patient records</p>
          </div>
          <div class="flex items-center gap-3">
            <Badge class="bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400 px-3 py-1">
              <Stethoscope class="w-4 h-4 mr-2" />
              {{ totalTreatments }} Treatments
            </Badge>
            <Button @click="openCreate" class="bg-blue-600 hover:bg-blue-700 text-white">
              <Plus class="w-4 h-4 mr-2" />
              Add Treatment
            </Button>
          </div>
        </div>

        <!-- Stats -->
        <div v-if="props.stats" class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-8">
          <Card class="shadow-md hover:shadow-lg transition-shadow">
            <CardContent class="p-4 flex justify-between items-center">
              <div>
                <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Total Treatments</p>
                <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ props.stats.total_treatments }}</p>
              </div>
              <Stethoscope class="w-8 h-8 text-blue-500" />
            </CardContent>
          </Card>
          <Card class="shadow-md hover:shadow-lg transition-shadow">
            <CardContent class="p-4 flex justify-between items-center">
              <div>
                <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Total Revenue</p>
                <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ formatUGX(props.stats.total_revenue) }}</p>
              </div>
              <Receipt class="w-8 h-8 text-green-500" />
            </CardContent>
          </Card>
          <Card class="shadow-md hover:shadow-lg transition-shadow">
            <CardContent class="p-4 flex justify-between items-center">
              <div>
                <p class="text-sm font-medium text-gray-600 dark:text-gray-400">This Month</p>
                <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ props.stats.this_month_treatments }}</p>
              </div>
              <Calendar class="w-8 h-8 text-purple-500" />
            </CardContent>
          </Card>
        </div>

        <!-- Main Content -->
        <Card class="shadow-md">
          <CardHeader>
            <CardTitle>Treatment Records</CardTitle>
            <CardDescription>View and manage all treatments</CardDescription>
          </CardHeader>
          <CardContent>
            <!-- Filters -->
            <div class="flex flex-col md:flex-row gap-4 mb-6">
              <div class="relative flex-1">
                <Search class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 w-4 h-4" />
                <Input v-model="searchQuery" placeholder="Search treatments..." class="pl-10" />
              </div>
              <Select v-model="filterPatient">
                <SelectTrigger class="w-48">
                  <SelectValue placeholder="All patients" />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem value="all">All patients</SelectItem>
                  <SelectItem v-for="patient in props.patients" :key="patient.id" :value="patient.id.toString()">
                    {{ patient.name }}
                  </SelectItem>
                </SelectContent>
              </Select>
              <Select v-model="sortBy">
                <SelectTrigger class="w-32">
                  <SelectValue />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem value="created_at">Date</SelectItem>
                  <SelectItem value="procedure">Procedure</SelectItem>
                  <SelectItem value="cost">Cost</SelectItem>
                  <SelectItem value="patient_id">Patient</SelectItem>
                </SelectContent>
              </Select>
              <Button @click="sortOrder = sortOrder === 'asc' ? 'desc' : 'asc'" variant="outline" size="sm">
                <i :class="['fas', sortOrder === 'asc' ? 'fa-arrow-up' : 'fa-arrow-down']"></i>
              </Button>
              <Select v-model="listViewPerPage">
                <SelectTrigger class="w-24">
                  <SelectValue />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem v-for="option in [10, 20, 30, 50]" :key="option" :value="option.toString()">
                    {{ option }}
                  </SelectItem>
                </SelectContent>
              </Select>
            </div>

            <!-- Table -->
            <div class="overflow-x-auto border rounded-lg">
              <table class="w-full">
                <thead class="bg-gray-50 dark:bg-gray-800">
                  <tr>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 dark:text-gray-300">Patient</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 dark:text-gray-300">Procedure</th>
                    <!-- <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 dark:text-gray-300">Prescriptions</th> -->
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 dark:text-gray-300">Notes</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 dark:text-gray-300">Date</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 dark:text-gray-300">Total</th>
                    <th class="px-4 py-3 text-right text-xs font-semibold text-gray-600 dark:text-gray-300">Actions</th>
                  </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                  <tr v-for="treatment in filteredTreatments" :key="treatment.id" class="hover:bg-gray-50 dark:hover:bg-gray-800 cursor-pointer" @click="openView(treatment)">
                    <td class="px-4 py-3">
                      <div class="flex items-center gap-3">
                        <div class="w-8 h-8 rounded-lg bg-blue-500 flex items-center justify-center">
                          <i :class="[getTreatmentIcon(treatment.procedure), 'text-white text-sm']"></i>
                        </div>
                        <div>
                          <p class="font-semibold text-gray-900 dark:text-white">{{ treatment.patient?.name || 'N/A' }}</p>
                          <p class="text-xs text-gray-500 dark:text-gray-400">ID: {{ treatment.id }}</p>
                        </div>
                      </div>
                    </td>
                    <td class="px-4 py-3 text-gray-700 dark:text-gray-300">
                      <div class="flex items-center gap-2">
                        <i class="fa-solid fa-teeth-open text-blue-400"></i>
                        {{ treatment.procedure }}
                      </div>
                      <p class="text-xs text-gray-500 dark:text-gray-400">{{ formatUGX(treatment.cost) }}</p>
                    </td>
                    <!-- <td class="px-4 py-3 text-gray-700 dark:text-gray-300">
                      <div v-if="treatment.prescriptions?.length" class="space-y-1">
                        <div v-for="prescription in treatment.prescriptions" :key="prescription.id || prescription.medicine_id" class="flex items-center gap-2">
                          <i class="fas fa-pills text-blue-400"></i>
                          <span>{{ prescription.medicine?.medicine_name || prescription.medicine_id }}</span>
                          <span class="text-xs text-green-600 dark:text-green-400">{{ formatUGX(prescription.prescription_amount) }}</span>
                        </div>
                      </div>
                      <span v-else class="text-gray-500 italic">None</span>
                    </td> -->
                    <td class="px-4 py-3 text-gray-600 dark:text-gray-400 max-w-xs truncate">{{ treatment.notes || '—' }}</td>
                    <td class="px-4 py-3 text-gray-700 dark:text-gray-300">
                      <div class="flex items-center gap-2">
                        <Calendar class="w-4 h-4 text-gray-400" />
                        {{ treatment.created_at ? new Date(treatment.created_at).toLocaleDateString() : '—' }}
                      </div>
                    </td>
                    <td class="px-4 py-3 text-red-600 dark:text-red-400 font-semibold">{{ formatUGX(calculateTotalCost(treatment)) }}</td>
                    <td class="px-4 py-3 text-right" @click.stop>
                      <div class="flex justify-end">
                        <DropdownMenu>
                          <DropdownMenuTrigger as-child>
                            <Button variant="ghost" size="sm" class="h-8 w-8 p-0">
                              <MoreVertical class="h-4 w-4" />
                            </Button>
                          </DropdownMenuTrigger>
                          <DropdownMenuContent align="end" class="w-48">
                            <DropdownMenuItem @click="openView(treatment)">
                              <i class="fas fa-eye mr-2 w-4 h-4 text-center"></i>View Details
                            </DropdownMenuItem>
                            <DropdownMenuItem v-if="!treatment.invoice" @click="openEdit(treatment)">
                              <i class="fas fa-edit mr-2 w-4 h-4 text-center"></i>Edit
                            </DropdownMenuItem>
                            <DropdownMenuItem v-if="!treatment.invoice" @click="createInvoice(treatment)" class="text-green-600">
                              <FileText class="w-4 h-4 mr-2" />Create Invoice
                            </DropdownMenuItem>
                            <DropdownMenuItem @click="openDelete(treatment)" class="text-red-600">
                              <i class="fas fa-trash mr-2 w-4 h-4 text-center"></i>Delete
                            </DropdownMenuItem>
                          </DropdownMenuContent>
                        </DropdownMenu>
                      </div>
                    </td>
                  </tr>
                  <tr v-if="!filteredTreatments.length">
                    <td colspan="7" class="px-6 py-12 text-center text-gray-600 dark:text-gray-400">
                      <Stethoscope class="w-10 h-10 mx-auto mb-4 text-gray-400" />
                      <p class="text-lg font-semibold">
                        {{ searchQuery || filterPatient !== 'all' ? 'No treatments found' : 'No treatments yet' }}
                      </p>
                      <p class="text-sm">
                        {{ searchQuery || filterPatient !== 'all' ? 'Try adjusting your filters' : 'Add your first treatment to get started.' }}
                      </p>
                      <Button
                        v-if="!searchQuery && filterPatient === 'all'"
                        @click="openCreate"
                        class="mt-4 bg-blue-600 hover:bg-blue-700 text-white"
                      >
                        <Plus class="w-4 h-4 mr-2" />Add Treatment
                      </Button>
                      <Button v-else @click="searchQuery = ''; filterPatient = 'all'" variant="outline" class="mt-4">
                        Clear Filters
                      </Button>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>

            <!-- Pagination -->
            <div v-if="paginationLinks.length > 0" class="flex flex-col md:flex-row md:items-center md:justify-between items-start gap-4 mt-6">
              <div class="flex-1">
                <Pagination
                  v-if="paginationLinks.length > 1"
                  :links="paginationLinks"
                  :from="paginationSummary.from"
                  :to="paginationSummary.to"
                  :total="paginationSummary.total"
                  :item-name="paginationSummary.total === 1 ? 'treatment' : 'treatments'"
                  @page-change="goToPage"
                />
              </div>
              <div class="flex items-center gap-2">
                <Label for="per-page" class="text-sm text-gray-600 dark:text-gray-400 whitespace-nowrap">Per page:</Label>
                <Select v-model="listViewPerPage" @update:model-value="() => fetchTreatments()">
                  <SelectTrigger class="h-8 w-20">
                    <SelectValue />
                  </SelectTrigger>
                  <SelectContent>
                    <SelectItem v-for="option in [10, 20, 30, 50]" :key="option" :value="option.toString()">
                      {{ option }}
                    </SelectItem>
                  </SelectContent>
                </Select>
              </div>
            </div>
          </CardContent>
        </Card>
      </div>

      <!-- Modals -->
      <Dialog v-model:open="isCreateOpen">
        <DialogContent class="max-w-3xl max-h-[90vh] overflow-y-auto">
          <DialogHeader>
            <DialogTitle class="text-2xl font-bold text-gray-900 dark:text-white">Add New Treatment</DialogTitle>
            <DialogDescription>Record a new dental procedure</DialogDescription>
          </DialogHeader>
          <form @submit.prevent="submitCreate" class="space-y-4">
            <div>
              <Label>Patient</Label>
              <Select v-model="createForm.patient_id">
                <SelectTrigger><SelectValue placeholder="Select a patient" /></SelectTrigger>
                <SelectContent>
                  <SelectItem v-for="patient in props.patients" :key="patient.id" :value="patient.id">
                    {{ patient.name }} ({{ patient.email }})
                  </SelectItem>
                </SelectContent>
              </Select>
            </div>
            <div>
              <Label>Procedure</Label>
              <Select v-model="createForm.procedure">
                <SelectTrigger><SelectValue placeholder="Select a procedure" /></SelectTrigger>
                <SelectContent>
                  <SelectItem v-for="type in props.appointmentTypes" :key="type" :value="type">{{ type }}</SelectItem>
                </SelectContent>
              </Select>
            </div>
            <div>
              <Label>Cost (UGX)</Label>
              <Input v-model="createForm.cost" type="number" placeholder="0.00" step="0.01" min="0" required />
            </div>
            <div class="space-y-2">
              <div class="flex items-center justify-between">
                <Label class="text-lg font-medium">Prescriptions</Label>
                <Button type="button" variant="outline" size="sm" @click="createForm.prescriptions.push({ id: null, medicine_id: null, prescription_amount: 0 })">
                  <Plus class="w-4 h-4 mr-2" />Add
                </Button>
              </div>
              <div v-for="(prescription, index) in createForm.prescriptions" :key="index" class="grid grid-cols-12 gap-2 items-end">
                <div class="col-span-8">
                  <Select v-model="prescription.medicine_id">
                    <SelectTrigger><SelectValue placeholder="Select a medicine" /></SelectTrigger>
                    <SelectContent>
                      <SelectItem v-for="medicine in props.medicines" :key="medicine.medicine_id" :value="medicine.medicine_id">
                        {{ medicine.medicine_name }}
                      </SelectItem>
                    </SelectContent>
                  </Select>
                </div>
                <div class="col-span-3">
                  <Input v-model="prescription.prescription_amount" type="number" placeholder="0.00" step="0.01" min="0" />
                </div>
                <div class="col-span-1">
                  <Button variant="destructive" size="sm" @click="createForm.prescriptions.splice(index, 1)">
                    <i class="fas fa-trash"></i>
                  </Button>
                </div>
              </div>
            </div>
            <div>
              <Label>Notes (Optional)</Label>
              <Input v-model="createForm.notes" placeholder="Additional details" />
            </div>
            <div>
              <Label>Upload File (Optional)</Label>
              <Input type="file" @change="(e) => (createForm.file = (e.target as HTMLInputElement).files?.[0] || null)" accept="image/*,.pdf" />
              <p class="text-xs text-gray-500 dark:text-gray-400">JPEG, PNG, JPG, PDF (max 10MB)</p>
            </div>
            <DialogFooter>
              <Button variant="outline" @click="isCreateOpen = false">Cancel</Button>
              <Button type="submit" :disabled="createForm.processing" class="bg-blue-600 hover:bg-blue-700">
                <i v-if="createForm.processing" class="fas fa-spinner fa-spin mr-2"></i>
                <i v-else class="fas fa-plus mr-2"></i>
                {{ createForm.processing ? 'Creating...' : 'Create' }}
              </Button>
            </DialogFooter>
          </form>
        </DialogContent>
      </Dialog>

      <Dialog v-model:open="isEditOpen">
        <DialogContent class="max-w-3xl max-h-[90vh] overflow-y-auto">
          <DialogHeader>
            <DialogTitle class="text-2xl font-bold text-gray-900 dark:text-white">Edit Treatment</DialogTitle>
            <DialogDescription>Update treatment details</DialogDescription>
          </DialogHeader>
          <form @submit.prevent="submitEdit" class="space-y-4">
            <div>
              <Label>Patient</Label>
              <Select v-model="editForm.patient_id">
                <SelectTrigger><SelectValue placeholder="Select a patient" /></SelectTrigger>
                <SelectContent>
                  <SelectItem v-for="patient in props.patients" :key="patient.id" :value="patient.id">
                    {{ patient.name }} ({{ patient.email }})
                  </SelectItem>
                </SelectContent>
              </Select>
            </div>
            <div>
              <Label>Procedure</Label>
              <Select v-model="editForm.procedure">
                <SelectTrigger><SelectValue placeholder="Select a procedure" /></SelectTrigger>
                <SelectContent>
                  <SelectItem v-for="type in props.appointmentTypes" :key="type" :value="type">{{ type }}</SelectItem>
                </SelectContent>
              </Select>
            </div>
            <div>
              <Label>Cost (UGX)</Label>
              <Input v-model="editForm.cost" type="number" placeholder="0.00" step="0.01" min="0" required />
            </div>
            <div class="space-y-2">
              <div class="flex items-center justify-between">
                <Label class="text-lg font-medium">Prescriptions</Label>
                <Button type="button" variant="outline" size="sm" @click="editForm.prescriptions.push({ id: null, medicine_id: null, dosage: '', quantity: 0, prescription_amount: 0 })">
                  <Plus class="w-4 h-4 mr-2" />Add
                </Button>
              </div>
              <div v-for="(prescription, index) in editForm.prescriptions" :key="index" class="grid grid-cols-12 gap-2 items-end">
                <input type="hidden" v-model="prescription.id" />
                <div class="col-span-5">
                  <Select v-model="prescription.medicine_id">
                    <SelectTrigger><SelectValue placeholder="Select a medicine" /></SelectTrigger>
                    <SelectContent>
                      <SelectItem v-for="medicine in props.medicines" :key="medicine.medicine_id" :value="medicine.medicine_id">
                        {{ medicine.medicine_name }} ({{ medicine.dosage_form }})
                      </SelectItem>
                    </SelectContent>
                  </Select>
                </div>
                <div class="col-span-2">
                  <Input v-model="prescription.prescription_amount" type="number" placeholder="0.00" step="0.01" min="0" />
                </div>
                <div class="col-span-1">
                  <Button variant="destructive" size="sm" @click="editForm.prescriptions.splice(index, 1)">
                    <i class="fas fa-trash"></i>
                  </Button>
                </div>
              </div>
            </div>
            <div>
              <Label>Notes (Optional)</Label>
              <Input v-model="editForm.notes" placeholder="Additional details" />
            </div>
            <div>
              <Label>Upload File (Optional)</Label>
              <Input type="file" @change="(e) => (editForm.file = (e.target as HTMLInputElement).files?.[0] || null)" accept="image/*,.pdf" />
              <p class="text-xs text-gray-500 dark:text-gray-400">JPEG, PNG, JPG, PDF (max 10MB)</p>
            </div>
            <DialogFooter>
              <Button variant="outline" @click="isEditOpen = false">Cancel</Button>
              <Button type="submit" :disabled="editForm.processing" class="bg-blue-600 hover:bg-blue-700">
                <i v-if="editForm.processing" class="fas fa-spinner fa-spin mr-2"></i>
                <i v-else class="fas fa-save mr-2"></i>
                {{ editForm.processing ? 'Saving...' : 'Save' }}
              </Button>
            </DialogFooter>
          </form>
        </DialogContent>
      </Dialog>

      <Dialog v-model:open="isDeleteOpen">
        <DialogContent class="max-w-md">
          <DialogHeader>
            <DialogTitle class="text-xl font-bold text-red-600">Delete Treatment</DialogTitle>
            <DialogDescription>This action cannot be undone.</DialogDescription>
          </DialogHeader>
          <div class="p-4 bg-red-50 dark:bg-red-900/20 rounded-lg">
            <p class="font-medium text-red-800 dark:text-red-200">Delete "{{ editingTreatment?.procedure }}"?</p>
            <p class="text-sm text-red-600 dark:text-red-400">This will permanently remove the treatment record.</p>
          </div>
          <DialogFooter>
            <Button variant="outline" @click="isDeleteOpen = false">Cancel</Button>
            <Button variant="destructive" @click="confirmDelete">Delete</Button>
          </DialogFooter>
        </DialogContent>
      </Dialog>

      <Dialog v-model:open="isViewOpen">
        <DialogContent class="max-w-3xl max-h-[90vh] overflow-y-auto">
          <DialogHeader>
            <DialogTitle class="text-2xl font-bold text-gray-900 dark:text-white">Treatment Details</DialogTitle>
            <DialogDescription>View complete treatment information</DialogDescription>
          </DialogHeader>
          <div v-if="viewingTreatment" class="space-y-4">
            <div class="p-4 bg-gray-50 dark:bg-gray-800 rounded-lg flex items-start gap-4">
              <div class="w-12 h-12 rounded-lg bg-blue-500 flex items-center justify-center">
                <i :class="[getTreatmentIcon(viewingTreatment.procedure), 'text-white text-lg']"></i>
              </div>
              <div>
                <p class="font-semibold text-gray-900 dark:text-white">{{ viewingTreatment.patient?.name || 'N/A' }}</p>
                <p class="text-sm text-gray-600 dark:text-gray-400">Treatment ID: {{ viewingTreatment.id }}</p>
                <p class="text-sm text-gray-600 dark:text-gray-400">
                  Created: {{ viewingTreatment.created_at ? new Date(viewingTreatment.created_at).toLocaleDateString() : '—' }}
                </p>
              </div>
            </div>
            <div>
              <h4 class="text-lg font-medium text-gray-900 dark:text-white">Cost Breakdown</h4>
              <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="p-4 bg-gray-50 dark:bg-gray-800 rounded-lg">
                  <div class="flex justify-between">
                    <span class="text-gray-700 dark:text-gray-300">Procedure</span>
                    <span class="font-medium text-green-600">{{ formatUGX(viewingTreatment.cost) }}</span>
                  </div>
                  <p class="text-sm text-gray-600 dark:text-gray-400">{{ viewingTreatment.procedure }}</p>
                </div>
                <div v-if="viewingTreatment.prescriptions?.length" class="p-4 bg-gray-50 dark:bg-gray-800 rounded-lg">
                  <div class="flex justify-between">
                    <span class="text-gray-700 dark:text-gray-300">Prescriptions</span>
                    <span class="font-medium text-green-600">{{ formatUGX(viewingTreatment.prescriptions.reduce((acc, p) => acc + Number(p.prescription_amount || 0), 0)) }}</span>
                  </div>
                  <div v-for="prescription in viewingTreatment.prescriptions" :key="prescription.id || prescription.medicine_id" class="mt-2">
                    <div class="flex justify-between">
                      <span class="text-gray-600 dark:text-gray-400">{{ prescription.medicine?.medicine_name || prescription.medicine_id }}</span>
                      <span class="text-sm">{{ formatUGX(prescription.prescription_amount) }}</span>
                    </div>
                  </div>
                </div>
                <div class="col-span-full pt-4 border-t">
                  <div class="flex justify-between font-bold text-lg text-red-600">
                    <span>Total Cost</span>
                    <span>{{ formatUGX(calculateTotalCost(viewingTreatment)) }}</span>
                  </div>
                </div>
              </div>
            </div>
            <Card v-if="viewingTreatment.notes">
              <CardHeader>
                <CardTitle class="text-lg flex items-center">
                  <FileText class="w-5 h-5 mr-2 text-purple-600" />Notes
                </CardTitle>
              </CardHeader>
              <CardContent>
                <p class="text-gray-700 dark:text-gray-300">{{ viewingTreatment.notes }}</p>
              </CardContent>
            </Card>
            <Card v-if="viewingTreatment.file_path">
              <CardHeader>
                <CardTitle class="text-lg flex items-center">
                  <Upload class="w-5 h-5 mr-2 text-orange-600" />Attachment
                </CardTitle>
              </CardHeader>
              <CardContent>
                <div class="flex items-center gap-3 p-3 bg-orange-50 dark:bg-orange-900/20 rounded-lg">
                  <i class="fas fa-file text-orange-600"></i>
                  <div>
                    <p class="font-medium text-gray-900 dark:text-white">Attachment Available</p>
                    <Button size="sm" variant="outline" class="mt-2">
                      <i class="fas fa-download mr-2"></i>Download
                    </Button>
                  </div>
                </div>
              </CardContent>
            </Card>
          </div>
          <DialogFooter>
            <Button variant="outline" @click="isViewOpen = false">Close</Button>
          </DialogFooter>
        </DialogContent>
      </Dialog>
    </div>
  </AppLayout>
</template>

<style scoped>
/* Minimal Tailwind-based styles */
</style>