<script setup lang="ts">
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import { route } from 'ziggy-js';
import { ref, computed, watch } from 'vue';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/Components/ui/card';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { Badge } from '@/Components/ui/badge';
import { Button } from '@/Components/ui/button';
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogDescription, DialogFooter } from '@/Components/ui/dialog';
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuTrigger } from '@/Components/ui/dropdown-menu';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/Components/ui/select';
import Pagination from '@/Components/ui/Pagination.vue';
import { Plus, Search, MoreVertical, Receipt, DollarSign, TrendingUp, Calendar, Filter, ArrowUpDown, RefreshCw } from 'lucide-vue-next';
import AppLayout from '@/Layouts/AppLayout.vue';

interface Expense {
  id: number;
  title: string;
  amount: number;
  description?: string;
  category: string;
  date: string;
  date_formatted: string;
  created_at: string;
}

interface PaginationLink {
  url: string | null;
  label: string;
  active: boolean;
}

interface PaginationMeta {
  current_page?: number;
  last_page?: number;
  per_page?: number;
  total?: number;
  from?: number;
  to?: number;
}

interface Filters {
  search?: string;
  category?: string;
  status?: string;
  start_date?: string;
  end_date?: string;
  min_amount?: number;
  max_amount?: number;
  sort_by?: string;
  sort_order?: 'asc' | 'desc';
  per_page?: number;
  page?: number;
  total?: number;
  from?: number;
  to?: number;
}

interface Props {
  expenses: {
    data: Expense[];
    links: PaginationLink[];
    meta?: PaginationMeta;
  };
  filters?: Filters;
  stats?: {
    total_expenses: number;
    total_amount: number;
    this_month_expenses: number;
    this_month_amount: number;
  };
  categories: Array<{
    id: number;
    name: string;
    description: string;
    examples: string[];
  }>;
  can: {
    createExpense: boolean;
    updateExpense: boolean;
    deleteExpense: boolean;
  };
}

const props = withDefaults(defineProps<Props>(), {
  filters: () => ({
    search: '',
    category: 'all',
    status: 'all',
    start_date: '',
    end_date: '',
    min_amount: undefined,
    max_amount: undefined,
    sort_by: 'date',
    sort_order: 'desc',
    per_page: 10,
    page: 1,
    total: 0,
    from: 0,
    to: 0
  })
});

// Categories computed - filter out invalid entries
const categories = computed(() => props.categories.filter(cat => cat && cat.id && cat.name && cat.name.trim() !== '') || []);

// Initialize filters from props
const searchQuery = ref(props.filters?.search || '');
const filterCategory = ref(props.filters?.category || 'all');
const statusFilter = ref(props.filters?.status || 'all');
const startDate = ref(props.filters?.start_date || '');
const endDate = ref(props.filters?.end_date || '');
const minAmount = ref(props.filters?.min_amount || '');
const maxAmount = ref(props.filters?.max_amount || '');
const sortBy = ref(props.filters?.sort_by || 'date');
const sortOrder = ref<'asc' | 'desc'>(props.filters?.sort_order || 'desc');
const currentPage = ref(props.filters?.page || 1);
const listViewPerPage = ref((props.filters?.per_page || 10).toString());

// Modal states
const isCreateOpen = ref(false);
const isEditOpen = ref(false);
const isDeleteOpen = ref(false);
const isViewOpen = ref(false);
const viewingExpense = ref<Expense | null>(null);
const editingExpense = ref<Expense | null>(null);

// Forms
const createForm = useForm({
  title: '',
  amount: 0,
  category: '',
  date: '',
  description: '',
});

const editForm = useForm({
  id: 0,
  title: '',
  amount: 0,
  category: '',
  date: '',
  description: '',
});

const deleteForm = useForm({ id: 0 });

// Watch for filter changes
watch([searchQuery, filterCategory, statusFilter, startDate, endDate, minAmount, maxAmount, sortBy, sortOrder, listViewPerPage], () => {
  currentPage.value = 1;
  fetchExpenses();
});

// Watch for route changes to update filters
watch(() => props.filters, (newFilters) => {
  if (newFilters) {
    searchQuery.value = newFilters.search || '';
    filterCategory.value = newFilters.category || 'all';
    statusFilter.value = newFilters.status || 'all';
    startDate.value = newFilters.start_date || '';
    endDate.value = newFilters.end_date || '';
    minAmount.value = newFilters.min_amount || '';
    maxAmount.value = newFilters.max_amount || '';
    sortBy.value = newFilters.sort_by || 'date';
    sortOrder.value = newFilters.sort_order || 'desc';
    currentPage.value = newFilters.page || 1;
    
    if (newFilters.per_page && newFilters.per_page.toString() !== listViewPerPage.value) {
      listViewPerPage.value = newFilters.per_page.toString();
    }
  }
}, { deep: true, immediate: true });

// Fetch expenses with current filters
function fetchExpenses(overrides = {}) {
  const params = {
    search: searchQuery.value,
    category: filterCategory.value !== 'all' ? filterCategory.value : undefined,
    status: statusFilter.value !== 'all' ? statusFilter.value : undefined,
    start_date: startDate.value || undefined,
    end_date: endDate.value || undefined,
    min_amount: minAmount.value ? parseFloat(minAmount.value) : undefined,
    max_amount: maxAmount.value ? parseFloat(maxAmount.value) : undefined,
    sort_by: sortBy.value,
    sort_order: sortOrder.value,
    per_page: Number(listViewPerPage.value),
    page: currentPage.value,
    ...overrides
  };

  router.get(route('expenses.index'), params, {
    preserveState: true,
    preserveScroll: true,
    replace: true,
  });
}

// Toggle sort order
function toggleSort(column: string) {
  if (sortBy.value === column) {
    sortOrder.value = sortOrder.value === 'asc' ? 'desc' : 'asc';
  } else {
    sortBy.value = column;
    sortOrder.value = 'asc';
  }
  fetchExpenses();
}

// Reset all filters
function resetFilters() {
  searchQuery.value = '';
  filterCategory.value = 'all';
  statusFilter.value = 'all';
  startDate.value = '';
  endDate.value = '';
  minAmount.value = '';
  maxAmount.value = '';
  sortBy.value = 'date';
  sortOrder.value = 'desc';
  currentPage.value = 1;
  listViewPerPage.value = '10';
  fetchExpenses();
}

// Pagination
const paginationLinks = computed(() => props.expenses?.links || []);
const totalItems = computed(() => props.filters?.total ?? props.expenses?.meta?.total ?? 0);

const paginationSummary = computed(() => ({
  from: props.filters?.from ?? props.expenses?.meta?.from ?? 0,
  to: props.filters?.to ?? props.expenses?.meta?.to ?? 0,
  total: totalItems.value,
}));

const goToPage = (link: PaginationLink) => {
  if (!link.url || link.active) return;
  
  try {
    const url = new URL(link.url, window.location.origin);
    const page = url.searchParams.get('page');
    if (page) {
      currentPage.value = parseInt(page, 10);
      fetchExpenses({ page: currentPage.value });
    }
  } catch (e) {
    console.error('Error parsing pagination URL:', e);
  }
};

// Format currency
const formatUGX = (value: number) => {
  const whole = Math.round(value);
  return `UGX ${whole.toLocaleString('en-US')}`;
};

// Get category icon
const getCategoryIcon = (category: string) => {
  const lowerCategory = category.toLowerCase();
  if (lowerCategory.includes('utilities')) return 'fas fa-bolt';
  if (lowerCategory.includes('rent') || lowerCategory.includes('facilities')) return 'fas fa-home';
  if (lowerCategory.includes('marketing') || lowerCategory.includes('advertising')) return 'fas fa-bullhorn';
  if (lowerCategory.includes('insurance')) return 'fas fa-shield-alt';
  if (lowerCategory.includes('training') || lowerCategory.includes('education')) return 'fas fa-graduation-cap';
  if (lowerCategory.includes('maintenance')) return 'fas fa-wrench';
  if (lowerCategory.includes('software') || lowerCategory.includes('technology')) return 'fas fa-laptop';
  if (lowerCategory.includes('professional services')) return 'fas fa-user-tie';
  if (lowerCategory.includes('travel') || lowerCategory.includes('transportation')) return 'fas fa-car';
  if (lowerCategory.includes('cleaning') || lowerCategory.includes('sanitation')) return 'fas fa-spray-can';
  return 'fas fa-receipt';
};

// Modal functions
const openCreate = () => {
  if (!props.can.createExpense) return;
  createForm.reset();
  createForm.date = new Date().toISOString().split('T')[0];
  isCreateOpen.value = true;
};

const openView = (expense: Expense) => {
  viewingExpense.value = expense;
  isViewOpen.value = true;
};

const openEdit = (expense: Expense) => {
  if (!props.can.updateExpense) return;
  
  // Close the view modal if it's open
  if (isViewOpen.value) {
    isViewOpen.value = false;
  }
  
  // Small delay to allow the view modal to close before opening edit modal
  setTimeout(() => {
    editingExpense.value = expense;
    editForm.id = expense.id;
    editForm.title = expense.title;
    editForm.amount = expense.amount;
    editForm.category = expense.category;
    editForm.date = expense.date.split('T')[0];
    editForm.description = expense.description || '';
    isEditOpen.value = true;
  }, 50);
};

const openDelete = (expense: Expense) => {
  if (!props.can.deleteExpense) return;
  editingExpense.value = expense;
  deleteForm.id = expense.id;
  isDeleteOpen.value = true;
};

const submitCreate = () => {
  createForm.post(route('expenses.store'), {
    onSuccess: () => { 
      isCreateOpen.value = false; 
      createForm.reset();
    }
  });
};

const submitEdit = () => {
  if (!editingExpense.value) return;
  editForm.put(route('expenses.update', editingExpense.value.id), {
    onSuccess: () => { 
      isEditOpen.value = false; 
      editForm.reset();
    }
  });
};

const confirmDelete = () => {
  if (!editingExpense.value) return;
  deleteForm.delete(route('expenses.destroy', editingExpense.value.id), {
    onSuccess: () => { 
      isDeleteOpen.value = false; 
    }
  });
};
</script>

<template>
  <AppLayout title="Expenses">
    <Head title="Expenses" />

    <div class="min-h-screen bg-gradient-to-br from-blue-50 via-white to-cyan-50 dark:from-gray-900 dark:via-gray-800 dark:to-blue-900">
      <div class="container mx-auto px-4 py-8">
        <!-- Header Section -->
        <div class="mb-8">
          <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div>
              <h1 class="text-4xl font-bold bg-gradient-to-r from-blue-600 to-cyan-600 bg-clip-text text-transparent mb-2">
                Operational Expenses
              </h1>
              <p class="text-gray-600 dark:text-gray-400 text-lg">
                Track and manage business operational costs and overhead expenses
              </p>
            </div>

            <div class="flex items-center gap-3">
              <Badge variant="secondary" class="bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400 px-3 py-1 font-medium rounded-md shadow-sm">
                <Receipt class="w-4 h-4 mr-2" />
                {{ props.expenses.data.length }} Total Expenses
              </Badge>

              <Button
                v-if="props.can.createExpense"
                @click="openCreate"
                class="bg-gradient-to-r from-blue-600 to-cyan-600 hover:from-blue-700 hover:to-cyan-700 text-white shadow-lg hover:shadow-xl transition-all duration-300"
              >
                <Plus class="w-4 h-4 mr-2" />
                Add Operational Expense
              </Button>
            </div>
          </div>
        </div>

        <!-- Stats Overview -->
        <div v-if="props.stats" class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
          <Card class="border-0 shadow-xl hover:shadow-2xl transition-all duration-300 hover:-translate-y-1 bg-gradient-to-br from-blue-50 to-blue-100 dark:from-blue-900/20 dark:to-blue-800/20">
            <CardContent class="p-6">
              <div class="flex items-center justify-between">
                <div>
                  <p class="text-sm font-medium text-blue-700 dark:text-blue-300 mb-1">Total Operational Expenses</p>
                  <p class="text-3xl font-bold text-blue-900 dark:text-blue-100 mb-1">{{ props.stats.total_expenses }}</p>
                  <p class="text-xs text-blue-600 dark:text-blue-400">All recorded operational expenses</p>
                </div>
                <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-blue-500 to-blue-600 flex items-center justify-center shadow-lg">
                  <Receipt class="w-6 h-6 text-white" />
                </div>
              </div>
            </CardContent>
          </Card>

          <Card class="border-0 shadow-xl hover:shadow-2xl transition-all duration-300 hover:-translate-y-1 bg-gradient-to-br from-green-50 to-green-100 dark:from-green-900/20 dark:to-green-800/20">
            <CardContent class="p-6">
              <div class="flex items-center justify-between">
                <div>
                  <p class="text-sm font-medium text-green-700 dark:text-green-300 mb-1">Total Operational Amount</p>
                  <p class="text-3xl font-bold text-green-900 dark:text-green-100 mb-1">{{ formatUGX(props.stats.total_amount) }}</p>
                  <p class="text-xs text-green-600 dark:text-green-400">Sum of all operational expenses</p>
                </div>
                <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-green-500 to-green-600 flex items-center justify-center shadow-lg">
                  <DollarSign class="w-6 h-6 text-white" />
                </div>
              </div>
            </CardContent>
          </Card>

          <Card class="border-0 shadow-xl hover:shadow-2xl transition-all duration-300 hover:-translate-y-1 bg-gradient-to-br from-purple-50 to-purple-100 dark:from-purple-900/20 dark:to-purple-800/20">
            <CardContent class="p-6">
              <div class="flex items-center justify-between">
                <div>
                  <p class="text-sm font-medium text-purple-700 dark:text-purple-300 mb-1">This Month's Operational</p>
                  <p class="text-3xl font-bold text-purple-900 dark:text-purple-100 mb-1">{{ props.stats.this_month_expenses }}</p>
                  <p class="text-xs text-purple-600 dark:text-purple-400">Operational expenses this month</p>
                </div>
                <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-purple-500 to-purple-600 flex items-center justify-center shadow-lg">
                  <Calendar class="w-6 h-6 text-white" />
                </div>
              </div>
            </CardContent>
          </Card>

          <Card class="border-0 shadow-xl hover:shadow-2xl transition-all duration-300 hover:-translate-y-1 bg-gradient-to-br from-orange-50 to-orange-100 dark:from-orange-900/20 dark:to-orange-800/20">
            <CardContent class="p-6">
              <div class="flex items-center justify-between">
                <div>
                  <p class="text-sm font-medium text-orange-700 dark:text-orange-300 mb-1">Monthly Operational Total</p>
                  <p class="text-3xl font-bold text-orange-900 dark:text-orange-100 mb-1">{{ formatUGX(props.stats.this_month_amount) }}</p>
                  <p class="text-xs text-orange-600 dark:text-orange-400">This month's operational expenses</p>
                </div>
                <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-orange-500 to-orange-600 flex items-center justify-center shadow-lg">
                  <TrendingUp class="w-6 h-6 text-white" />
                </div>
              </div>
            </CardContent>
          </Card>
        </div>

        <!-- Main Content -->
        <Card class="border-0 shadow-xl bg-white dark:bg-gray-900 mb-8">
          <CardHeader class="pb-4">
            <div>
              <CardTitle class="text-2xl text-gray-900 dark:text-white">Operational Expense Management</CardTitle>
              <CardDescription class="text-gray-600 dark:text-gray-400">
                View and manage all business operational costs and overhead expenses
              </CardDescription>
            </div>
          </CardHeader>

          <CardContent>
            <div class="space-y-6">
              <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 w-full">
                <!-- Search -->
                <div class="relative">
                  <Search class="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400 w-4 h-4" />
                  <Input
                    v-model="searchQuery"
                    @update:model-value="() => fetchExpenses()"
                    placeholder="Search expenses..."
                    class="pl-10 w-full"
                  />
                </div>
                
                <!-- Category Filter -->
                <Select v-model="filterCategory" @update:model-value="() => fetchExpenses()">
                  <SelectTrigger>
                    <SelectValue placeholder="All Categories" />
                  </SelectTrigger>
                  <SelectContent>
                    <SelectItem value="all">All Categories</SelectItem>
                    <SelectItem v-for="cat in categories" :key="cat.id" :value="cat.name">
                      {{ cat.name }}
                    </SelectItem>
                  </SelectContent>
                </Select>
                
                <!-- Status Filter -->
                <!-- <Select v-model="statusFilter" @update:model-value="() => fetchExpenses()">
                  <SelectTrigger>
                    <SelectValue placeholder="All Status" />
                  </SelectTrigger>
                  <SelectContent>
                    <SelectItem value="all">All Status</SelectItem>
                    <SelectItem value="pending">Pending</SelectItem>
                    <SelectItem value="approved">Approved</SelectItem>
                    <SelectItem value="rejected">Rejected</SelectItem>
                  </SelectContent>
                </Select> -->
                
                <!-- Date Range -->
                <div class="flex items-center gap-2">
                  <Input
                    v-model="startDate"
                    @update:model-value="() => fetchExpenses()"
                    type="date"
                    placeholder="Start Date"
                    class="w-full"
                  />
                  <span class="text-gray-500">to</span>
                  <Input
                    v-model="endDate"
                    @update:model-value="() => fetchExpenses()"
                    type="date"
                    placeholder="End Date"
                    class="w-full"
                  />
                </div>
              </div>

              <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mt-4">
                <!-- Amount Range -->
                <div class="flex items-center gap-2">
                  <Input
                    v-model="minAmount"
                    @update:model-value="() => fetchExpenses()"
                    type="number"
                    placeholder="Min Amount"
                    min="0"
                    step="0.01"
                    class="w-full"
                  />
                  <span class="text-gray-500">to</span>
                  <Input
                    v-model="maxAmount"
                    @update:model-value="() => fetchExpenses()"
                    type="number"
                    placeholder="Max Amount"
                    min="0"
                    step="0.01"
                    class="w-full"
                  />
                </div>
                
                <!-- Sort By -->
                <div class="flex items-center gap-2">
                  <Label class="whitespace-nowrap text-sm text-gray-600 dark:text-gray-400">Sort by:</Label>
                  <Select v-model="sortBy" @update:model-value="() => fetchExpenses()">
                    <SelectTrigger>
                      <SelectValue />
                    </SelectTrigger>
                    <SelectContent>
                      <SelectItem value="date">Date</SelectItem>
                      <SelectItem value="amount">Amount</SelectItem>
                      <SelectItem value="created_at">Created At</SelectItem>
                      <SelectItem value="category">Category</SelectItem>
                    </SelectContent>
                  </Select>
                  <Button
                    variant="outline"
                    size="icon"
                    @click="() => { sortOrder = sortOrder === 'asc' ? 'desc' : 'asc'; fetchExpenses(); }"
                  >
                    <ArrowUpDown class="h-4 w-4" />
                  </Button>
                </div>
                
                <!-- Items Per Page -->
                <div class="flex items-center gap-2">
                  <Label for="per-page" class="whitespace-nowrap text-sm text-gray-600 dark:text-gray-400">Items per page:</Label>
                  <Select v-model="listViewPerPage" @update:model-value="() => fetchExpenses()">
                    <SelectTrigger class="h-10 w-20">
                      <SelectValue />
                    </SelectTrigger>
                    <SelectContent>
                      <SelectItem v-for="option in [10, 20, 30, 50]" :key="option" :value="option.toString()">
                        {{ option }}
                      </SelectItem>
                    </SelectContent>
                  </Select>
                </div>
                
                <!-- Reset Filters -->
                <Button
                  variant="outline"
                  @click="resetFilters"
                  class="justify-center"
                >
                  <RefreshCw class="h-4 w-4 mr-2" />
                  Reset Filters
                </Button>
              </div>

              <div class="overflow-x-auto border border-gray-200 dark:border-gray-800 rounded-lg">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-800">
                  <thead class="bg-gray-50 dark:bg-gray-800">
                    <tr>
                      <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-600 dark:text-gray-300 cursor-pointer" @click="toggleSort('title')">
                        <div class="flex items-center gap-1">
                          <span>Expense</span>
                          <span v-if="sortBy === 'title'" class="text-blue-600 dark:text-blue-400">
                            {{ sortOrder === 'asc' ? '↑' : '↓' }}
                          </span>
                        </div>
                      </th>
                      <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-600 dark:text-gray-300 cursor-pointer" @click="toggleSort('category')">
                        <div class="flex items-center gap-1">
                          <span>Category</span>
                          <span v-if="sortBy === 'category'" class="text-blue-600 dark:text-blue-400">
                            {{ sortOrder === 'asc' ? '↑' : '↓' }}
                          </span>
                        </div>
                      </th>
                      <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-600 dark:text-gray-300 cursor-pointer" @click="toggleSort('amount')">
                        <div class="flex items-center gap-1">
                          <span>Amount</span>
                          <span v-if="sortBy === 'amount'" class="text-blue-600 dark:text-blue-400">
                            {{ sortOrder === 'asc' ? '↑' : '↓' }}
                          </span>
                        </div>
                      </th>
                      <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-600 dark:text-gray-300 cursor-pointer" @click="toggleSort('date')">
                        <div class="flex items-center gap-1">
                          <span>Date</span>
                          <span v-if="sortBy === 'date'" class="text-blue-600 dark:text-blue-400">
                            {{ sortOrder === 'asc' ? '↑' : '↓' }}
                          </span>
                        </div>
                      </th>
                      <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-600 dark:text-gray-300">Description</th>
                      <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-gray-600 dark:text-gray-300">Actions</th>
                    </tr>
                  </thead>
                  <tbody class="bg-white dark:bg-gray-900 divide-y divide-gray-100 dark:divide-gray-800">
                    <tr
                      v-for="expense in props.expenses.data"
                      :key="expense.id"
                      class="hover:bg-gray-50 dark:hover:bg-gray-800/60 transition-colors cursor-pointer"
                      @click="openView(expense)"
                    >
                      <td class="px-4 py-4 align-top">
                        <div class="flex flex-col">
                          <span class="font-semibold text-gray-900 dark:text-white">{{ expense.title }}</span>
                          <span class="text-xs text-gray-500 dark:text-gray-400">ID: {{ expense.id }}</span>
                        </div>
                      </td>
                      <td class="px-4 py-4 align-top text-sm text-gray-600 dark:text-gray-400">
                        <Badge variant="secondary" class="bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400">
                          <Filter class="w-3 h-3 mr-1" />
                          {{ expense.category.charAt(0).toUpperCase() + expense.category.slice(1) }}
                        </Badge>
                      </td>
                      <td class="px-4 py-4 align-top text-sm text-gray-600 dark:text-gray-400">
                        <span class="font-semibold text-blue-600 dark:text-blue-400">{{ formatUGX(expense.amount) }}</span>
                      </td>
                      <td class="px-4 py-4 align-top text-sm text-gray-600 dark:text-gray-400">
                        <div class="flex items-center gap-2">
                          <Calendar class="w-4 h-4 text-gray-400" />
                          {{ expense.date_formatted }}
                        </div>
                      </td>
                      <td class="px-4 py-4 align-top text-sm text-gray-600 dark:text-gray-400">
                        {{ expense.description || 'No description provided' }}
                      </td>
                      <td class="px-4 py-4 align-top" @click.stop>
                        <div class="flex items-center justify-end">
                          <DropdownMenu>
                            <DropdownMenuTrigger as-child>
                              <Button variant="ghost" size="sm" class="h-8 w-8 p-0">
                                <MoreVertical class="h-4 w-4" />
                              </Button>
                            </DropdownMenuTrigger>
                            <DropdownMenuContent align="end" class="w-40">
                              <DropdownMenuItem @click="openView(expense)">
                                <Receipt class="w-4 h-4 mr-2" />
                                View
                              </DropdownMenuItem>
                              <DropdownMenuItem v-if="props.can.updateExpense" @click="openEdit(expense)">
                                <i class="fas fa-edit mr-2"></i>
                                Edit
                              </DropdownMenuItem>
                              <DropdownMenuItem 
                                v-if="props.can.deleteExpense" 
                                @click="openDelete(expense)" 
                                class="text-red-600"
                              >
                                <i class="fas fa-trash mr-2"></i>
                                Delete
                              </DropdownMenuItem>
                            </DropdownMenuContent>
                          </DropdownMenu>
                        </div>
                      </td>
                    </tr>
                    <tr v-if="props.expenses.data.length === 0">
                      <td colspan="6" class="px-6 py-12 text-center">
                        <div class="flex flex-col items-center gap-4 text-gray-600 dark:text-gray-400">
                          <DollarSign class="w-10 h-10 text-gray-400" />
                          <div>
                            <p class="text-xl font-semibold text-gray-900 dark:text-white">
                              {{ searchQuery || filterCategory !== 'all' || statusFilter !== 'all' ? 'No expenses found' : 'No expenses yet' }}
                            </p>
                            <p class="text-sm">
                              {{ searchQuery || filterCategory !== 'all' || statusFilter !== 'all' ? 'Try adjusting your search criteria' : 'Start by recording your first expense.' }}
                            </p>
                          </div>
                          <div class="flex gap-2">
                            <Button v-if="props.can.createExpense && !searchQuery && filterCategory === 'all' && statusFilter === 'all'" @click.stop="openCreate" class="bg-gradient-to-r from-blue-600 to-cyan-600 hover:from-blue-700 hover:to-cyan-700">
                              <Plus class="w-4 h-4 mr-2" />
                              Add Expense
                            </Button>
                            <Button v-else @click.stop="resetFilters" variant="outline">
                              Clear Filters
                            </Button>
                          </div>
                        </div>
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>

              <div v-if="paginationLinks.length > 0" class="flex flex-col md:flex-row md:items-center md:justify-between items-start gap-4">
                <div class="flex-1">
                  <Pagination
                    :links="paginationLinks"
                    :from="paginationSummary.from"
                    :to="paginationSummary.to"
                    :total="paginationSummary.total"
                    :item-name="paginationSummary.total === 1 ? 'expense' : 'expenses'"
                    @page-change="goToPage"
                  />
                </div>
                <div class="flex items-center gap-2">
                  <Label for="per-page" class="text-sm text-gray-600 dark:text-gray-400 whitespace-nowrap">Per page:</Label>
                  <Select v-model="listViewPerPage" @update:model-value="() => fetchExpenses()">
                    <SelectTrigger class="h-8 w-20">
                      <SelectValue />
                    </SelectTrigger>
                    <SelectContent>
                      <SelectItem v-for="option in [10, 20, 30, 50]" :key="option" :value="option.toString()">
                        {{ option }}
                      </SelectItem>
                    </SelectContent>
                  </Select>
                </div>
              </div>
            </div>
          </CardContent>
        </Card>
      </div>
    </div>

    <!-- Create Modal -->
    <Dialog :open="isCreateOpen" @update:open="(value) => isCreateOpen = value">
      <DialogContent class="max-w-4xl">
        <DialogHeader>
          <DialogTitle class="text-2xl font-bold bg-gradient-to-r from-blue-600 to-cyan-600 bg-clip-text text-transparent">
            Add Operational Expense
          </DialogTitle>
          <DialogDescription class="text-gray-600 dark:text-gray-400">
            Record a new business operational cost or overhead expense.
          </DialogDescription>
        </DialogHeader>
        <form @submit.prevent="submitCreate" class="space-y-6">
          <div class="space-y-2">
            <Label for="title" class="text-gray-700 dark:text-gray-300">Expense Title</Label>
            <Input
              id="title"
              v-model="createForm.title"
              placeholder="Enter expense title"
              class="h-12 bg-gray-50 dark:bg-gray-800 border-gray-200 dark:border-gray-700"
              required
            />
          </div>

          <div class="space-y-2">
            <Label for="amount" class="text-gray-700 dark:text-gray-300">Amount (UGX)</Label>
            <Input
              id="amount"
              type="number"
              v-model="createForm.amount"
              placeholder="0.00"
              step="0.01"
              min="0"
              class="h-12 bg-gray-50 dark:bg-gray-800 border-gray-200 dark:border-gray-700"
              required
            />
          </div>

          <div class="space-y-2">
            <Label for="category" class="text-gray-700 dark:text-gray-300">Category</Label>
            <Select v-model="createForm.category">
              <SelectTrigger class="h-12 bg-gray-50 dark:bg-gray-800 border-gray-200 dark:border-gray-700">
                <SelectValue placeholder="Select a category" />
              </SelectTrigger>
              <SelectContent>
                <SelectItem v-for="cat in categories" :key="cat.id" :value="cat.name">
                  {{ cat.name }}
                </SelectItem>
              </SelectContent>
            </Select>
          </div>

          <div class="space-y-2">
            <Label for="date" class="text-gray-700 dark:text-gray-300">Date</Label>
            <Input
              id="date"
              type="date"
              v-model="createForm.date"
              class="h-12 bg-gray-50 dark:bg-gray-800 border-gray-200 dark:border-gray-700"
              required
            />
          </div>

          <div class="space-y-2">
            <Label for="description" class="text-gray-700 dark:text-gray-300">Description (Optional)</Label>
            <Input
              id="description"
              v-model="createForm.description"
              placeholder="Additional details about the expense"
              class="h-12 bg-gray-50 dark:bg-gray-800 border-gray-200 dark:border-gray-700"
            />
          </div>

          <DialogFooter class="gap-2">
            <Button type="button" variant="outline" @click="isCreateOpen = false">
              Cancel
            </Button>
            <Button type="submit" :disabled="createForm.processing" class="bg-gradient-to-r from-blue-600 to-cyan-600 hover:from-blue-700 hover:to-cyan-700">
              <i v-if="createForm.processing" class="fas fa-spinner fa-spin mr-2"></i>
              <i v-else class="fas fa-plus mr-2"></i>
              {{ createForm.processing ? 'Creating...' : 'Create Expense' }}
            </Button>
          </DialogFooter>
        </form>
      </DialogContent>
    </Dialog>

    <!-- Edit Modal -->
    <Dialog :open="isEditOpen" @update:open="(value) => isEditOpen = value">
      <DialogContent class="max-w-4xl">
        <DialogHeader>
          <DialogTitle class="text-2xl font-bold bg-gradient-to-r from-blue-600 to-cyan-600 bg-clip-text text-transparent">
            Edit Operational Expense
          </DialogTitle>
          <DialogDescription class="text-gray-600 dark:text-gray-400">
            Update the operational expense information below.
          </DialogDescription>
        </DialogHeader>
        <form @submit.prevent="submitEdit" class="space-y-6">
          <div class="space-y-2">
            <Label for="edit-title" class="text-gray-700 dark:text-gray-300">Expense Title</Label>
            <Input
              id="edit-title"
              v-model="editForm.title"
              placeholder="Enter expense title"
              class="h-12 bg-gray-50 dark:bg-gray-800 border-gray-200 dark:border-gray-700"
              required
            />
          </div>

          <div class="space-y-2">
            <Label for="edit-amount" class="text-gray-700 dark:text-gray-300">Amount (UGX)</Label>
            <Input
              id="edit-amount"
              type="number"
              v-model="editForm.amount"
              placeholder="0.00"
              step="0.01"
              min="0"
              class="h-12 bg-gray-50 dark:bg-gray-800 border-gray-200 dark:border-gray-700"
              required
            />
          </div>

          <div class="space-y-2">
            <Label for="edit-category" class="text-gray-700 dark:text-gray-300">Category</Label>
            <Select v-model="editForm.category">
              <SelectTrigger class="h-12 bg-gray-50 dark:bg-gray-800 border-gray-200 dark:border-gray-700">
                <SelectValue placeholder="Select a category" />
              </SelectTrigger>
              <SelectContent>
                <SelectItem v-for="cat in categories" :key="cat.id" :value="cat.name">
                  {{ cat.name }}
                </SelectItem>
              </SelectContent>
            </Select>
          </div>

          <div class="space-y-2">
            <Label for="edit-date" class="text-gray-700 dark:text-gray-300">Date</Label>
            <Input
              id="edit-date"
              type="date"
              v-model="editForm.date"
              class="h-12 bg-gray-50 dark:bg-gray-800 border-gray-200 dark:border-gray-700"
              required
            />
          </div>

          <div class="space-y-2">
            <Label for="edit-description" class="text-gray-700 dark:text-gray-300">Description (Optional)</Label>
            <Input
              id="edit-description"
              v-model="editForm.description"
              placeholder="Additional details about the expense"
              class="h-12 bg-gray-50 dark:bg-gray-800 border-gray-200 dark:border-gray-700"
            />
          </div>

          <DialogFooter class="gap-2">
            <Button type="button" variant="outline" @click="isEditOpen = false">
              Cancel
            </Button>
            <Button type="submit" :disabled="editForm.processing" class="bg-gradient-to-r from-blue-600 to-cyan-600 hover:from-blue-700 hover:to-cyan-700">
              <i v-if="editForm.processing" class="fas fa-spinner fa-spin mr-2"></i>
              <i v-else class="fas fa-save mr-2"></i>
              {{ editForm.processing ? 'Saving...' : 'Save Changes' }}
            </Button>
          </DialogFooter>
        </form>
      </DialogContent>
    </Dialog>

    <!-- Delete Modal -->
    <Dialog :open="isDeleteOpen" @update:open="(value) => isDeleteOpen = value">
      <DialogContent class="max-w-4xl">
        <DialogHeader>
          <DialogTitle class="text-xl font-bold text-red-600">Delete Operational Expense</DialogTitle>
          <DialogDescription class="text-gray-600 dark:text-gray-400">
            This action cannot be undone. This will permanently delete the operational expense record.
          </DialogDescription>
        </DialogHeader>

        <div class="py-4">
          <div class="flex items-center space-x-3 p-4 bg-red-50 dark:bg-red-900/20 rounded-lg border border-red-200 dark:border-red-800">
            <i class="fas fa-exclamation-triangle text-red-600 text-xl"></i>
            <div>
              <p class="font-medium text-red-800 dark:text-red-200">Delete "{{ editingExpense?.title }}"?</p>
              <p class="text-sm text-red-600 dark:text-red-400">This will remove the expense record permanently.</p>
            </div>
          </div>
        </div>

        <DialogFooter class="gap-2">
          <Button type="button" variant="outline" @click="isDeleteOpen = false">
            Cancel
          </Button>
          <Button
            @click="confirmDelete"
            variant="destructive"
            class="bg-red-600 hover:bg-red-700"
          >
            <i class="fas fa-trash mr-2"></i>
            Delete Expense
          </Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>

    <!-- View Expense Modal -->
    <Dialog :open="isViewOpen" @update:open="(value) => isViewOpen = value">
      <DialogContent class="max-w-6xl max-h-[90vh] overflow-y-auto">
        <DialogHeader>
          <DialogTitle class="text-2xl font-bold bg-gradient-to-r from-blue-600 to-cyan-600 bg-clip-text text-transparent">
            Operational Expense Details: {{ viewingExpense?.title }}
          </DialogTitle>
          <DialogDescription class="text-gray-600 dark:text-gray-400">
            Complete information about this operational expense.
          </DialogDescription>
        </DialogHeader>

        <div v-if="viewingExpense" class="space-y-6">
          <!-- Expense Header -->
          <div class="flex items-start space-x-4 p-4 bg-gradient-to-r from-blue-50 to-cyan-50 dark:from-blue-900/20 dark:to-cyan-900/20 rounded-lg">
            <div class="w-16 h-16 rounded-xl bg-gradient-to-br from-blue-500 to-cyan-500 flex items-center justify-center shadow-lg">
              <i :class="[getCategoryIcon(viewingExpense.category), 'text-white text-2xl']"></i>
            </div>
            <div class="flex-1">
              <h3 class="text-xl font-bold text-gray-900 dark:text-white">{{ viewingExpense.title }}</h3>
              <p class="text-gray-600 dark:text-gray-400">Expense ID: {{ viewingExpense.id }}</p>
              <p class="text-sm text-gray-500 dark:text-gray-500">
                Date: {{ viewingExpense.date_formatted }}
              </p>
            </div>
          </div>

          <!-- Expense Information -->
          <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Basic Information -->
            <Card class="border-0 shadow-lg">
              <CardHeader class="pb-3">
                <CardTitle class="text-lg flex items-center">
                  <Receipt class="w-5 h-5 mr-2 text-blue-600" />
                  Operational Expense Information
                </CardTitle>
              </CardHeader>
              <CardContent class="space-y-3">
                <div>
                  <Label class="text-sm font-medium text-gray-500 dark:text-gray-400">Category</Label>
                  <p class="text-gray-900 dark:text-white font-medium">{{ viewingExpense.category }}</p>
                </div>
                <div>
                  <Label class="text-sm font-medium text-gray-500 dark:text-gray-400">Amount</Label>
                  <p class="text-2xl font-bold text-green-600 dark:text-green-400">{{ formatUGX(viewingExpense.amount) }}</p>
                </div>
              </CardContent>
            </Card>

            <!-- Additional Details -->
            <Card class="border-0 shadow-lg">
              <CardHeader class="pb-3">
                <CardTitle class="text-lg flex items-center">
                  <Calendar class="w-5 h-5 mr-2 text-green-600" />
                  Additional Details
                </CardTitle>
              </CardHeader>
              <CardContent class="space-y-3">
                <div>
                  <Label class="text-sm font-medium text-gray-500 dark:text-gray-400">Date</Label>
                  <p class="text-gray-900 dark:text-white">{{ viewingExpense.date_formatted }}</p>
                </div>
                <div>
                  <Label class="text-sm font-medium text-gray-500 dark:text-gray-400">Created</Label>
                  <p class="text-gray-900 dark:text-white">{{ new Date(viewingExpense.created_at).toLocaleDateString() }}</p>
                </div>
              </CardContent>
            </Card>
          </div>

          <!-- Description -->
          <Card v-if="viewingExpense.description" class="border-0 shadow-lg">
            <CardHeader class="pb-3">
              <CardTitle class="text-lg flex items-center">
                <i class="fas fa-align-left mr-2 text-purple-600"></i>
                Description
              </CardTitle>
            </CardHeader>
            <CardContent>
              <p class="text-gray-700 dark:text-gray-300 whitespace-pre-wrap">{{ viewingExpense.description }}</p>
            </CardContent>
          </Card>

          <!-- Actions -->
          <div class="flex justify-between items-center pt-4 border-t border-gray-200 dark:border-gray-700">
            <Button variant="outline" @click="openEdit(viewingExpense)">
              <i class="fas fa-edit mr-2"></i>
              Edit Expense
            </Button>
            <div class="flex space-x-2">
              <Button @click="isViewOpen = false">
                Close
              </Button>
            </div>
          </div>
        </div>
      </DialogContent>
    </Dialog>
  </AppLayout>
</template>

<style scoped>
.bg-clip-text {
  -webkit-background-clip: text;
  background-clip: text;
  -webkit-text-fill-color: transparent;
}

.line-clamp-2 {
  overflow: hidden;
  display: -webkit-box;
  -webkit-box-orient: vertical;
  -webkit-line-clamp: 2;
}
</style>