<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/Components/ui/card'
import { Button } from '@/Components/ui/button'
import { Label } from '@/Components/ui/label'
import { ref } from 'vue'

interface Template { id: number; title: string; body: string; version: number; active: boolean; updated_at: string }

const props = defineProps<{ templates: Template[] }>()

const form = useForm({ title: '', body: '', active: true, signature_required: true as boolean })
const editorRef = ref<HTMLDivElement | null>(null)

const exec = (cmd: string, value?: string) => {
  document.execCommand(cmd, false, value)
  // sync back
  if (editorRef.value) form.body = editorRef.value.innerHTML
}

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
  if (!form.title.trim()) return alert('Title required')
  if (!form.body.trim()) return alert('Body required')
  form.post(route('consents.templates.store'), { preserveScroll: true })
}
</script>

<template>
  <AppLayout title="Consent Templates">
    <Head title="Consent Templates" />

    <div class="container mx-auto px-4 py-8 space-y-6">
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-2xl font-bold">Consent Templates</h1>
          <p class="text-gray-500">Create a new version by saving with the same title</p>
        </div>
      </div>

      <Card>
        <CardHeader>
          <CardTitle>New / Update Template</CardTitle>
          <CardDescription>Saving with an existing title creates a newer version</CardDescription>
        </CardHeader>
        <CardContent>
          <form @submit.prevent="submit" class="space-y-3">
            <div>
              <Label>Title</Label>
              <input v-model="form.title" class="w-full border rounded-md p-2 bg-white text-gray-800 dark:bg-slate-900 dark:text-slate-200 dark:border-slate-700" placeholder="e.g., General Treatment Consent" />
            </div>
            <div>
              <Label>Body</Label>
              <div class="flex items-center gap-2 mb-2 text-sm">
                <Button type="button" variant="outline" size="sm" @click="exec('bold')"><i class="fas fa-bold"></i></Button>
                <Button type="button" variant="outline" size="sm" @click="exec('italic')"><i class="fas fa-italic"></i></Button>
                <Button type="button" variant="outline" size="sm" @click="exec('underline')"><i class="fas fa-underline"></i></Button>
                <Button type="button" variant="outline" size="sm" @click="exec('insertUnorderedList')"><i class="fas fa-list-ul"></i></Button>
                <Button type="button" variant="outline" size="sm" @click="exec('insertOrderedList')"><i class="fas fa-list-ol"></i></Button>
              </div>
              <div
                ref="editorRef"
                class="w-full border rounded-md p-2 min-h-[200px] bg-white text-gray-800 dark:bg-slate-900 dark:text-slate-200 dark:border-slate-700"
                contenteditable
                @input="form.body = (editorRef as any)?.innerHTML || ''"
              >
              </div>
            </div>
            <div class="flex items-center justify-between">
              <div class="flex items-center gap-6">
                <label class="text-sm flex items-center gap-2">
                  <input type="checkbox" v-model="(form.active as any)" /> Active
                </label>
                <label class="text-sm flex items-center gap-2">
                  <input type="checkbox" v-model="(form.signature_required as any)" /> Signature required
                </label>
              </div>
              <Button type="submit" :disabled="form.processing" class="bg-blue-600 hover:bg-blue-700">Save Template</Button>
            </div>
            <div class="text-xs text-gray-500 dark:text-slate-400">Variables: {{ '{' }}{{ '{' }}patient_name{{ '}' }}{{ '}' }}, {{ '{' }}{{ '{' }}date{{ '}' }}{{ '}' }}, {{ '{' }}{{ '{' }}time{{ '}' }}{{ '}' }}, {{ '{' }}{{ '{' }}clinic_name{{ '}' }}{{ '}' }}</div>
          </form>
        </CardContent>
      </Card>

      <Card>
        <CardHeader>
          <CardTitle>Preview</CardTitle>
          <CardDescription>Example preview with sample variables</CardDescription>
        </CardHeader>
        <CardContent>
          <div class="prose dark:prose-invert max-w-none border rounded-md p-4 bg-white text-gray-800 dark:bg-slate-900 dark:text-slate-200 dark:border-slate-700" v-html="renderPreview(form.body)"></div>
        </CardContent>
      </Card>

      <Card>
        <CardHeader>
          <CardTitle>Existing Templates</CardTitle>
          <CardDescription>Latest updates first</CardDescription>
        </CardHeader>
        <CardContent>
          <div class="overflow-x-auto">
            <table class="w-full text-sm text-gray-800 dark:text-slate-200">
              <thead class="bg-gray-50 dark:bg-slate-800">
                <tr class="border-b border-gray-200 dark:border-slate-700 text-gray-600 dark:text-slate-300">
                  <th class="text-left py-2 px-3">Title</th>
                  <th class="text-left py-2 px-3">Version</th>
                  <th class="text-left py-2 px-3">Active</th>
                  <th class="text-left py-2 px-3">Updated</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-gray-200 dark:divide-slate-700">
                <tr v-for="t in props.templates" :key="t.id" class="hover:bg-gray-50 dark:hover:bg-slate-800/60">
                  <td class="py-2 px-3">{{ t.title }}</td>
                  <td class="py-2 px-3">v{{ t.version }}</td>
                  <td class="py-2 px-3">{{ t.active ? 'Yes' : 'No' }}</td>
                  <td class="py-2 px-3">{{ new Date(t.updated_at).toLocaleString() }}</td>
                </tr>
              </tbody>
            </table>
          </div>
        </CardContent>
      </Card>
    </div>
  </AppLayout>
</template>
