<script setup lang="ts">
import { Head, Link, useForm, router, usePage } from '@inertiajs/vue3'
import { route } from 'ziggy-js'
import { ref, computed } from 'vue'
import AppLayout from '@/Layouts/AppLayout.vue'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/Components/ui/card'
import { Button } from '@/Components/ui/button'
import { Input } from '@/Components/ui/input'
import { Label } from '@/Components/ui/label'
import { Badge } from '@/Components/ui/badge'
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/Components/ui/table'

interface Movement {
  id: number
  type: 'inflow' | 'outflow'
  method: 'cash' | 'card' | 'mobile_money' | 'bank_transfer' | null
  amount: number | string
  reason?: string | null
  created_at: string
  payment?: { id: number; invoice?: { id: number; patient?: { name?: string } } }
}

interface Session {
  id: number
  opening_amount: number | string
  closing_amount?: number | string | null
  status: 'open' | 'closed'
  started_at: string
  ended_at?: string | null
}

interface Props {
  active_session: Session | null
  movements: Movement[]
  totals: { inflow: number; outflow: number }
  session_summary?: {
    status: 'open' | 'closed'
    opening_amount: number
    expected_cash_now: number
    closing_amount?: number | null
    expected_cash_at_close?: number | null
    variance?: number | null
  } | null
}

const props = defineProps<Props>()
const page = usePage<any>()

const canManage = computed(() => {
  const roles = (page?.props?.auth?.user?.roles || []).map((r: any) => r?.name ?? r)
  return Array.isArray(roles) && (roles.includes('admin') || roles.includes('receptionist'))
})

// Initialize form with active session's data if available
const openForm = useForm({ 
  opening_amount: props.active_session?.opening_amount || 0, 
  notes: props.active_session?.notes || '' 
})
const closeForm = useForm({ closing_amount: 0, notes: '' })
const adjustForm = useForm({ type: 'inflow' as 'inflow'|'outflow', method: 'cash' as 'cash'|'card'|'mobile_money'|'bank_transfer', amount: 0, reason: '' })

const openSession = () => {
  if (openForm.opening_amount < 0) return// Store the opening amount in a variable before the form submission
  const openingAmount = openForm.opening_amount
  openForm.post(route('cash-drawer.open'), {
    preserveScroll: true,
    onSuccess: () => {
      // The page will reload, and the form will be initialized with the active session's amount
      router.reload()
    },
    onError: (errors) => {
      const first = errors.opening_amount || errors.notes || Object.values(errors)[0]
      if (first) alert(String(first))
    },
  })
}

const closeSession = () => {
  if (closeForm.closing_amount < 0) return alert('Closing amount must be >= 0')
  // If we know expected cash now and there is a variance, require notes client-side as a hint
  const expectedNow = (props.session_summary && (props.session_summary as any).expected_cash_now) || null
  if (expectedNow != null) {
    const variance = Number(closeForm.closing_amount || 0) - Number(expectedNow)
    if (Math.abs(variance) > 0.009 && !String(closeForm.notes || '').trim()) {
      if (!confirm('There is a variance between expected and counted cash. Add a note explaining the variance and continue?')) {
        return
      }
    }
  }
  closeForm.post(route('cash-drawer.close'), {
    onSuccess: () => router.reload(),
    onError: (errors) => {
      const first = errors.closing_amount || errors.notes || Object.values(errors)[0]
      if (first) alert(String(first))
    },
  })
}

const submitAdjustment = () => {
  if (adjustForm.amount <= 0) return alert('Amount must be > 0')
  if (!adjustForm.reason.trim()) return alert('Reason is required')
  adjustForm.post(route('cash-drawer.adjust'), { onSuccess: () => { adjustForm.reset(); router.reload() } })
}

const net = computed(() => Number((props.totals && (props.totals as any).inflow) || 0) - Number((props.totals && (props.totals as any).outflow) || 0))
</script>

<template>
  <AppLayout title="Cash Drawer">
    <Head title="Cash Drawer" />

    <div class="container mx-auto px-4 py-8">
      <div class="mb-6 flex items-center justify-between">
        <div>
          <h1 class="text-2xl font-bold">Cash Drawer</h1>
          <p class="text-gray-500 dark:text-slate-400">Open/close cashier sessions and view today's movements</p>
        </div>
        <div v-if="canManage">
          <Badge v-if="props.active_session?.status === 'open'" class="bg-green-100 text-green-700 dark:bg-green-900/40 dark:text-green-300">Session Open</Badge>
          <Badge v-else class="bg-gray-100 text-gray-700 dark:bg-slate-800 dark:text-slate-300">No Active Session</Badge>
        </div>
      </div>

      <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
        <Card>
          <CardHeader>
            <CardTitle>Open Session</CardTitle>
            <CardDescription>Start a cashier session</CardDescription>
          </CardHeader>
          <CardContent>
            <div class="space-y-3">
              <div>
                <Label>Opening Amount</Label>
                <Input 
                  v-model.number="openForm.opening_amount" 
                  type="number" 
                  min="0" 
                  step="0.01" 
                  :disabled="!!props.active_session"
                  :class="{'bg-gray-100 dark:bg-gray-800': !!props.active_session}"
                />
                <div v-if="openForm.errors.opening_amount" class="text-red-600 text-xs mt-1">{{ openForm.errors.opening_amount }}</div>
                <p v-if="props.active_session" class="text-xs text-gray-500 mt-1">
                  Current session's opening amount
                </p>
              </div>
              <div>
                <Label>Notes</Label>
                <Input 
                  v-model="openForm.notes" 
                  :disabled="!!props.active_session" 
                  :class="{'bg-gray-100 dark:bg-gray-800': !!props.active_session}"
                />
                <div v-if="openForm.errors.notes" class="text-red-600 text-xs mt-1">{{ openForm.errors.notes }}</div>
              </div>
              <Button class="bg-blue-600 hover:bg-blue-700" :disabled="openForm.processing || !!props.active_session || !canManage" @click="openSession">{{ openForm.processing ? 'Opening...' : 'Open Session' }}</Button>
            </div>
          </CardContent>
        </Card>

        <Card>
          <CardHeader>
            <CardTitle>Close Session</CardTitle>
            <CardDescription>End the current session</CardDescription>
          </CardHeader>
          <CardContent>
            <div class="space-y-3">
              <div v-if="props.active_session">
                <Label>Closing Amount</Label>
                <Input v-model.number="closeForm.closing_amount" type="number" min="0" step="0.01" :disabled="!props.active_session" />
                <div v-if="closeForm.errors.closing_amount" class="text-red-600 text-xs mt-1">{{ closeForm.errors.closing_amount }}</div>
              </div>
              <div>
                <Label>Notes</Label>
                <Input v-model="closeForm.notes" :disabled="!props.active_session" />
                <div v-if="closeForm.errors.notes" class="text-red-600 text-xs mt-1">{{ closeForm.errors.notes }}</div>
              </div>
              <Button 
                variant="destructive" 
                class="bg-red-600 hover:bg-red-700 text-white font-semibold" 
                :disabled="closeForm.processing || !props.active_session || !canManage" 
                @click="closeSession"
              >
                {{ closeForm.processing ? 'Closing...' : 'Close Session' }}
              </Button>
            </div>
          </CardContent>
        </Card>
      </div>

      <Card>
        <CardHeader>
          <CardTitle>Today's Movements</CardTitle>
          <CardDescription>Latest 200 records (inflows/outflows)</CardDescription>
        </CardHeader>
        <CardContent>
          <div class="flex gap-4 mb-4">
            <Badge class="bg-green-100 text-green-700 dark:bg-green-900/40 dark:text-green-300">Inflow: UGX {{ Number(props.totals.inflow || 0).toLocaleString() }}</Badge>
            <Badge class="bg-red-100 text-red-700 dark:bg-red-900/40 dark:text-red-300">Outflow: UGX {{ Number(props.totals.outflow || 0).toLocaleString() }}</Badge>
            <Badge :class="net >= 0 ? 'bg-blue-100 text-blue-700 dark:bg-blue-900/40 dark:text-blue-300' : 'bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-300'">Net: UGX {{ net.toLocaleString() }}</Badge>
          </div>
          <div class="overflow-x-auto">
            <Table>
              <TableHeader>
                <TableRow>
                  <TableHead>Time</TableHead>
                  <TableHead>Type</TableHead>
                  <TableHead>Method</TableHead>
                  <TableHead>Amount</TableHead>
                  <TableHead>Reason</TableHead>
                  <TableHead>Link</TableHead>
                </TableRow>
              </TableHeader>
              <TableBody>
                <TableRow v-for="m in props.movements" :key="m.id">
                  <TableCell>{{ new Date(m.created_at).toLocaleTimeString() }}</TableCell>
                  <TableCell>
                    <Badge :class="m.type === 'inflow' ? 'bg-green-100 text-green-700 dark:bg-green-900/40 dark:text-green-300' : 'bg-red-100 text-red-700 dark:bg-red-900/40 dark:text-red-300'">
                      {{ m.type === 'inflow' ? 'Inflow' : 'Outflow' }}
                    </Badge>
                  </TableCell>
                  <TableCell>{{ m.method || '—' }}</TableCell>
                  <TableCell>UGX {{ Number(m.amount || 0).toLocaleString() }}</TableCell>
                  <TableCell>{{ m.reason || '—' }}</TableCell>
                  <TableCell>
                    <Link v-if="m.payment?.id && m.payment?.invoice?.id" :href="route('invoices.payments.receipt', [m.payment.invoice.id, m.payment.id])" class="text-blue-600 dark:text-blue-400 hover:underline">Receipt</Link>
                  </TableCell>
                </TableRow>
              </TableBody>
            </Table>
          </div>
        </CardContent>
      </Card>

      <Card v-if="(page?.props?.auth?.user?.roles || []).some((r:any)=> (r?.name??r)==='admin')" class="mt-8">
        <CardHeader>
          <CardTitle>Manual Adjustment (Admin)</CardTitle>
          <CardDescription>Record petty cash or corrections</CardDescription>
        </CardHeader>
        <CardContent>
          <div class="grid md:grid-cols-4 gap-3 items-end">
            <div>
              <Label>Type</Label>
              <select v-model="adjustForm.type" class="w-full border rounded-md p-2 bg-white text-gray-800 dark:bg-slate-900 dark:text-slate-200 dark:border-slate-700">
                <option value="inflow">Inflow</option>
                <option value="outflow">Outflow</option>
              </select>
            </div>
            <div>
              <Label>Method</Label>
              <select v-model="adjustForm.method" class="w-full border rounded-md p-2 bg-white text-gray-800 dark:bg-slate-900 dark:text-slate-200 dark:border-slate-700">
                <option value="cash">Cash</option>
                <option value="card">Card</option>
                <option value="mobile_money">Mobile Money</option>
                <option value="bank_transfer">Bank Transfer</option>
              </select>
            </div>
            <div>
              <Label>Amount</Label>
              <Input v-model.number="adjustForm.amount" type="number" min="0" step="0.01" />
            </div>
            <div class="md:col-span-2">
              <Label>Reason</Label>
              <Input v-model="adjustForm.reason" placeholder="e.g., petty cash" />
            </div>
            <div class="md:col-span-2 flex justify-end">
              <Button @click="submitAdjustment" :disabled="adjustForm.processing" class="bg-blue-600 hover:bg-blue-700">Save Adjustment</Button>
            </div>
          </div>
        </CardContent>
      </Card>
    </div>
  </AppLayout>
</template>
