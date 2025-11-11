<script setup lang="ts">
import { Head, useForm, usePage } from '@inertiajs/vue3'
import { onMounted, ref, computed } from 'vue'
import AppLayout from '@/Layouts/AppLayout.vue'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/Components/ui/card'
import { Button } from '@/Components/ui/button'
import { Label } from '@/Components/ui/label'
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/Components/ui/select'

interface Patient { id: number; name: string; email?: string }
interface Template { id: number; title: string; version: number; active: boolean; body?: string; signature_required?: boolean }
interface Consent { id: number; title: string; template_version?: number | null; signed_at: string; signature_path?: string | null }

interface Props {
  patient: Patient
  templates: Template[]
  consents: Consent[]
  context_vars?: Record<string, any>
}

const props = defineProps<Props>()
const page = usePage<any>()

const form = useForm({
  template_id: props.templates?.[0]?.id || null as number | null,
  signed_by_name: props.patient.name || '',
  signature_data: '' as string,
})

const selectedTemplate = computed(() => props.templates.find(t => t.id === form.template_id) || null)
const renderPreview = (tpl: string | undefined) => {
  if (!tpl) return ''
  const today = new Date()
  const clinicName = (props.context_vars?.clinic_name) || (page?.props?.app?.name) || 'DentalPro'
  const dentistName = props.context_vars?.dentist_name || ''
  const apptDate = props.context_vars?.appointment_date || today.toLocaleDateString()
  const vars: Record<string,string> = {
    patient_name: props.patient.name,
    date: today.toLocaleDateString(),
    time: today.toLocaleTimeString(),
    clinic_name: clinicName,
    dentist_name: dentistName,
    appointment_date: apptDate,
  }
  return tpl.replace(/\{\{\s*(\w+)\s*\}\}/g, (_, key) => vars[key] ?? '')
}

const showPreview = ref(false)
const openPreview = () => { if (selectedTemplate.value) showPreview.value = true }
const closePreview = () => { showPreview.value = false }

const printPreview = () => {
  const html = renderPreview(selectedTemplate.value?.body)
  const w = window.open('', '_blank', 'width=800,height=900')
  if (!w) return
  w.document.write(`<!doctype html><html><head><title>Consent Preview</title><style>body{font-family:ui-sans-serif,system-ui,Segoe UI,Roboto,Helvetica,Arial,sans-serif;white-space:pre-wrap;font-size:14px;color:#111;padding:24px}</style></head><body>${html.replace(/</g,'&lt;').replace(/\n/g,'<br/>')}</body></html>`)
  w.document.close()
  w.focus()
  w.print()
}

// Signature pad
const canvasRef = ref<HTMLCanvasElement | null>(null)
let drawing = false
let ctx: CanvasRenderingContext2D | null = null

const resizeCanvas = () => {
  const c = canvasRef.value
  if (!c) return
  const rect = c.getBoundingClientRect()
  c.width = rect.width * window.devicePixelRatio
  c.height = rect.height * window.devicePixelRatio
  if (ctx) {
    ctx.scale(window.devicePixelRatio, window.devicePixelRatio)
    ctx.lineWidth = 2
    ctx.lineCap = 'round'
    ctx.strokeStyle = '#111827'
  }
}

onMounted(() => {
  const c = canvasRef.value
  if (!c) return
  ctx = c.getContext('2d')
  resizeCanvas()
  window.addEventListener('resize', resizeCanvas)
})

const pos = (e: MouseEvent | TouchEvent) => {
  const c = canvasRef.value!
  const rect = c.getBoundingClientRect()
  const x = 'touches' in e ? e.touches[0].clientX : (e as MouseEvent).clientX
  const y = 'touches' in e ? e.touches[0].clientY : (e as MouseEvent).clientY
  return { x: x - rect.left, y: y - rect.top }
}

const startDraw = (e: MouseEvent | TouchEvent) => {
  drawing = true
  const p = pos(e)
  ctx?.beginPath()
  ctx?.moveTo(p.x, p.y)
}
const draw = (e: MouseEvent | TouchEvent) => {
  if (!drawing) return
  const p = pos(e)
  ctx?.lineTo(p.x, p.y)
  ctx?.stroke()
}
const endDraw = () => { drawing = false }
const clearSig = () => {
  const c = canvasRef.value
  if (!c || !ctx) return
  ctx.clearRect(0, 0, c.width, c.height)
}

const submit = () => {
  if (!form.template_id) return alert('Select a template')
  if (!form.signed_by_name.trim()) return alert('Enter signer name')
  // capture signature
  if (canvasRef.value) {
    form.signature_data = canvasRef.value.toDataURL('image/png')
  }
  form.post(route('patients.consents.sign', props.patient.id), { preserveScroll: true })
}
</script>

<template>
  <AppLayout :title="`Consents - ${props.patient.name}`">
    <Head :title="`Consents - ${props.patient.name}`" />

    <div class="container mx-auto px-4 py-8 space-y-6">
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-2xl font-bold">Consents</h1>
          <p class="text-gray-500">{{ props.patient.name }}</p>
        </div>
        <div class="flex items-center gap-2">
          <Button variant="outline" as-child>
            <a :href="route('consents.templates.index')">Templates</a>
          </Button>
          <Button variant="outline" as-child>
            <a :href="route('patients.show', props.patient.id)">Back to Patient</a>
          </Button>
        </div>
      </div>

      <Card>
        <CardHeader>
          <CardTitle>Record New Consent</CardTitle>
          <CardDescription>Select a template and capture a signature name</CardDescription>
        </CardHeader>
        <CardContent>
          <form @submit.prevent="submit" class="grid md:grid-cols-3 gap-4 items-start">
            <div>
              <Label>Template</Label>
              <Select v-model="(form.template_id as any)">
                <SelectTrigger>
                  <SelectValue placeholder="Select template" />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem v-for="t in props.templates" :key="t.id" :value="t.id">{{ t.title }} (v{{ t.version }})</SelectItem>
                </SelectContent>
              </Select>
              <div v-if="selectedTemplate" class="mt-2 text-xs">
                <span v-if="selectedTemplate.signature_required" class="inline-block px-2 py-0.5 rounded bg-red-100 text-red-700">Signature required</span>
                <span v-else class="inline-block px-2 py-0.5 rounded bg-gray-100 text-gray-700">Signature optional</span>
              </div>
            </div>
            <div>
              <Label>Signed By</Label>
              <input v-model="form.signed_by_name" class="w-full border rounded-md p-2" placeholder="Patient full name" />
            </div>
            <div class="flex gap-2">
              <Button type="submit" :disabled="form.processing" class="bg-blue-600 hover:bg-blue-700 w-full md:w-auto">
                {{ form.processing ? 'Saving...' : 'Record Consent' }}
              </Button>
              <Button type="button" variant="outline" :disabled="!selectedTemplate" @click="openPreview">Preview</Button>
            </div>
            <div class="md:col-span-3">
              <Label>Template Preview</Label>
              <div class="whitespace-pre-wrap border rounded-md p-3 bg-white min-h-[120px]">{{ renderPreview(selectedTemplate?.body) }}</div>
            </div>
          </form>
        </CardContent>
      </Card>

      <!-- Preview Modal -->
      <div v-if="showPreview" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50">
        <div class="bg-white rounded-md shadow-xl max-w-2xl w-full p-4">
          <div class="flex items-center justify-between mb-2">
            <div class="text-lg font-semibold">Consent Preview</div>
            <button class="p-1" @click="closePreview"><i class="fas fa-times"></i></button>
          </div>
          <div class="whitespace-pre-wrap border rounded-md p-3 max-h-[60vh] overflow-auto">{{ renderPreview(selectedTemplate?.body) }}</div>
          <div class="mt-3 flex justify-end gap-2">
            <Button variant="outline" @click="closePreview">Close</Button>
            <Button class="bg-blue-600 hover:bg-blue-700" @click="printPreview">Print</Button>
          </div>
        </div>
      </div>

      <Card>
        <CardHeader>
          <CardTitle>Signed Consents</CardTitle>
          <CardDescription>Most recent first</CardDescription>
        </CardHeader>
        <CardContent>
          <div v-if="!props.consents.length" class="text-sm text-gray-500">No consents yet.</div>
          <div v-else class="overflow-x-auto">
            <table class="w-full text-sm">
              <thead>
                <tr class="border-b">
                  <th class="text-left py-2 px-3">Title</th>
                  <th class="text-left py-2 px-3">Version</th>
                  <th class="text-left py-2 px-3">Signed At</th>
                  <th class="text-left py-2 px-3">Signature</th>
                  <th class="text-left py-2 px-3">PDF</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="c in props.consents" :key="c.id" class="border-b hover:bg-gray-50">
                  <td class="py-2 px-3">{{ c.title }}</td>
                  <td class="py-2 px-3">{{ c.template_version || '—' }}</td>
                  <td class="py-2 px-3">{{ new Date(c.signed_at).toLocaleString() }}</td>
                  <td class="py-2 px-3">
                    <div v-if="c.signature_path" class="flex items-center gap-2">
                      <img :src="'/' + c.signature_path" alt="Signature" class="h-10 border rounded bg-white" />
                    </div>
                    <span v-else class="text-gray-400">—</span>
                  </td>
                  <td class="py-2 px-3">
                    <a :href="route('patients.consents.pdf', [props.patient.id, (c as any).id])" class="text-blue-600 hover:underline">Download</a>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </CardContent>
      </Card>
    </div>
  </AppLayout>
</template>
