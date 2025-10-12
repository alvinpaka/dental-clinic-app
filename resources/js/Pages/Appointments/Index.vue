<script setup lang="ts">
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import { route } from 'ziggy-js';
import { onMounted, ref, computed, watch } from 'vue';
import FullCalendar from '@fullcalendar/vue3';
import dayGridPlugin from '@fullcalendar/daygrid';
import timeGridPlugin from '@fullcalendar/timegrid';
import interactionPlugin from '@fullcalendar/interaction';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/Components/ui/card';
import { Button } from '@/Components/ui/button';
import { Badge } from '@/Components/ui/badge';
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogDescription, DialogFooter } from '@/Components/ui/dialog';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/Components/ui/select';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/Components/ui/tabs';
import { Plus, Calendar, Clock, Users, Search, Filter, MoreVertical } from 'lucide-vue-next';
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuTrigger } from '@/Components/ui/dropdown-menu';
import AppLayout from '@/Layouts/AppLayout.vue';

interface Appointment {
  id: number;
  title: string;
  patient: { id: number; name: string };
  dentist?: { id: number; name: string };
  start: string;
  end: string;
  status: 'scheduled' | 'confirmed' | 'completed' | 'cancelled';
  type: string;
  notes?: string;
}

interface Patient {
  id: number;
  name: string;
  email: string;
}

interface Props {
  appointments: Appointment[];
  patients: Patient[];
  stats?: {
    total_today: number;
    total_week: number;
    total_month: number;
    upcoming: number;
  };
}

const props = defineProps<Props>();

// Helper functions
const getStatusColor = (status: string) => {
  switch (status) {
    case 'scheduled': return '#3B82F6'; // blue
    case 'confirmed': return '#10B981'; // green
    case 'completed': return '#6B7280'; // gray
    case 'cancelled': return '#EF4444'; // red
    default: return '#6B7280';
  }
};

const getStatusBadgeVariant = (status: string) => {
  switch (status) {
    case 'scheduled': return 'secondary';
    case 'confirmed': return 'default';
    case 'completed': return 'outline';
    case 'cancelled': return 'destructive';
    default: return 'outline';
  }
};

const formatTime = (dateString: string) => {
  return new Date(dateString).toLocaleTimeString('en-US', {
    hour: '2-digit',
    minute: '2-digit',
    hour12: true
  });
};

const formatDate = (dateString: string) => {
  return new Date(dateString).toLocaleDateString('en-US', {
    weekday: 'short',
    month: 'short',
    day: 'numeric'
  });
};

const activeTab = ref('calendar');
const selectedDate = ref(new Date().toISOString().split('T')[0]);
const searchQuery = ref('');
const statusFilter = ref('');

// Convert appointments to FullCalendar events
const calendarEvents = computed(() => {
  return props.appointments.map(apt => ({
    id: apt.id,
    title: `${apt.patient.name} - ${apt.type}`,
    start: apt.start,
    end: apt.end,
    backgroundColor: getStatusColor(apt.status),
    borderColor: getStatusColor(apt.status),
    extendedProps: apt,
  }));
});

const filteredAppointments = computed(() => {
  let appointments = [...props.appointments];

  if (searchQuery.value) {
    appointments = appointments.filter(apt =>
      apt.patient.name.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
      apt.type.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
      (apt.notes && apt.notes.toLowerCase().includes(searchQuery.value.toLowerCase()))
    );
  }

  if (statusFilter.value && statusFilter.value !== 'all') {
    appointments = appointments.filter(apt => apt.status === statusFilter.value);
  }

  return appointments.sort((a, b) => new Date(a.start).getTime() - new Date(b.start).getTime());
});

const calendarOptions = ref({
  plugins: [dayGridPlugin, timeGridPlugin, interactionPlugin],
  initialView: 'dayGridMonth',
  events: calendarEvents.value,
  editable: false,
  selectable: true,
  selectMirror: true,
  dayMaxEvents: 3,
  weekends: true,
  headerToolbar: {
    left: 'prev,next today',
    center: 'title',
    right: 'dayGridMonth,timeGridWeek,timeGridDay'
  },
  select: (info: any) => {
    selectedDate.value = info.startStr.split('T')[0];
    openCreate();
  },
  eventClick: (info: any) => {
    console.log('Calendar event clicked:', info.event);
    console.log('Extended props:', info.event.extendedProps);
    const appointment = info.event.extendedProps;
    openEdit(appointment);
  },
  height: '600px',
  eventDisplay: 'block',
  eventTimeFormat: {
    hour: '2-digit',
    minute: '2-digit',
    hour12: false
  }
});

watch(calendarEvents, (newEvents) => {
  calendarOptions.value.events = newEvents;
});

// Check for patient_id query parameter and auto-open create modal
onMounted(() => {
  const urlParams = new URLSearchParams(window.location.search);
  const patientId = urlParams.get('patient_id');

  if (patientId) {
    createForm.patient_id = parseInt(patientId);
    isCreateOpen.value = true;

    // Clean up URL by removing the query parameter
    const newUrl = window.location.pathname;
    window.history.replaceState({}, '', newUrl);
  }
});

// Forms
const createForm = useForm({
  patient_id: null as number | null,
  date: '',
  start_time: '',
  end_time: '',
  type: '',
  notes: '',
});

const editForm = useForm({
  patient_id: null as number | null,
  date: '',
  start_time: '',
  end_time: '',
  type: '',
  status: '',
  notes: '',
});

// Modal states
const isCreateOpen = ref(false);
const isEditOpen = ref(false);
const isDeleteOpen = ref(false);
const editingAppointment = ref<Appointment | null>(null);

const openCreate = () => {
  createForm.date = selectedDate.value;
  isCreateOpen.value = true;
};

const openEdit = (appointment: Appointment) => {
  console.log('Opening edit for appointment:', appointment);
  editingAppointment.value = appointment;
  editForm.patient_id = appointment.patient.id;
  editForm.date = new Date(appointment.start).toISOString().split('T')[0];
  editForm.start_time = new Date(appointment.start).toTimeString().slice(0, 5);
  editForm.end_time = new Date(appointment.end).toTimeString().slice(0, 5);
  editForm.type = appointment.type;
  editForm.status = appointment.status;
  editForm.notes = appointment.notes || '';
  isEditOpen.value = true;
};

const openDelete = (appointment: Appointment) => {
  editingAppointment.value = appointment;
  isDeleteOpen.value = true;
};

const submitCreate = () => {
  if (!createForm.patient_id || !createForm.date || !createForm.start_time || !createForm.end_time) {
    alert('Please fill in all required fields');
    return;
  }

  createForm.post(route('appointments.store'), {
    onSuccess: () => {
      createForm.reset();
      isCreateOpen.value = false;
    },
  });
};

const submitEdit = () => {
  if (!editForm.patient_id || !editForm.date || !editForm.start_time || !editForm.end_time) {
    alert('Please fill in all required fields');
    return;
  }

  if (editingAppointment.value && editingAppointment.value.id) {
    console.log('Editing appointment:', editingAppointment.value);
    console.log('Appointment ID:', editingAppointment.value.id);
    const routeUrl = `/appointments/${editingAppointment.value.id}`;
    console.log('Route URL:', routeUrl);
    editForm.put(routeUrl, {
      onSuccess: () => {
        editForm.reset();
        isEditOpen.value = false;
        editingAppointment.value = null;
      },
      onError: (errors) => {
        console.error('Update errors:', errors);
      },
    });
  } else {
    console.error('No editing appointment found or invalid ID');
    console.error('editingAppointment:', editingAppointment.value);
  }
};

const confirmDelete = () => {
  if (editingAppointment.value) {
    console.log('Deleting appointment:', editingAppointment.value);
    console.log('Appointment ID:', editingAppointment.value.id);
    const deleteUrl = `/appointments/${editingAppointment.value.id}`;
    console.log('Delete URL:', deleteUrl);
    router.delete(deleteUrl, {
      onSuccess: () => {
        isDeleteOpen.value = false;
        editingAppointment.value = null;
      },
    });
  }
};
</script>

<template>
  <AppLayout title="Appointments">
    <Head title="Appointments" />

    <div class="min-h-screen bg-gradient-to-br from-blue-50 via-white to-cyan-50 dark:from-gray-900 dark:via-gray-800 dark:to-blue-900">
      <div class="container mx-auto px-4 py-8">
        <!-- Header Section -->
        <div class="mb-8">
          <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div>
              <h1 class="text-4xl font-bold bg-gradient-to-r from-blue-600 to-cyan-600 bg-clip-text text-transparent mb-2">
                Appointment Management
              </h1>
              <p class="text-gray-600 dark:text-gray-400 text-lg">
                Schedule and manage patient appointments efficiently
              </p>
            </div>

            <div class="flex items-center gap-3">
              <Badge variant="secondary" class="bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400">
                <Calendar class="w-4 h-4 mr-1" />
                {{ props.appointments.length }} Total Appointments
              </Badge>

              <Button @click="openCreate" class="bg-gradient-to-r from-blue-600 to-cyan-600 hover:from-blue-700 hover:to-cyan-700 text-white shadow-lg hover:shadow-xl transition-all duration-300">
                <Plus class="w-4 h-4 mr-2" />
                New Appointment
              </Button>
            </div>
          </div>
        </div>

        <!-- Stats Overview -->
        <div v-if="props.stats" class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
          <Card class="border-0 shadow-xl hover:shadow-2xl transition-all duration-300 hover:-translate-y-1 bg-gradient-to-br from-blue-50 to-blue-100 dark:from-blue-900/20 dark:to-blue-800/20">
            <CardContent class="p-6">
              <div class="flex items-center justify-between">
                <div>
                  <p class="text-sm font-medium text-blue-700 dark:text-blue-300 mb-1">Today</p>
                  <p class="text-3xl font-bold text-blue-900 dark:text-blue-100 mb-1">{{ props.stats.total_today }}</p>
                  <p class="text-xs text-blue-600 dark:text-blue-400">Appointments scheduled</p>
                </div>
                <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-blue-500 to-blue-600 flex items-center justify-center shadow-lg">
                  <Calendar class="w-6 h-6 text-white" />
                </div>
              </div>
            </CardContent>
          </Card>

          <Card class="border-0 shadow-xl hover:shadow-2xl transition-all duration-300 hover:-translate-y-1 bg-gradient-to-br from-green-50 to-green-100 dark:from-green-900/20 dark:to-green-800/20">
            <CardContent class="p-6">
              <div class="flex items-center justify-between">
                <div>
                  <p class="text-sm font-medium text-green-700 dark:text-green-300 mb-1">This Week</p>
                  <p class="text-3xl font-bold text-green-900 dark:text-green-100 mb-1">{{ props.stats.total_week }}</p>
                  <p class="text-xs text-green-600 dark:text-green-400">Upcoming appointments</p>
                </div>
                <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-green-500 to-green-600 flex items-center justify-center shadow-lg">
                  <Clock class="w-6 h-6 text-white" />
                </div>
              </div>
            </CardContent>
          </Card>

          <Card class="border-0 shadow-xl hover:shadow-2xl transition-all duration-300 hover:-translate-y-1 bg-gradient-to-br from-purple-50 to-purple-100 dark:from-purple-900/20 dark:to-purple-800/20">
            <CardContent class="p-6">
              <div class="flex items-center justify-between">
                <div>
                  <p class="text-sm font-medium text-purple-700 dark:text-purple-300 mb-1">This Month</p>
                  <p class="text-3xl font-bold text-purple-900 dark:text-purple-100 mb-1">{{ props.stats.total_month }}</p>
                  <p class="text-xs text-purple-600 dark:text-purple-400">Total appointments</p>
                </div>
                <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-purple-500 to-purple-600 flex items-center justify-center shadow-lg">
                  <Users class="w-6 h-6 text-white" />
                </div>
              </div>
            </CardContent>
          </Card>

          <Card class="border-0 shadow-xl hover:shadow-2xl transition-all duration-300 hover:-translate-y-1 bg-gradient-to-br from-orange-50 to-orange-100 dark:from-orange-900/20 dark:to-orange-800/20">
            <CardContent class="p-6">
              <div class="flex items-center justify-between">
                <div>
                  <p class="text-sm font-medium text-orange-700 dark:text-orange-300 mb-1">Pending</p>
                  <p class="text-3xl font-bold text-orange-900 dark:text-orange-100 mb-1">{{ props.stats.upcoming }}</p>
                  <p class="text-xs text-orange-600 dark:text-orange-400">Awaiting confirmation</p>
                </div>
                <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-orange-500 to-orange-600 flex items-center justify-center shadow-lg">
                  <Clock class="w-6 h-6 text-white" />
                </div>
              </div>
            </CardContent>
          </Card>
        </div>

        <!-- Main Content -->
        <Card class="border-0 shadow-xl bg-white dark:bg-gray-900">
          <CardHeader class="pb-4">
            <div>
              <CardTitle class="text-2xl text-gray-900 dark:text-white">Appointment Calendar</CardTitle>
              <CardDescription class="text-gray-600 dark:text-gray-400">
                View and manage all patient appointments
              </CardDescription>
            </div>
          </CardHeader>

          <CardContent>
            <Tabs v-model="activeTab" class="w-full">
              <TabsList class="grid w-full grid-cols-2">
                <TabsTrigger value="calendar">Calendar View</TabsTrigger>
                <TabsTrigger value="list">List View</TabsTrigger>
              </TabsList>

              <!-- Calendar View -->
              <TabsContent value="calendar" class="mt-0">
                <div class="calendar-container">
                  <FullCalendar :options="calendarOptions" />
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
                        placeholder="Search appointments..."
                        class="pl-10 h-12 bg-gray-50 dark:bg-gray-800 border-gray-200 dark:border-gray-700"
                      />
                    </div>

                    <Select v-model="statusFilter">
                      <SelectTrigger class="w-48 h-12">
                        <SelectValue placeholder="Filter by status" />
                      </SelectTrigger>
                      <SelectContent>
                        <SelectItem value="all">All Statuses</SelectItem>
                        <SelectItem value="scheduled">Scheduled</SelectItem>
                        <SelectItem value="confirmed">Confirmed</SelectItem>
                        <SelectItem value="completed">Completed</SelectItem>
                        <SelectItem value="cancelled">Cancelled</SelectItem>
                      </SelectContent>
                    </Select>
                  </div>

                  <!-- Appointments List -->
                  <div class="space-y-3 max-h-96 overflow-y-auto">
                    <Card
                      v-for="(appointment, index) in filteredAppointments"
                      :key="appointment.id"
                      class="border hover:shadow-md transition-shadow cursor-pointer group"
                      @click="openEdit(appointment)"
                    >
                      <CardContent class="p-4">
                        <div class="flex items-center justify-between">
                          <div class="flex items-center space-x-3">
                            <div
                              class="w-3 h-3 rounded-full flex-shrink-0"
                              :style="{ backgroundColor: getStatusColor(appointment.status) }"
                            ></div>
                            <div>
                              <h4 class="font-medium text-gray-900 dark:text-white group-hover:text-blue-600 transition-colors">
                                {{ appointment.patient.name }}
                              </h4>
                              <p class="text-sm text-gray-600 dark:text-gray-400">
                                {{ appointment.type }} • {{ formatDate(appointment.start) }} at {{ formatTime(appointment.start) }}
                              </p>
                            </div>
                          </div>

                          <div class="flex items-center space-x-2">
                            <Badge :variant="getStatusBadgeVariant(appointment.status)" class="text-xs">
                              {{ appointment.status }}
                            </Badge>

                            <DropdownMenu>
                              <DropdownMenuTrigger as-child @click.stop>
                                <Button variant="ghost" size="sm" class="h-8 w-8 p-0">
                                  <MoreVertical class="h-4 w-4" />
                                </Button>
                              </DropdownMenuTrigger>
                              <DropdownMenuContent align="end">
                                <DropdownMenuItem @click.stop="openEdit(appointment)">
                                  <i class="fas fa-edit mr-2"></i>
                                  Edit
                                </DropdownMenuItem>
                                <DropdownMenuItem @click.stop="openDelete(appointment)" class="text-red-600">
                                  <i class="fas fa-trash mr-2"></i>
                                  Delete
                                </DropdownMenuItem>
                              </DropdownMenuContent>
                            </DropdownMenu>
                          </div>
                        </div>
                      </CardContent>
                    </Card>

                    <div v-if="filteredAppointments.length === 0" class="text-center py-8">
                      <Calendar class="w-12 h-12 mx-auto mb-4 text-gray-400" />
                      <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-2">No appointments found</h3>
                      <p class="text-gray-600 dark:text-gray-400">Try adjusting your search criteria or create a new appointment.</p>
                    </div>
                  </div>
                </div>
              </TabsContent>
            </Tabs>
          </CardContent>
        </Card>
      </div>
    </div>

    <!-- Create Appointment Modal -->
    <Dialog :open="isCreateOpen" @update:open="(value) => isCreateOpen = value">
      <DialogContent class="max-w-md">
        <DialogHeader>
          <DialogTitle class="text-2xl font-bold bg-gradient-to-r from-blue-600 to-cyan-600 bg-clip-text text-transparent">
            Schedule New Appointment
          </DialogTitle>
          <DialogDescription class="text-gray-600 dark:text-gray-400">
            Book a new appointment for a patient.
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
                <SelectItem v-for="patient in props.patients" :key="patient.id" :value="patient.id">
                  {{ patient.name }} ({{ patient.email }})
                </SelectItem>
              </SelectContent>
            </Select>
          </div>

          <div class="grid grid-cols-2 gap-4">
            <div class="space-y-2">
              <Label for="date" class="text-gray-700 dark:text-gray-300">Date</Label>
              <Input
                id="date"
                type="date"
                v-model="createForm.date"
                class="h-12 bg-gray-50 dark:bg-gray-800 border-gray-200 dark:border-gray-700"
                required
              />
            </div>

            <div class="space-y-2">
              <Label for="type" class="text-gray-700 dark:text-gray-300">Type</Label>
              <Select v-model="createForm.type">
                <SelectTrigger class="h-12 bg-gray-50 dark:bg-gray-800 border-gray-200 dark:border-gray-700">
                  <SelectValue placeholder="Appointment type" />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem value="Consultation">Consultation</SelectItem>
                  <SelectItem value="Cleaning">Cleaning</SelectItem>
                  <SelectItem value="Filling">Filling</SelectItem>
                  <SelectItem value="Root Canal">Root Canal</SelectItem>
                  <SelectItem value="Crown">Crown</SelectItem>
                  <SelectItem value="Extraction">Extraction</SelectItem>
                  <SelectItem value="X-Ray">X-Ray</SelectItem>
                  <SelectItem value="Other">Other</SelectItem>
                </SelectContent>
              </Select>
            </div>
          </div>

          <div class="grid grid-cols-2 gap-4">
            <div class="space-y-2">
              <Label for="start_time" class="text-gray-700 dark:text-gray-300">Start Time</Label>
              <Input
                id="start_time"
                type="time"
                v-model="createForm.start_time"
                class="h-12 bg-gray-50 dark:bg-gray-800 border-gray-200 dark:border-gray-700"
                required
              />
            </div>

            <div class="space-y-2">
              <Label for="end_time" class="text-gray-700 dark:text-gray-300">End Time</Label>
              <Input
                id="end_time"
                type="time"
                v-model="createForm.end_time"
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
              placeholder="Additional appointment details"
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
              <i v-else class="fas fa-calendar-plus mr-2"></i>
              {{ createForm.processing ? 'Scheduling...' : 'Schedule Appointment' }}
            </Button>
          </DialogFooter>
        </form>
      </DialogContent>
    </Dialog>

    <!-- Edit Appointment Modal -->
    <Dialog :open="isEditOpen" @update:open="(value) => isEditOpen = value">
      <DialogContent class="max-w-md">
        <DialogHeader>
          <DialogTitle class="text-2xl font-bold bg-gradient-to-r from-blue-600 to-cyan-600 bg-clip-text text-transparent">
            Edit Appointment
          </DialogTitle>
          <DialogDescription class="text-gray-600 dark:text-gray-400">
            Update the appointment details.
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
                <SelectItem v-for="patient in props.patients" :key="patient.id" :value="patient.id">
                  {{ patient.name }} ({{ patient.email }})
                </SelectItem>
              </SelectContent>
            </Select>
          </div>

          <div class="grid grid-cols-2 gap-4">
            <div class="space-y-2">
              <Label for="edit-date" class="text-gray-700 dark:text-gray-300">Date</Label>
              <Input
                id="edit-date"
                type="date"
                v-model="editForm.date"
                class="h-12 bg-gray-50 dark:bg-gray-800 border-gray-200 dark:border-gray-700"
                required
              />
            </div>

            <div class="space-y-2">
              <Label for="edit-status" class="text-gray-700 dark:text-gray-300">Status</Label>
              <Select v-model="editForm.status">
                <SelectTrigger class="h-12 bg-gray-50 dark:bg-gray-800 border-gray-200 dark:border-gray-700">
                  <SelectValue placeholder="Appointment status" />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem value="scheduled">Scheduled</SelectItem>
                  <SelectItem value="confirmed">Confirmed</SelectItem>
                  <SelectItem value="completed">Completed</SelectItem>
                  <SelectItem value="cancelled">Cancelled</SelectItem>
                </SelectContent>
              </Select>
            </div>
          </div>

          <div class="grid grid-cols-2 gap-4">
            <div class="space-y-2">
              <Label for="edit-start_time" class="text-gray-700 dark:text-gray-300">Start Time</Label>
              <Input
                id="edit-start_time"
                type="time"
                v-model="editForm.start_time"
                class="h-12 bg-gray-50 dark:bg-gray-800 border-gray-200 dark:border-gray-700"
                required
              />
            </div>

            <div class="space-y-2">
              <Label for="edit-end_time" class="text-gray-700 dark:text-gray-300">End Time</Label>
              <Input
                id="edit-end_time"
                type="time"
                v-model="editForm.end_time"
                class="h-12 bg-gray-50 dark:bg-gray-800 border-gray-200 dark:border-gray-700"
                required
              />
            </div>
          </div>

          <div class="space-y-2">
            <Label for="edit-type" class="text-gray-700 dark:text-gray-300">Type</Label>
            <Select v-model="editForm.type">
              <SelectTrigger class="h-12 bg-gray-50 dark:bg-gray-800 border-gray-200 dark:border-gray-700">
                <SelectValue placeholder="Appointment type" />
              </SelectTrigger>
              <SelectContent>
                <SelectItem value="Consultation">Consultation</SelectItem>
                <SelectItem value="Cleaning">Cleaning</SelectItem>
                <SelectItem value="Filling">Filling</SelectItem>
                <SelectItem value="Root Canal">Root Canal</SelectItem>
                <SelectItem value="Crown">Crown</SelectItem>
                <SelectItem value="Extraction">Extraction</SelectItem>
                <SelectItem value="X-Ray">X-Ray</SelectItem>
                <SelectItem value="Other">Other</SelectItem>
              </SelectContent>
            </Select>
          </div>

          <div class="space-y-2">
            <Label for="edit-notes" class="text-gray-700 dark:text-gray-300">Notes (Optional)</Label>
            <Input
              id="edit-notes"
              v-model="editForm.notes"
              placeholder="Additional appointment details"
              class="h-12 bg-gray-50 dark:bg-gray-800 border-gray-200 dark:border-gray-700"
            />
          </div>

          <!-- Debug information -->
          <div v-if="editingAppointment" class="p-4 bg-yellow-50 dark:bg-yellow-900/20 rounded-lg border border-yellow-200 dark:border-yellow-800">
            <p class="text-sm font-medium text-yellow-800 dark:text-yellow-200 mb-2">Debug Info:</p>
            <p class="text-xs text-yellow-700 dark:text-yellow-300">Appointment ID: {{ editingAppointment.id }}</p>
            <p class="text-xs text-yellow-700 dark:text-yellow-300">Patient: {{ editingAppointment.patient?.name }}</p>
            <p class="text-xs text-yellow-700 dark:text-yellow-300">Status: {{ editingAppointment.status }}</p>
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
          <DialogTitle class="text-xl font-bold text-red-600">Delete Appointment</DialogTitle>
          <DialogDescription class="text-gray-600 dark:text-gray-400">
            This action cannot be undone. This will permanently delete the appointment.
          </DialogDescription>
        </DialogHeader>

        <div class="py-4">
          <div class="flex items-center space-x-3 p-4 bg-red-50 dark:bg-red-900/20 rounded-lg border border-red-200 dark:border-red-800">
            <i class="fas fa-exclamation-triangle text-red-600 text-xl"></i>
            <div>
              <p class="font-medium text-red-800 dark:text-red-200">Delete appointment for {{ editingAppointment?.patient.name }}?</p>
              <p class="text-sm text-red-600 dark:text-red-400">This will remove the appointment permanently.</p>
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
            Delete Appointment
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

.calendar-container :deep(.fc) {
  font-family: inherit;
}

.calendar-container :deep(.fc-header-toolbar) {
  margin-bottom: 1.5rem !important;
  flex-wrap: wrap;
  gap: 0.5rem;
}

.calendar-container :deep(.fc-button) {
  background: linear-gradient(to right, #2563eb, #06b6d4) !important;
  border: none !important;
  color: white !important;
  font-weight: 500 !important;
  padding: 0.5rem 1rem !important;
  border-radius: 0.5rem !important;
  transition: all 0.2s ease !important;
}

.calendar-container :deep(.fc-button:hover) {
  background: linear-gradient(to right, #1d4ed8, #0891b2) !important;
  transform: translateY(-1px) !important;
}

.calendar-container :deep(.fc-button:not(:disabled).fc-button-active) {
  background: linear-gradient(to right, #1e40af, #0e7490) !important;
  box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1) !important;
}

.calendar-container :deep(.fc-daygrid-day) {
  transition: all 0.2s ease;
}

.calendar-container :deep(.fc-daygrid-day:hover) {
  background-color: rgba(59, 130, 246, 0.1) !important;
}

.calendar-container :deep(.fc-event) {
  border-radius: 0.375rem !important;
  border: none !important;
  font-size: 0.75rem !important;
  font-weight: 500 !important;
  padding: 0.125rem 0.375rem !important;
  transition: all 0.2s ease !important;
}

.calendar-container :deep(.fc-event:hover) {
  transform: translateY(-1px) !important;
  box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1) !important;
}

.calendar-container :deep(.fc-col-header-cell) {
  font-weight: 600 !important;
  color: rgb(55, 65, 81) !important;
  background: rgb(249, 250, 251) !important;
}

.dark .calendar-container :deep(.fc-col-header-cell) {
  color: rgb(209, 213, 219) !important;
  background: rgb(31, 41, 55) !important;
}
</style>