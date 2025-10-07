<script setup lang="ts">
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import { Button } from '@/Components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/Components/ui/card';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { Badge } from '@/Components/ui/badge';
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogDescription, DialogFooter } from '@/Components/ui/dialog';
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuTrigger } from '@/Components/ui/dropdown-menu';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/Components/ui/select';
import { Plus, Search, MoreVertical, Users, UserPlus, Calendar, Phone } from 'lucide-vue-next';
import AppLayout from '@/Layouts/AppLayout.vue';

interface Patient {
  id: number;
  name: string;
  email: string;
  phone: string;
  dob: string;
  dob_formatted: string;
}

interface Props {
  patients: {
    data: Patient[];
    links: any[];
  };
  stats?: {
    total_patients: number;
    new_this_month: number;
    active_appointments: number;
  };
}

const props = defineProps<Props>();

const searchQuery = ref('');
const sortBy = ref('name');
const sortOrder = ref('asc');

// Define forms with useForm
const createForm = useForm({
  name: '',
  email: '',
  phone: '',
  dob: '',
});

const editForm = useForm({
  name: '',
  email: '',
  phone: '',
  dob: '',
});

const isCreateOpen = ref(false);
const isEditOpen = ref(false);
const isDeleteOpen = ref(false);
const isViewOpen = ref(false);
const editingPatient = ref<Patient | null>(null);
const viewingPatient = ref<Patient | null>(null);

// Filtered and sorted patients
const filteredPatients = computed(() => {
  let patients = [...props.patients.data];

  // Search filter
  if (searchQuery.value) {
    patients = patients.filter(patient =>
      patient.name.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
      patient.email.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
      patient.phone.includes(searchQuery.value)
    );
  }

  // Sort
  patients.sort((a, b) => {
    let aValue = a[sortBy.value as keyof Patient];
    let bValue = b[sortBy.value as keyof Patient];

    if (sortBy.value === 'name') {
      aValue = aValue.toString().toLowerCase();
      bValue = bValue.toString().toLowerCase();
    }

    if (aValue < bValue) return sortOrder.value === 'asc' ? -1 : 1;
    if (aValue > bValue) return sortOrder.value === 'asc' ? 1 : -1;
    return 0;
  });

  return patients;
});

const openCreate = () => {
  isCreateOpen.value = true;
};

const openEdit = (patient: Patient) => {
  editingPatient.value = patient;
  editForm.name = patient.name;
  editForm.email = patient.email;
  editForm.phone = patient.phone;
  editForm.dob = patient.dob;
  isEditOpen.value = true;
};

const openDelete = (patient: Patient) => {
  editingPatient.value = patient;
  isDeleteOpen.value = true;
};

const openView = (patient: Patient) => {
  viewingPatient.value = patient;
  isViewOpen.value = true;
};

const submitCreate = () => {
  createForm.post(route('patients.store'), {
    onSuccess: () => {
      createForm.reset();
      isCreateOpen.value = false;
    },
  });
};

const submitEdit = () => {
  if (editingPatient.value) {
    editForm.put(route('patients.update', editingPatient.value.id), {
      onSuccess: () => {
        editForm.reset();
        isEditOpen.value = false;
        editingPatient.value = null;
      },
    });
  }
};

const confirmDelete = () => {
  if (editingPatient.value) {
    router.delete(route('patients.destroy', editingPatient.value.id), {
      onSuccess: () => {
        isDeleteOpen.value = false;
        editingPatient.value = null;
      },
    });
  }
};

// Calculate age from DOB
const calculateAge = (dob: string) => {
  const birthDate = new Date(dob);
  const today = new Date();
  let age = today.getFullYear() - birthDate.getFullYear();
  const monthDiff = today.getMonth() - birthDate.getMonth();

  if (monthDiff < 0 || (monthDiff === 0 && today.getDate() < birthDate.getDate())) {
    age--;
  }

  return age;
};
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
              <Badge variant="secondary" class="bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400">
                <Users class="w-4 h-4 mr-1" />
                {{ props.patients.data.length }} Total Patients
              </Badge>

              <Button @click="openCreate" class="bg-gradient-to-r from-blue-600 to-cyan-600 hover:from-blue-700 hover:to-cyan-700 text-white shadow-lg hover:shadow-xl transition-all duration-300">
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

        <!-- Search and Filters -->
        <Card class="border-0 shadow-xl bg-white dark:bg-gray-900 mb-8">
          <CardContent class="p-6">
            <div class="flex flex-col md:flex-row gap-4 items-center">
              <div class="relative flex-1">
                <Search class="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400 w-4 h-4" />
                <Input
                  v-model="searchQuery"
                  placeholder="Search patients by name, email, or phone..."
                  class="pl-10 h-12 bg-gray-50 dark:bg-gray-800 border-gray-200 dark:border-gray-700"
                />
              </div>

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
                    <SelectItem value="dob">Age</SelectItem>
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
          </CardContent>
        </Card>

        <!-- Patients Grid/List -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
          <Card
            v-for="(patient, index) in filteredPatients"
            :key="patient.id"
            class="border-0 shadow-xl hover:shadow-2xl transition-all duration-300 hover:-translate-y-2 bg-white dark:bg-gray-900 group"
          >
            <CardHeader class="pb-4">
              <div class="flex items-start justify-between">
                <div class="flex items-center space-x-3">
                  <div class="w-12 h-12 rounded-full bg-gradient-to-br from-blue-500 to-cyan-500 flex items-center justify-center shadow-lg">
                    <span class="text-white font-bold text-lg">{{ patient.name.charAt(0).toUpperCase() }}</span>
                  </div>
                  <div>
                    <CardTitle class="text-lg text-gray-900 dark:text-white group-hover:text-blue-600 dark:group-hover:text-cyan-400 transition-colors">
                      {{ patient.name }}
                    </CardTitle>
                    <CardDescription class="text-gray-600 dark:text-gray-400">
                      ID: {{ patient.id }}
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
                    <DropdownMenuItem @click="openView(patient)">
                      <i class="fas fa-eye mr-2"></i>
                      View Details
                    </DropdownMenuItem>
                    <DropdownMenuItem @click="openEdit(patient)">
                      <i class="fas fa-edit mr-2"></i>
                      Edit Patient
                    </DropdownMenuItem>
                    <DropdownMenuItem @click="openDelete(patient)" class="text-red-600">
                      <i class="fas fa-trash mr-2"></i>
                      Delete Patient
                    </DropdownMenuItem>
                  </DropdownMenuContent>
                </DropdownMenu>
              </div>
            </CardHeader>

            <CardContent class="space-y-4">
              <div class="grid grid-cols-2 gap-4 text-sm">
                <div class="flex items-center space-x-2">
                  <i class="fas fa-envelope text-gray-400 w-4"></i>
                  <span class="text-gray-600 dark:text-gray-400 truncate">{{ patient.email }}</span>
                </div>
                <div class="flex items-center space-x-2">
                  <Phone class="w-4 h-4 text-gray-400" />
                  <span class="text-gray-600 dark:text-gray-400">{{ patient.phone }}</span>
                </div>
                <div class="flex items-center space-x-2">
                  <i class="fas fa-birthday-cake text-gray-400 w-4"></i>
                  <span class="text-gray-600 dark:text-gray-400">{{ patient.dob_formatted }}</span>
                </div>
                <div class="flex items-center space-x-2">
                  <i class="fas fa-user text-gray-400 w-4"></i>
                  <span class="text-gray-600 dark:text-gray-400">{{ calculateAge(patient.dob) }} years old</span>
                </div>
              </div>

              <div class="flex justify-between items-center pt-4 border-t border-gray-100 dark:border-gray-800">
                <Button variant="outline" size="sm" @click="openView(patient)">
                  <i class="fas fa-eye mr-2"></i>
                  View Profile
                </Button>
                <Button size="sm" as-child>
                  <Link :href="route('appointments.index', { patient_id: patient.id })">
                    <Calendar class="w-4 h-4 mr-2" />
                    Book Appointment
                  </Link>
                </Button>
              </div>
            </CardContent>
          </Card>

          <!-- Empty State -->
          <div v-if="filteredPatients.length === 0" class="col-span-full">
            <Card class="border-0 shadow-xl bg-white dark:bg-gray-900">
              <CardContent class="p-12 text-center">
                <div class="w-24 h-24 mx-auto mb-6 rounded-full bg-gradient-to-br from-gray-100 to-gray-200 dark:from-gray-800 dark:to-gray-700 flex items-center justify-center">
                  <Users class="w-12 h-12 text-gray-400" />
                </div>
                <h3 class="text-xl font-semibold text-gray-900 dark:text-white mb-2">
                  {{ searchQuery ? 'No patients found' : 'No patients yet' }}
                </h3>
                <p class="text-gray-600 dark:text-gray-400 mb-6">
                  {{ searchQuery ? 'Try adjusting your search criteria' : 'Get started by adding your first patient' }}
                </p>
                <Button v-if="!searchQuery" @click="openCreate" class="bg-gradient-to-r from-blue-600 to-cyan-600 hover:from-blue-700 hover:to-cyan-700">
                  <Plus class="w-4 h-4 mr-2" />
                  Add First Patient
                </Button>
                <Button v-else @click="searchQuery = ''" variant="outline">
                  Clear Search
                </Button>
              </CardContent>
            </Card>
          </div>
        </div>
      </div>
    </div>

    <!-- Create Modal -->
    <Dialog :open="isCreateOpen" @update:open="(value) => isCreateOpen = value">
      <DialogContent class="max-w-md">
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
              placeholder="patient@example.com"
              class="h-12 bg-gray-50 dark:bg-gray-800 border-gray-200 dark:border-gray-700"
              required
            />
          </div>

          <div class="space-y-2">
            <Label for="phone" class="text-gray-700 dark:text-gray-300">Phone Number</Label>
            <Input
              id="phone"
              v-model="createForm.phone"
              placeholder="(555) 123-4567"
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
              required
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
              <i v-else class="fas fa-user-plus mr-2"></i>
              {{ createForm.processing ? 'Creating...' : 'Create Patient' }}
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
              placeholder="patient@example.com"
              class="h-12 bg-gray-50 dark:bg-gray-800 border-gray-200 dark:border-gray-700"
              required
            />
          </div>

          <div class="space-y-2">
            <Label for="edit-phone" class="text-gray-700 dark:text-gray-300">Phone Number</Label>
            <Input
              id="edit-phone"
              v-model="editForm.phone"
              placeholder="(555) 123-4567"
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
              required
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
      <DialogContent class="max-w-2xl max-h-[80vh] overflow-y-auto">
        <DialogHeader>
          <DialogTitle class="text-2xl font-bold bg-gradient-to-r from-blue-600 to-cyan-600 bg-clip-text text-transparent">
            Patient Profile: {{ viewingPatient?.name }}
          </DialogTitle>
          <DialogDescription class="text-gray-600 dark:text-gray-400">
            Detailed patient information and records
          </DialogDescription>
        </DialogHeader>

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
                    <p class="font-medium">{{ viewingPatient.email }}</p>
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
                    <p class="font-medium">{{ calculateAge(viewingPatient.dob) }} years old</p>
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
              <div class="text-center py-8 text-gray-500">
                <i class="fas fa-tooth text-4xl mb-4 text-gray-300"></i>
                <p>Treatment records are available in the detailed patient view.</p>
                <p class="text-sm mt-2">Click "View Full Profile" to see complete treatment history.</p>
              </div>
            </CardContent>
          </Card>

          <!-- Actions -->
          <div class="flex justify-between items-center pt-4 border-t border-gray-200 dark:border-gray-700">
            <Button variant="outline" @click="openEdit(viewingPatient)">
              <i class="fas fa-edit mr-2"></i>
              Edit Patient
            </Button>
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