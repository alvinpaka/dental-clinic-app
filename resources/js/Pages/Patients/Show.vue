<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import { router } from '@inertiajs/vue3';
import { Button } from '@/Components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/Components/ui/card';
import { Badge } from '@/Components/ui/badge';
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogDescription, DialogFooter } from '@/Components/ui/dialog';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import AppLayout from '@/Layouts/AppLayout.vue';
import { ref, onUnmounted } from 'vue';
import { Plus, ArrowLeft } from 'lucide-vue-next';

interface Patient {
  id: number;
  name: string;
  email: string;
  phone: string;
  dob: string;
  dob_formatted: string;
  treatments: Treatment[];
  appointments: any[];
  invoices: any[];
  prescriptions: any[];
}

interface Treatment {
  id: number;
  procedure: string;
  cost: number;
  notes: string;
  created_at: string;
  prescriptions?: any[];
}

interface Props {
  patient: Patient;
  patients: Patient[];
}

const props = defineProps<Props>();

const isCreateTreatmentOpen = ref(false);

const treatmentForm = useForm({
  patient_id: props.patient.id.toString(),
  procedure: '',
  cost: '',
  notes: '',
  file: null as File | null,
});

const openCreateTreatment = () => {
  isCreateTreatmentOpen.value = true;
};

const filePreview = ref<string | null>(null);
const fileInput = ref<HTMLInputElement | null>(null);

const handleFileChange = (e: Event) => {
  const target = e.target as HTMLInputElement;
  if (target.files && target.files[0]) {
    const file = target.files[0];
    treatmentForm.file = file;
    
    // Revoke previous object URL to prevent memory leaks
    if (filePreview.value) {
      URL.revokeObjectURL(filePreview.value);
    }
    
    // Create new preview URL
    filePreview.value = URL.createObjectURL(file);
  }
};

const removeFile = () => {
  // Revoke the object URL to prevent memory leaks
  if (filePreview.value) {
    URL.revokeObjectURL(filePreview.value);
  }
  
  if (fileInput.value) {
    fileInput.value.value = '';
  }
  treatmentForm.file = null;
  filePreview.value = null;
};

// Clean up object URLs when component is unmounted
onUnmounted(() => {
  if (filePreview.value) {
    URL.revokeObjectURL(filePreview.value);
  }
});

const submitTreatment = () => {
  if (!treatmentForm.procedure.trim()) {
    alert('Please enter a procedure name');
    return;
  }
  if (!treatmentForm.cost || Number(treatmentForm.cost) <= 0) {
    alert('Please enter a valid cost');
    return;
  }

  const formData = new FormData();
  formData.append('patient_id', treatmentForm.patient_id);
  formData.append('procedure', treatmentForm.procedure);
  formData.append('cost', treatmentForm.cost);
  formData.append('notes', treatmentForm.notes);
  
  if (treatmentForm.file) {
    formData.append('file', treatmentForm.file);
  }

  // Use axios directly to send form data
  window.axios.post(route('treatments.store'), formData, {
    headers: {
      'Content-Type': 'multipart/form-data',
    },
  })
  .then(response => {
    treatmentForm.reset();
    isCreateTreatmentOpen.value = false;
    router.reload();
  })
  .catch(error => {
    console.error('Treatment creation error:', error);
    const errorMessage = error.response?.data?.message || 'Error creating treatment';
    alert(errorMessage);
  });
};

const formatUGX = (value: number | string) => {
  const n = Number(value || 0);
  return `UGX ${Math.round(n).toLocaleString('en-US')}`;
};

// Format currency values
const formatCurrency = (amount: number) => {
  return new Intl.NumberFormat('en-US', {
    style: 'currency',
    currency: 'UGX',
    minimumFractionDigits: 0,
  }).format(amount).replace('UGX', 'UGX');
};

// Calculate total cost for treatment (procedure + prescriptions)
const calculateTotalCost = (treatment: Treatment) => {
  const procedureCost = Number(treatment.cost) || 0;
  const prescriptionCost = treatment.prescriptions?.reduce((total: number, prescription: any) => {
    return total + (Number(prescription.prescription_amount) || 0);
  }, 0) || 0;

  return procedureCost + prescriptionCost;
};
</script>

<template>
  <AppLayout :title="`Patient: ${props.patient.name}`">
    <Head :title="`Patient: ${props.patient.name}`" />

    <div class="space-y-6">
      <!-- Header -->
      <div class="flex items-center justify-between">
        <div class="flex items-center space-x-4">
          <Button variant="ghost" @click="$inertia.visit(route('patients.index'))">
            <ArrowLeft class="mr-2 h-4 w-4" />
            Back to Patients
          </Button>
          <div>
            <h1 class="text-3xl font-bold">{{ props.patient.name }}</h1>
            <p class="text-gray-600">{{ props.patient.email }} • {{ props.patient.phone }}</p>
          </div>
        </div>
        <div class="flex items-center gap-2">
          <template v-if="!$page.url.includes('/patients/')">
            <Button @click="openCreateTreatment" class="bg-dental-blue hover:bg-dental-dark flex items-center space-x-2">
              <Plus class="mr-2 h-4 w-4" />
              <span>Add Treatment</span>
            </Button>
          </template>
          <Button variant="outline" @click="$inertia.visit(route('patients.medical-history.show', props.patient.id))">
            Medical History
          </Button>
          <Button variant="outline" @click="$inertia.visit(route('patients.consents.index', props.patient.id))">
            Consents
          </Button>
          <Button variant="outline" @click="$inertia.visit(route('patients.notes.index', props.patient.id))">
            Clinical Notes
          </Button>
          <Button variant="outline" @click="$inertia.visit(route('patients.odontogram.show', props.patient.id))">
            Odontogram
          </Button>
        </div>
      </div>

      <!-- Patient Details -->
      <Card>
        <CardHeader>
          <CardTitle>Patient Information</CardTitle>
          <CardDescription>Basic patient details</CardDescription>
        </CardHeader>
        <CardContent class="grid grid-cols-2 gap-4">
          <div>
            <Label class="text-sm font-medium text-gray-500">Name</Label>
            <p class="text-sm">{{ props.patient.name }}</p>
          </div>
          <div>
            <Label class="text-sm font-medium text-gray-500">Email</Label>
            <p class="text-sm">{{ props.patient.email }}</p>
          </div>
          <div>
            <Label class="text-sm font-medium text-gray-500">Phone</Label>
            <p class="text-sm">{{ props.patient.phone }}</p>
          </div>
          <div>
            <Label class="text-sm font-medium text-gray-500">Date of Birth</Label>
            <p class="text-sm">{{ props.patient.dob_formatted }}</p>
          </div>
        </CardContent>
      </Card>

      <!-- Treatments -->
      <Card>
        <CardHeader>
          <CardTitle>Treatments</CardTitle>
          <CardDescription>Medical procedures performed</CardDescription>
        </CardHeader>
        <CardContent>
          <div v-if="props.patient.treatments.length === 0" class="text-center py-8 text-gray-500">
            No treatments recorded yet.
          </div>
          <div v-else class="space-y-4">
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
                  <tr v-for="treatment in props.patient.treatments" :key="treatment.id" class="hover:bg-gray-50 dark:hover:bg-gray-800/50">
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
                            {{ prescription.medicine?.medicine_name || prescription.medication }}
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
        </CardContent>
      </Card>

      <!-- Add Treatment Modal -->
      <Dialog :open="isCreateTreatmentOpen" @update:open="(value) => isCreateTreatmentOpen = value">
        <DialogContent>
          <DialogHeader>
            <DialogTitle>Add Treatment</DialogTitle>
            <DialogDescription>Select a patient and record a new medical procedure</DialogDescription>
          </DialogHeader>
          <form @submit.prevent="submitTreatment" class="space-y-4">
            <input type="hidden" v-model="treatmentForm.patient_id" />
            
            <div>
              <Label for="procedure">Procedure</Label>
              <Input 
                id="procedure" 
                v-model="treatmentForm.procedure" 
                required 
                placeholder="e.g., Teeth Cleaning" 
                class="w-full"
              />
            </div>
            
            <div>
              <Label for="cost">Cost (UGX)</Label>
              <Input 
                id="cost" 
                type="number" 
                step="1" 
                min="0"
                v-model="treatmentForm.cost" 
                required 
                placeholder="0" 
                class="w-full"
              />
            </div>
            
            <div>
              <Label for="notes">Notes</Label>
              <textarea
                id="notes"
                v-model="treatmentForm.notes"
                class="flex h-20 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
                placeholder="Additional notes..."
              ></textarea>
            </div>
            
            <div>
              <Label for="file">Attachment (Optional)</Label>
              <Input 
                id="file" 
                ref="fileInput"
                type="file" 
                @change="handleFileChange" 
                accept="image/jpeg,image/png"
                class="w-full"
              />
              <p class="text-xs text-muted-foreground mt-1">JPEG or PNG only. Max 2MB.</p>
              
              <!-- File Preview -->
              <div v-if="filePreview" class="mt-4">
                <p class="text-sm font-medium text-gray-700 mb-2">Preview:</p>
                <div class="relative inline-block border rounded-md p-2 bg-gray-50">
                  <img 
                    :src="filePreview" 
                    class="max-h-40 max-w-full object-contain" 
                    alt="Preview"
                  />
                  <button 
                    type="button"
                    @click.prevent="removeFile"
                    class="absolute -top-2 -right-2 bg-red-500 text-white rounded-full p-1 hover:bg-red-600 transition-colors flex items-center justify-center w-5 h-5"
                    title="Remove file"
                  >
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" viewBox="0 0 20 20" fill="currentColor">
                      <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
                    </svg>
                  </button>
                </div>
                <p class="mt-1 text-xs text-gray-500">
                  {{ treatmentForm.file?.name || 'Selected file' }}
                  <span v-if="treatmentForm.file?.size" class="text-gray-400">
                    ({{ Math.round(treatmentForm.file.size / 1024) }} KB)
                  </span>
                </p>
              </div>
            </div>
            
            <DialogFooter>
              <Button 
                type="submit" 
                :disabled="treatmentForm.processing"
                class="w-full sm:w-auto"
              >
                Save Treatment
              </Button>
            </DialogFooter>
          </form>
        </DialogContent>
      </Dialog>
    </div>
  </AppLayout>
</template>
