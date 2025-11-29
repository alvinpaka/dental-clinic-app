<script setup lang="ts">
import { Head, router, useForm } from '@inertiajs/vue3'
import { reactive } from 'vue'
import AppLayout from '@/Layouts/AppLayout.vue'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/Components/ui/card'
import { Button } from '@/Components/ui/button'
import { Label } from '@/Components/ui/label'
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuTrigger } from '@/Components/ui/dropdown-menu'
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/Components/ui/dialog'
import { MoreVertical } from 'lucide-vue-next'

interface Tpl { id: number; name: string; subjective?: string|null; objective?: string|null; assessment?: string|null; plan?: string|null; active: boolean }

const props = defineProps<{ filters: any; templates: any }>()

const qForm = useForm({ q: props.filters?.q || '', active: props.filters?.active ?? '' })
const applyFilters = () => {
  router.get(route('admin.clinical-note-templates.index'), qForm.data(), { preserveState: true, preserveScroll: true })
}

const createForm = useForm({ name: '', subjective: '', objective: '', assessment: '', plan: '', active: true as boolean })
const createTpl = () => {
  if (!createForm.name.trim()) return alert('Name is required')
  createForm.post(route('admin.clinical-note-templates.store'), {
    preserveScroll: true,
    onSuccess: () => createForm.reset('name','subjective','objective','assessment','plan')
  })
}

const editing = reactive<Record<number, boolean>>({})
const editForms = reactive<Record<number, any>>({})
const startEdit = (t: Tpl) => {
  editing[t.id] = true
  editForms[t.id] = useForm({ name: t.name, subjective: t.subjective || '', objective: t.objective || '', assessment: t.assessment || '', plan: t.plan || '', active: t.active })
}
const cancelEdit = (id: number) => { editing[id] = false }
const saveEdit = (id: number) => {
  const f = editForms[id]
  if (!f) return
  f.post(route('admin.clinical-note-templates.update', id), { preserveScroll: true, onSuccess: () => { editing[id] = false } })
}

// Delete confirmation state
const isDeleteOpen = reactive<{ open: boolean }>({ open: false })
const deletingTpl = reactive<{ id: number|null; name: string }>({ id: null, name: '' })
const openDelete = (t: Tpl) => { deletingTpl.id = t.id; deletingTpl.name = t.name; isDeleteOpen.open = true }
const confirmDelete = () => {
  if (!deletingTpl.id) return
  router.delete(route('admin.clinical-note-templates.destroy', deletingTpl.id), {
    preserveScroll: true,
    onSuccess: () => { isDeleteOpen.open = false; deletingTpl.id = null as any; deletingTpl.name = '' },
  })
}
const toggleActive = (id: number) => {
  router.post(route('admin.clinical-note-templates.toggle', id), {}, { preserveScroll: true })
}
const removeTpl = (id: number) => {
  if (!confirm('Delete this template?')) return
  router.delete(route('admin.clinical-note-templates.destroy', id), { preserveScroll: true })
}
</script>

<template>
  <AppLayout title="Clinical Note Templates">
    <Head title="Clinical Note Templates" />

    <div class="container mx-auto px-4 py-8 space-y-6">
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-2xl font-bold">Clinical Note Templates</h1>
          <p class="text-gray-500 dark:text-slate-400">Manage templates used to prefill SOAP notes</p>
        </div>
      </div>

      <Card>
        <CardHeader>
          <CardTitle>Filters</CardTitle>
          <CardDescription>Search and filter templates</CardDescription>
        </CardHeader>
        <CardContent>
          <div class="grid md:grid-cols-4 gap-3 items-end">
            <div class="md:col-span-2">
              <Label>Search</Label>
              <input v-model="qForm.q" class="w-full border rounded-md p-2 bg-white text-gray-800 dark:bg-slate-900 dark:text-slate-200 dark:border-slate-700" placeholder="Search by name" />
            </div>
            <div>
              <Label>Status</Label>
              <select v-model="(qForm.active as any)" class="w-full border rounded-md p-2 bg-white text-gray-800 dark:bg-slate-900 dark:text-slate-200 dark:border-slate-700">
                <option value="">All</option>
                <option value="true">Active</option>
                <option value="false">Inactive</option>
              </select>
            </div>
            <div class="flex gap-2">
              <Button @click="applyFilters" class="bg-blue-600 hover:bg-blue-700 text-white">Apply</Button>
            </div>
          </div>
        </CardContent>
      </Card>

      <Card>
        <CardHeader>
          <CardTitle>New Template</CardTitle>
          <CardDescription>Create a new SOAP template</CardDescription>
        </CardHeader>
        <CardContent>
          <form @submit.prevent="createTpl" class="grid md:grid-cols-2 gap-3">
            <div class="md:col-span-2">
              <Label>Name</Label>
              <input v-model="createForm.name" class="w-full border rounded-md p-2 bg-white text-gray-800 dark:bg-slate-900 dark:text-slate-200 dark:border-slate-700" placeholder="e.g., Routine Checkup" />
            </div>
            <div>
              <Label>Chief Complaint</Label>
              <textarea v-model="createForm.subjective" class="w-full border rounded-md p-2 min-h-[90px] bg-white text-gray-800 dark:bg-slate-900 dark:text-slate-200 dark:border-slate-700" />
            </div>
            <div>
              <Label>Diagnosis</Label>
              <textarea v-model="createForm.objective" class="w-full border rounded-md p-2 min-h-[90px] bg-white text-gray-800 dark:bg-slate-900 dark:text-slate-200 dark:border-slate-700" />
            </div>
            <div>
              <Label>Oral Examination</Label>
              <textarea v-model="createForm.assessment" class="w-full border rounded-md p-2 min-h-[90px] bg-white text-gray-800 dark:bg-slate-900 dark:text-slate-200 dark:border-slate-700" />
            </div>
            <div>
              <Label>Plan</Label>
              <textarea v-model="createForm.plan" class="w-full border rounded-md p-2 min-h-[90px] bg-white text-gray-800 dark:bg-slate-900 dark:text-slate-200 dark:border-slate-700" />
            </div>
            <div class="md:col-span-2 flex items-center justify-between">
              <label class="text-sm flex items-center gap-2">
                <input type="checkbox" v-model="(createForm.active as any)" /> Active
              </label>
              <Button type="submit" :disabled="createForm.processing" class="bg-blue-600 hover:bg-blue-700 text-white">Create</Button>
            </div>
          </form>
        </CardContent>
      </Card>

      <Card>
        <CardHeader>
          <CardTitle>Templates</CardTitle>
        </CardHeader>
        <CardContent>
          <div class="overflow-x-auto">
            <table class="w-full text-sm text-gray-800 dark:text-slate-200">
              <thead class="bg-gray-50 dark:bg-slate-800">
                <tr class="border-b border-gray-200 dark:border-slate-700 text-gray-600 dark:text-slate-300">
                  <th class="text-left py-2 px-3">Name</th>
                  <th class="text-left py-2 px-3">Active</th>
                  <th class="text-left py-2 px-3">Actions</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-gray-200 dark:divide-slate-700">
                <tr v-for="t in props.templates.data" :key="t.id" class="hover:bg-gray-50 dark:hover:bg-slate-800/60">
                  <td class="py-2 px-3">
                    <div v-if="!editing[t.id]">
                      <div class="font-medium">{{ t.name }}</div>
                      <div class="grid md:grid-cols-2 gap-2 mt-1 text-xs">
                        <div><span class="font-semibold">S:</span> <span class="whitespace-pre-wrap">{{ t.subjective || '—' }}</span></div>
                        <div><span class="font-semibold">O:</span> <span class="whitespace-pre-wrap">{{ t.objective || '—' }}</span></div>
                        <div><span class="font-semibold">A:</span> <span class="whitespace-pre-wrap">{{ t.assessment || '—' }}</span></div>
                        <div><span class="font-semibold">P:</span> <span class="whitespace-pre-wrap">{{ t.plan || '—' }}</span></div>
                      </div>
                    </div>
                    <div v-else>
                      <div class="grid md:grid-cols-2 gap-2">
                        <div class="md:col-span-2">
                          <Label>Name</Label>
                          <input v-model="editForms[t.id].name" class="w-full border rounded-md p-2 bg-white text-gray-800 dark:bg-slate-900 dark:text-slate-200 dark:border-slate-700" />
                        </div>
                        <div>
                          <Label>Chief Complaint</Label>
                          <textarea v-model="editForms[t.id].subjective" class="w-full border rounded-md p-2 min-h-[80px] bg-white text-gray-800 dark:bg-slate-900 dark:text-slate-200 dark:border-slate-700" />
                        </div>
                        <div>
                          <Label>Diagnosis</Label>
                          <textarea v-model="editForms[t.id].objective" class="w-full border rounded-md p-2 min-h-[80px] bg-white text-gray-800 dark:bg-slate-900 dark:text-slate-200 dark:border-slate-700" />
                        </div>
                        <div>
                          <Label>Oral Examination</Label>
                          <textarea v-model="editForms[t.id].assessment" class="w-full border rounded-md p-2 min-h-[80px] bg-white text-gray-800 dark:bg-slate-900 dark:text-slate-200 dark:border-slate-700" />
                        </div>
                        <div>
                          <Label>Plan</Label>
                          <textarea v-model="editForms[t.id].plan" class="w-full border rounded-md p-2 min-h-[80px] bg-white text-gray-800 dark:bg-slate-900 dark:text-slate-200 dark:border-slate-700" />
                        </div>
                        <div class="md:col-span-2 flex items-center justify-between">
                          <label class="text-sm flex items-center gap-2">
                            <input type="checkbox" v-model="editForms[t.id].active" /> Active
                          </label>
                          <div class="flex gap-2">
                            <Button size="sm" variant="outline" @click="cancelEdit(t.id)">Cancel</Button>
                            <Button size="sm" class="bg-blue-600 hover:bg-blue-700" :disabled="editForms[t.id].processing" @click="saveEdit(t.id)">Save</Button>
                          </div>
                        </div>
                      </div>
                    </div>
                  </td>
                  <td class="py-2 px-3">
                    <span v-if="t.active" class="px-2 py-0.5 rounded text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900/40 dark:text-green-300">Active</span>
                    <span v-else class="px-2 py-0.5 rounded text-xs font-medium bg-gray-200 text-gray-800 dark:bg-slate-700 dark:text-slate-200">Inactive</span>
                  </td>
                  <td class="py-2 px-3">
                    <div v-if="!editing[t.id]">
                      <DropdownMenu>
                        <DropdownMenuTrigger as-child>
                          <Button variant="ghost" size="sm" class="h-8 w-8 p-0">
                            <MoreVertical class="h-4 w-4" />
                          </Button>
                        </DropdownMenuTrigger>
                        <DropdownMenuContent align="end" class="w-48">
                          <DropdownMenuItem class="cursor-pointer" @click="startEdit(t)">
                            <i class="fas fa-edit mr-2"></i> Edit
                          </DropdownMenuItem>
                          <DropdownMenuItem class="cursor-pointer" @click="toggleActive(t.id)">
                            <i :class="[t.active ? 'fas fa-ban' : 'fas fa-check', 'mr-2']"></i> {{ t.active ? 'Deactivate' : 'Activate' }}
                          </DropdownMenuItem>
                          <DropdownMenuItem class="cursor-pointer text-red-600 focus:text-red-600 dark:text-red-400 dark:focus:text-red-400" @click="openDelete(t)">
                            <i class="fas fa-trash mr-2"></i> Delete
                          </DropdownMenuItem>
                        </DropdownMenuContent>
                      </DropdownMenu>
                    </div>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>

          <div class="flex items-center justify-between mt-4" v-if="props.templates.links">
            <div class="text-sm text-gray-600 dark:text-slate-300">Showing {{ props.templates.from }} - {{ props.templates.to }} of {{ props.templates.total }}</div>
            <div class="flex gap-2 flex-wrap">
              <Button
                v-for="l in props.templates.links"
                :key="(l.url || '') + l.label"
                :disabled="!l.url"
                :variant="l.active ? 'default' : 'outline'"
                size="sm"
                @click="l.url && router.get(l.url, {}, { preserveState: true, preserveScroll: true })"
                v-html="l.label"
              />
            </div>
          </div>
        </CardContent>
      </Card>

      <!-- Delete Template Dialog -->
      <Dialog :open="isDeleteOpen.open" @update:open="(v:boolean)=> isDeleteOpen.open = v">
        <DialogContent class="max-w-md">
          <DialogHeader>
            <DialogTitle class="text-xl font-bold text-red-600">Delete Template</DialogTitle>
            <DialogDescription class="text-gray-600 dark:text-gray-400">
              This action cannot be undone. This will permanently delete the clinical note template.
            </DialogDescription>
          </DialogHeader>
          <div class="py-2 text-sm">
            Are you sure you want to delete template <span class="font-medium">{{ deletingTpl.name }}</span>?
          </div>
          <DialogFooter class="gap-2">
            <Button type="button" variant="outline" @click="isDeleteOpen.open = false">Cancel</Button>
            <Button type="button" class="bg-red-600 hover:bg-red-700" @click="confirmDelete">
              <i class="fas fa-trash mr-2"></i>
              Delete Template
            </Button>
          </DialogFooter>
        </DialogContent>
      </Dialog>
    </div>
  </AppLayout>
</template>
