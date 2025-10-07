<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { Button } from '@/Components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/Components/ui/card';
import { Badge } from '@/Components/ui/badge';
import AppLayout from '@/Layouts/AppLayout.vue';
import { ArrowLeft } from 'lucide-vue-next';

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
}

interface Props {
  treatment: Treatment;
}

const props = defineProps<Props>();
</script>

<template>
  <AppLayout :title="`Treatment: ${props.treatment.procedure}`">
    <Head :title="`Treatment: ${props.treatment.procedure}`" />

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
      </div>

      <!-- Treatment Details -->
      <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <Card>
          <CardHeader>
            <CardTitle>Treatment Information</CardTitle>
            <CardDescription>Procedure details and cost</CardDescription>
          </CardHeader>
          <CardContent class="space-y-4">
            <div>
              <Label class="text-sm font-medium text-gray-500">Procedure</Label>
              <p class="text-sm">{{ props.treatment.procedure }}</p>
            </div>
            <div>
              <Label class="text-sm font-medium text-gray-500">Cost</Label>
              <p class="text-sm">
                <Badge variant="secondary">${{ props.treatment.cost }}</Badge>
              </p>
            </div>
            <div>
              <Label class="text-sm font-medium text-gray-500">Patient</Label>
              <p class="text-sm">{{ props.treatment.patient.name }} ({{ props.treatment.patient.email }})</p>
            </div>
            <div>
              <Label class="text-sm font-medium text-gray-500">Created</Label>
              <p class="text-sm">{{ new Date(props.treatment.created_at).toLocaleDateString() }}</p>
            </div>
          </CardContent>
        </Card>

        <Card>
          <CardHeader>
            <CardTitle>Additional Details</CardTitle>
            <CardDescription>Notes and attachments</CardDescription>
          </CardHeader>
          <CardContent class="space-y-4">
            <div>
              <Label class="text-sm font-medium text-gray-500">Notes</Label>
              <p class="text-sm">{{ props.treatment.notes || 'No notes provided' }}</p>
            </div>
            <div v-if="props.treatment.file_path">
              <Label class="text-sm font-medium text-gray-500">Attachment</Label>
              <p class="text-sm">
                <a :href="props.treatment.file_path" target="_blank" class="text-blue-600 hover:text-blue-800">
                  View attached file
                </a>
              </p>
            </div>
            <div v-if="props.treatment.appointment">
              <Label class="text-sm font-medium text-gray-500">Related Appointment</Label>
              <p class="text-sm">Appointment #{{ props.treatment.appointment.id }}</p>
            </div>
          </CardContent>
        </Card>
      </div>
    </div>
  </AppLayout>
</template>
