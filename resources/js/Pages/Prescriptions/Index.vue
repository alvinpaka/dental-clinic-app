<script setup lang="ts">
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import { route } from 'ziggy-js';
import { ref, computed } from 'vue';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/Components/ui/card';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { Badge } from '@/Components/ui/badge';
import { Button } from '@/Components/ui/button';
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogDescription, DialogFooter } from '@/Components/ui/dialog';
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuTrigger } from '@/Components/ui/dropdown-menu';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/Components/ui/tabs';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/Components/ui/select';
import { Plus, Search, MoreVertical, FileText, User, Calendar, Pill, Clock, AlertTriangle, CheckCircle } from 'lucide-vue-next';
import AppLayout from '@/Layouts/AppLayout.vue';

interface DentalMedicine {
  medicine_id: number;
  medicine_name: string;
  category: string;
  dosage_form: string | null;
  prescription_required: boolean;
}

interface Props {
  prescriptions: {
    data: Prescription[];
    links: any[];
  };
  patients: Patient[];
  medicines: DentalMedicine[];
  stats?: {
    total_prescriptions: number;
    active_prescriptions: number;
    expiring_soon: number;
    completed_today: number;
  };
}

const props = defineProps<Props>();

const searchQuery = ref('');
const statusFilter = ref('all');
const selectedPatient = ref<Patient | null>(null);
const activeTab = ref('grid');

// Filtered prescriptions
const filteredPrescriptions = computed(() => {
  let prescriptions = [...props.prescriptions.data];

  // Search filter
  if (searchQuery.value) {
    prescriptions = prescriptions.filter(rx =>
      rx.patient.name.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
      rx.medication.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
      rx.dentist.name.toLowerCase().includes(searchQuery.value.toLowerCase())
    );
  }

  // Status filter
  if (statusFilter.value && statusFilter.value !== 'all') {
    prescriptions = prescriptions.filter(rx => rx.status === statusFilter.value);
  }

  return prescriptions.sort((a, b) => new Date(b.created_at || 0).getTime() - new Date(a.created_at || 0).getTime());
});

// Forms
interface Prescription {
  id: number;
  patient: { id: number; name: string; email: string };
  medicine_id?: number;
  medication: string;
  dosage: string;
  frequency: string;
  duration: string;
  instructions?: string;
  issue_date: string;
  expiry_date?: string;
  status: 'active' | 'completed' | 'cancelled' | 'expired';
  dentist: { id: number; name: string };
  refill_count?: number;
  max_refills?: number;
  invoice?: { id: number };
  created_at?: string;
}

const createForm = useForm<Prescription>({
  id: null,
  patient_id: null,
  medicine_id: null,
  medication: '',
  dosage: '',
  frequency: '',
  duration: '',
  instructions: '',
  issue_date: '',
  expiry_date: '',
  status: '',
  max_refills: 0,
});

const editForm = useForm({
  patient_id: null,
  medicine_id: null,
  medication: '',
  dosage: '',
  frequency: '',
  duration: '',
  instructions: '',
  expiry_date: '',
  status: '',
  max_refills: 0,
});

// Modal states
const isCreateOpen = ref(false);
const isViewOpen = ref(false);
const isEditOpen = ref(false);
const isDeleteOpen = ref(false);
const editingPrescription = ref<Prescription | null>(null);
const viewingPrescription = ref<Prescription | null>(null);

// Event handlers
const openCreate = () => {
  createForm.reset();
  selectedPatient.value = null;
  isCreateOpen.value = true;
};

const openView = (prescription: Prescription) => {
  viewingPrescription.value = prescription;
  isViewOpen.value = true;
};

const openEdit = (prescription: Prescription) => {
  editingPrescription.value = prescription;
  selectedPatient.value = props.patients.find(p => p.id === prescription.patient.id) || null;

  editForm.patient_id = prescription.patient.id;
  editForm.medicine_id = prescription.medicine_id || null;
  editForm.medication = prescription.medication;
  editForm.dosage = prescription.dosage;
  editForm.frequency = prescription.frequency;
  editForm.duration = prescription.duration;
  editForm.instructions = prescription.instructions || '';
  editForm.expiry_date = prescription.expiry_date ? prescription.expiry_date.split('T')[0] : '';
  editForm.status = prescription.status;
  editForm.max_refills = prescription.max_refills || 0;

  isEditOpen.value = true;
};

const openDelete = (prescription: Prescription) => {
  editingPrescription.value = prescription;
  isDeleteOpen.value = true;
};

const submitCreate = () => {
  if (!createForm.patient_id || !createForm.medicine_id || !createForm.dosage) {
    alert('Please fill in all required fields');
    return;
  }

  createForm.post(route('prescriptions.store'), {
    onSuccess: () => {
      createForm.reset();
      isCreateOpen.value = false;
    },
  });
};

const submitEdit = () => {
  if (!editForm.patient_id || !editForm.medicine_id || !editForm.dosage) {
    alert('Please fill in all required fields');
    return;
  }

  if (editingPrescription.value) {
    editForm.put(route('prescriptions.update', editingPrescription.value.id), {
      onSuccess: () => {
        editForm.reset();
        isEditOpen.value = false;
        editingPrescription.value = null;
      },
    });
  }
};

const confirmDelete = () => {
  if (editingPrescription.value) {
    router.delete(route('prescriptions.destroy', editingPrescription.value.id), {
      onSuccess: () => {
        isDeleteOpen.value = false;
        editingPrescription.value = null;
      },
    });
  }
};

// Helper functions
const getStatusColor = (status: string) => {
  switch (status) {
    case 'active': return 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400';
    case 'completed': return 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400';
    case 'expired': return 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400';
    case 'cancelled': return 'bg-gray-100 text-gray-700 dark:bg-gray-900/30 dark:text-gray-400';
    default: return 'bg-gray-100 text-gray-700 dark:bg-gray-900/30 dark:text-gray-400';
  }
};

const getStatusIcon = (status: string) => {
  switch (status) {
    case 'active': return 'fas fa-check-circle';
    case 'completed': return 'fas fa-check-double';
    case 'expired': return 'fas fa-times-circle';
    case 'cancelled': return 'fas fa-ban';
    default: return 'fas fa-prescription';
  }
};

const formatDate = (dateString: string) => {
  return new Date(dateString).toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
  });
};

const isExpiringSoon = (expiryDate: string) => {
  if (!expiryDate) return false;
  const expiry = new Date(expiryDate);
  const now = new Date();
  const diffTime = expiry.getTime() - now.getTime();
  const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
  return diffDays <= 7 && diffDays > 0;
};

const getRefillStatus = (current: number, max: number) => {
  if (current >= max) return { status: 'max_reached', text: 'Max refills reached' };
  return { status: 'available', text: `${max - current} refills remaining` };
};
</script>

<template>
  <AppLayout title="Prescriptions">
    <Head title="Prescriptions" />

    <div class="min-h-screen bg-gradient-to-br from-blue-50 via-white to-cyan-50 dark:from-gray-900 dark:via-gray-800 dark:to-blue-900">
      <div class="container mx-auto px-4 py-8">
        <!-- Header Section -->
        <div class="mb-8">
          <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div>
              <h1 class="text-4xl font-bold bg-gradient-to-r from-blue-600 to-cyan-600 bg-clip-text text-transparent mb-2">
                Prescription Management
              </h1>
              <p class="text-gray-600 dark:text-gray-400 text-lg">
                Manage and track patient prescriptions efficiently
              </p>
            </div>

            <div class="flex items-center gap-3">
              <Badge variant="secondary" class="bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400">
                <Pill class="w-4 h-4 mr-1" />
                {{ props.prescriptions.data.length }} Total Prescriptions
              </Badge>

              <Button @click="openCreate" class="bg-gradient-to-r from-blue-600 to-cyan-600 hover:from-blue-700 hover:to-cyan-700 text-white shadow-lg hover:shadow-xl transition-all duration-300">
                <Plus class="w-4 h-4 mr-2" />
                Issue Prescription
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
                  <p class="text-sm font-medium text-green-700 dark:text-green-300 mb-1">Total Prescriptions</p>
                  <p class="text-3xl font-bold text-green-900 dark:text-green-100 mb-1">{{ props.stats.total_prescriptions }}</p>
                  <p class="text-xs text-green-600 dark:text-green-400">All time</p>
                </div>
                <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-green-500 to-green-600 flex items-center justify-center shadow-lg">
                  <FileText class="w-6 h-6 text-white" />
                </div>
              </div>
            </CardContent>
          </Card>

          <Card class="border-0 shadow-xl hover:shadow-2xl transition-all duration-300 hover:-translate-y-1 bg-gradient-to-br from-blue-50 to-blue-100 dark:from-blue-900/20 dark:to-blue-800/20">
            <CardContent class="p-6">
              <div class="flex items-center justify-between">
                <div>
                  <p class="text-sm font-medium text-blue-700 dark:text-blue-300 mb-1">Active Prescriptions</p>
                  <p class="text-3xl font-bold text-blue-900 dark:text-blue-100 mb-1">{{ props.stats.active_prescriptions }}</p>
                  <p class="text-xs text-blue-600 dark:text-blue-400">Currently active</p>
                </div>
                <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-blue-500 to-blue-600 flex items-center justify-center shadow-lg">
                  <Pill class="w-6 h-6 text-white" />
                </div>
              </div>
            </CardContent>
          </Card>

          <Card class="border-0 shadow-xl hover:shadow-2xl transition-all duration-300 hover:-translate-y-1 bg-gradient-to-br from-amber-50 to-amber-100 dark:from-amber-900/20 dark:to-amber-800/20">
            <CardContent class="p-6">
              <div class="flex items-center justify-between">
                <div>
                  <p class="text-sm font-medium text-amber-700 dark:text-amber-300 mb-1">Expiring Soon</p>
                  <p class="text-3xl font-bold text-amber-900 dark:text-amber-100 mb-1">{{ props.stats.expiring_soon }}</p>
                  <p class="text-xs text-amber-600 dark:text-amber-400">Within 7 days</p>
                </div>
                <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-amber-500 to-amber-600 flex items-center justify-center shadow-lg">
                  <AlertTriangle class="w-6 h-6 text-white" />
                </div>
              </div>
            </CardContent>
          </Card>

          <Card class="border-0 shadow-xl hover:shadow-2xl transition-all duration-300 hover:-translate-y-1 bg-gradient-to-br from-purple-50 to-purple-100 dark:from-purple-900/20 dark:to-purple-800/20">
            <CardContent class="p-6">
              <div class="flex items-center justify-between">
                <div>
                  <p class="text-sm font-medium text-purple-700 dark:text-purple-300 mb-1">Completed Today</p>
                  <p class="text-3xl font-bold text-purple-900 dark:text-purple-100 mb-1">{{ props.stats.completed_today }}</p>
                  <p class="text-xs text-purple-600 dark:text-purple-400">Filled today</p>
                </div>
                <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-purple-500 to-purple-600 flex items-center justify-center shadow-lg">
                  <CheckCircle class="w-6 h-6 text-white" />
                </div>
              </div>
            </CardContent>
          </Card>
        </div>

        <!-- Main Content -->
        <Card class="border-0 shadow-xl bg-white dark:bg-gray-900 mb-8">
          <CardHeader class="pb-4">
            <div>
              <CardTitle class="text-2xl text-gray-900 dark:text-white">Prescription Management</CardTitle>
              <CardDescription class="text-gray-600 dark:text-gray-400">
                View and manage all patient prescriptions
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
                        placeholder="Search prescriptions by patient, medication, or dentist..."
                        class="pl-10 h-12 bg-gray-50 dark:bg-gray-800 border-gray-200 dark:border-gray-700"
                      />
                    </div>

                    <Select v-model="statusFilter">
                      <SelectTrigger class="w-48 h-12">
                        <SelectValue placeholder="Filter by status" />
                      </SelectTrigger>
                      <SelectContent>
                        <SelectItem value="all">All Statuses</SelectItem>
                        <SelectItem value="active">Active</SelectItem>
                        <SelectItem value="completed">Completed</SelectItem>
                        <SelectItem value="expired">Expired</SelectItem>
                        <SelectItem value="cancelled">Cancelled</SelectItem>
                      </SelectContent>
                    </Select>
                  </div>

                  <!-- Prescriptions Grid -->
                  <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <Card
                      v-for="(prescription, index) in filteredPrescriptions"
                      :key="prescription.id"
                      :class="[
                        'border-0 shadow-xl hover:shadow-2xl transition-all duration-300 hover:-translate-y-2 bg-white dark:bg-gray-900 group',
                        prescription.status === 'expired' && prescription.status !== 'completed' ? 'ring-2 ring-red-200 dark:ring-red-800' : '',
                        isExpiringSoon(prescription.expiry_date || '') ? 'ring-2 ring-amber-200 dark:ring-amber-800' : ''
                      ]"
                    >
                      <CardHeader class="pb-4">
                        <div class="flex items-start justify-between">
                          <div class="flex items-center space-x-3">
                            <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-blue-500 to-cyan-500 flex items-center justify-center shadow-lg">
                              <Pill class="w-6 h-6 text-white" />
                            </div>
                            <div>
                              <CardTitle class="text-lg text-gray-900 dark:text-white group-hover:text-blue-600 dark:group-hover:text-cyan-400 transition-colors line-clamp-2">
                                {{ prescription.medication }}
                              </CardTitle>
                              <CardDescription class="text-gray-600 dark:text-gray-400">
                                {{ prescription.patient.name }}
                              </CardDescription>
                            </div>
                          </div>

                          <div class="flex items-center space-x-2">
                            <Badge :class="getStatusColor(prescription.status)" variant="secondary">
                              <i :class="[getStatusIcon(prescription.status), 'mr-1']"></i>
                              {{ prescription.status }}
                            </Badge>

                            <DropdownMenu>
                              <DropdownMenuTrigger as-child>
                                <Button variant="ghost" size="sm" class="h-8 w-8 p-0 opacity-0 group-hover:opacity-100 transition-opacity">
                                  <MoreVertical class="h-4 w-4" />
                                </Button>
                              </DropdownMenuTrigger>
                              <DropdownMenuContent align="end">
                                <DropdownMenuItem @click="openView(prescription)">
                                  <i class="fas fa-eye mr-2"></i>
                                  View Details
                                </DropdownMenuItem>
                                <DropdownMenuItem @click="openEdit(prescription)">
                                  <i class="fas fa-edit mr-2"></i>
                                  Edit Prescription
                                </DropdownMenuItem>
                                <DropdownMenuItem @click="openDelete(prescription)" class="text-red-600">
                                  <i class="fas fa-trash mr-2"></i>
                                  Delete Prescription
                                </DropdownMenuItem>
                              </DropdownMenuContent>
                            </DropdownMenu>
                          </div>
                        </div>
                      </CardHeader>

                      <CardContent class="space-y-4">
                        <div class="grid grid-cols-2 gap-4 text-sm">
                          <div class="flex items-center space-x-2">
                            <User class="w-4 h-4 text-gray-400" />
                            <span class="text-gray-600 dark:text-gray-400">{{ prescription.dentist.name }}</span>
                          </div>
                          <div class="flex items-center space-x-2">
                            <Calendar class="w-4 h-4 text-gray-400" />
                            <span class="text-gray-600 dark:text-gray-400">{{ formatDate(prescription.issue_date) }}</span>
                          </div>
                          <div class="flex items-center space-x-2 col-span-2">
                            <i class="fas fa-prescription-bottle text-gray-400 w-4"></i>
                            <span class="text-gray-600 dark:text-gray-400">{{ prescription.dosage }} • {{ prescription.frequency }}</span>
                          </div>
                        </div>

                        <div v-if="prescription.instructions" class="flex items-start space-x-2 text-sm">
                          <FileText class="w-4 h-4 text-gray-400 mt-0.5 flex-shrink-0" />
                          <span class="text-gray-600 dark:text-gray-400 line-clamp-2">{{ prescription.instructions }}</span>
                        </div>

                        <div v-if="prescription.expiry_date" class="flex items-center space-x-2">
                          <Clock class="w-4 h-4 text-gray-400" />
                          <span :class="['text-sm', isExpiringSoon(prescription.expiry_date) ? 'text-amber-600 dark:text-amber-400 font-medium' : 'text-gray-600 dark:text-gray-400']">
                            Expires {{ formatDate(prescription.expiry_date) }}
                            <i v-if="isExpiringSoon(prescription.expiry_date)" class="fas fa-exclamation-triangle ml-1"></i>
                          </span>
                        </div>

                        <div v-if="prescription.refill_count !== undefined && prescription.max_refills" class="flex items-center space-x-2">
                          <i class="fas fa-sync text-gray-400 w-4"></i>
                          <span class="text-sm text-gray-600 dark:text-gray-400">
                            {{ getRefillStatus(prescription.refill_count, prescription.max_refills).text }}
                          </span>
                        </div>

                        <div class="flex justify-between items-center pt-4 border-t border-gray-100 dark:border-gray-800">
                          <Button variant="outline" size="sm" @click="openView(prescription)">
                            <i class="fas fa-eye mr-2"></i>
                            View Details
                          </Button>
                          <div class="flex items-center space-x-2">
                            <Button size="sm" @click="openEdit(prescription)">
                              <i class="fas fa-edit mr-2"></i>
                              Edit
                            </Button>
                            <Button v-if="!prescription.invoice" size="sm" as-child>
                              <Link :href="route('invoices.create', { prescription_id: prescription.id })">
                                <i class="fas fa-file-invoice mr-2"></i>
                                Create Invoice
                              </Link>
                            </Button>
                          </div>
                        </div>
                      </CardContent>
                    </Card>

                    <!-- Empty State -->
                    <div v-if="filteredPrescriptions.length === 0" class="col-span-full">
                      <Card class="border-0 shadow-xl bg-white dark:bg-gray-900">
                        <CardContent class="p-12 text-center">
                          <div class="w-24 h-24 mx-auto mb-6 rounded-full bg-gradient-to-br from-gray-100 to-gray-200 dark:from-gray-800 dark:to-gray-700 flex items-center justify-center">
                            <Pill class="w-12 h-12 text-gray-400" />
                          </div>
                          <h3 class="text-xl font-semibold text-gray-900 dark:text-white mb-2">
                            {{ searchQuery || (statusFilter !== 'all') ? 'No prescriptions found' : 'No prescriptions yet' }}
                          </h3>
                          <p class="text-gray-600 dark:text-gray-400 mb-6">
                            {{ searchQuery || (statusFilter !== 'all') ? 'Try adjusting your search criteria' : 'Get started by issuing your first prescription' }}
                          </p>
                          <Button v-if="!searchQuery && statusFilter === 'all'" @click="openCreate" class="bg-gradient-to-r from-blue-600 to-cyan-600 hover:from-blue-700 hover:to-cyan-700">
                            <Plus class="w-4 h-4 mr-2" />
                            Issue First Prescription
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
                        placeholder="Search prescriptions..."
                        class="pl-10 h-12 bg-gray-50 dark:bg-gray-800 border-gray-200 dark:border-gray-700"
                      />
                    </div>

                    <Select v-model="statusFilter">
                      <SelectTrigger class="w-48 h-12">
                        <SelectValue placeholder="Filter by status" />
                      </SelectTrigger>
                      <SelectContent>
                        <SelectItem value="all">All Statuses</SelectItem>
                        <SelectItem value="active">Active</SelectItem>
                        <SelectItem value="completed">Completed</SelectItem>
                        <SelectItem value="expired">Expired</SelectItem>
                        <SelectItem value="cancelled">Cancelled</SelectItem>
                      </SelectContent>
                    </Select>
                  </div>

                  <!-- Prescriptions List -->
                  <div class="space-y-3 max-h-96 overflow-y-auto">
                    <Card
                      v-for="(prescription, index) in filteredPrescriptions"
                      :key="prescription.id"
                      class="border hover:shadow-md transition-shadow cursor-pointer group"
                      @click="openView(prescription)"
                    >
                      <CardContent class="p-4">
                        <div class="flex items-center justify-between">
                          <div class="flex items-center space-x-3">
                            <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-blue-500 to-cyan-500 flex items-center justify-center shadow-lg">
                              <Pill class="w-5 h-5 text-white" />
                            </div>
                            <div>
                              <h4 class="font-medium text-gray-900 dark:text-white group-hover:text-blue-600 transition-colors">
                                {{ prescription.patient.name }} - {{ prescription.medication }}
                              </h4>
                              <p class="text-sm text-gray-600 dark:text-gray-400">
                                {{ prescription.dosage }} • {{ prescription.frequency }} • {{ prescription.dentist.name }}
                                <span v-if="prescription.expiry_date"> • Expires {{ formatDate(prescription.expiry_date) }}</span>
                              </p>
                            </div>
                          </div>

                          <div class="flex items-center space-x-2">
                            <Badge :class="getStatusColor(prescription.status)" variant="secondary">
                              <i :class="[getStatusIcon(prescription.status), 'mr-1']"></i>
                              {{ prescription.status }}
                            </Badge>

                            <DropdownMenu>
                              <DropdownMenuTrigger as-child @click.stop>
                                <Button variant="ghost" size="sm" class="h-8 w-8 p-0">
                                  <MoreVertical class="h-4 w-4" />
                                </Button>
                              </DropdownMenuTrigger>
                              <DropdownMenuContent align="end">
                                <DropdownMenuItem @click.stop="openView(prescription)">
                                  <i class="fas fa-eye mr-2"></i>
                                  View Details
                                </DropdownMenuItem>
                                <DropdownMenuItem @click.stop="openEdit(prescription)">
                                  <i class="fas fa-edit mr-2"></i>
                                  Edit Prescription
                                </DropdownMenuItem>
                                <DropdownMenuItem @click.stop="openDelete(prescription)" class="text-red-600">
                                  <i class="fas fa-trash mr-2"></i>
                                  Delete Prescription
                                </DropdownMenuItem>
                              </DropdownMenuContent>
                            </DropdownMenu>
                          </div>
                        </div>
                      </CardContent>
                    </Card>

                    <div v-if="filteredPrescriptions.length === 0" class="text-center py-8">
                      <Pill class="w-12 h-12 mx-auto mb-4 text-gray-400" />
                      <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-2">No prescriptions found</h3>
                      <p class="text-gray-600 dark:text-gray-400">Try adjusting your search criteria or issue a new prescription.</p>
                    </div>
                  </div>
                </div>
              </TabsContent>
            </Tabs>
          </CardContent>
        </Card>
      </div>
    </div>

    <!-- Create Prescription Modal -->
    <Dialog :open="isCreateOpen" @update:open="(value) => isCreateOpen = value">
      <DialogContent class="max-w-md">
        <DialogHeader>
          <DialogTitle class="text-2xl font-bold bg-gradient-to-r from-blue-600 to-cyan-600 bg-clip-text text-transparent">
            Issue New Prescription
          </DialogTitle>
          <DialogDescription class="text-gray-600 dark:text-gray-400">
            Create a prescription for a patient.
          </DialogDescription>
        </DialogHeader>

        <form @submit.prevent="submitCreate" class="space-y-6">
          <div class="space-y-2">
            <Label for="patient" class="text-gray-700 dark:text-gray-300">Patient</Label>
            <Select id="patient" v-model="createForm.patient_id">
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
            <Label for="medicine" class="text-gray-700 dark:text-gray-300">Medication</Label>
            <Select id="medicine" v-model="createForm.medicine_id">
              <SelectTrigger class="h-12 bg-gray-50 dark:bg-gray-800 border-gray-200 dark:border-gray-700">
                <SelectValue placeholder="Select a medication" />
              </SelectTrigger>
              <SelectContent>
                <SelectItem v-for="medicine in props.medicines" :key="medicine.medicine_id" :value="medicine.medicine_id">
                  {{ medicine.medicine_name }} ({{ medicine.category }})
                  <span v-if="medicine.dosage_form" class="text-gray-500 text-sm"> - {{ medicine.dosage_form }}</span>
                </SelectItem>
              </SelectContent>
            </Select>
          </div>

          <div class="grid grid-cols-2 gap-4">
            <div class="space-y-2">
              <Label for="dosage" class="text-gray-700 dark:text-gray-300">Dosage</Label>
              <Input
                id="dosage"
                v-model="createForm.dosage"
                placeholder="500mg, 10ml"
                class="h-12 bg-gray-50 dark:bg-gray-800 border-gray-200 dark:border-gray-700"
                required
              />
            </div>

            <div class="space-y-2">
              <Label for="frequency" class="text-gray-700 dark:text-gray-300">Frequency</Label>
              <Select id="frequency" v-model="createForm.frequency">
                <SelectTrigger class="h-12 bg-gray-50 dark:bg-gray-800 border-gray-200 dark:border-gray-700">
                  <SelectValue placeholder="How often" />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem value="Once daily">Once daily</SelectItem>
                  <SelectItem value="Twice daily">Twice daily</SelectItem>
                  <SelectItem value="Three times daily">Three times daily</SelectItem>
                  <SelectItem value="Four times daily">Four times daily</SelectItem>
                  <SelectItem value="As needed">As needed</SelectItem>
                  <SelectItem value="Every 4 hours">Every 4 hours</SelectItem>
                  <SelectItem value="Every 6 hours">Every 6 hours</SelectItem>
                  <SelectItem value="Every 8 hours">Every 8 hours</SelectItem>
                </SelectContent>
              </Select>
            </div>
          </div>

          <div class="grid grid-cols-2 gap-4">
            <div class="space-y-2">
              <Label for="duration" class="text-gray-700 dark:text-gray-300">Duration</Label>
              <Input
                id="duration"
                v-model="createForm.duration"
                placeholder="7 days, 2 weeks"
                class="h-12 bg-gray-50 dark:bg-gray-800 border-gray-200 dark:border-gray-700"
                required
              />
            </div>

            <div class="space-y-2">
              <Label for="max_refills" class="text-gray-700 dark:text-gray-300">Max Refills</Label>
              <Input
                id="max_refills"
                type="number"
                v-model="createForm.max_refills"
                placeholder="0"
                min="0"
                class="h-12 bg-gray-50 dark:bg-gray-800 border-gray-200 dark:border-gray-700"
              />
            </div>
          </div>

          <div class="space-y-2">
            <Label for="instructions" class="text-gray-700 dark:text-gray-300">Instructions (Optional)</Label>
            <Input
              id="instructions"
              v-model="createForm.instructions"
              placeholder="Take with food, avoid alcohol, etc."
              class="h-12 bg-gray-50 dark:bg-gray-800 border-gray-200 dark:border-gray-700"
            />
          </div>

          <div class="space-y-2">
            <Label for="expiry_date" class="text-gray-700 dark:text-gray-300">Expiry Date (Optional)</Label>
            <Input
              id="expiry_date"
              type="date"
              v-model="createForm.expiry_date"
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
              <i v-else class="fas fa-prescription mr-2"></i>
              {{ createForm.processing ? 'Issuing...' : 'Issue Prescription' }}
            </Button>
          </DialogFooter>
        </form>
      </DialogContent>
    </Dialog>

    <!-- View Prescription Details Modal -->
    <Dialog :open="isViewOpen" @update:open="(value) => isViewOpen = value">
      <DialogContent class="max-w-4xl max-h-[90vh] overflow-y-auto">
        <DialogHeader>
          <DialogTitle class="text-2xl font-bold bg-gradient-to-r from-blue-600 to-cyan-600 bg-clip-text text-transparent">
            Prescription Details
          </DialogTitle>
          <DialogDescription class="text-gray-600 dark:text-gray-400">
            Detailed information for prescription #{{ viewingPrescription?.id }}
          </DialogDescription>
        </DialogHeader>

        <div v-if="viewingPrescription" class="space-y-6">
          <!-- Status and Actions -->
          <div class="flex items-center justify-between">
            <Badge :class="getStatusColor(viewingPrescription.status)" variant="secondary" class="text-lg px-3 py-1">
              <i :class="[getStatusIcon(viewingPrescription.status), 'w-5 h-5 mr-2']"></i>
              {{ viewingPrescription.status }}
            </Badge>
            <div class="flex gap-2">
              <Button @click="openEdit(viewingPrescription)" variant="outline">
                <i class="fas fa-edit mr-2"></i>
                Edit
              </Button>
              <Button @click="openDelete(viewingPrescription)" variant="destructive">
                <i class="fas fa-trash mr-2"></i>
                Delete
              </Button>
            </div>
          </div>

          <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Main Prescription Details -->
            <div class="lg:col-span-2 space-y-6">
              <!-- Medication Info -->
              <Card class="border-0 shadow-lg">
                <CardHeader>
                  <CardTitle class="flex items-center">
                    <Pill class="w-5 h-5 mr-2 text-blue-600" />
                    Medication Details
                  </CardTitle>
                </CardHeader>
                <CardContent class="space-y-4">
                  <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                      <label class="text-sm font-medium text-gray-700 dark:text-gray-300">Medication</label>
                      <p class="text-lg font-semibold text-gray-900 dark:text-white">{{ viewingPrescription.medication }}</p>
                      <p v-if="viewingPrescription.medicine" class="text-sm text-gray-600 dark:text-gray-400">
                        {{ viewingPrescription.medicine.category }}
                        <span v-if="viewingPrescription.medicine.dosage_form"> • {{ viewingPrescription.medicine.dosage_form }}</span>
                      </p>
                    </div>

                    <div>
                      <label class="text-sm font-medium text-gray-700 dark:text-gray-300">Dosage & Frequency</label>
                      <p class="text-gray-900 dark:text-white">{{ viewingPrescription.dosage }} • {{ viewingPrescription.frequency }}</p>
                    </div>

                    <div>
                      <label class="text-sm font-medium text-gray-700 dark:text-gray-300">Duration</label>
                      <p class="text-gray-900 dark:text-white">{{ viewingPrescription.duration }}</p>
                    </div>

                    <div v-if="viewingPrescription.max_refills">
                      <label class="text-sm font-medium text-gray-700 dark:text-gray-300">Refills</label>
                      <p class="text-gray-900 dark:text-white">
                        {{ getRefillStatus(viewingPrescription.refill_count || 0, viewingPrescription.max_refills).text }}
                      </p>
                    </div>
                  </div>

                  <div v-if="viewingPrescription.instructions" class="space-y-2">
                    <label class="text-sm font-medium text-gray-700 dark:text-gray-300">Instructions</label>
                    <p class="text-gray-900 dark:text-white bg-gray-50 dark:bg-gray-800 p-3 rounded-lg">
                      {{ viewingPrescription.instructions }}
                    </p>
                  </div>

                  <div v-if="viewingPrescription.medicine?.common_uses" class="space-y-2">
                    <label class="text-sm font-medium text-gray-700 dark:text-gray-300">Common Uses</label>
                    <p class="text-gray-600 dark:text-gray-400 bg-blue-50 dark:bg-blue-900/20 p-3 rounded-lg">
                      {{ viewingPrescription.medicine.common_uses }}
                    </p>
                  </div>
                </CardContent>
              </Card>

              <!-- Medicine Information -->
              <Card v-if="viewingPrescription.medicine" class="border-0 shadow-lg">
                <CardHeader>
                  <CardTitle class="flex items-center">
                    <FileText class="w-5 h-5 mr-2 text-green-600" />
                    Medicine Information
                  </CardTitle>
                </CardHeader>
                <CardContent>
                  <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                      <label class="text-sm font-medium text-gray-700 dark:text-gray-300">Category</label>
                      <p class="text-gray-900 dark:text-white">{{ viewingPrescription.medicine.category }}</p>
                    </div>
                    <div>
                      <label class="text-sm font-medium text-gray-700 dark:text-gray-300">Dosage Form</label>
                      <p class="text-gray-900 dark:text-white">{{ viewingPrescription.medicine.dosage_form || 'N/A' }}</p>
                    </div>
                    <div class="md:col-span-2">
                      <label class="text-sm font-medium text-gray-700 dark:text-gray-300">Prescription Required</label>
                      <Badge :variant="viewingPrescription.medicine.prescription_required ? 'destructive' : 'secondary'" class="mt-1">
                        {{ viewingPrescription.medicine.prescription_required ? 'Yes' : 'No' }}
                      </Badge>
                    </div>
                  </div>
                </CardContent>
              </Card>
            </div>

            <!-- Sidebar -->
            <div class="space-y-6">
              <!-- Patient Info -->
              <Card class="border-0 shadow-lg">
                <CardHeader>
                  <CardTitle class="flex items-center">
                    <User class="w-5 h-5 mr-2 text-purple-600" />
                    Patient Information
                  </CardTitle>
                </CardHeader>
                <CardContent class="space-y-4">
                  <div>
                    <label class="text-sm font-medium text-gray-700 dark:text-gray-300">Name</label>
                    <p class="text-gray-900 dark:text-white font-medium">{{ viewingPrescription.patient.name }}</p>
                  </div>
                  <div>
                    <label class="text-sm font-medium text-gray-700 dark:text-gray-300">Email</label>
                    <p class="text-gray-900 dark:text-white">{{ viewingPrescription.patient.email }}</p>
                  </div>
                </CardContent>
              </Card>

              <!-- Dentist Info -->
              <Card class="border-0 shadow-lg">
                <CardHeader>
                  <CardTitle class="flex items-center">
                    <User class="w-5 h-5 mr-2 text-indigo-600" />
                    Prescribed By
                  </CardTitle>
                </CardHeader>
                <CardContent>
                  <div>
                    <label class="text-sm font-medium text-gray-700 dark:text-gray-300">Dentist</label>
                    <p class="text-gray-900 dark:text-white font-medium">{{ viewingPrescription.dentist.name }}</p>
                  </div>
                </CardContent>
              </Card>

              <!-- Dates -->
              <Card class="border-0 shadow-lg">
                <CardHeader>
                  <CardTitle class="flex items-center">
                    <Calendar class="w-5 h-5 mr-2 text-gray-600" />
                    Important Dates
                  </CardTitle>
                </CardHeader>
                <CardContent class="space-y-3">
                  <div>
                    <label class="text-sm font-medium text-gray-700 dark:text-gray-300">Issue Date</label>
                    <p class="text-gray-900 dark:text-white">{{ formatDate(viewingPrescription.issue_date) }}</p>
                  </div>
                  <div v-if="viewingPrescription.expiry_date">
                    <label class="text-sm font-medium text-gray-700 dark:text-gray-300">Expiry Date</label>
                    <p :class="['font-medium', isExpiringSoon(viewingPrescription.expiry_date) ? 'text-amber-600 dark:text-amber-400' : 'text-gray-900 dark:text-white']">
                      {{ formatDate(viewingPrescription.expiry_date) }}
                      <AlertTriangle v-if="isExpiringSoon(viewingPrescription.expiry_date)" class="w-4 h-4 inline ml-1" />
                    </p>
                  </div>
                </CardContent>
              </Card>
            </div>
          </div>
        </div>

        <DialogFooter>
          <Button type="button" variant="outline" @click="isViewOpen = false">
            Close
          </Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>

    <!-- Edit Prescription Modal -->
    <Dialog :open="isEditOpen" @update:open="(value) => isEditOpen = value">
      <DialogContent class="max-w-md">
        <DialogHeader>
          <DialogTitle class="text-2xl font-bold bg-gradient-to-r from-blue-600 to-cyan-600 bg-clip-text text-transparent">
            Edit Prescription
          </DialogTitle>
          <DialogDescription class="text-gray-600 dark:text-gray-400">
            Update the prescription information.
          </DialogDescription>
        </DialogHeader>

        <form @submit.prevent="submitEdit" class="space-y-6">
          <div class="space-y-2">
            <Label for="edit-patient" class="text-gray-700 dark:text-gray-300">Patient</Label>
            <Select id="edit-patient" v-model="editForm.patient_id">
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
            <Label for="edit-medicine" class="text-gray-700 dark:text-gray-300">Medication</Label>
            <Select id="edit-medicine" v-model="editForm.medicine_id">
              <SelectTrigger class="h-12 bg-gray-50 dark:bg-gray-800 border-gray-200 dark:border-gray-700">
                <SelectValue placeholder="Select a medication" />
              </SelectTrigger>
              <SelectContent>
                <SelectItem v-for="medicine in props.medicines" :key="medicine.medicine_id" :value="medicine.medicine_id">
                  {{ medicine.medicine_name }} ({{ medicine.category }})
                  <span v-if="medicine.dosage_form" class="text-gray-500 text-sm"> - {{ medicine.dosage_form }}</span>
                </SelectItem>
              </SelectContent>
            </Select>
          </div>

          <div class="grid grid-cols-2 gap-4">
            <div class="space-y-2">
              <Label for="edit-dosage" class="text-gray-700 dark:text-gray-300">Dosage</Label>
              <Input
                id="edit-dosage"
                v-model="editForm.dosage"
                placeholder="500mg, 10ml"
                class="h-12 bg-gray-50 dark:bg-gray-800 border-gray-200 dark:border-gray-700"
                required
              />
            </div>

            <div class="space-y-2">
              <Label for="edit-frequency" class="text-gray-700 dark:text-gray-300">Frequency</Label>
              <Select id="edit-frequency" v-model="editForm.frequency">
                <SelectTrigger class="h-12 bg-gray-50 dark:bg-gray-800 border-gray-200 dark:border-gray-700">
                  <SelectValue placeholder="How often" />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem value="Once daily">Once daily</SelectItem>
                  <SelectItem value="Twice daily">Twice daily</SelectItem>
                  <SelectItem value="Three times daily">Three times daily</SelectItem>
                  <SelectItem value="Four times daily">Four times daily</SelectItem>
                  <SelectItem value="As needed">As needed</SelectItem>
                  <SelectItem value="Every 4 hours">Every 4 hours</SelectItem>
                  <SelectItem value="Every 6 hours">Every 6 hours</SelectItem>
                  <SelectItem value="Every 8 hours">Every 8 hours</SelectItem>
                </SelectContent>
              </Select>
            </div>
          </div>

          <div class="grid grid-cols-2 gap-4">
            <div class="space-y-2">
              <Label for="edit-duration" class="text-gray-700 dark:text-gray-300">Duration</Label>
              <Input
                id="edit-duration"
                v-model="editForm.duration"
                placeholder="7 days, 2 weeks"
                class="h-12 bg-gray-50 dark:bg-gray-800 border-gray-200 dark:border-gray-700"
                required
              />
            </div>

            <div class="space-y-2">
              <Label for="edit-status" class="text-gray-700 dark:text-gray-300">Status</Label>
              <Select id="edit-status" v-model="editForm.status">
                <SelectTrigger class="h-12 bg-gray-50 dark:bg-gray-800 border-gray-200 dark:border-gray-700">
                  <SelectValue placeholder="Prescription status" />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem value="active">Active</SelectItem>
                  <SelectItem value="completed">Completed</SelectItem>
                  <SelectItem value="expired">Expired</SelectItem>
                  <SelectItem value="cancelled">Cancelled</SelectItem>
                </SelectContent>
              </Select>
            </div>
          </div>

          <div class="space-y-2">
            <Label for="edit-instructions" class="text-gray-700 dark:text-gray-300">Instructions (Optional)</Label>
            <Input
              id="edit-instructions"
              v-model="editForm.instructions"
              placeholder="Take with food, avoid alcohol, etc."
              class="h-12 bg-gray-50 dark:bg-gray-800 border-gray-200 dark:border-gray-700"
            />
          </div>

          <div class="space-y-2">
            <Label for="edit-expiry_date" class="text-gray-700 dark:text-gray-300">Expiry Date (Optional)</Label>
            <Input
              id="edit-expiry_date"
              type="date"
              v-model="editForm.expiry_date"
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
          <DialogTitle class="text-xl font-bold text-red-600">Delete Prescription</DialogTitle>
          <DialogDescription class="text-gray-600 dark:text-gray-400">
            This action cannot be undone. This will permanently delete the prescription record.
          </DialogDescription>
        </DialogHeader>

        <div class="py-4">
          <div class="flex items-center space-x-3 p-4 bg-red-50 dark:bg-red-900/20 rounded-lg border border-red-200 dark:border-red-800">
            <i class="fas fa-exclamation-triangle text-red-600 text-xl"></i>
            <div>
              <p class="font-medium text-red-800 dark:text-red-200">Delete prescription for {{ editingPrescription?.patient.name }}?</p>
              <p class="text-sm text-red-600 dark:text-red-400">This will remove the prescription permanently.</p>
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
            Delete Prescription
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