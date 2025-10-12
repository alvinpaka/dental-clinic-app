<script setup lang="ts">
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/Components/ui/card';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { Button } from '@/Components/ui/button';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/Components/ui/select';
import { ArrowLeft, Save, Trash2 } from 'lucide-vue-next';
import AppLayout from '@/Layouts/AppLayout.vue';

interface DentalMedicine {
  medicine_id: number;
  medicine_name: string;
  category: string;
  dosage_form: string | null;
  prescription_required: boolean;
}

interface Patient {
  id: number;
  name: string;
  email: string;
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
}

interface Props {
  prescription: Prescription;
  patients: Patient[];
  medicines: DentalMedicine[];
}

const props = defineProps<Props>();

const form = useForm({
  patient_id: props.prescription.patient.id,
  medicine_id: props.prescription.medicine_id || null,
  dosage: props.prescription.dosage,
  frequency: props.prescription.frequency,
  duration: props.prescription.duration,
  instructions: props.prescription.instructions || '',
  expiry_date: props.prescription.expiry_date ? props.prescription.expiry_date.split('T')[0] : '',
  status: props.prescription.status,
  max_refills: props.prescription.max_refills || 0,
});

const submit = () => {
  if (!form.patient_id || !form.medicine_id || !form.dosage) {
    alert('Please fill in all required fields');
    return;
  }

  form.put(route('prescriptions.update', props.prescription.id), {
    onSuccess: () => {
      router.visit(route('prescriptions.show', props.prescription.id));
    },
  });
};

const confirmDelete = () => {
  if (confirm('Are you sure you want to delete this prescription? This action cannot be undone.')) {
    router.delete(route('prescriptions.destroy', props.prescription.id));
  }
};
</script>

<template>
  <AppLayout title="Edit Prescription">
    <Head title="Edit Prescription" />

    <div class="min-h-screen bg-gradient-to-br from-blue-50 via-white to-cyan-50 dark:from-gray-900 dark:via-gray-800 dark:to-blue-900">
      <div class="container mx-auto px-4 py-8 max-w-2xl">
        <!-- Header -->
        <div class="mb-8">
          <div class="flex items-center gap-4 mb-4">
            <Button variant="outline" size="sm" as-child>
              <Link :href="route('prescriptions.show', prescription.id)" class="flex items-center">
                <ArrowLeft class="w-4 h-4 mr-2" />
                Back to Details
              </Link>
            </Button>
          </div>

          <div>
            <h1 class="text-4xl font-bold bg-gradient-to-r from-blue-600 to-cyan-600 bg-clip-text text-transparent mb-2">
              Edit Prescription
            </h1>
            <p class="text-gray-600 dark:text-gray-400 text-lg">
              Update prescription details for {{ prescription.patient.name }}
            </p>
          </div>
        </div>

        <!-- Edit Form -->
        <Card class="border-0 shadow-xl bg-white dark:bg-gray-900">
          <CardHeader>
            <CardTitle>Prescription Information</CardTitle>
            <CardDescription>
              Modify the prescription details below
            </CardDescription>
          </CardHeader>
          <CardContent>
            <form @submit.prevent="submit" class="space-y-6">
              <div class="space-y-2">
                <Label for="patient" class="text-gray-700 dark:text-gray-300">Patient</Label>
                <Select v-model="form.patient_id">
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
                <Select v-model="form.medicine_id">
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
                    v-model="form.dosage"
                    placeholder="500mg, 10ml"
                    class="h-12 bg-gray-50 dark:bg-gray-800 border-gray-200 dark:border-gray-700"
                    required
                  />
                </div>

                <div class="space-y-2">
                  <Label for="frequency" class="text-gray-700 dark:text-gray-300">Frequency</Label>
                  <Select v-model="form.frequency">
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
                    v-model="form.duration"
                    placeholder="7 days, 2 weeks"
                    class="h-12 bg-gray-50 dark:bg-gray-800 border-gray-200 dark:border-gray-700"
                    required
                  />
                </div>

                <div class="space-y-2">
                  <Label for="status" class="text-gray-700 dark:text-gray-300">Status</Label>
                  <Select v-model="form.status">
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
                <Label for="max_refills" class="text-gray-700 dark:text-gray-300">Max Refills</Label>
                <Input
                  id="max_refills"
                  type="number"
                  v-model="form.max_refills"
                  placeholder="0"
                  min="0"
                  class="h-12 bg-gray-50 dark:bg-gray-800 border-gray-200 dark:border-gray-700"
                />
              </div>

              <div class="space-y-2">
                <Label for="instructions" class="text-gray-700 dark:text-gray-300">Instructions (Optional)</Label>
                <Input
                  id="instructions"
                  v-model="form.instructions"
                  placeholder="Take with food, avoid alcohol, etc."
                  class="h-12 bg-gray-50 dark:bg-gray-800 border-gray-200 dark:border-gray-700"
                />
              </div>

              <div class="space-y-2">
                <Label for="expiry_date" class="text-gray-700 dark:text-gray-300">Expiry Date (Optional)</Label>
                <Input
                  id="expiry_date"
                  type="date"
                  v-model="form.expiry_date"
                  class="h-12 bg-gray-50 dark:bg-gray-800 border-gray-200 dark:border-gray-700"
                />
              </div>

              <div class="flex flex-col sm:flex-row gap-3 pt-6">
                <Button
                  type="submit"
                  :disabled="form.processing"
                  class="bg-gradient-to-r from-blue-600 to-cyan-600 hover:from-blue-700 hover:to-cyan-700 flex-1"
                >
                  <Save class="w-4 h-4 mr-2" />
                  {{ form.processing ? 'Saving...' : 'Save Changes' }}
                </Button>

                <Button
                  type="button"
                  variant="destructive"
                  @click="confirmDelete"
                  class="sm:w-auto"
                >
                  <Trash2 class="w-4 h-4 mr-2" />
                  Delete
                </Button>
              </div>
            </form>
          </CardContent>
        </Card>
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
