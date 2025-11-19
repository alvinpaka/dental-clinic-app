<script setup lang="ts">
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import { route } from 'ziggy-js';
import { ref, computed, watch } from 'vue';
import debounce from 'lodash.debounce';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/Components/ui/card';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { Badge } from '@/Components/ui/badge';
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogDescription, DialogFooter } from '@/Components/ui/dialog';
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuTrigger } from '@/Components/ui/dropdown-menu';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/Components/ui/select';
import Pagination from '@/Components/ui/Pagination.vue';
import { Plus, Search, MoreVertical, Users, UserPlus, Calendar, Phone } from 'lucide-vue-next';
import AppLayout from '@/Layouts/AppLayout.vue';

interface Patient {
  id: number;
  name: string;
  email: string;
  phone: string;
  dob: string;
  dob_formatted: string;
  dob_formatted_edit: string;
  appointments?: any[];
  treatments?: any[];
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
  patients: {
    data: Patient[];
    meta?: PaginationMeta;
  };
  stats?: {
    total_patients: number;
    new_this_month: number;
    active_appointments: number;
  };
  can: {
    createPatient: boolean;
    updatePatient: boolean;
    deletePatient: boolean;
  };
}

const props = defineProps<Props>();

// State
const searchQuery = ref('');
const sortBy = ref('name');
const sortOrder = ref('asc');
const listViewPerPage = ref((props.patients?.meta?.per_page ?? 10).toString());
const currentPage = ref(props.patients?.meta?.current_page ?? 1);
const listPerPageOptions = [10, 20, 30, 50];

// Computed properties
const totalPatients = computed(() => props.patients?.meta?.total ?? props.patients?.data.length ?? 0);
const lastPage = computed(() => props.patients?.meta?.last_page ?? 1);

const patientsFromServer = computed(() => props.patients?.data ?? []);

const paginationLinks = computed(() => props.patients?.meta?.links ?? []);

const paginationSummary = computed(() => {
  const meta = props.patients?.meta;
  if (meta) {
    return { from: meta.from ?? 0, to: meta.to ?? 0, total: meta.total ?? totalPatients.value };
  }
  const count = patientsFromServer.value.length;
  return { from: count ? 1 : 0, to: count, total: totalPatients.value };
});

const buildFiltersPayload = () => ({
  page: currentPage.value,
  per_page: Number(listViewPerPage.value),
  search: searchQuery.value,
  sort_by: sortBy.value,
  sort_order: sortOrder.value,
});

const fetchPatients = (overrides: Partial<{ page: number; per_page: number; search: string; sort_by: string; sort_order: string }> = {}, { replace = true } = {}) => {
  const payload = {
    ...buildFiltersPayload(),
    ...overrides,
  };

  const pageNumber = Number(payload.page);
  if (Number.isFinite(pageNumber) && pageNumber > 0) {
    currentPage.value = pageNumber;
    payload.page = pageNumber;
  } else {
    payload.page = currentPage.value;
  }

  payload.per_page = Number(payload.per_page ?? Number(listViewPerPage.value));

  router.get(route('patients.index'), payload, {
    preserveState: true,
    preserveScroll: true,
    replace,
    only: ['patients', 'filters']
  });
};

const debouncedFetchPatients = debounce((overrides = {}) => {
  fetchPatients(overrides);
}, 300);

watch(listViewPerPage, (value, oldValue) => {
  if (value === oldValue) return;
  const perPage = Number(value);
  if (!Number.isFinite(perPage) || perPage <= 0) return;

  currentPage.value = 1;
  fetchPatients({ per_page: perPage, page: 1 });
});

watch(searchQuery, (value, oldValue) => {
  if (value === oldValue) return;
  currentPage.value = 1;
  debouncedFetchPatients({ search: value, page: 1 });
});

watch(sortBy, (value, oldValue) => {
  if (value === oldValue) return;
  currentPage.value = 1;
  fetchPatients({ sort_by: value, page: 1 });
});

watch(sortOrder, (value, oldValue) => {
  if (value === oldValue) return;
  currentPage.value = 1;
  fetchPatients({ sort_order: value, page: 1 });
});

watch(() => props.patients?.meta?.per_page, (value) => {
  if (value && value.toString() !== listViewPerPage.value) {
    listViewPerPage.value = value.toString();
  }
});

watch(() => props.patients?.meta?.current_page, (value) => {
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
    fetchPatients({ page: newPage }, { replace: true });
  }
};

// Form handling
const createForm = useForm({
  name: '',
  email: '',
  phone: '',
  dob: '',
  age: '',
});

const editForm = useForm({
  name: '',
  email: '',
  phone: '',
  dob: '',
  age: '',
});

const isCreateOpen = ref(false);
const isEditOpen = ref(false);
const isDeleteOpen = ref(false);
const isViewOpen = ref(false);
const editingPatient = ref<Patient | null>(null);
const viewingPatient = ref<Patient | null>(null);

const openCreate = () => {
  if (!props.can.createPatient) return;
  isCreateOpen.value = true;
};

const openEdit = (patient: Patient) => {
  if (!props.can.updatePatient) return;
  editingPatient.value = patient;
  
  // Reset the form first
  editForm.reset();
  
  // Set the form values
  editForm.name = patient.name;
  editForm.email = patient.email;
  editForm.phone = patient.phone;
  editForm.dob = patient.dob_formatted_edit || patient.dob;
  editForm.age = calculateAgeFromDOB(patient.dob);
  
  isEditOpen.value = true;
};

const openDelete = (patient: Patient) => {
  if (!props.can.deletePatient) return;
  editingPatient.value = patient;
  isDeleteOpen.value = true;
};

const openView = (patient: Patient) => {
  router.visit(route('patients.show', patient.id));
};

const submitCreate = () => {
  if (!props.can.createPatient) return;

  if (!createForm.age && !createForm.dob) {
    alert('Please provide either age or date of birth');
    return;
  }

  if (createForm.age && !createForm.dob) {
    createForm.dob = calculateDOBFromAge(parseInt(createForm.age));
  }

  createForm.post(route('patients.store'), {
    onSuccess: () => {
      createForm.reset();
      isCreateOpen.value = false;
    },
  });
};

const submitEdit = () => {
  if (!props.can.updatePatient || !editingPatient.value) return;

  if (!editForm.age && !editForm.dob) {
    alert('Please provide either age or date of birth');
    return;
  }

  if (editForm.age && !editForm.dob) {
    editForm.dob = calculateDOBFromAge(parseInt(editForm.age));
  }

  editForm.put(route('patients.update', editingPatient.value.id), {
    onSuccess: () => {
      editForm.reset();
      isEditOpen.value = false;
      editingPatient.value = null;
    },
  });
};

const confirmDelete = () => {
  if (!props.can.deletePatient || !editingPatient.value) return;

  router.delete(route('patients.destroy', editingPatient.value.id), {
    onSuccess: () => {
      isDeleteOpen.value = false;
      editingPatient.value = null;
    },
  });
};

const calculateAge = (dob: string) => {
  if (!dob) return 'N/A';
  const birthDate = new Date(dob);
  const today = new Date();
  let age = today.getFullYear() - birthDate.getFullYear();
  const monthDiff = today.getMonth() - birthDate.getMonth();
  if (monthDiff < 0 || (monthDiff === 0 && today.getDate() < birthDate.getDate())) {
    age--;
  }
  return age;
};

const calculateDOBFromAge = (age: number) => {
  const today = new Date();
  const birthYear = today.getFullYear() - age;
  return new Date(birthYear, 0, 1).toISOString().split('T')[0]; // YYYY-MM-DD
};

const calculateAgeFromDOB = (dob: string) => {
  if (!dob) return '';
  return calculateAge(dob).toString();
};

const formatCurrency = (amount: number) => {
  return new Intl.NumberFormat('en-US', {
    style: 'currency',
    currency: 'UGX',
    minimumFractionDigits: 0,
  }).format(amount).replace('UGX', 'UGX ');
};

const calculateTotalCost = (treatment: any) => {
  const procedureCost = Number(treatment.cost) || 0;
  const prescriptionCost = treatment.prescriptions?.reduce((total: number, prescription: any) => {
    return total + (Number(prescription.prescription_amount) || 0);
  }, 0) || 0;
  return procedureCost + prescriptionCost;
};

// Watchers for form age/DOB sync
watch(() => createForm.age, (newAge) => {
  if (newAge && !createForm.dob) {
    createForm.dob = calculateDOBFromAge(parseInt(newAge));
  } else if (!newAge) {
    createForm.dob = '';
  }
});

watch(() => createForm.dob, (newDOB) => {
  if (newDOB && !createForm.age) {
    createForm.age = calculateAgeFromDOB(newDOB);
  } else if (!newDOB) {
    createForm.age = '';
  }
});

watch(() => editForm.age, (newAge) => {
  if (newAge && !editForm.dob) {
    editForm.dob = calculateDOBFromAge(parseInt(newAge));
  } else if (!newAge) {
    editForm.dob = '';
  }
});

watch(() => editForm.dob, (newDOB) => {
  if (newDOB && !editForm.age) {
    editForm.age = calculateAgeFromDOB(newDOB);
  } else if (!newDOB) {
    editForm.age = '';
  }
});
</script>

<template>
  <AppLayout title="Patients">
    <Head title="Patients" />

    <div class="min-h-screen bg-gradient-to-br from-blue-50 via-white to-cyan-50 dark:from-gray-900 dark:via-gray-800 dark:to-blue-900">
      <div class="container mx-auto px-4 py-8">
        <!-- Header Section -->
        <div class="mb-8">
          <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div>
              <h1 class="text-4xl font-bold bg-gradient-to-r from-blue-600 to-cyan-600 bg-clip-text text-transparent mb-2">
                Patient Management
              </h1>
              <p class="text-gray-600 dark:text-gray-400 text-lg">
                Manage your patient records and information efficiently
              </p>
            </div>
            <div class="flex items-center gap-3">
              <Badge variant="secondary" class="bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400 px-3 py-1 font-medium rounded-md shadow-sm">
                <Users class="w-4 h-4 mr-2" />
                {{ totalPatients }} Total Patients
              </Badge>
              <Button
                v-if="props.can.createPatient"
                @click="openCreate"
                class="bg-gradient-to-r from-blue-600 to-cyan-600 hover:from-blue-700 hover:to-cyan-700 text-white shadow-lg hover:shadow-xl transition-all duration-300"
              >
                <Plus class="w-4 h-4 mr-2" />
                Add New Patient
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
                  <p class="text-sm font-medium text-blue-700 dark:text-blue-300 mb-1">Total Patients</p>
                  <p class="text-3xl font-bold text-blue-900 dark:text-blue-100 mb-1">{{ props.stats.total_patients }}</p>
                  <p class="text-xs text-blue-600 dark:text-blue-400">All registered patients</p>
                </div>
                <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-blue-500 to-blue-600 flex items-center justify-center shadow-lg">
                  <Users class="w-6 h-6 text-white" />
                </div>
              </div>
            </CardContent>
          </Card>
          <Card class="border-0 shadow-xl hover:shadow-2xl transition-all duration-300 hover:-translate-y-1 bg-gradient-to-br from-green-50 to-green-100 dark:from-green-900/20 dark:to-green-800/20">
            <CardContent class="p-6">
              <div class="flex items-center justify-between">
                <div>
                  <p class="text-sm font-medium text-green-700 dark:text-green-300 mb-1">New This Month</p>
                  <p class="text-3xl font-bold text-green-900 dark:text-green-100 mb-1">{{ props.stats.new_this_month }}</p>
                  <p class="text-xs text-green-600 dark:text-green-400">Recent registrations</p>
                </div>
                <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-green-500 to-green-600 flex items-center justify-center shadow-lg">
                  <UserPlus class="w-6 h-6 text-white" />
                </div>
              </div>
            </CardContent>
          </Card>
          <Card class="border-0 shadow-xl hover:shadow-2xl transition-all duration-300 hover:-translate-y-1 bg-gradient-to-br from-purple-50 to-purple-100 dark:from-purple-900/20 dark:to-purple-800/20">
            <CardContent class="p-6">
              <div class="flex items-center justify-between">
                <div>
                  <p class="text-sm font-medium text-purple-700 dark:text-purple-300 mb-1">Active Appointments</p>
                  <p class="text-3xl font-bold text-purple-900 dark:text-purple-100 mb-1">{{ props.stats.active_appointments }}</p>
                  <p class="text-xs text-purple-600 dark:text-purple-400">Scheduled visits</p>
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
            <CardTitle class="text-2xl text-gray-900 dark:text-white">Patient Management</CardTitle>
            <CardDescription class="text-gray-600 dark:text-gray-400">
              View and manage all patient records
            </CardDescription>
          </CardHeader>
          <CardContent>
            <div class="space-y-6">
              <div class="flex flex-col lg:flex-row gap-4 lg:items-center">
                <div class="relative flex-1 w-full">
                  <Search class="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400 w-4 h-4" />
                  <Input
                    v-model="searchQuery"
                    placeholder="Search patients by name, email, or phone..."
                    class="pl-10 h-12 bg-gray-50 dark:bg-gray-800 border-gray-200 dark:border-gray-700"
                  />
                </div>
                <div class="flex items-center gap-3">
                  <div class="flex items-center gap-2">
                    <Label class="text-sm font-medium text-gray-700 dark:text-gray-300">Sort by:</Label>
                    <Select v-model="sortBy">
                      <SelectTrigger class="w-32">
                        <SelectValue />
                      </SelectTrigger>
                      <SelectContent>
                        <SelectItem value="name">Name</SelectItem>
                        <SelectItem value="email">Email</SelectItem>
                        <SelectItem value="phone">Phone</SelectItem>
                        <SelectItem value="dob">Date of Birth</SelectItem>
                        <SelectItem value="created_at">Date Added</SelectItem>
                      </SelectContent>
                    </Select>
                  </div>
                  <Button
                    @click="sortOrder = sortOrder === 'asc' ? 'desc' : 'asc'"
                    variant="outline"
                    size="sm"
                    class="px-3"
                  >
                    <i :class="['fas', sortOrder === 'asc' ? 'fa-arrow-up' : 'fa-arrow-down', 'text-sm']"></i>
                  </Button>
                </div>
                <div class="flex items-center gap-2">
                  <Label class="text-sm font-medium text-gray-700 dark:text-gray-300">Per page:</Label>
                  <Select v-model="listViewPerPage">
                    <SelectTrigger class="w-24">
                      <SelectValue />
                    </SelectTrigger>
                    <SelectContent>
                      <SelectItem v-for="option in listPerPageOptions" :key="`patients-per-page-${option}`" :value="option.toString()">
                        {{ option }}
                      </SelectItem>
                    </SelectContent>
                  </Select>
                </div>
              </div>

              <div class="overflow-x-auto border border-gray-200 dark:border-gray-800 rounded-xl shadow-sm">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-800">
                  <thead class="bg-gray-50 dark:bg-gray-800/70">
                    <tr>
                      <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-600 dark:text-gray-300">
                        Patient
                      </th>
                      <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-600 dark:text-gray-300">
                        Email
                      </th>
                      <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-600 dark:text-gray-300">
                        Phone
                      </th>
                      <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-600 dark:text-gray-300">
                        Age
                      </th>
                      <th scope="col" class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-gray-600 dark:text-gray-300">
                        Actions
                      </th>
                    </tr>
                  </thead>
                  <tbody class="bg-white dark:bg-gray-900 divide-y divide-gray-100 dark:divide-gray-800">
                    <tr
                      v-for="patient in patientsFromServer"
                      :key="patient.id"
                      class="hover:bg-gray-50 dark:hover:bg-gray-800/60 transition-colors cursor-pointer"
                      @click="openView(patient)"
                    >
                      <td class="px-4 py-4 align-top">
                        <div class="flex items-center gap-3">
                          <div class="w-10 h-10 rounded-full bg-gradient-to-br from-blue-500 to-cyan-500 flex items-center justify-center shadow-lg text-white font-semibold">
                            {{ patient.name.charAt(0).toUpperCase() }}
                          </div>
                          <div>
                            <p class="font-semibold text-gray-900 dark:text-white">{{ patient.name }}</p>
                            <p class="text-xs text-gray-500 dark:text-gray-400">ID: {{ patient.id }}</p>
                          </div>
                        </div>
                      </td>
                      <td class="px-4 py-4 align-top text-gray-700 dark:text-gray-300">
                        <div class="flex items-center gap-2">
                          <i class="fas fa-envelope text-gray-400"></i>
                          <span class="truncate max-w-[220px]">{{ patient.email }}</span>
                        </div>
                      </td>
                      <td class="px-4 py-4 align-top text-gray-700 dark:text-gray-300">
                        <div class="flex items-center gap-2">
                          <Phone class="w-4 h-4 text-gray-400" />
                          <span>{{ patient.phone }}</span>
                        </div>
                      </td>
                      <td class="px-4 py-4 align-top text-gray-700 dark:text-gray-300">
                        <div class="flex items-center gap-2">
                          <i class="fas fa-user text-gray-400"></i>
                          <span>{{ calculateAge(patient.dob) }} years</span>
                        </div>
                      </td>
                      <td class="px-4 py-4 align-top" @click.stop>
                        <div class="flex items-center justify-end">
                          <DropdownMenu>
                            <DropdownMenuTrigger as-child>
                              <Button variant="ghost" size="sm" class="h-8 w-8 p-0">
                                <MoreVertical class="h-4 w-4" />
                              </Button>
                            </DropdownMenuTrigger>
                            <DropdownMenuContent align="end" class="w-48">
                              <DropdownMenuItem @click="openView(patient)">
                                <i class="fas fa-eye mr-2 w-4 text-center"></i>
                                <span>View Details</span>
                              </DropdownMenuItem>
                              <DropdownMenuItem 
                                v-if="props.can.updatePatient"
                                @click="openEdit(patient)"
                              >
                                <i class="fas fa-edit mr-2 w-4 text-center"></i>
                                <span>Edit</span>
                              </DropdownMenuItem>
                              <DropdownMenuItem 
                                v-if="props.can.deletePatient"
                                @click="openDelete(patient)" 
                                class="text-red-600 focus:text-red-600 dark:text-red-400 dark:focus:text-red-400"
                              >
                                <i class="fas fa-trash mr-2 w-4 text-center"></i>
                                <span>Delete</span>
                              </DropdownMenuItem>
                            </DropdownMenuContent>
                          </DropdownMenu>
                        </div>
                      </td>
                    </tr>
                    <tr v-if="patientsFromServer.length === 0">
                      <td colspan="6" class="px-6 py-12 text-center">
                        <div class="flex flex-col items-center gap-4 text-gray-600 dark:text-gray-400">
                          <Users class="w-10 h-10 text-gray-400" />
                          <div>
                            <p class="text-xl font-semibold text-gray-900 dark:text-white">
                              {{ searchQuery ? 'No patients found' : 'No patients yet' }}
                            </p>
                            <p class="text-sm">
                              {{ searchQuery ? 'Try adjusting your search criteria' : 'Get started by adding your first patient.' }}
                            </p>
                          </div>
                          <div class="flex gap-2">
                            <Button
                              v-if="!searchQuery && props.can.createPatient"
                              @click.stop="openCreate"
                              class="bg-gradient-to-r from-blue-600 to-cyan-600 hover:from-blue-700 hover:to-cyan-700"
                            >
                              <Plus class="w-4 h-4 mr-2" />
                              Add First Patient
                            </Button>
                            <Button v-else @click.stop="searchQuery = ''" variant="outline">
                              Clear Search
                            </Button>
                          </div>
                        </div>
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>

              <div v-if="paginationLinks.length > 0" class="flex flex-col md:flex-row md:items-center md:justify-between items-start gap-4 mt-6">
                <div class="flex-1">
                  <Pagination
                    v-if="paginationLinks.length > 1"
                    :links="paginationLinks"
                    :from="(currentPage - 1) * parseInt(listViewPerPage) + 1"
                    :to="Math.min(currentPage * parseInt(listViewPerPage), totalPatients)"
                    :total="totalPatients"
                    :item-name="totalPatients === 1 ? 'patient' : 'patients'"
                    @page-change="goToPage"
                  />
                </div>
                <div class="flex items-center gap-2">
                  <Label for="per-page" class="text-sm text-gray-600 dark:text-gray-400 whitespace-nowrap">Per page:</Label>
                  <Select v-model="listViewPerPage">
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
            </div>
          </CardContent>
        </Card>
      </div>
    </div>

    <!-- Create Modal -->
    <Dialog :open="isCreateOpen" @update:open="(value) => isCreateOpen = value">
      <DialogContent class="max-w-2xl">
        <DialogHeader>
          <DialogTitle class="text-2xl font-bold bg-gradient-to-r from-blue-600 to-cyan-600 bg-clip-text text-transparent">
            Add New Patient
          </DialogTitle>
          <DialogDescription class="text-gray-600 dark:text-gray-400">
            Fill in the details to add a new patient to the system.
          </DialogDescription>
        </DialogHeader>
        <form @submit.prevent="submitCreate" class="space-y-6">
          <div class="space-y-2">
            <Label for="name" class="text-gray-700 dark:text-gray-300">Full Name</Label>
            <Input
              id="name"
              v-model="createForm.name"
              placeholder="Enter patient's full name"
              class="h-12 bg-gray-50 dark:bg-gray-800 border-gray-200 dark:border-gray-700"
              required
            />
          </div>
          <div class="space-y-2">
            <Label for="email" class="text-gray-700 dark:text-gray-300">Email Address</Label>
            <Input
              id="email"
              type="email"
              v-model="createForm.email"
              placeholder="email@email.com"
              class="h-12 bg-gray-50 dark:bg-gray-800 border-gray-200 dark:border-gray-700"
            />
            <div v-if="createForm.errors.email" class="text-sm text-red-600 dark:text-red-400">
              {{ Array.isArray(createForm.errors.email) ? createForm.errors.email[0] : createForm.errors.email }}
            </div>
          </div>
          <div class="space-y-2">
            <Label for="phone" class="text-gray-700 dark:text-gray-300">Phone Number</Label>
            <Input
              id="phone"
              v-model="createForm.phone"
              placeholder="07XXXXXXXX"
              class="h-12 bg-gray-50 dark:bg-gray-800 border-gray-200 dark:border-gray-700"
              required
            />
          </div>
          <div class="space-y-2">
            <Label for="dob" class="text-gray-700 dark:text-gray-300">Date of Birth</Label>
            <Input
              id="dob"
              type="date"
              v-model="createForm.dob"
              class="h-12 bg-gray-50 dark:bg-gray-800 border-gray-200 dark:border-gray-700"
              :required="!createForm.age"
            />
            <div v-if="createForm.errors.dob" class="text-sm text-red-600 dark:text-red-400">
              {{ Array.isArray(createForm.errors.dob) ? createForm.errors.dob[0] : createForm.errors.dob }}
            </div>
            <p class="text-xs text-gray-500 dark:text-gray-400">Or enter age below</p>
          </div>
          <div class="space-y-2">
            <Label for="age" class="text-gray-700 dark:text-gray-300">Age</Label>
            <Input
              id="age"
              type="number"
              min="0"
              max="150"
              v-model="createForm.age"
              placeholder="e.g., 25"
              class="h-12 bg-gray-50 dark:bg-gray-800 border-gray-200 dark:border-gray-700"
              :required="!createForm.dob"
            />
            <p class="text-xs text-gray-500 dark:text-gray-400">Or enter date of birth above</p>
          </div>
          <DialogFooter class="gap-2">
            <Button type="button" variant="outline" @click="isCreateOpen = false">
              Cancel
            </Button>
            <Button type="submit" :disabled="createForm.processing" class="bg-gradient-to-r from-blue-600 to-cyan-600 hover:from-blue-700 hover:to-cyan-700">
              <i v-if="createForm.processing" class="fas fa-spinner fa-spin mr-2"></i>
              <i v-else class="fas fa-user-plus mr-2"></i>
              {{ createForm.processing ? 'Creating...' : 'Create Patient' }}
            </Button>
          </DialogFooter>
        </form>
      </DialogContent>
    </Dialog>

    <!-- Edit Modal -->
    <Dialog :open="isEditOpen" @update:open="(value) => isEditOpen = value">
      <DialogContent class="max-w-2xl">
        <DialogHeader>
          <DialogTitle class="text-2xl font-bold bg-gradient-to-r from-blue-600 to-cyan-600 bg-clip-text text-transparent">
            Edit Patient
          </DialogTitle>
          <DialogDescription class="text-gray-600 dark:text-gray-400">
            Update the patient information below.
          </DialogDescription>
        </DialogHeader>
        <form @submit.prevent="submitEdit" class="space-y-6">
          <div class="space-y-2">
            <Label for="edit-name" class="text-gray-700 dark:text-gray-300">Full Name</Label>
            <Input
              id="edit-name"
              v-model="editForm.name"
              placeholder="Enter patient's full name"
              class="h-12 bg-gray-50 dark:bg-gray-800 border-gray-200 dark:border-gray-700"
              required
            />
          </div>
          <div class="space-y-2">
            <Label for="edit-email" class="text-gray-700 dark:text-gray-300">Email Address</Label>
            <Input
              id="edit-email"
              type="email"
              v-model="editForm.email"
              placeholder="email@email.com"
              class="h-12 bg-gray-50 dark:bg-gray-800 border-gray-200 dark:border-gray-700"
            />
            <div v-if="editForm.errors.email" class="text-sm text-red-600 dark:text-red-400">
              {{ Array.isArray(editForm.errors.email) ? editForm.errors.email[0] : editForm.errors.email }}
            </div>
          </div>
          <div class="space-y-2">
            <Label for="edit-phone" class="text-gray-700 dark:text-gray-300">Phone Number</Label>
            <Input
              id="edit-phone"
              v-model="editForm.phone"
              placeholder="07XXXXXXXX"
              class="h-12 bg-gray-50 dark:bg-gray-800 border-gray-200 dark:border-gray-700"
              required
            />
          </div>
          <div class="space-y-2">
            <Label for="edit-dob" class="text-gray-700 dark:text-gray-300">Date of Birth</Label>
            <Input
              id="edit-dob"
              type="date"
              v-model="editForm.dob"
              class="h-12 bg-gray-50 dark:bg-gray-800 border-gray-200 dark:border-gray-700"
              :required="!editForm.age"
            />
            <div v-if="editForm.errors.dob" class="text-sm text-red-600 dark:text-red-400">
              {{ Array.isArray(editForm.errors.dob) ? editForm.errors.dob[0] : editForm.errors.dob }}
            </div>
            <p class="text-xs text-gray-500 dark:text-gray-400">Or enter age below</p>
          </div>
          <div class="space-y-2">
            <Label for="edit-age" class="text-gray-700 dark:text-gray-300">Age</Label>
            <Input
              id="edit-age"
              type="number"
              min="0"
              max="150"
              v-model="editForm.age"
              placeholder="e.g., 25"
              class="h-12 bg-gray-50 dark:bg-gray-800 border-gray-200 dark:border-gray-700"
              :required="!editForm.dob"
            />
            <p class="text-xs text-gray-500 dark:text-gray-400">Or enter date of birth above</p>
          </div>
          <DialogFooter class="gap-2">
            <Button type="button" variant="outline" @click="isEditOpen = false">
              Cancel
            </Button>
            <Button type="submit" :disabled="editForm.processing" class="bg-gradient-to-r from-blue-600 to-cyan-600 hover:from-blue-700 hover:to-cyan-700">
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
          <DialogTitle class="text-xl font-bold text-red-600">Delete Patient</DialogTitle>
          <DialogDescription class="text-gray-600 dark:text-gray-400">
            This action cannot be undone. This will permanently delete the patient record and all associated data.
          </DialogDescription>
        </DialogHeader>
        <div class="py-4">
          <div class="flex items-center space-x-3 p-4 bg-red-50 dark:bg-red-900/20 rounded-lg border border-red-200 dark:border-red-800">
            <i class="fas fa-exclamation-triangle text-red-600 text-xl"></i>
            <div>
              <p class="font-medium text-red-800 dark:text-red-200">Delete {{ editingPatient?.name }}?</p>
              <p class="text-sm text-red-600 dark:text-red-400">This will remove all patient data permanently.</p>
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
            Delete Patient
          </Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>

    <!-- View Patient Modal -->
    <Dialog :open="isViewOpen" @update:open="(value) => isViewOpen = value">
      <DialogContent class="max-w-4xl max-h-[90vh] flex flex-col">
        <DialogHeader class="flex-shrink-0">
          <DialogTitle class="text-2xl font-bold bg-gradient-to-r from-blue-600 to-cyan-600 bg-clip-text text-transparent">
            Patient Profile: {{ viewingPatient?.name }}
          </DialogTitle>
          <DialogDescription class="text-gray-600 dark:text-gray-400">
            Detailed patient information and records
          </DialogDescription>
        </DialogHeader>
        <div class="flex-1 overflow-y-auto py-4">
          <div v-if="viewingPatient" class="space-y-6">
            <!-- Patient Avatar and Basic Info -->
            <div class="flex items-center space-x-4 p-4 bg-gradient-to-r from-blue-50 to-cyan-50 dark:from-blue-900/20 dark:to-cyan-900/20 rounded-lg">
              <div class="w-16 h-16 rounded-full bg-gradient-to-br from-blue-500 to-cyan-500 flex items-center justify-center shadow-lg">
                <span class="text-white font-bold text-2xl">{{ viewingPatient.name.charAt(0).toUpperCase() }}</span>
              </div>
              <div>
                <h3 class="text-xl font-semibold text-gray-900 dark:text-white">{{ viewingPatient.name }}</h3>
                <p class="text-gray-600 dark:text-gray-400">Patient ID: {{ viewingPatient.id }}</p>
              </div>
            </div>
            <!-- Patient Details -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
              <Card>
                <CardHeader>
                  <CardTitle class="text-lg">Contact Information</CardTitle>
                </CardHeader>
                <CardContent class="space-y-4">
                  <div class="flex items-center space-x-3">
                    <i class="fas fa-envelope text-blue-500 w-5"></i>
                    <div>
                      <p class="text-sm text-gray-500 dark:text-gray-400">Email</p>
                      <p class="font-medium">{{ viewingPatient.email || 'N/A' }}</p>
                    </div>
                  </div>
                  <div class="flex items-center space-x-3">
                    <Phone class="w-5 h-5 text-green-500" />
                    <div>
                      <p class="text-sm text-gray-500 dark:text-gray-400">Phone</p>
                      <p class="font-medium">{{ viewingPatient.phone }}</p>
                    </div>
                  </div>
                </CardContent>
              </Card>
              <Card>
                <CardHeader>
                  <CardTitle class="text-lg">Personal Information</CardTitle>
                </CardHeader>
                <CardContent class="space-y-4">
                  <div class="flex items-center space-x-3">
                    <i class="fas fa-birthday-cake text-purple-500 w-5"></i>
                    <div>
                      <p class="text-sm text-gray-500 dark:text-gray-400">Date of Birth</p>
                      <p class="font-medium">{{ viewingPatient.dob_formatted }}</p>
                    </div>
                  </div>
                  <div class="flex items-center space-x-3">
                    <i class="fas fa-user text-orange-500 w-5"></i>
                    <div>
                      <p class="text-sm text-gray-500 dark:text-gray-400">Age</p>
                      <p class="font-medium">{{ calculateAge(viewingPatient.dob) }} years</p>
                    </div>
                  </div>
                </CardContent>
              </Card>
            </div>
            <!-- Treatments Section -->
            <Card>
              <CardHeader>
                <CardTitle class="text-lg">Treatment History</CardTitle>
                <CardDescription>Medical procedures performed for this patient</CardDescription>
              </CardHeader>
              <CardContent>
                <div v-if="viewingPatient.treatments && viewingPatient.treatments.length > 0" class="space-y-4">
                  <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                      <thead>
                        <tr class="border-b border-gray-200 dark:border-gray-700">
                          <th class="text-left py-2 px-3 font-medium text-gray-700 dark:text-gray-300">Procedure</th>
                          <th class="text-left py-2 px-3 font-medium text-gray-700 dark:text-gray-300">Prescriptions</th>
                          <th class="text-left py-2 px-3 font-medium text-gray-700 dark:text-gray-300">Date</th>
                          <th class="text-left py-2 px-3 font-medium text-gray-700 dark:text-gray-300">Total Cost</th>
                        </tr>
                      </thead>
                      <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                        <tr v-for="treatment in viewingPatient.treatments" :key="treatment.id" class="hover:bg-gray-50 dark:hover:bg-gray-800/50">
                          <td class="py-3 px-3">
                            <div class="flex items-center space-x-2">
                              <i class="fas fa-tooth text-blue-500"></i>
                              <span class="font-medium text-gray-900 dark:text-white">{{ treatment.procedure }}</span>
                              <span class="text-green-600 dark:text-green-400">({{ formatCurrency(treatment.cost || 0) }})</span>
                            </div>
                          </td>
                          <td class="py-3 px-3">
                            <div v-if="treatment.prescriptions && treatment.prescriptions.length > 0" class="space-y-1">
                              <div v-for="prescription in treatment.prescriptions" :key="prescription.id" class="text-xs">
                                <span class="text-gray-600 dark:text-gray-400">
                                  {{ prescription.medicine?.medicine_name || prescription.medicine_id || 'N/A' }}
                                  <span v-if="prescription.prescription_amount" class="text-green-600 dark:text-green-400">
                                    ({{ formatCurrency(prescription.prescription_amount) }})
                                  </span>
                                </span>
                              </div>
                            </div>
                            <span v-else class="text-gray-500 dark:text-gray-500 italic">No prescriptions</span>
                          </td>
                          <td class="py-3 px-3 text-gray-600 dark:text-gray-400">
                            {{ treatment.created_at ? new Date(treatment.created_at).toLocaleDateString() : 'Not specified' }}
                          </td>
                          <td class="py-3 px-3">
                            <span class="font-medium text-red-600 dark:text-red-400">
                              {{ formatCurrency(calculateTotalCost(treatment)) }}
                            </span>
                          </td>
                        </tr>
                      </tbody>
                    </table>
                  </div>
                </div>
                <div v-else class="text-center py-8 text-gray-500">
                  <i class="fas fa-tooth text-4xl mb-4 text-gray-300"></i>
                  <p>No treatment records found for this patient.</p>
                  <p class="text-sm mt-2">Treatments will appear here once procedures are performed.</p>
                </div>
              </CardContent>
            </Card>
            <!-- Actions -->
            <div class="flex justify-between items-center pt-4 border-t border-gray-200 dark:border-gray-700 flex-shrink-0">
              <div class="flex space-x-2">
                <Button variant="outline" as-child>
                  <Link :href="route('patients.show', viewingPatient.id)">
                    View Full Profile
                  </Link>
                </Button>
                <Button @click="isViewOpen = false">
                  Close
                </Button>
              </div>
            </div>
          </div>
        </div>
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