<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/Components/ui/card'
import { Button } from '@/Components/ui/button'
import { Label } from '@/Components/ui/label'
import { Input } from '@/Components/ui/input'
import { Checkbox } from '@/Components/ui/checkbox'
import { Alert, AlertDescription, AlertTitle } from '@/Components/ui/alert'
import { Badge } from '@/Components/ui/badge'
import { computed, nextTick, onMounted, ref, watch } from 'vue'
import { AlertCircle, Bold, Info, Italic, List as ListIcon, ListOrdered, Quote, Type, Underline } from 'lucide-vue-next'

interface Template { id: number; title: string; body: string; version: number; active: boolean; updated_at: string }

const props = defineProps<{ templates: Template[] }>()

const form = useForm({ title: '', body: '', active: true, signature_required: true as boolean })
const editorRef = ref<HTMLDivElement | null>(null)
const formError = ref<string | null>(null)

const toolbarActions = [
  { icon: Bold, cmd: 'bold', label: 'Bold' },
  { icon: Italic, cmd: 'italic', label: 'Italic' },
  { icon: Underline, cmd: 'underline', label: 'Underline' },
  { icon: ListIcon, cmd: 'insertUnorderedList', label: 'Bulleted list' },
  { icon: ListOrdered, cmd: 'insertOrderedList', label: 'Numbered list' },
  { icon: Quote, cmd: 'formatBlock', value: 'blockquote', label: 'Block quote' },
  { icon: Type, cmd: 'formatBlock', value: 'p', label: 'Paragraph' },
]

const mergeCurrentSelection = (callback: () => void) => {
  const selection = window.getSelection()
  if (!selection || selection.rangeCount === 0) {
    const range = document.createRange()
    const editor = editorRef.value
    if (editor && editor.firstChild) {
      range.selectNodeContents(editor)
      range.collapse(false)
      selection?.removeAllRanges()
      selection?.addRange(range)
    }
  }
  callback()
}

const exec = (cmd: string, value?: string) => {
  mergeCurrentSelection(() => {
    document.execCommand(cmd, false, value)
    if (editorRef.value) form.body = editorRef.value.innerHTML
  })
}

const availableVariables = [
  { key: '{{patient_name}}', label: 'Patient Name' },
  { key: '{{date}}', label: 'Current Date' },
  { key: '{{time}}', label: 'Current Time' },
  { key: '{{clinic_name}}', label: 'Clinic Name' },
]

const insertVariable = (token: string) => {
  mergeCurrentSelection(() => {
    document.execCommand('insertText', false, token)
    if (editorRef.value) form.body = editorRef.value.innerHTML
  })
}

const syncEditorFromForm = () => {
  if (editorRef.value && editorRef.value.innerHTML !== form.body) {
    editorRef.value.innerHTML = form.body
  }
}

onMounted(async () => {
  await nextTick()
  syncEditorFromForm()
})

watch(() => form.body, () => syncEditorFromForm())

const sortedTemplates = computed(() => {
  return [...props.templates].sort((a, b) => new Date(b.updated_at).getTime() - new Date(a.updated_at).getTime())
})

const renderPreview = (tpl: string) => {
  const today = new Date()
  const vars: Record<string,string> = {
    patient_name: 'John Doe',
    date: today.toLocaleDateString(),
    time: today.toLocaleTimeString(),
    clinic_name: 'Your Clinic',
  }
  return tpl.replace(/\{\{\s*(\w+)\s*\}\}/g, (_, key) => vars[key] ?? '')
}

const submit = () => {
  formError.value = null
  if (!form.title.trim()) {
    formError.value = 'Provide a title for this consent template.'
    return
  }
  const stripped = form.body.replace(/<[^>]*>/g, '').trim()
  if (!stripped) {
    formError.value = 'Add template content before saving.'
    return
  }
  form.post(route('consents.templates.store'), {
    preserveScroll: true,
    onSuccess: () => {
      formError.value = null
      form.reset('title', 'body', 'active', 'signature_required')
      nextTick(() => {
        if (editorRef.value) editorRef.value.innerHTML = ''
      })
    },
  })
}
</script>

<template>
  <AppLayout title="Consent Templates">
    <Head title="Consent Templates" />

    <div class="container mx-auto px-4 py-8 space-y-6">
      <div class="flex flex-col gap-2">
        <h1 class="text-3xl font-semibold text-gray-900 dark:text-white">Consent Templates</h1>
        <p class="text-sm text-gray-500 dark:text-gray-400">Create reusable consent language and keep previous versions by saving with the same title.</p>
      </div>

      <Alert class="border-blue-200 bg-blue-50/80 dark:border-blue-800 dark:bg-blue-900/20">
        <Info class="mr-3 h-5 w-5 text-blue-600 dark:text-blue-400" />
        <div>
          <AlertTitle class="text-blue-800 dark:text-blue-300">Tip: Personalise your templates</AlertTitle>
          <AlertDescription class="text-blue-700 dark:text-blue-200">
            Use the dynamic variables below to pull in patient and clinic information automatically when a consent is recorded.
          </AlertDescription>
        </div>
      </Alert>

      <Card class="border border-gray-200/80 dark:border-slate-800">
        <CardHeader class="space-y-1">
          <CardTitle class="text-xl font-semibold text-gray-900 dark:text-white">New / Update Template</CardTitle>
          <CardDescription class="text-sm text-gray-600 dark:text-gray-400">Saving with an existing title creates a new version while keeping history.</CardDescription>
        </CardHeader>
        <CardContent>
          <form @submit.prevent="submit" class="space-y-6">
            <div class="grid gap-4 md:grid-cols-5">
              <div class="md:col-span-2 space-y-2">
                <Label for="template_title" class="text-sm font-medium text-gray-700 dark:text-gray-300">Title</Label>
                <Input
                  id="template_title"
                  v-model="form.title"
                  placeholder="e.g. General Treatment Consent"
                  class="bg-white dark:bg-slate-950"
                />
              </div>
              <div class="md:col-span-3 space-y-2">
                <Label class="text-sm font-medium text-gray-700 dark:text-gray-300">Quick variables</Label>
                <div class="flex flex-wrap gap-2">
                  <Button
                    v-for="token in availableVariables"
                    :key="token.key"
                    type="button"
                    variant="outline"
                    size="sm"
                    class="border-dashed"
                    @click="insertVariable(token.key)"
                  >
                    <i class="fas fa-plus-circle text-sm mr-1"></i>
                    {{ token.label }}
                  </Button>
                </div>
              </div>
            </div>

            <div class="space-y-2">
              <div class="flex items-center justify-between">
                <Label class="text-sm font-medium text-gray-700 dark:text-gray-300">Body</Label>
                <div class="flex items-center gap-2 text-xs text-gray-500 dark:text-gray-400">
                  <AlertCircle class="h-4 w-4" />
                  Use the toolbar to format your content.
                </div>
              </div>
              <div class="flex flex-wrap gap-2">
                <Button
                  v-for="action in toolbarActions"
                  :key="action.label"
                  type="button"
                  variant="outline"
                  size="sm"
                  class="bg-white/70 dark:bg-slate-900/60"
                  :title="action.label"
                  @click="exec(action.cmd, action.value)">
                  <component :is="action.icon" class="h-4 w-4" />
                </Button>
              </div>
              <div
                ref="editorRef"
                class="rounded-md border border-dashed border-gray-300 bg-white/80 p-4 text-sm leading-relaxed text-gray-800 shadow-inner focus-within:ring-2 focus-within:ring-blue-500 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-100"
                contenteditable
                role="textbox"
                aria-multiline="true"
                @input="form.body = (editorRef as any)?.innerHTML || ''"
              ></div>
            </div>

            <div class="flex flex-wrap items-center justify-between gap-4 border-t border-gray-200 pt-4 dark:border-slate-800">
              <div class="flex flex-wrap items-center gap-6 text-sm">
                <label class="flex items-center gap-2 text-gray-700 dark:text-gray-300">
                  <Checkbox v-model:checked="(form.active as any)" />
                  <span>Active</span>
                </label>
                <label class="flex items-center gap-2 text-gray-700 dark:text-gray-300">
                  <Checkbox v-model:checked="(form.signature_required as any)" />
                  <span>Signature required</span>
                </label>
              </div>
              <Button type="submit" :disabled="form.processing" class="gap-2 bg-blue-600 hover:bg-blue-700">
                <i v-if="form.processing" class="fas fa-spinner fa-spin"></i>
                {{ form.processing ? 'Saving...' : 'Save Template' }}
              </Button>
            </div>

            <div class="flex flex-wrap gap-2 text-xs text-gray-500 dark:text-gray-400">
              <span>Available variables:</span>
              <span v-for="token in availableVariables" :key="token.key" class="font-mono bg-gray-100 px-2 py-1 rounded dark:bg-slate-900/60">{{ token.key }}</span>
            </div>
          </form>
        </CardContent>
      </Card>

      <Card class="border border-gray-200/80 dark:border-slate-800">
        <CardHeader class="space-y-1">
          <CardTitle class="text-lg font-semibold text-gray-900 dark:text-white">Preview</CardTitle>
          <CardDescription class="text-sm text-gray-600 dark:text-gray-400">Rendered with example values for dynamic variables.</CardDescription>
        </CardHeader>
        <CardContent>
          <div class="prose max-w-none rounded-md border border-gray-200 bg-white/80 p-5 text-gray-800 shadow-sm dark:prose-invert dark:border-slate-800 dark:bg-slate-950 dark:text-slate-100" v-html="renderPreview(form.body)"></div>
        </CardContent>
      </Card>

      <Card class="border border-gray-200/80 dark:border-slate-800">
        <CardHeader class="space-y-1">
          <CardTitle class="text-lg font-semibold text-gray-900 dark:text-white">Existing Templates</CardTitle>
          <CardDescription class="text-sm text-gray-600 dark:text-gray-400">Most recently updated first</CardDescription>
        </CardHeader>
        <CardContent>
          <div class="overflow-x-auto">
            <table class="w-full text-sm text-gray-800 dark:text-slate-200">
              <thead class="bg-gray-50 dark:bg-slate-900/60">
                <tr class="border-b border-gray-200 text-gray-600 dark:border-slate-800 dark:text-slate-300">
                  <th class="py-3 px-3 text-left font-medium">Title</th>
                  <th class="py-3 px-3 text-left font-medium">Version</th>
                  <th class="py-3 px-3 text-left font-medium">Status</th>
                  <th class="py-3 px-3 text-left font-medium">Signature</th>
                  <th class="py-3 px-3 text-left font-medium">Updated</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-gray-200 dark:divide-slate-800">
                <tr v-for="t in sortedTemplates" :key="t.id" class="transition-colors hover:bg-gray-50 dark:hover:bg-slate-900/40">
                  <td class="py-3 px-3 font-medium">{{ t.title }}</td>
                  <td class="py-3 px-3 font-mono text-xs uppercase">v{{ t.version }}</td>
                  <td class="py-3 px-3">
                    <Badge :variant="t.active ? 'default' : 'outline'" class="px-2 py-0.5 text-xs">
                      <i :class="['fas', t.active ? 'fa-circle-check text-green-500' : 'fa-ban text-red-500', 'mr-1']"></i>
                      {{ t.active ? 'Active' : 'Inactive' }}
                    </Badge>
                  </td>
                  <td class="py-3 px-3">
                    <Badge class="px-2 py-0.5 text-xs" :variant="t.active ? 'default' : 'secondary'">
                      <i class="fas fa-signature mr-1"></i>
                      {{ t.active ? 'Required' : 'Optional' }}
                    </Badge>
                  </td>
                  <td class="py-3 px-3 text-sm text-gray-500 dark:text-gray-400">{{ new Date(t.updated_at).toLocaleString() }}</td>
                </tr>
                <tr v-if="!sortedTemplates.length">
                  <td colspan="5" class="py-6 text-center text-sm text-gray-500 dark:text-gray-400">No templates saved yet.</td>
                </tr>
              </tbody>
            </table>
          </div>
        </CardContent>
      </Card>
    </div>
  </AppLayout>
</template>
