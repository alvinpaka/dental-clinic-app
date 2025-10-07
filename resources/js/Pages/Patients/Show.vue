<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import { router } from '@inertiajs/vue3';
import { Button } from '@/Components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/Components/ui/card';
import { Badge } from '@/Components/ui/badge';
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogDescription, DialogFooter } from '@/Components/ui/dialog';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/Components/ui/select';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import AppLayout from '@/Layouts/AppLayout.vue';
import { ref } from 'vue';
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
}

interface Props {
  patient: Patient;
  patients: Patient[];
}

const props = defineProps<Props>();

const isCreateTreatmentOpen = ref(false);

const treatmentForm = useForm({
  patient_id: '',
  procedure: '',
  cost: '',
  notes: '',
});

const openCreateTreatment = () => {
  isCreateTreatmentOpen.value = true;
};

const submitTreatment = () => {
  // Basic client-side validation
  if (!treatmentForm.patient_id) {
    alert('Please select a patient');
    return;
  }
  if (!treatmentForm.procedure.trim()) {
    alert('Please enter a procedure name');
    return;
  }
  if (!treatmentForm.cost || treatmentForm.cost <= 0) {
    alert('Please enter a valid cost');
    return;
  }

  console.log('Submitting treatment form:', {
    patient_id: treatmentForm.patient_id,
    procedure: treatmentForm.procedure,
    cost: treatmentForm.cost,
    notes: treatmentForm.notes
  });

  treatmentForm.post(route('treatments.store'), {
    onSuccess: () => {
      treatmentForm.reset();
      isCreateTreatmentOpen.value = false;
      router.reload();
    },
    onError: (errors) => {
      console.error('Treatment creation errors:', errors);
      alert('Error creating treatment: ' + Object.values(errors).flat().join(', '));
    }
  });
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
        <Button @click="openCreateTreatment" class="bg-dental-blue hover:bg-dental-dark flex items-center space-x-2">
          <Plus class="mr-2 h-4 w-4" />
          <span>Add Treatment</span>
        </Button>
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
            <div
              v-for="treatment in props.patient.treatments"
              :key="treatment.id"
              class="border rounded-lg p-4"
            >
              <div class="flex justify-between items-start">
                <div>
                  <h3 class="font-medium">{{ treatment.procedure }}</h3>
                  <p class="text-sm text-gray-600 mt-1">{{ treatment.notes }}</p>
                  <p class="text-xs text-gray-500 mt-2">
                    {{ new Date(treatment.created_at).toLocaleDateString() }}
                  </p>
                </div>
                <Badge variant="secondary">${{ treatment.cost }}</Badge>
              </div>
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
            <div>
              <Label for="patient">Patient</Label>
              <Select v-model="treatmentForm.patient_id">
                <SelectTrigger>
                  <SelectValue placeholder="Select a patient" />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem v-for="patient in props.patients" :key="patient.id" :value="patient.id.toString()">
                    {{ patient.name }} ({{ patient.email }})
                  </SelectItem>
                </SelectContent>
              </Select>
            </div>
            <div>
              <Label for="procedure">Procedure</Label>
              <Input id="procedure" v-model="treatmentForm.procedure" required placeholder="e.g., Teeth Cleaning" />
            </div>
            <div>
              <Label for="cost">Cost ($)</Label>
              <Input id="cost" type="number" step="0.01" v-model="treatmentForm.cost" required placeholder="0.00" />
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
            <DialogFooter>
              <Button type="submit" :disabled="treatmentForm.processing">Save Treatment</Button>
            </DialogFooter>
          </form>
        </DialogContent>
      </Dialog>
    </div>
  </AppLayout>
</template>
