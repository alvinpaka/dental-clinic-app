<script setup lang="ts">
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import { route } from 'ziggy-js';
import { ref, computed } from 'vue';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/Components/ui/card';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { Badge } from '@/Components/ui/badge';
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogDescription, DialogFooter } from '@/Components/ui/dialog';
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuTrigger } from '@/Components/ui/dropdown-menu';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/Components/ui/tabs';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/Components/ui/select';
import { Plus, Search, MoreVertical, Stethoscope, FileText, User, Calendar, Upload, Receipt } from 'lucide-vue-next';
import AppLayout from '@/Layouts/AppLayout.vue';

interface Patient {
  id: number;
  name: string;
  email: string;
}

interface Treatment {
  id: number;
  patient: { id: number; name: string };
  procedure: string;
  cost: number;
  notes?: string;
  file_path?: string;
  appointment?: { id: number };
  invoice?: { id: number };
  created_at?: string;
}

interface Props {
  treatments: {
    data: Treatment[];
    links: any[];
  };
  patients: Patient[];
  stats?: {
    total_treatments: number;
    total_revenue: number;
    this_month_treatments: number;
  };
  appointmentTypes?: string[];
}

const props = defineProps<Props>();

const searchQuery = ref('');
const filterPatient = ref('all');
const sortBy = ref('created_at');
const sortOrder = ref('desc');
const activeTab = ref('grid');

// Filtered and sorted treatments
const filteredTreatments = computed(() => {
  if (!props.treatments?.data) return [];

  let treatments = [...props.treatments.data];

  // Search filter
  if (searchQuery.value) {
    treatments = treatments.filter(treatment =>
      treatment.procedure.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
      treatment.patient?.name.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
      (treatment.notes && treatment.notes.toLowerCase().includes(searchQuery.value.toLowerCase()))
    );
  }

  // Patient filter
  if (filterPatient.value !== 'all') {
    treatments = treatments.filter(treatment =>
      treatment.patient?.id.toString() === filterPatient.value
    );
  }

  // Sort
  treatments.sort((a, b) => {
    let aValue: any, bValue: any;

    switch (sortBy.value) {
      case 'procedure':
        aValue = a.procedure.toLowerCase();
        bValue = b.procedure.toLowerCase();
        break;
      case 'cost':
        aValue = a.cost;
        bValue = b.cost;
        break;
      case 'patient':
        aValue = a.patient?.name.toLowerCase() || '';
        bValue = b.patient?.name.toLowerCase() || '';
        break;
      case 'created_at':
      default:
        aValue = new Date(a.created_at || 0).getTime();
        bValue = new Date(b.created_at || 0).getTime();
        break;
    }

    if (aValue < bValue) return sortOrder.value === 'asc' ? -1 : 1;
    if (aValue > bValue) return sortOrder.value === 'asc' ? 1 : -1;
    return 0;
  });

  return treatments;
});

const createForm = useForm({
  patient_id: null as number | null,
  appointment_id: null as number | null,
  procedure: '',
  cost: 0,
  notes: '',
  file: null as File | null,
});

const editForm = useForm({
  patient_id: null as number | null,
  procedure: '',
  cost: '',
  notes: '',
  file: null as File | null,
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
  if ((treatment as any).invoice) {
    alert('This treatment has an invoice and cannot be edited.');
    return;
  }
  editingTreatment.value = treatment;
  editForm.patient_id = treatment.patient?.id || null;
  editForm.procedure = treatment.procedure;
  editForm.cost = treatment.cost.toString();
  editForm.notes = treatment.notes || '';
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

const submitCreate = () => {
  if (!createForm.patient_id) {
    alert('Please select a patient');
    return;
  }
  if (!createForm.procedure) {
    alert('Please select a procedure');
    return;
  }
  if (!createForm.cost || createForm.cost <= 0) {
    alert('Please enter a valid cost');
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
  if (!editForm.patient_id) {
    alert('Please select a patient');
    return;
  }
  if (!editForm.procedure) {
    alert('Please select a procedure');
    return;
  }
  if (!editForm.cost || parseFloat(editForm.cost) <= 0) {
    alert('Please enter a valid cost');
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

// Treatment type icons
const getTreatmentIcon = (procedure: string) => {
  const lowerProcedure = procedure.toLowerCase();
  if (lowerProcedure.includes('cleaning') || lowerProcedure.includes('hygiene')) {
    return 'fas fa-toothbrush';
  }
  if (lowerProcedure.includes('filling') || lowerProcedure.includes('cavity')) {
    return 'fas fa-fill';
  }
  if (lowerProcedure.includes('extraction') || lowerProcedure.includes('removal')) {
    return 'fas fa-tooth';
  }
  if (lowerProcedure.includes('crown') || lowerProcedure.includes('bridge')) {
    return 'fas fa-crown';
  }
  if (lowerProcedure.includes('implant')) {
    return 'fas fa-bolt';
  }
  if (lowerProcedure.includes('x-ray') || lowerProcedure.includes('radiograph')) {
    return 'fas fa-x-ray';
  }
  return 'fas fa-stethoscope';
};

const formatUGX = (value: number) => {
  const whole = Math.round(value);
  return `UGX ${whole.toLocaleString('en-US')}`;
};
</script>

<template>
  <AppLayout title="Treatments">
    <Head title="Treatments" />

    <div class="min-h-screen bg-gradient-to-br from-blue-50 via-white to-cyan-50 dark:from-gray-900 dark:via-gray-800 dark:to-blue-900">
      <div class="container mx-auto px-4 py-8">
        <!-- Header Section -->
        <div class="mb-8">
          <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div>
              <h1 class="text-4xl font-bold bg-gradient-to-r from-blue-600 to-cyan-600 bg-clip-text text-transparent mb-2">
                Treatment Management
              </h1>
              <p class="text-gray-600 dark:text-gray-400 text-lg">
                Track and manage dental procedures and treatments
              </p>
            </div>

            <div class="flex items-center gap-3">
              <Badge variant="secondary" class="bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400">
                <Stethoscope class="w-4 h-4 mr-1" />
                {{ props.treatments.data.length }} Total Treatments
              </Badge>

              <Button @click="openCreate" class="bg-gradient-to-r from-blue-600 to-cyan-600 hover:from-blue-700 hover:to-cyan-700 text-white shadow-lg hover:shadow-xl transition-all duration-300">
                <Plus class="w-4 h-4 mr-2" />
                Add Treatment
              </Button>
            </div>
          </div>
        </div>

        <!-- Stats Overview -->
        <div v-if="props.stats" class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
          <Card class="border-0 shadow-xl hover:shadow-2xl transition-all duration-300 hover:-translate-y-1 bg-gradient-to-br from-blue-50 to-blue-100 dark:from-blue-900/20 dark:to-blue-800/20">
            <CardContent class="p-6">
              <div class="flex items-center justify-between">
                <div>
                  <p class="text-sm font-medium text-blue-700 dark:text-blue-300 mb-1">Total Treatments</p>
                  <p class="text-3xl font-bold text-blue-900 dark:text-blue-100 mb-1">{{ props.stats.total_treatments }}</p>
                  <p class="text-xs text-blue-600 dark:text-blue-400">All procedures performed</p>
                </div>
                <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-blue-500 to-blue-600 flex items-center justify-center shadow-lg">
                  <Stethoscope class="w-6 h-6 text-white" />
                </div>
              </div>
            </CardContent>
          </Card>

          <Card class="border-0 shadow-xl hover:shadow-2xl transition-all duration-300 hover:-translate-y-1 bg-gradient-to-br from-green-50 to-green-100 dark:from-green-900/20 dark:to-green-800/20">
            <CardContent class="p-6">
              <div class="flex items-center justify-between">
                <div>
                  <p class="text-sm font-medium text-green-700 dark:text-green-300 mb-1">Total Revenue</p>
                  <p class="text-3xl font-bold text-green-900 dark:text-green-100 mb-1">UGX{{ props.stats.total_revenue.toLocaleString() }}</p>
                  <p class="text-xs text-green-600 dark:text-green-400">From all treatments</p>
                </div>
                <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-green-500 to-green-600 flex items-center justify-center shadow-lg">
                  < class="w-6 h-6 text-white" />
                </div>
              </div>
            </CardContent>
          </Card>

          <Card class="border-0 shadow-xl hover:shadow-2xl transition-all duration-300 hover:-translate-y-1 bg-gradient-to-br from-purple-50 to-purple-100 dark:from-purple-900/20 dark:to-purple-800/20">
            <CardContent class="p-6">
              <div class="flex items-center justify-between">
                <div>
                  <p class="text-sm font-medium text-purple-700 dark:text-purple-300 mb-1">This Month</p>
                  <p class="text-3xl font-bold text-purple-900 dark:text-purple-100 mb-1">{{ props.stats.this_month_treatments }}</p>
                  <p class="text-xs text-purple-600 dark:text-purple-400">Treatments performed</p>
                </div>
                <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-purple-500 to-purple-600 flex items-center justify-center shadow-lg">
                  <Calendar class="w-6 h-6 text-white" />
                </div>
              </div>
            </CardContent>
          </Card>
        </div>

        <!-- Main Content -->
        <Card class="border-0 shadow-xl bg-white dark:bg-gray-900 mb-8">
          <CardHeader class="pb-4">
            <div>
              <CardTitle class="text-2xl text-gray-900 dark:text-white">Treatment Management</CardTitle>
              <CardDescription class="text-gray-600 dark:text-gray-400">
                View and manage all treatment records
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
                        placeholder="Search treatments by procedure, patient, or notes..."
                        class="pl-10 h-12 bg-gray-50 dark:bg-gray-800 border-gray-200 dark:border-gray-700"
                      />
                    </div>

                    <div class="flex items-center gap-2">
                      <Label class="text-sm font-medium text-gray-700 dark:text-gray-300">Patient:</Label>
                      <Select v-model="filterPatient">
                        <SelectTrigger class="w-48">
                          <SelectValue placeholder="All patients" />
                        </SelectTrigger>
                        <SelectContent>
                          <SelectItem value="all">All patients</SelectItem>
                          <SelectItem v-for="patient in (props.patients || [])" :key="patient.id" :value="patient.id.toString()">
                            {{ patient.name }}
                          </SelectItem>
                        </SelectContent>
                      </Select>
                    </div>

                    <div class="flex items-center gap-2">
                      <Label class="text-sm font-medium text-gray-700 dark:text-gray-300">Sort by:</Label>
                      <Select v-model="sortBy">
                        <SelectTrigger class="w-32">
                          <SelectValue />
                        </SelectTrigger>
                        <SelectContent>
                          <SelectItem value="created_at">Date</SelectItem>
                          <SelectItem value="procedure">Procedure</SelectItem>
                          <SelectItem value="cost">Cost</SelectItem>
                          <SelectItem value="patient">Patient</SelectItem>
                        </SelectContent>
                      </Select>

                      <Button
                        @click="sortOrder = sortOrder === 'asc' ? 'desc' : 'asc'"
                        variant="outline"
                        size="sm"
                        class="px-3"
                      >
                        <i :class="['fas', sortOrder === 'asc' ? 'fa-arrow-up' : 'fa-arrow-down', 'text-sm']"></i>
                      </Button>
                    </div>
                  </div>

                  <!-- Treatments Grid -->
                  <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <Card
                      v-for="(treatment, index) in filteredTreatments"
                      :key="treatment.id"
                      class="border-0 shadow-xl hover:shadow-2xl transition-all duration-300 hover:-translate-y-2 bg-white dark:bg-gray-900 group"
                    >
                      <CardHeader class="pb-4">
                        <div class="flex items-start justify-between">
                          <div class="flex items-center space-x-3">
                            <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-blue-500 to-cyan-500 flex items-center justify-center shadow-lg">
                              <i :class="[getTreatmentIcon(treatment.procedure), 'text-white text-lg']"></i>
                            </div>
                            <div>
                              <CardTitle class="text-lg text-gray-900 dark:text-white group-hover:text-blue-600 dark:group-hover:text-cyan-400 transition-colors line-clamp-2">
                                {{ treatment.procedure }}
                              </CardTitle>
                              <CardDescription class="text-gray-600 dark:text-gray-400">
                                ID: {{ treatment.id }}
                              </CardDescription>
                            </div>
                          </div>

                          <DropdownMenu>
                            <DropdownMenuTrigger as-child>
                              <Button variant="ghost" size="sm" class="h-8 w-8 p-0 opacity-0 group-hover:opacity-100 transition-opacity">
                                <MoreVertical class="h-4 w-4" />
                              </Button>
                            </DropdownMenuTrigger>
                            <DropdownMenuContent align="end">
                              <DropdownMenuItem @click="openView(treatment)">
                                <i class="fas fa-eye mr-2"></i>
                                View Details
                              </DropdownMenuItem>
                              <DropdownMenuItem v-if="!treatment.invoice" @click="openEdit(treatment)">
                                <i class="fas fa-edit mr-2"></i>
                                Edit Treatment
                              </DropdownMenuItem>
                              <DropdownMenuItem @click="openDelete(treatment)" class="text-red-600">
                                <i class="fas fa-trash mr-2"></i>
                                Delete Treatment
                              </DropdownMenuItem>
                            </DropdownMenuContent>
                          </DropdownMenu>
                        </div>
                      </CardHeader>

                      <CardContent class="space-y-4">
                        <div class="grid grid-cols-2 gap-4 text-sm">
                          <div class="flex items-center space-x-2">
                            <User class="w-4 h-4 text-gray-400" />
                            <span class="text-gray-600 dark:text-gray-400 truncate">{{ treatment.patient?.name || 'N/A' }}</span>
                          </div>
                          <div class="flex items-center space-x-2">
                            <Receipt class="w-4 h-4 text-gray-400" />
                            <span class="text-gray-600 dark:text-gray-400 font-medium">{{ formatUGX(treatment.cost) }}</span>
                          </div>
                          <div v-if="treatment.notes" class="flex items-start space-x-2 col-span-2">
                            <FileText class="w-4 h-4 text-gray-400 mt-0.5 flex-shrink-0" />
                            <span class="text-gray-600 dark:text-gray-400 line-clamp-2">{{ treatment.notes }}</span>
                          </div>
                          <div v-if="treatment.file_path" class="flex items-center space-x-2 col-span-2">
                            <Upload class="w-4 h-4 text-gray-400" />
                            <span class="text-gray-600 dark:text-gray-400">Attachment available</span>
                          </div>
                        </div>

                        <div class="flex justify-between items-center pt-4 border-t border-gray-100 dark:border-gray-800">
                          <Button variant="outline" size="sm" @click="openView(treatment)">
                            <i class="fas fa-eye mr-2"></i>
                            View Details
                          </Button>
                          <Button v-if="!treatment.invoice" size="sm" as-child>
                            <Link :href="route('invoices.create', { treatment_id: treatment.id })">
                              <FileText class="w-4 h-4 mr-2" />
                              Create Invoice
                            </Link>
                          </Button>
                        </div>
                      </CardContent>
                    </Card>

                    <!-- Empty State -->
                    <div v-if="filteredTreatments.length === 0" class="col-span-full">
                      <Card class="border-0 shadow-xl bg-white dark:bg-gray-900">
                        <CardContent class="p-12 text-center">
                          <div class="w-24 h-24 mx-auto mb-6 rounded-full bg-gradient-to-br from-gray-100 to-gray-200 dark:from-gray-800 dark:to-gray-700 flex items-center justify-center">
                            <Stethoscope class="w-12 h-12 text-gray-400" />
                          </div>
                          <h3 class="text-xl font-semibold text-gray-900 dark:text-white mb-2">
                            {{ searchQuery || filterPatient !== 'all' ? 'No treatments found' : 'No treatments yet' }}
                          </h3>
                          <p class="text-gray-600 dark:text-gray-400 mb-6">
                            {{ searchQuery || filterPatient !== 'all' ? 'Try adjusting your search criteria' : 'Get started by adding your first treatment' }}
                          </p>
                          <Button v-if="!searchQuery && filterPatient === 'all'" @click="openCreate" class="bg-gradient-to-r from-blue-600 to-cyan-600 hover:from-blue-700 hover:to-cyan-700">
                            <Plus class="w-4 h-4 mr-2" />
                            Add First Treatment
                          </Button>
                          <Button v-else @click="searchQuery = ''; filterPatient = 'all'" variant="outline">
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
                        placeholder="Search treatments..."
                        class="pl-10 h-12 bg-gray-50 dark:bg-gray-800 border-gray-200 dark:border-gray-700"
                      />
                    </div>

                    <div class="flex items-center gap-2">
                      <Label class="text-sm font-medium text-gray-700 dark:text-gray-300">Patient:</Label>
                      <Select v-model="filterPatient">
                        <SelectTrigger class="w-48">
                          <SelectValue placeholder="All patients" />
                        </SelectTrigger>
                        <SelectContent>
                          <SelectItem value="all">All patients</SelectItem>
                          <SelectItem v-for="patient in (props.patients || [])" :key="patient.id" :value="patient.id.toString()">
                            {{ patient.name }}
                          </SelectItem>
                        </SelectContent>
                      </Select>
                    </div>

                    <div class="flex items-center gap-2">
                      <Label class="text-sm font-medium text-gray-700 dark:text-gray-300">Sort by:</Label>
                      <Select v-model="sortBy">
                        <SelectTrigger class="w-32">
                          <SelectValue />
                        </SelectTrigger>
                        <SelectContent>
                          <SelectItem value="created_at">Date</SelectItem>
                          <SelectItem value="procedure">Procedure</SelectItem>
                          <SelectItem value="cost">Cost</SelectItem>
                          <SelectItem value="patient">Patient</SelectItem>
                        </SelectContent>
                      </Select>

                      <Button
                        @click="sortOrder = sortOrder === 'asc' ? 'desc' : 'asc'"
                        variant="outline"
                        size="sm"
                        class="px-3"
                      >
                        <i :class="['fas', sortOrder === 'asc' ? 'fa-arrow-up' : 'fa-arrow-down', 'text-sm']"></i>
                      </Button>
                    </div>
                  </div>

                  <!-- Treatments List -->
                  <div class="space-y-3 max-h-96 overflow-y-auto">
                    <Card
                      v-for="(treatment, index) in filteredTreatments"
                      :key="treatment.id"
                      class="border hover:shadow-md transition-shadow cursor-pointer group"
                      @click="openView(treatment)"
                    >
                      <CardContent class="p-4">
                        <div class="flex items-center justify-between">
                          <div class="flex items-center space-x-3">
                            <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-blue-500 to-cyan-500 flex items-center justify-center shadow-lg">
                              <i :class="[getTreatmentIcon(treatment.procedure), 'text-white text-sm']"></i>
                            </div>
                            <div>
                              <h4 class="font-medium text-gray-900 dark:text-white group-hover:text-blue-600 transition-colors">
                                {{ treatment.patient?.name || 'Unknown' }} - {{ treatment.procedure }}
                              </h4>
                              <p class="text-sm text-gray-600 dark:text-gray-400">
                                UGX{{ treatment.cost }} • {{ new Date(treatment.created_at || '').toLocaleDateString() }}
                                <span v-if="treatment.notes"> • {{ treatment.notes }}</span>
                              </p>
                            </div>
                          </div>

                          <div class="flex items-center space-x-2">
                            <DropdownMenu>
                              <DropdownMenuTrigger as-child @click.stop>
                                <Button variant="ghost" size="sm" class="h-8 w-8 p-0">
                                  <MoreVertical class="h-4 w-4" />
                                </Button>
                              </DropdownMenuTrigger>
                              <DropdownMenuContent align="end">
                                <DropdownMenuItem @click.stop="openView(treatment)">
                                  <i class="fas fa-eye mr-2"></i>
                                  View Details
                                </DropdownMenuItem>
                                <DropdownMenuItem v-if="!treatment.invoice" @click.stop="openEdit(treatment)">
                                  <i class="fas fa-edit mr-2"></i>
                                  Edit Treatment
                                </DropdownMenuItem>
                                <DropdownMenuItem @click.stop="openDelete(treatment)" class="text-red-600">
                                  <i class="fas fa-trash mr-2"></i>
                                  Delete Treatment
                                </DropdownMenuItem>
                              </DropdownMenuContent>
                            </DropdownMenu>
                          </div>
                        </div>
                      </CardContent>
                    </Card>

                    <div v-if="filteredTreatments.length === 0" class="text-center py-8">
                      <Stethoscope class="w-12 h-12 mx-auto mb-4 text-gray-400" />
                      <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-2">No treatments found</h3>
                      <p class="text-gray-600 dark:text-gray-400">Try adjusting your search criteria or add a new treatment.</p>
                    </div>
                  </div>
                </div>
              </TabsContent>
            </Tabs>
          </CardContent>
        </Card>
      </div>
    </div>

    <!-- Create Modal -->
    <Dialog :open="isCreateOpen" @update:open="(value) => isCreateOpen = value">
      <DialogContent class="max-w-md">
        <DialogHeader>
          <DialogTitle class="text-2xl font-bold bg-gradient-to-r from-blue-600 to-cyan-600 bg-clip-text text-transparent">
            Add New Treatment
          </DialogTitle>
          <DialogDescription class="text-gray-600 dark:text-gray-400">
            Record a new dental procedure or treatment.
          </DialogDescription>
        </DialogHeader>
        <form @submit.prevent="submitCreate" class="space-y-6">
          <div class="space-y-2">
            <Label for="patient" class="text-gray-700 dark:text-gray-300">Patient</Label>
            <Select v-model="createForm.patient_id">
              <SelectTrigger class="h-12 bg-gray-50 dark:bg-gray-800 border-gray-200 dark:border-gray-700">
                <SelectValue placeholder="Select a patient" />
              </SelectTrigger>
              <SelectContent>
                <SelectItem v-for="patient in (props.patients || [])" :key="patient.id" :value="patient.id">
                  {{ patient.name }} ({{ patient.email }})
                </SelectItem>
              </SelectContent>
            </Select>
          </div>

          <div class="space-y-2">
            <Label for="procedure" class="text-gray-700 dark:text-gray-300">Procedure</Label>
            <Select v-model="createForm.procedure">
              <SelectTrigger class="h-12 bg-gray-50 dark:bg-gray-800 border-gray-200 dark:border-gray-700">
                <SelectValue placeholder="Select a procedure" />
              </SelectTrigger>
              <SelectContent>
                <SelectItem v-for="type in (props.appointmentTypes || [])" :key="type" :value="type">
                  {{ type }}
                </SelectItem>
              </SelectContent>
            </Select>
          </div>

          <div class="space-y-2">
            <Label for="cost" class="text-gray-700 dark:text-gray-300">Cost (UGX)</Label>
            <Input
              id="cost"
              type="number"
              v-model="createForm.cost"
              placeholder="0.00"
              step="0.01"
              min="0"
              class="h-12 bg-gray-50 dark:bg-gray-800 border-gray-200 dark:border-gray-700"
              required
            />
          </div>

          <div class="space-y-2">
            <Label for="notes" class="text-gray-700 dark:text-gray-300">Notes (Optional)</Label>
            <Input
              id="notes"
              v-model="createForm.notes"
              placeholder="Additional treatment details or observations"
              class="h-12 bg-gray-50 dark:bg-gray-800 border-gray-200 dark:border-gray-700"
            />
          </div>

          <div class="space-y-2">
            <Label for="file" class="text-gray-700 dark:text-gray-300">Upload File (Optional)</Label>
            <Input
              id="file"
              type="file"
              @change="(e) => (createForm.file = (e.target as HTMLInputElement).files?.[0] || null)"
              accept="image/*,.pdf"
              class="h-12 bg-gray-50 dark:bg-gray-800 border-gray-200 dark:border-gray-700 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100"
            />
            <p class="text-xs text-gray-500 dark:text-gray-400">Upload X-rays, images, or documents (max 10MB)</p>
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
              {{ createForm.processing ? 'Creating...' : 'Create Treatment' }}
            </Button>
          </DialogFooter>
        </form>
      </DialogContent>
    </Dialog>

    <!-- Edit Modal -->
    <Dialog :open="isEditOpen" @update:open="(value) => isEditOpen = value">
      <DialogContent class="max-w-md">
        <DialogHeader>
          <DialogTitle class="text-2xl font-bold bg-gradient-to-r from-blue-600 to-cyan-600 bg-clip-text text-transparent">
            Edit Treatment
          </DialogTitle>
          <DialogDescription class="text-gray-600 dark:text-gray-400">
            Update the treatment information below.
          </DialogDescription>
        </DialogHeader>
        <form @submit.prevent="submitEdit" class="space-y-6">
          <div class="space-y-2">
            <Label for="edit-patient" class="text-gray-700 dark:text-gray-300">Patient</Label>
            <Select v-model="editForm.patient_id">
              <SelectTrigger class="h-12 bg-gray-50 dark:bg-gray-800 border-gray-200 dark:border-gray-700">
                <SelectValue placeholder="Select a patient" />
              </SelectTrigger>
              <SelectContent>
                <SelectItem v-for="patient in (props.patients || [])" :key="patient.id" :value="patient.id">
                  {{ patient.name }} ({{ patient.email }})
                </SelectItem>
              </SelectContent>
            </Select>
          </div>

          <div class="space-y-2">
            <Label for="edit-procedure" class="text-gray-700 dark:text-gray-300">Procedure</Label>
            <Select v-model="editForm.procedure">
              <SelectTrigger class="h-12 bg-gray-50 dark:bg-gray-800 border-gray-200 dark:border-gray-700">
                <SelectValue placeholder="Select a procedure" />
              </SelectTrigger>
              <SelectContent>
                <SelectItem v-for="type in (props.appointmentTypes || [])" :key="type" :value="type">
                  {{ type }}
                </SelectItem>
              </SelectContent>
            </Select>
          </div>

          <div class="space-y-2">
            <Label for="edit-cost" class="text-gray-700 dark:text-gray-300">Cost (UGX)</Label>
            <Input
              id="edit-cost"
              type="number"
              v-model="editForm.cost"
              placeholder="0.00"
              step="0.01"
              min="0"
              class="h-12 bg-gray-50 dark:bg-gray-800 border-gray-200 dark:border-gray-700"
              required
            />
          </div>

          <div class="space-y-2">
            <Label for="edit-notes" class="text-gray-700 dark:text-gray-300">Notes (Optional)</Label>
            <Input
              id="edit-notes"
              v-model="editForm.notes"
              placeholder="Additional treatment details or observations"
              class="h-12 bg-gray-50 dark:bg-gray-800 border-gray-200 dark:border-gray-700"
            />
          </div>

          <div class="space-y-2">
            <Label for="edit-file" class="text-gray-700 dark:text-gray-300">Upload File (Optional)</Label>
            <Input
              id="edit-file"
              type="file"
              @change="(e) => (editForm.file = (e.target as HTMLInputElement).files?.[0] || null)"
              accept="image/*,.pdf"
              class="h-12 bg-gray-50 dark:bg-gray-800 border-gray-200 dark:border-gray-700 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100"
            />
            <p class="text-xs text-gray-500 dark:text-gray-400">Upload X-rays, images, or documents (max 10MB)</p>
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
          <DialogTitle class="text-xl font-bold text-red-600">Delete Treatment</DialogTitle>
          <DialogDescription class="text-gray-600 dark:text-gray-400">
            This action cannot be undone. This will permanently delete the treatment record and all associated data.
          </DialogDescription>
        </DialogHeader>

        <div class="py-4">
          <div class="flex items-center space-x-3 p-4 bg-red-50 dark:bg-red-900/20 rounded-lg border border-red-200 dark:border-red-800">
            <i class="fas fa-exclamation-triangle text-red-600 text-xl"></i>
            <div>
              <p class="font-medium text-red-800 dark:text-red-200">Delete "{{ editingTreatment?.procedure }}"?</p>
              <p class="text-sm text-red-600 dark:text-red-400">This will remove the treatment record permanently.</p>
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
            Delete Treatment
          </Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>

    <!-- View Modal -->
    <Dialog :open="isViewOpen" @update:open="(value) => isViewOpen = value">
      <DialogContent class="max-w-2xl">
        <DialogHeader>
          <DialogTitle class="text-2xl font-bold bg-gradient-to-r from-blue-600 to-cyan-600 bg-clip-text text-transparent">
            Treatment Details
          </DialogTitle>
          <DialogDescription class="text-gray-600 dark:text-gray-400">
            Complete information about this dental treatment.
          </DialogDescription>
        </DialogHeader>

        <div v-if="viewingTreatment" class="space-y-6">
          <!-- Treatment Header -->
          <div class="flex items-start space-x-4 p-4 bg-gradient-to-r from-blue-50 to-cyan-50 dark:from-blue-900/20 dark:to-cyan-900/20 rounded-lg">
            <div class="w-16 h-16 rounded-xl bg-gradient-to-br from-blue-500 to-cyan-500 flex items-center justify-center shadow-lg">
              <i :class="[getTreatmentIcon(viewingTreatment.procedure), 'text-white text-2xl']"></i>
            </div>
            <div class="flex-1">
              <h3 class="text-xl font-bold text-gray-900 dark:text-white">{{ viewingTreatment.procedure }}</h3>
              <p class="text-gray-600 dark:text-gray-400">Treatment ID: {{ viewingTreatment.id }}</p>
              <p class="text-sm text-gray-500 dark:text-gray-500">
                Created: {{ new Date(viewingTreatment.created_at || '').toLocaleDateString() }}
              </p>
            </div>
          </div>

          <!-- Treatment Information -->
          <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Patient Information -->
            <Card class="border-0 shadow-lg">
              <CardHeader class="pb-3">
                <CardTitle class="text-lg flex items-center">
                  <User class="w-5 h-5 mr-2 text-blue-600" />
                  Patient Information
                </CardTitle>
              </CardHeader>
              <CardContent class="space-y-3">
                <div>
                  <Label class="text-sm font-medium text-gray-500 dark:text-gray-400">Patient Name</Label>
                  <p class="text-gray-900 dark:text-white font-medium">{{ viewingTreatment.patient?.name || 'N/A' }}</p>
                </div>
                <div v-if="viewingTreatment.appointment">
                  <Label class="text-sm font-medium text-gray-500 dark:text-gray-400">Related Appointment</Label>
                  <p class="text-gray-900 dark:text-white">Appointment #{{ viewingTreatment.appointment.id }}</p>
                </div>
              </CardContent>
            </Card>

            <!-- Financial Information -->
            <Card class="border-0 shadow-lg">
              <CardHeader class="pb-3">
                <CardTitle class="text-lg flex items-center">
                  <Receipt class="w-5 h-5 mr-2 text-green-600" />
                  Financial Details
                </CardTitle>
              </CardHeader>
              <CardContent class="space-y-3">
                <div>
                  <Label class="text-sm font-medium text-gray-500 dark:text-gray-400">Cost</Label>
                  <p class="text-2xl font-bold text-green-600 dark:text-green-400">{{ formatUGX(viewingTreatment.cost) }}</p>
                </div>
              </CardContent>
            </Card>
          </div>

          <!-- Notes -->
          <Card v-if="viewingTreatment.notes" class="border-0 shadow-lg">
            <CardHeader class="pb-3">
              <CardTitle class="text-lg flex items-center">
                <FileText class="w-5 h-5 mr-2 text-purple-600" />
                Treatment Notes
              </CardTitle>
            </CardHeader>
            <CardContent>
              <p class="text-gray-700 dark:text-gray-300 whitespace-pre-wrap">{{ viewingTreatment.notes }}</p>
            </CardContent>
          </Card>

          <!-- File Attachment -->
          <Card v-if="viewingTreatment.file_path" class="border-0 shadow-lg">
            <CardHeader class="pb-3">
              <CardTitle class="text-lg flex items-center">
                <Upload class="w-5 h-5 mr-2 text-orange-600" />
                File Attachment
              </CardTitle>
            </CardHeader>
            <CardContent>
              <div class="flex items-center space-x-3 p-3 bg-orange-50 dark:bg-orange-900/20 rounded-lg border border-orange-200 dark:border-orange-800">
                <i class="fas fa-file text-orange-600 text-xl"></i>
                <div class="flex-1">
                  <p class="font-medium text-gray-900 dark:text-white">Attachment Available</p>
                  <p class="text-sm text-gray-600 dark:text-gray-400">File has been uploaded for this treatment</p>
                </div>
                <Button size="sm" variant="outline">
                  <i class="fas fa-download mr-2"></i>
                  Download
                </Button>
              </div>
            </CardContent>
          </Card>
        </div>

        <DialogFooter class="gap-2">
          <Button type="button" variant="outline" @click="isViewOpen = false">
            Close
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

.line-clamp-2 {
  overflow: hidden;
  display: -webkit-box;
  -webkit-box-orient: vertical;
  -webkit-line-clamp: 2;
}
</style>