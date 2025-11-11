<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3'
import { computed } from 'vue'
import AppLayout from '@/Layouts/AppLayout.vue'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/Components/ui/card'
import { Button } from '@/Components/ui/button'
import { Label } from '@/Components/ui/label'
import { Textarea } from '@/Components/ui/textarea'

interface Patient { id: number; name: string; email?: string; dob?: string }
interface History {
  id?: number
  conditions?: string | null
  medications?: string | null
  allergies?: string | null
  alerts?: string | null
  last_reviewed_at?: string | null
  reviewed_by?: number | null
}

interface Props {
  patient: Patient
  history: History | null
}

const props = defineProps<Props>()

const form = useForm({
  conditions: props.history?.conditions || '',
  medications: props.history?.medications || '',
  allergies: props.history?.allergies || '',
  alerts: props.history?.alerts || '',
})

const lastReviewed = computed(() => props.history?.last_reviewed_at ? new Date(props.history.last_reviewed_at).toLocaleString() : null)

const submit = () => {
  form.post(route('patients.medical-history.upsert', props.patient.id), {
    preserveScroll: true,
  })
}
</script>

<template>
  <AppLayout :title="`Medical History - ${props.patient.name}`">
    <Head :title="`Medical History - ${props.patient.name}`" />

    <div class="container mx-auto px-4 py-8 space-y-6">
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-2xl font-bold">Medical History</h1>
          <p class="text-gray-500">{{ props.patient.name }}</p>
        </div>
        <Button variant="outline" as-child>
          <a :href="route('patients.show', props.patient.id)">Back to Patient</a>
        </Button>
      </div>

      <Card>
        <CardHeader>
          <CardTitle>Clinical Background</CardTitle>
          <CardDescription>Record conditions, medications, allergies, and critical alerts</CardDescription>
        </CardHeader>
        <CardContent>
          <form @submit.prevent="submit" class="space-y-4">
            <div>
              <Label>Conditions</Label>
              <textarea v-model="form.conditions" class="w-full border rounded-md p-2 min-h-[90px]" placeholder="e.g., Hypertension, Diabetes"></textarea>
            </div>
            <div>
              <Label>Medications</Label>
              <textarea v-model="form.medications" class="w-full border rounded-md p-2 min-h-[90px]" placeholder="e.g., Metformin 500mg, Lisinopril 10mg"></textarea>
            </div>
            <div>
              <Label>Allergies</Label>
              <textarea v-model="form.allergies" class="w-full border rounded-md p-2 min-h-[90px]" placeholder="e.g., Penicillin, Latex"></textarea>
            </div>
            <div>
              <Label>Critical Alerts</Label>
              <textarea v-model="form.alerts" class="w-full border rounded-md p-2 min-h-[90px]" placeholder="e.g., Premedication required, bleeding disorder"></textarea>
            </div>
            <div class="flex items-center justify-between pt-2">
              <div class="text-xs text-gray-500">
                <span v-if="lastReviewed">Last reviewed: {{ lastReviewed }}</span>
                <span v-else>Not reviewed yet</span>
              </div>
              <Button type="submit" :disabled="form.processing" class="bg-blue-600 hover:bg-blue-700">
                {{ form.processing ? 'Saving...' : 'Save' }}
              </Button>
            </div>
          </form>
        </CardContent>
      </Card>
    </div>
  </AppLayout>
</template>
