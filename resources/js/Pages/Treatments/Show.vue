<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { Button } from '@/Components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/Components/ui/card';
import { Badge } from '@/Components/ui/badge';
import { Label } from '@/Components/ui/label';
import AppLayout from '@/Layouts/AppLayout.vue';
import { ArrowLeft, Download } from 'lucide-vue-next';
import { formatUGX } from '@/Composables/useCurrency';
import { computed } from 'vue';

interface Treatment {
  id: number;
  procedure: string;
  cost: number;
  notes?: string;
  file_path?: string;
  patient: { name: string; email: string };
  appointment?: { id: number };
  created_at: string;
  updated_at: string;
  prescriptions?: Array<{
    id: number;
    medicine?: { medicine_name: string };
    medication?: string;
    dosage?: string;
    frequency?: string;
    duration?: string;
    prescription_amount?: number;
    prescription_issue_date?: string;
    prescription_instructions?: string;
  }>;
}

interface Props {
  treatment: Treatment;
}

import { route } from 'ziggy-js';

const props = defineProps<Props>();

// Computed properties for performance optimization
const pageTitle = computed(() => `Treatment: ${props.treatment.procedure}`);
const createdDate = computed(() => new Date(props.treatment.created_at).toLocaleDateString());
const hasPrescriptions = computed(() => props.treatment?.prescriptions && props.treatment.prescriptions.length > 0);
const hasFile = computed(() => !!props.treatment.file_path);
const hasAppointment = computed(() => !!props.treatment.appointment);
const totalCost = computed(() => {
  const treatmentCost = Number(props.treatment?.cost) || 0;
  const prescriptionCost = props.treatment?.prescriptions?.reduce((sum, prescription) => {
    const amount = Number(prescription?.prescription_amount) || 0;
    return sum + amount;
  }, 0) || 0;
  return treatmentCost + prescriptionCost;
});

const downloadTreatment = () => {
  window.location.href = route('treatments.download', props.treatment.id);
};

const viewAttachment = () => {
  if (props.treatment.file_path) {
    window.open(`/storage/${props.treatment.file_path}`, '_blank');
  }
};

// Helper function for prescription data
const getPrescriptionDisplayData = (prescription: any) => ({
  medicine: prescription.medicine?.medicine_name || prescription.medication || 'N/A',
  dosage: prescription.dosage || 'N/A',
  frequency: prescription.frequency || 'N/A',
  duration: prescription.duration || 'N/A',
  amount: Number(prescription?.prescription_amount) > 0 ? formatUGX(Number(prescription?.prescription_amount) || 0) : 'N/A',
  issueDate: prescription.prescription_issue_date ? new Date(prescription.prescription_issue_date).toLocaleDateString() : 'N/A',
  hasInstructions: !!prescription.prescription_instructions,
  instructions: prescription.prescription_instructions || ''
});
</script>

<template>
  <AppLayout :title="pageTitle">
    <Head :title="pageTitle" />

    <div class="space-y-6">
      <!-- Header -->
      <div class="flex items-center justify-between">
        <div class="flex items-center space-x-4">
          <Button variant="ghost" @click="$inertia.visit(route('treatments.index'))">
            <ArrowLeft class="mr-2 h-4 w-4" />
            Back to Treatments
          </Button>
          <div>
            <h1 class="text-3xl font-bold">{{ props.treatment.procedure }}</h1>
            <p class="text-gray-600">Patient: {{ props.treatment.patient.name }}</p>
          </div>
        </div>
        <Button 
          @click="downloadTreatment" 
          class="bg-gradient-to-r from-green-600 to-emerald-600 hover:from-green-700 hover:to-emerald-700 text-white shadow-lg hover:shadow-xl transition-all duration-300"
        >
          <Download class="mr-2 h-4 w-4" />
          Download PDF Report
        </Button>
      </div>

      <!-- Treatment Details -->
      <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <Card class="border-0 shadow-lg bg-gradient-to-br from-blue-50 to-indigo-50 dark:from-blue-900/20 dark:to-indigo-900/20">
          <CardHeader class="pb-4">
            <CardTitle class="text-xl flex items-center">
              <div class="w-8 h-8 bg-gradient-to-r from-blue-500 to-indigo-600 rounded-lg flex items-center justify-center mr-3">
                <i class="fas fa-procedures text-white text-sm"></i>
              </div>
              Treatment Information
            </CardTitle>
            <CardDescription class="text-sm">Procedure details and cost</CardDescription>
          </CardHeader>
          <CardContent class="space-y-5">
            <div class="bg-white dark:bg-gray-800 rounded-lg p-4 border border-blue-100 dark:border-blue-800/50">
              <div class="flex items-center space-x-3 mb-2">
                <i class="fas fa-stethoscope text-blue-500"></i>
                <Label class="text-sm font-medium text-gray-600 dark:text-gray-400 uppercase tracking-wide">Procedure</Label>
              </div>
              <p class="text-lg font-semibold text-gray-900 dark:text-white">{{ props.treatment.procedure }}</p>
            </div>
            
            <div class="bg-white dark:bg-gray-800 rounded-lg p-4 border border-blue-100 dark:border-blue-800/50">
              <div class="flex items-center justify-between">
                <div class="flex items-center space-x-3">
                  <i class="fas fa-money-bill-wave text-emerald-500"></i>
                  <Label class="text-sm font-medium text-gray-600 dark:text-gray-400 uppercase tracking-wide">Cost</Label>
                </div>
                <Badge class="bg-gradient-to-r from-emerald-500 to-teal-600 text-white border-0 px-3 py-1 text-sm font-semibold">
                  {{ formatUGX(props.treatment?.cost || 0) }}
                </Badge>
              </div>
            </div>
            
            <div class="bg-white dark:bg-gray-800 rounded-lg p-4 border border-blue-100 dark:border-blue-800/50">
              <div class="flex items-center space-x-3 mb-2">
                <i class="fas fa-user text-purple-500"></i>
                <Label class="text-sm font-medium text-gray-600 dark:text-gray-400 uppercase tracking-wide">Patient</Label>
              </div>
              <div class="space-y-1">
                <p class="text-base font-semibold text-gray-900 dark:text-white">{{ props.treatment.patient.name }}</p>
                <p class="text-sm text-gray-500 dark:text-gray-400">{{ props.treatment.patient.email }}</p>
              </div>
            </div>
            
            <div class="bg-white dark:bg-gray-800 rounded-lg p-4 border border-blue-100 dark:border-blue-800/50">
              <div class="flex items-center space-x-3 mb-2">
                <i class="fas fa-calendar-plus text-orange-500"></i>
                <Label class="text-sm font-medium text-gray-600 dark:text-gray-400 uppercase tracking-wide">Created</Label>
              </div>
              <p class="text-base font-semibold text-gray-900 dark:text-white">{{ createdDate }}</p>
            </div>
          </CardContent>
        </Card>

        <Card class="border-0 shadow-lg bg-gradient-to-br from-purple-50 to-pink-50 dark:from-purple-900/20 dark:to-pink-900/20">
          <CardHeader class="pb-4">
            <CardTitle class="text-xl flex items-center">
              <div class="w-8 h-8 bg-gradient-to-r from-purple-500 to-pink-600 rounded-lg flex items-center justify-center mr-3">
                <i class="fas fa-notes-medical text-white text-sm"></i>
              </div>
              Additional Details
            </CardTitle>
            <CardDescription class="text-sm">Notes and attachments</CardDescription>
          </CardHeader>
          <CardContent class="space-y-5">
            <div class="bg-white dark:bg-gray-800 rounded-lg p-4 border border-purple-100 dark:border-purple-800/50">
              <div class="flex items-center space-x-3 mb-2">
                <i class="fas fa-comment-medical text-purple-500"></i>
                <Label class="text-sm font-medium text-gray-600 dark:text-gray-400 uppercase tracking-wide">Notes</Label>
              </div>
              <p class="text-gray-700 dark:text-gray-300 leading-relaxed">{{ props.treatment.notes || 'No notes provided' }}</p>
            </div>
            
            <div v-if="hasFile" class="bg-white dark:bg-gray-800 rounded-lg p-4 border border-purple-100 dark:border-purple-800/50">
              <div class="flex items-center space-x-3 mb-3">
                <i class="fas fa-paperclip text-pink-500"></i>
                <Label class="text-sm font-medium text-gray-600 dark:text-gray-400 uppercase tracking-wide">Attachment</Label>
              </div>
              <Button 
                variant="outline" 
                size="sm"
                @click="viewAttachment"
                class="bg-gradient-to-r from-pink-50 to-purple-50 hover:from-pink-100 hover:to-purple-100 border-pink-300 text-pink-700 hover:bg-pink-50 transition-all duration-300"
              >
                <i class="fas fa-external-link-alt mr-2"></i>
                View attached file
              </Button>
            </div>
            
            <div v-if="hasAppointment" class="bg-white dark:bg-gray-800 rounded-lg p-4 border border-purple-100 dark:border-purple-800/50">
              <div class="flex items-center space-x-3 mb-2">
                <i class="fas fa-calendar-check text-indigo-500"></i>
                <Label class="text-sm font-medium text-gray-600 dark:text-gray-400 uppercase tracking-wide">Related Appointment</Label>
              </div>
              <div class="flex items-center space-x-2">
                <Badge class="bg-gradient-to-r from-indigo-500 to-purple-600 text-white border-0">
                  #{{ props.treatment.appointment.id }}
                </Badge>
                <span class="text-sm text-gray-500 dark:text-gray-400">Appointment ID</span>
              </div>
            </div>
          </CardContent>
        </Card>
      </div>

      <!-- Prescription Information -->
      <Card v-if="hasPrescriptions" class="border-0 shadow-lg bg-gradient-to-br from-emerald-50 to-teal-50 dark:from-emerald-900/20 dark:to-teal-900/20">
        <CardHeader class="pb-4">
          <CardTitle class="text-xl flex items-center">
            <div class="w-8 h-8 bg-gradient-to-r from-emerald-500 to-teal-600 rounded-lg flex items-center justify-center mr-3">
              <i class="fas fa-pills text-white text-sm"></i>
            </div>
            Prescriptions
          </CardTitle>
          <CardDescription class="text-sm">Medications prescribed for this treatment</CardDescription>
        </CardHeader>
        <CardContent class="space-y-4">
          <div v-for="(prescription, index) in props.treatment.prescriptions" :key="prescription.id" 
               class="bg-white dark:bg-gray-800 rounded-xl shadow-md hover:shadow-lg transition-all duration-300 p-5 border border-emerald-100 dark:border-emerald-800/50">
            <!-- Prescription Header -->
            <div class="flex items-center space-x-3 mb-4">
              <div class="w-10 h-10 bg-gradient-to-r from-blue-500 to-indigo-600 rounded-full flex items-center justify-center">
                <span class="text-white font-bold text-sm">{{ index + 1 }}</span>
              </div>
              <div>
                <h3 class="font-semibold text-gray-900 dark:text-white text-lg">
                  {{ getPrescriptionDisplayData(prescription).medicine }}
                </h3>
              </div>
            </div>
            
            <!-- Prescription Details Grid -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
              <div class="bg-gray-50 dark:bg-gray-700/50 rounded-lg p-3">
                <div class="flex items-center space-x-2 mb-1">
                  <i class="fas fa-prescription-bottle text-blue-500 text-sm"></i>
                  <Label class="text-xs font-medium text-gray-600 dark:text-gray-400 uppercase tracking-wide">Dosage</Label>
                </div>
                <p class="text-sm font-semibold text-gray-900 dark:text-white">{{ getPrescriptionDisplayData(prescription).dosage }}</p>
              </div>
              
              <div class="bg-gray-50 dark:bg-gray-700/50 rounded-lg p-3">
                <div class="flex items-center space-x-2 mb-1">
                  <i class="fas fa-calendar-alt text-green-500 text-sm"></i>
                  <Label class="text-xs font-medium text-gray-600 dark:text-gray-400 uppercase tracking-wide">Issue Date</Label>
                </div>
                <p class="text-sm font-semibold text-gray-900 dark:text-white">{{ getPrescriptionDisplayData(prescription).issueDate }}</p>
              </div>
              
              <div class="bg-gray-50 dark:bg-gray-700/50 rounded-lg p-3">
                <div class="flex items-center space-x-2 mb-1">
                  <i class="fas fa-money-bill-wave text-emerald-500 text-sm"></i>
                  <Label class="text-xs font-medium text-gray-600 dark:text-gray-400 uppercase tracking-wide">Cost</Label>
                </div>
                <p class="text-sm font-semibold text-gray-900 dark:text-white">{{ getPrescriptionDisplayData(prescription).amount }}</p>
              </div>
            </div>
            
            <!-- Instructions Section -->
            <div v-if="getPrescriptionDisplayData(prescription).hasInstructions" 
                 class="bg-gradient-to-r from-blue-50 to-indigo-50 dark:from-blue-900/20 dark:to-indigo-900/20 rounded-lg p-4 border-l-4 border-blue-500">
              <div class="flex items-start space-x-3">
                <i class="fas fa-info-circle text-blue-500 mt-1"></i>
                <div class="flex-1">
                  <Label class="text-sm font-medium text-blue-900 dark:text-blue-100 mb-2 block">Instructions</Label>
                  <p class="text-sm text-blue-800 dark:text-blue-200 whitespace-pre-wrap leading-relaxed">
                    {{ getPrescriptionDisplayData(prescription).instructions }}
                  </p>
                </div>
              </div>
            </div>
          </div>
        </CardContent>
      </Card>

      <!-- Total Summary -->
      <Card class="border-0 shadow-lg bg-gradient-to-r from-blue-50 to-indigo-50 dark:from-blue-900/20 dark:to-indigo-900/20">
        <CardHeader>
          <CardTitle class="text-lg flex items-center">
            <i class="fas fa-calculator mr-2 text-blue-600 dark:text-blue-400"></i>
            Total Summary
          </CardTitle>
          <CardDescription>Complete cost breakdown for this treatment</CardDescription>
        </CardHeader>
        <CardContent>
          <div class="space-y-3">
            <div class="flex justify-between items-center py-2 border-b border-gray-200 dark:border-gray-700">
              <span class="text-sm font-medium text-gray-600 dark:text-gray-400">Procedure Cost:</span>
              <span class="text-sm font-semibold text-gray-900 dark:text-white">{{ formatUGX(props.treatment?.cost || 0) }}</span>
            </div>
            <div v-if="hasPrescriptions" class="flex justify-between items-center py-2 border-b border-gray-200 dark:border-gray-700">
              <span class="text-sm font-medium text-gray-600 dark:text-gray-400">Prescription Cost:</span>
              <span class="text-sm font-semibold text-gray-900 dark:text-white">
                {{ formatUGX(props.treatment?.prescriptions?.reduce((sum, prescription) => {
                  const amount = Number(prescription?.prescription_amount) || 0;
                  return sum + amount;
                }, 0) || 0) }}
              </span>
            </div>
            <div class="flex justify-between items-center py-3 bg-blue-100 dark:bg-blue-900/50 rounded-lg px-4">
              <span class="text-base font-bold text-blue-900 dark:text-blue-100">Total Treatment Cost:</span>
              <span class="text-lg font-bold text-blue-900 dark:text-blue-100">{{ formatUGX(totalCost) }}</span>
            </div>
          </div>
        </CardContent>
      </Card>
    </div>
  </AppLayout>
</template>
