<script setup lang="ts">
import { ref } from 'vue'
import { Head, useForm, router } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/Components/ui/card'
import { Button } from '@/Components/ui/button'
import { Label } from '@/Components/ui/label'
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuTrigger } from '@/Components/ui/dropdown-menu'
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/Components/ui/dialog'
import { MoreVertical } from 'lucide-vue-next'

interface Patient { id: number; name: string }
interface User { id: number; name: string }
interface Note {
  id: number
  subjective?: string | null
  objective?: string | null
  assessment?: string | null
  plan?: string | null
  status: 'draft' | 'signed'
  author?: User
  signer?: User | null
  created_at: string
  signed_at?: string | null
}

interface NoteTpl { id: number; name: string; subjective?: string|null; objective?: string|null; assessment?: string|null; plan?: string|null }

const props = defineProps<{ patient: Patient; notes: Note[]; templates: NoteTpl[] }>()

const form = useForm({
  subjective: '',
  objective: '',
  assessment: '',
  plan: '',
})

// Global default snippets
const subjectiveSnippets = [
  'Patient reports intermittent pain in lower right molar for 3 days.',
  'No known allergies. No current medications.',
  'Patient presents for routine check-up and cleaning.'
]
const objectiveSnippets = [
  'Vital signs within normal limits. No swelling or trismus.',
  'Caries noted on #46 occlusal. Gingiva mildly inflamed.',
  'Probing depths WNL. Good oral hygiene.'
]
const assessmentSnippets = [
  'Dental caries on #46. Mild gingivitis.',
  'Asymptomatic. Routine oral health maintenance.'
]
const planSnippets = [
  'Restoration for #46 with composite. OHI provided. Recall in 6 months.',
  'Prophylaxis performed. Fluoride varnish applied. Recall in 6 months.'
]

const applyTemplate = (tplId: number|string) => {
  const id = Number(tplId)
  const tpl = props.templates.find(t => t.id === id)
  if (!tpl) return
  form.subjective = tpl.subjective
  form.objective = tpl.objective
  form.assessment = tpl.assessment
  form.plan = tpl.plan
}

const addSnippet = (field: 'subjective'|'objective'|'assessment'|'plan', text: string) => {
  const current = (form as any)[field] as string
  ;(form as any)[field] = current ? (current.trimEnd() + '\n' + text) : text
}

const submit = () => {
  form.post(route('patients.notes.store', props.patient.id), {
    preserveScroll: true,
    onSuccess: () => {
      form.reset()
      router.reload()
    }
  })
}

const signNote = (noteId: number) => {
  router.post(route('patients.notes.sign', [props.patient.id, noteId]), {}, { preserveScroll: true })
}

const isDeleteOpen = ref(false)
const deletingNote = ref<Note | null>(null)
const openDelete = (note: Note) => { deletingNote.value = note; isDeleteOpen.value = true }
const confirmDelete = () => {
  if (!deletingNote.value) return
  router.delete(route('patients.notes.destroy', [props.patient.id, deletingNote.value.id]), {
    preserveScroll: true,
    onSuccess: () => { isDeleteOpen.value = false; deletingNote.value = null; router.reload() }
  })
}
</script>

<template>
  <AppLayout :title="`Clinical Notes - ${props.patient.name}`">
    <Head :title="`Clinical Notes - ${props.patient.name}`" />

    <div class="container mx-auto px-4 py-8 space-y-6">
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Clinical Notes</h1>
          <p class="text-gray-500 dark:text-gray-400">{{ props.patient.name }}</p>
        </div>
        <Button variant="outline" as-child>
          <a :href="route('patients.show', props.patient.id)">Back to Patient</a>
        </Button>
      </div>

      <Card class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 shadow-sm">
        <CardHeader>
          <CardTitle class="text-gray-900 dark:text-white">New Note (SOAP)</CardTitle>
          <CardDescription class="text-gray-600 dark:text-gray-400">Enter clinical note sections. You can sign later.</CardDescription>
        </CardHeader>
        <CardContent>
          <form @submit.prevent="submit" class="grid md:grid-cols-2 gap-4">
            <div class="md:col-span-2 flex items-end gap-3">
              <div>
                <Label class="text-gray-700 dark:text-gray-300">Templates</Label>
                <select
                  class="border border-gray-200 dark:border-gray-700 rounded-md p-2 bg-white dark:bg-gray-950 text-gray-900 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                  @change="(e:any)=> applyTemplate(e.target.value)"
                >
                  <option value="">Select template…</option>
                  <option v-for="t in props.templates" :key="t.id" :value="t.id">{{ t.name }}</option>
                </select>
              </div>
            </div>
            <div>
              <Label class="text-gray-700 dark:text-gray-300">Subjective</Label>
              <textarea
                v-model="form.subjective"
                class="w-full border border-gray-200 dark:border-gray-700 rounded-md p-2 min-h-[100px] bg-white dark:bg-gray-950 text-gray-900 dark:text-gray-100 placeholder:text-gray-400 dark:placeholder:text-gray-500 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                placeholder="Patient-reported symptoms, history"
              ></textarea>
              <div class="flex flex-wrap gap-2 mt-2">
                <Button v-for="s in subjectiveSnippets" :key="s" type="button" size="sm" variant="outline" @click="addSnippet('subjective', s)">{{ s }}</Button>
              </div>
            </div>
            <div>
              <Label class="text-gray-700 dark:text-gray-300">Objective</Label>
              <textarea
                v-model="form.objective"
                class="w-full border border-gray-200 dark:border-gray-700 rounded-md p-2 min-h-[100px] bg-white dark:bg-gray-950 text-gray-900 dark:text-gray-100 placeholder:text-gray-400 dark:placeholder:text-gray-500 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                placeholder="Observations, vitals, exam findings"
              ></textarea>
              <div class="flex flex-wrap gap-2 mt-2">
                <Button v-for="s in objectiveSnippets" :key="s" type="button" size="sm" variant="outline" @click="addSnippet('objective', s)">{{ s }}</Button>
              </div>
            </div>
            <div>
              <Label class="text-gray-700 dark:text-gray-300">Assessment</Label>
              <textarea
                v-model="form.assessment"
                class="w-full border border-gray-200 dark:border-gray-700 rounded-md p-2 min-h-[100px] bg-white dark:bg-gray-950 text-gray-900 dark:text-gray-100 placeholder:text-gray-400 dark:placeholder:text-gray-500 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                placeholder="Diagnosis/impression"
              ></textarea>
              <div class="flex flex-wrap gap-2 mt-2">
                <Button v-for="s in assessmentSnippets" :key="s" type="button" size="sm" variant="outline" @click="addSnippet('assessment', s)">{{ s }}</Button>
              </div>
            </div>
            <div>
              <Label class="text-gray-700 dark:text-gray-300">Plan</Label>
              <textarea
                v-model="form.plan"
                class="w-full border border-gray-200 dark:border-gray-700 rounded-md p-2 min-h-[100px] bg-white dark:bg-gray-950 text-gray-900 dark:text-gray-100 placeholder:text-gray-400 dark:placeholder:text-gray-500 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                placeholder="Treatment plan, follow-up"
              ></textarea>
              <div class="flex flex-wrap gap-2 mt-2">
                <Button v-for="s in planSnippets" :key="s" type="button" size="sm" variant="outline" @click="addSnippet('plan', s)">{{ s }}</Button>
              </div>
            </div>
            <div class="md:col-span-2 flex justify-end">
              <Button type="submit" :disabled="form.processing" class="bg-blue-600 hover:bg-blue-700 dark:bg-blue-500 dark:hover:bg-blue-600">Save Note</Button>
            </div>
          </form>
        </CardContent>
      </Card>

      <!-- Delete Note Dialog -->
      <Dialog :open="isDeleteOpen" @update:open="(v:boolean) => isDeleteOpen = v">
        <DialogContent class="max-w-md">
          <DialogHeader>
            <DialogTitle class="text-xl font-semibold">Delete Note</DialogTitle>
            <DialogDescription class="text-gray-600 dark:text-gray-400">
              This action cannot be undone. This will permanently delete the clinical note.
            </DialogDescription>
          </DialogHeader>
          <div class="py-2 text-sm">
            Are you sure you want to delete this note for <span class="font-medium">{{ props.patient.name }}</span>?
          </div>
          <DialogFooter class="gap-2">
            <Button type="button" variant="outline" @click="isDeleteOpen = false">Cancel</Button>
            <Button type="button" variant="destructive" @click="confirmDelete">
              <i class="fas fa-trash mr-2"></i>
              Delete
            </Button>
          </DialogFooter>
        </DialogContent>
      </Dialog>

      <Card class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 shadow-sm">
        <CardHeader>
          <CardTitle class="text-gray-900 dark:text-white">Existing Notes</CardTitle>
          <CardDescription class="text-gray-600 dark:text-gray-400">Latest first</CardDescription>
        </CardHeader>
        <CardContent>
          <div v-if="!props.notes.length" class="text-sm text-gray-500 dark:text-gray-400">No notes yet.</div>
          <div v-else class="space-y-4">
            <div v-for="n in props.notes" :key="n.id" class="border border-gray-200 dark:border-gray-800 rounded-md p-3 bg-white dark:bg-gray-950">
              <div class="flex items-center justify-between">
                <div class="text-sm text-gray-600 dark:text-gray-400">
                  <span class="mr-2">Created: {{ new Date(n.created_at).toLocaleString() }}</span>
                  <span v-if="n.status === 'signed'"> • Signed: {{ n.signed_at ? new Date(n.signed_at).toLocaleString() : '' }}</span>
                </div>
                <div class="flex items-center gap-2">
                  <span v-if="n.status === 'signed'" class="px-2 py-1 text-xs rounded bg-green-100 text-green-700 dark:bg-green-900/40 dark:text-green-300">Signed</span>
                  <DropdownMenu>
                    <DropdownMenuTrigger as-child>
                      <Button variant="ghost" size="sm" class="h-8 w-8 p-0">
                        <MoreVertical class="h-4 w-4" />
                      </Button>
                    </DropdownMenuTrigger>
                    <DropdownMenuContent align="end" class="w-48">
                      <DropdownMenuItem as-child>
                        <a :href="route('patients.notes.pdf', [props.patient.id, n.id])" target="_blank" class="w-full flex items-center">
                          <i class="fas fa-file-pdf mr-2 w-4 text-center"></i>
                          <span>Download PDF</span>
                        </a>
                      </DropdownMenuItem>
                      <DropdownMenuItem v-if="n.status !== 'signed'" class="cursor-pointer" @click="signNote(n.id)">
                        <i class="fas fa-pen-nib mr-2 w-4 text-center"></i>
                        <span>Sign</span>
                      </DropdownMenuItem>
                      <DropdownMenuItem class="cursor-pointer text-red-600 focus:text-red-600 dark:text-red-400 dark:focus:text-red-400" @click="openDelete(n)">
                        <i class="fas fa-trash mr-2 w-4 text-center"></i>
                        <span>Delete</span>
                      </DropdownMenuItem>
                    </DropdownMenuContent>
                  </DropdownMenu>
                </div>
              </div>
              <div class="grid md:grid-cols-2 gap-3 mt-3 text-sm text-gray-800 dark:text-gray-200">
                <div>
                  <div class="font-semibold">Subjective</div>
                  <div class="whitespace-pre-wrap">{{ n.subjective || '—' }}</div>
                </div>
                <div>
                  <div class="font-semibold">Objective</div>
                  <div class="whitespace-pre-wrap">{{ n.objective || '—' }}</div>
                </div>
                <div>
                  <div class="font-semibold">Assessment</div>
                  <div class="whitespace-pre-wrap">{{ n.assessment || '—' }}</div>
                </div>
                <div>
                  <div class="font-semibold">Plan</div>
                  <div class="whitespace-pre-wrap">{{ n.plan || '—' }}</div>
                </div>
              </div>
            </div>
          </div>
        </CardContent>
      </Card>
    </div>
  </AppLayout>
</template>
