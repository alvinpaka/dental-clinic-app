<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/Components/ui/card';
import { Badge } from '@/Components/ui/badge';
import { Button } from '@/Components/ui/button';
import { Separator } from '@/Components/ui/separator';
import { Calendar, Clock, User, Pill, FileText, AlertTriangle, CheckCircle, ArrowLeft, Edit, Trash2 } from 'lucide-vue-next';
import AppLayout from '@/Layouts/AppLayout.vue';
import { Link, router } from '@inertiajs/vue3';

interface DentalMedicine {
  medicine_id: number;
  medicine_name: string;
  category: string;
  dosage_form: string | null;
  prescription_required: boolean;
  common_uses: string | null;
}

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
  medicine?: DentalMedicine;
  created_at?: string;
  updated_at?: string;
}

interface Props {
  prescription: Prescription;
}

const props = defineProps<Props>();

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
    case 'active': return CheckCircle;
    case 'completed': return CheckCircle;
    case 'expired': return AlertTriangle;
    case 'cancelled': return AlertTriangle;
    default: return Pill;
  }
};

const formatDate = (dateString: string) => {
  return new Date(dateString).toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'long',
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
  <AppLayout title="Prescription Details">
    <Head title="Prescription Details" />

    <div class="min-h-screen bg-gradient-to-br from-blue-50 via-white to-cyan-50 dark:from-gray-900 dark:via-gray-800 dark:to-blue-900">
      <div class="container mx-auto px-4 py-8 max-w-4xl">
        <!-- Header -->
        <div class="mb-8">
          <div class="flex items-center gap-4 mb-4">
            <Button variant="outline" size="sm" as-child>
              <Link :href="route('prescriptions.index')" class="flex items-center">
                <ArrowLeft class="w-4 h-4 mr-2" />
                Back to Prescriptions
              </Link>
            </Button>
          </div>

          <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div>
              <h1 class="text-4xl font-bold bg-gradient-to-r from-blue-600 to-cyan-600 bg-clip-text text-transparent mb-2">
                Prescription Details
              </h1>
              <p class="text-gray-600 dark:text-gray-400 text-lg">
                Prescription #{{ prescription.id }} for {{ prescription.patient.name }}
              </p>
            </div>

            <div class="flex items-center gap-3">
              <Badge :class="getStatusColor(prescription.status)" variant="secondary">
                <component :is="getStatusIcon(prescription.status)" class="w-4 h-4 mr-1" />
                {{ prescription.status }}
              </Badge>
            </div>
          </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
          <!-- Main Prescription Details -->
          <div class="lg:col-span-2 space-y-6">
            <!-- Prescription Info -->
            <Card class="border-0 shadow-xl bg-white dark:bg-gray-900">
              <CardHeader>
                <CardTitle class="flex items-center">
                  <Pill class="w-5 h-5 mr-2 text-blue-600" />
                  Medication Details
                </CardTitle>
              </CardHeader>
              <CardContent class="space-y-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                  <div class="space-y-4">
                    <div>
                      <label class="text-sm font-medium text-gray-700 dark:text-gray-300">Medication</label>
                      <p class="text-lg font-semibold text-gray-900 dark:text-white">{{ prescription.medication }}</p>
                      <p v-if="prescription.medicine" class="text-sm text-gray-600 dark:text-gray-400">
                        {{ prescription.medicine.category }}
                        <span v-if="prescription.medicine.dosage_form"> • {{ prescription.medicine.dosage_form }}</span>
                      </p>
                    </div>

                    <div>
                      <label class="text-sm font-medium text-gray-700 dark:text-gray-300">Dosage & Frequency</label>
                      <p class="text-gray-900 dark:text-white">{{ prescription.dosage }} • {{ prescription.frequency }}</p>
                    </div>

                    <div>
                      <label class="text-sm font-medium text-gray-700 dark:text-gray-300">Duration</label>
                      <p class="text-gray-900 dark:text-white">{{ prescription.duration }}</p>
                    </div>
                  </div>

                  <div class="space-y-4">
                    <div>
                      <label class="text-sm font-medium text-gray-700 dark:text-gray-300">Issue Date</label>
                      <p class="text-gray-900 dark:text-white">{{ formatDate(prescription.issue_date) }}</p>
                    </div>

                    <div v-if="prescription.expiry_date">
                      <label class="text-sm font-medium text-gray-700 dark:text-gray-300">Expiry Date</label>
                      <p :class="['font-medium', isExpiringSoon(prescription.expiry_date) ? 'text-amber-600 dark:text-amber-400' : 'text-gray-900 dark:text-white']">
                        {{ formatDate(prescription.expiry_date) }}
                        <AlertTriangle v-if="isExpiringSoon(prescription.expiry_date)" class="w-4 h-4 inline ml-1" />
                      </p>
                    </div>

                    <div v-if="prescription.max_refills">
                      <label class="text-sm font-medium text-gray-700 dark:text-gray-300">Refills</label>
                      <p class="text-gray-900 dark:text-white">
                        {{ getRefillStatus(prescription.refill_count || 0, prescription.max_refills).text }}
                      </p>
                    </div>
                  </div>
                </div>

                <Separator />

                <div v-if="prescription.instructions" class="space-y-2">
                  <label class="text-sm font-medium text-gray-700 dark:text-gray-300">Instructions</label>
                  <p class="text-gray-900 dark:text-white bg-gray-50 dark:bg-gray-800 p-3 rounded-lg">
                    {{ prescription.instructions }}
                  </p>
                </div>

                <div v-if="prescription.medicine?.common_uses" class="space-y-2">
                  <label class="text-sm font-medium text-gray-700 dark:text-gray-300">Common Uses</label>
                  <p class="text-gray-600 dark:text-gray-400 bg-blue-50 dark:bg-blue-900/20 p-3 rounded-lg">
                    {{ prescription.medicine.common_uses }}
                  </p>
                </div>
              </CardContent>
            </Card>

            <!-- Medicine Information -->
            <Card v-if="prescription.medicine" class="border-0 shadow-xl bg-white dark:bg-gray-900">
              <CardHeader>
                <CardTitle class="flex items-center">
                  <FileText class="w-5 h-5 mr-2 text-green-600" />
                  Medicine Information
                </CardTitle>
              </CardHeader>
              <CardContent class="space-y-4">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                  <div>
                    <label class="text-sm font-medium text-gray-700 dark:text-gray-300">Category</label>
                    <p class="text-gray-900 dark:text-white">{{ prescription.medicine.category }}</p>
                  </div>
                  <div>
                    <label class="text-sm font-medium text-gray-700 dark:text-gray-300">Dosage Form</label>
                    <p class="text-gray-900 dark:text-white">{{ prescription.medicine.dosage_form || 'N/A' }}</p>
                  </div>
                  <div class="md:col-span-2">
                    <label class="text-sm font-medium text-gray-700 dark:text-gray-300">Prescription Required</label>
                    <Badge :variant="prescription.medicine.prescription_required ? 'destructive' : 'secondary'" class="mt-1">
                      {{ prescription.medicine.prescription_required ? 'Yes' : 'No' }}
                    </Badge>
                  </div>
                </div>
              </CardContent>
            </Card>
          </div>

          <!-- Sidebar -->
          <div class="space-y-6">
            <!-- Patient Info -->
            <Card class="border-0 shadow-xl bg-white dark:bg-gray-900">
              <CardHeader>
                <CardTitle class="flex items-center">
                  <User class="w-5 h-5 mr-2 text-purple-600" />
                  Patient Information
                </CardTitle>
              </CardHeader>
              <CardContent class="space-y-4">
                <div>
                  <label class="text-sm font-medium text-gray-700 dark:text-gray-300">Name</label>
                  <p class="text-gray-900 dark:text-white font-medium">{{ prescription.patient.name }}</p>
                </div>
                <div>
                  <label class="text-sm font-medium text-gray-700 dark:text-gray-300">Email</label>
                  <p class="text-gray-900 dark:text-white">{{ prescription.patient.email }}</p>
                </div>
              </CardContent>
            </Card>

            <!-- Dentist Info -->
            <Card class="border-0 shadow-xl bg-white dark:bg-gray-900">
              <CardHeader>
                <CardTitle class="flex items-center">
                  <User class="w-5 h-5 mr-2 text-indigo-600" />
                  Prescribed By
                </CardTitle>
              </CardHeader>
              <CardContent>
                <div>
                  <label class="text-sm font-medium text-gray-700 dark:text-gray-300">Dentist</label>
                  <p class="text-gray-900 dark:text-white font-medium">{{ prescription.dentist.name }}</p>
                </div>
              </CardContent>
            </Card>

            <!-- Actions -->
            <Card class="border-0 shadow-xl bg-white dark:bg-gray-900">
              <CardHeader>
                <CardTitle>Actions</CardTitle>
              </CardHeader>
              <CardContent class="space-y-3">
                <Button class="w-full" variant="outline" as-child>
                  <Link :href="route('prescriptions.edit', prescription.id)" class="flex items-center">
                    <Edit class="w-4 h-4 mr-2" />
                    Edit Prescription
                  </Link>
                </Button>
                <Button
                  class="w-full"
                  variant="destructive"
                  @click="confirmDelete"
                >
                  <Trash2 class="w-4 h-4 mr-2" />
                  Delete Prescription
                </Button>
              </CardContent>
            </Card>

            <!-- Timestamps -->
            <Card class="border-0 shadow-xl bg-white dark:bg-gray-900">
              <CardHeader>
                <CardTitle class="flex items-center">
                  <Calendar class="w-5 h-5 mr-2 text-gray-600" />
                  Record Information
                </CardTitle>
              </CardHeader>
              <CardContent class="space-y-3">
                <div>
                  <label class="text-sm font-medium text-gray-700 dark:text-gray-300">Created</label>
                  <p class="text-gray-900 dark:text-white text-sm">
                    {{ prescription.created_at ? formatDate(prescription.created_at) : 'N/A' }}
                  </p>
                </div>
                <div v-if="prescription.updated_at && prescription.updated_at !== prescription.created_at">
                  <label class="text-sm font-medium text-gray-700 dark:text-gray-300">Last Updated</label>
                  <p class="text-gray-900 dark:text-white text-sm">
                    {{ formatDate(prescription.updated_at) }}
                  </p>
                </div>
              </CardContent>
            </Card>
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<style scoped>
.bg-clip-text {
  -webkit-background-clip: text;
  background-clip: text;
  -webkit-text-fill-color: transparent;
}
</style>
