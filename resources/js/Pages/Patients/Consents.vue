<script setup lang="ts">
import { Head, useForm, usePage } from '@inertiajs/vue3'
import { computed, nextTick, onBeforeUnmount, onMounted, ref } from 'vue'
import AppLayout from '@/Layouts/AppLayout.vue'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/Components/ui/card'
import { Button } from '@/Components/ui/button'
import { Label } from '@/Components/ui/label'
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/Components/ui/select'
import { Alert, AlertDescription } from '@/Components/ui/alert'

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
const formError = ref<string | null>(null)
const renderPreview = (tpl: string | undefined) => {
  if (!tpl) return ''
  const today = new Date()
  const clinicName = (props.context_vars?.clinic_name) || (page?.props?.app?.name) || 'Victoria Dental Lounge'
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
let themeObserver: MutationObserver | null = null

const applyDrawingStyle = () => {
  if (!ctx || !canvasRef.value) return
  // Always use a dark ink so exported signatures remain visible on white PDFs
  ctx.strokeStyle = '#0b1220'
  ctx.lineWidth = 2.5
  ctx.lineCap = 'round'
  // Ensure the canvas has a white backdrop for visibility in dark mode UI
  canvasRef.value.style.backgroundColor = '#ffffff'
}

const resizeCanvas = () => {
  const c = canvasRef.value
  if (!c) return
  const rect = c.getBoundingClientRect()
  c.width = rect.width * window.devicePixelRatio
  c.height = rect.height * window.devicePixelRatio
  if (ctx) {
    ctx.scale(window.devicePixelRatio, window.devicePixelRatio)
    applyDrawingStyle()
  }
}

onMounted(async () => {
  const c = canvasRef.value
  if (!c) return
  ctx = c.getContext('2d')
  if (c) {
    c.style.touchAction = 'none'
  }
  await nextTick()
  resizeCanvas()
  applyDrawingStyle()
  themeObserver = new MutationObserver(() => applyDrawingStyle())
  themeObserver.observe(document.documentElement, { attributes: true, attributeFilter: ['class'] })
  window.addEventListener('resize', resizeCanvas)
})

onBeforeUnmount(() => {
  window.removeEventListener('resize', resizeCanvas)
  themeObserver?.disconnect()
})

const pos = (e: MouseEvent | TouchEvent | PointerEvent) => {
  const c = canvasRef.value!
  const rect = c.getBoundingClientRect()
  const clientX = 'touches' in e ? e.touches[0].clientX : 'clientX' in e ? e.clientX : 0
  const clientY = 'touches' in e ? e.touches[0].clientY : 'clientY' in e ? e.clientY : 0
  const x = clientX - rect.left
  const y = clientY - rect.top
  return { x, y }
}

const startDraw = (e: MouseEvent | TouchEvent | PointerEvent) => {
  drawing = true
  if ('preventDefault' in e) e.preventDefault()
  const p = pos(e)
  ctx?.beginPath()
  ctx?.moveTo(p.x, p.y)
  if ('pointerId' in e && canvasRef.value) {
    canvasRef.value.setPointerCapture(e.pointerId)
  }
}
const draw = (e: MouseEvent | TouchEvent | PointerEvent) => {
  if (!drawing) return
  if ('preventDefault' in e) e.preventDefault()
  const p = pos(e)
  ctx?.lineTo(p.x, p.y)
  ctx?.stroke()
}
const endDraw = (e?: MouseEvent | TouchEvent | PointerEvent) => {
  drawing = false
  if (e && 'pointerId' in e && canvasRef.value?.hasPointerCapture(e.pointerId)) {
    canvasRef.value.releasePointerCapture(e.pointerId)
  }
}
const clearSig = () => {
  const c = canvasRef.value
  if (!c || !ctx) return
  ctx.clearRect(0, 0, c.width, c.height)
}

const submit = () => {
  formError.value = null
  if (!form.template_id) {
    formError.value = 'Select a consent template before recording.'
    return
  }
  if (!form.signed_by_name.trim()) {
    formError.value = 'Enter the signer’s full name to continue.'
    return
  }
  // capture signature with solid background to ensure readability in light mode
  if (canvasRef.value) {
    const src = canvasRef.value
    const out = document.createElement('canvas')
    // Use same pixel dimensions to preserve resolution
    out.width = src.width
    out.height = src.height
    const octx = out.getContext('2d')
    if (octx) {
      // Fill with light background (white) for visibility on exports
      octx.fillStyle = '#ffffff'
      octx.fillRect(0, 0, out.width, out.height)
      // Draw the signature strokes on top
      octx.drawImage(src, 0, 0)
      form.signature_data = out.toDataURL('image/png')
    } else {
      form.signature_data = src.toDataURL('image/png')
    }
  }
  form.post(route('patients.consents.sign', props.patient.id), {
    preserveScroll: true,
    onSuccess: () => {
      formError.value = null
    }
  })
}
</script>

<template>
  <AppLayout :title="`Consents - ${props.patient.name}`">
    <Head :title="`Consents - ${props.patient.name}`" />

    <div class="container mx-auto px-4 py-8 space-y-6">
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Consents</h1>
          <p class="text-gray-500 dark:text-gray-400">{{ props.patient.name }}</p>
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

      <Card class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 shadow-sm">
        <CardHeader>
          <CardTitle class="text-gray-900 dark:text-white">Record New Consent</CardTitle>
          <CardDescription class="text-gray-600 dark:text-gray-400">Select a template and capture a signature name</CardDescription>
        </CardHeader>
        <CardContent>
          <Alert
            v-if="formError"
            variant="destructive"
            class="mb-4 bg-red-50 dark:bg-red-900/20 border-red-200 dark:border-red-800"
          >
            <i class="fas fa-exclamation-triangle text-red-600 dark:text-red-400 mr-2"></i>
            <AlertDescription class="text-red-800 dark:text-red-300">
              {{ formError }}
            </AlertDescription>
          </Alert>

          <form @submit.prevent="submit" class="grid gap-6 md:grid-cols-12">
            <div class="md:col-span-4 space-y-2">
              <Label class="text-gray-700 dark:text-gray-300">Template</Label>
              <Select v-model="(form.template_id as any)">
                <SelectTrigger class="w-full">
                  <SelectValue placeholder="Select template" />
                </SelectTrigger>
                <SelectContent class="max-h-64">
                  <SelectItem v-for="t in props.templates" :key="t.id" :value="t.id">{{ t.title }} (v{{ t.version }})</SelectItem>
                </SelectContent>
              </Select>
              <div v-if="selectedTemplate" class="mt-2 text-xs">
                <span
                  v-if="selectedTemplate.signature_required"
                  class="inline-block px-2 py-0.5 rounded bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-300"
                >Signature required</span>
                <span
                  v-else
                  class="inline-block px-2 py-0.5 rounded bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-300"
                >Signature optional</span>
              </div>
            </div>
            <div class="md:col-span-4 space-y-2">
              <Label class="text-gray-700 dark:text-gray-300">Signed By</Label>
              <input
                v-model="form.signed_by_name"
                class="w-full border border-gray-200 dark:border-gray-700 rounded-md p-2 bg-white dark:bg-gray-950 text-gray-900 dark:text-gray-100 placeholder:text-gray-400 dark:placeholder:text-gray-500 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                placeholder="Patient full name"
              />
            </div>
            <div class="md:col-span-4 flex flex-wrap gap-2 md:justify-end items-end">
              <Button type="submit" :disabled="form.processing" class="bg-blue-600 hover:bg-blue-700 text-white w-full md:w-auto">
                {{ form.processing ? 'Saving...' : 'Record Consent' }}
              </Button>
              <Button type="button" variant="outline" :disabled="!selectedTemplate" @click="openPreview" class="w-full md:w-auto">
                Preview
              </Button>
            </div>
            <div class="md:col-span-6 space-y-2">
              <Label class="text-gray-700 dark:text-gray-300">Template Preview</Label>
              <div class="whitespace-pre-wrap border border-gray-200 dark:border-gray-700 rounded-md p-3 bg-white dark:bg-gray-950 text-gray-800 dark:text-gray-200 min-h-[160px]">
                {{ renderPreview(selectedTemplate?.body) }}
              </div>
            </div>
            <div class="md:col-span-6 space-y-2">
              <Label class="text-gray-700 dark:text-gray-300">Signature</Label>
              <div class="border border-dashed border-gray-300 dark:border-gray-700 rounded-md bg-white dark:bg-gray-950 p-3">
                <canvas
                  ref="canvasRef"
                  class="w-full h-40 rounded-md transition-colors"
                  @mousedown="startDraw"
                  @mousemove="draw"
                  @mouseup="endDraw"
                  @mouseleave="endDraw"
                  @touchstart.prevent="startDraw"
                  @touchmove.prevent="draw"
                  @touchend="endDraw"
                ></canvas>
                <div class="mt-2 flex flex-wrap items-center justify-between gap-2 text-xs text-gray-500 dark:text-gray-400">
                  <span>Sign within the box using a mouse or touch device.</span>
                  <Button type="button" variant="outline" size="sm" @click="clearSig">Clear Signature</Button>
                </div>
              </div>
            </div>
          </form>
        </CardContent>
      </Card>

      <!-- Preview Modal -->
      <div v-if="showPreview" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50">
        <div class="bg-white dark:bg-gray-900 rounded-md shadow-xl max-w-2xl w-full p-4 border border-gray-200 dark:border-gray-800">
          <div class="flex items-center justify-between mb-2">
            <div class="text-lg font-semibold text-gray-900 dark:text-white">Consent Preview</div>
            <button class="p-1 text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200" @click="closePreview"><i class="fas fa-times"></i></button>
          </div>
          <div class="whitespace-pre-wrap border border-gray-200 dark:border-gray-700 rounded-md p-3 max-h-[60vh] overflow-auto bg-white dark:bg-gray-950 text-gray-800 dark:text-gray-200">
            {{ renderPreview(selectedTemplate?.body) }}
          </div>
          <div class="mt-3 flex justify-end gap-2">
            <Button variant="outline" @click="closePreview">Close</Button>
            <Button class="bg-blue-600 hover:bg-blue-700 dark:bg-blue-500 dark:hover:bg-blue-600" @click="printPreview">Print</Button>
          </div>
        </div>
      </div>

      <Card class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 shadow-sm">
        <CardHeader>
          <CardTitle class="text-gray-900 dark:text-white">Signed Consents</CardTitle>
          <CardDescription class="text-gray-600 dark:text-gray-400">Most recent first</CardDescription>
        </CardHeader>
        <CardContent>
          <div v-if="!props.consents.length" class="text-sm text-gray-500 dark:text-gray-400">No consents yet.</div>
          <div v-else class="overflow-x-auto">
            <table class="w-full text-sm">
              <thead>
                <tr class="border-b border-gray-200 dark:border-gray-800 text-gray-600 dark:text-gray-300">
                  <th class="text-left py-2 px-3">Title</th>
                  <th class="text-left py-2 px-3">Version</th>
                  <th class="text-left py-2 px-3">Signed At</th>
                  <th class="text-left py-2 px-3">Signature</th>
                  <th class="text-left py-2 px-3">PDF</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-gray-200 dark:divide-gray-800">
                <tr v-for="c in props.consents" :key="c.id" class="hover:bg-gray-50 dark:hover:bg-gray-800/60 transition-colors text-gray-800 dark:text-gray-200">
                  <td class="py-2 px-3">{{ c.title }}</td>
                  <td class="py-2 px-3">{{ c.template_version || '—' }}</td>
                  <td class="py-2 px-3">{{ new Date(c.signed_at).toLocaleString() }}</td>
                  <td class="py-2 px-3">
                    <div v-if="c.signature_path" class="flex items-center gap-2">
                      <img :src="'/' + c.signature_path" alt="Signature" class="h-10 border border-gray-200 dark:border-gray-700 rounded bg-white dark:bg-gray-950" />
                    </div>
                    <span v-else class="text-gray-400 dark:text-gray-500">—</span>
                  </td>
                  <td class="py-2 px-3">
                    <a :href="route('patients.consents.pdf', [props.patient.id, (c as any).id])" class="text-blue-600 dark:text-blue-400 hover:underline">Download</a>
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
