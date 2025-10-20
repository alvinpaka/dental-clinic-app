<script setup lang="ts">
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import { route } from 'ziggy-js';
import { ref, computed } from 'vue';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/Components/ui/card';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { Badge } from '@/Components/ui/badge';
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogDescription, DialogFooter } from '@/Components/ui/dialog';
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuTrigger } from '@/Components/ui/dropdown-menu';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/Components/ui/select';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/Components/ui/tabs';
import { Plus, Search, MoreVertical, Receipt, DollarSign, TrendingUp, Calendar, Filter } from 'lucide-vue-next';
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

interface Props {
  expenses: {
    data: Expense[];
    links: any[];
  };
  stats?: {
    total_expenses: number;
    total_amount: number;
    this_month_expenses: number;
    this_month_amount: number;
  };
  categories: string[];
  can: {
    createExpense: boolean;
    updateExpense: boolean;
    deleteExpense: boolean;
  };
}

const props = defineProps<Props>();

const searchQuery = ref('');
const filterCategory = ref('all');
const sortBy = ref('date');
const sortOrder = ref('desc');
const activeTab = ref('grid');

// Define forms with useForm
const createForm = useForm({
  title: '',
  amount: 0,
  description: '',
  category: '',
  date: new Date().toISOString().split('T')[0],
});

const editForm = useForm({
  title: '',
  amount: 0,
  description: '',
  category: '',
  date: '',
});

const isCreateOpen = ref(false);
const isEditOpen = ref(false);
const isDeleteOpen = ref(false);
const isViewOpen = ref(false);
const editingExpense = ref<Expense | null>(null);
const viewingExpense = ref<Expense | null>(null);

// Filtered and sorted expenses
const filteredExpenses = computed(() => {
  let expenses = [...props.expenses.data];

  // Search filter
  if (searchQuery.value) {
    expenses = expenses.filter(expense =>
      expense.title.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
      expense.description?.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
      expense.category.toLowerCase().includes(searchQuery.value.toLowerCase())
    );
  }

  // Category filter
  if (filterCategory.value !== 'all') {
    expenses = expenses.filter(expense => expense.category === filterCategory.value);
  }

  // Sort
  expenses.sort((a, b) => {
    let aValue: any, bValue: any;

    switch (sortBy.value) {
      case 'title':
        aValue = a.title.toLowerCase();
        bValue = b.title.toLowerCase();
        break;
      case 'amount':
        aValue = a.amount;
        bValue = b.amount;
        break;
      case 'category':
        aValue = a.category.toLowerCase();
        bValue = b.category.toLowerCase();
        break;
      case 'date':
      default:
        aValue = new Date(a.date).getTime();
        bValue = new Date(b.date).getTime();
        break;
    }

    if (aValue < bValue) return sortOrder.value === 'asc' ? -1 : 1;
    if (aValue > bValue) return sortOrder.value === 'asc' ? 1 : -1;
    return 0;
  });

  return expenses;
});

const openCreate = () => {
  if (!props.can.createExpense) return;
  isCreateOpen.value = true;
};

const openEdit = (expense: Expense) => {
  if (!props.can.updateExpense) return;
  editingExpense.value = expense;
  editForm.title = expense.title;
  editForm.amount = expense.amount;
  editForm.description = expense.description || '';
  editForm.category = expense.category;
  editForm.date = expense.date;
  isEditOpen.value = true;
};

const openDelete = (expense: Expense) => {
  if (!props.can.deleteExpense) return;
  editingExpense.value = expense;
  isDeleteOpen.value = true;
};

const openView = (expense: Expense) => {
  viewingExpense.value = expense;
  isViewOpen.value = true;
};

const submitCreate = () => {
  if (!props.can.createExpense) return;
  createForm.post(route('expenses.store'), {
    onSuccess: () => {
      createForm.reset();
      isCreateOpen.value = false;
    },
  });
};

const submitEdit = () => {
  if (!props.can.updateExpense || !editingExpense.value) {
    return;
  }

  editForm.put(route('expenses.update', editingExpense.value.id), {
    onSuccess: () => {
      editForm.reset();
      isEditOpen.value = false;
      editingExpense.value = null;
    },
  });
};

const confirmDelete = () => {
  if (!props.can.deleteExpense || !editingExpense.value) {
    return;
  }

  router.delete(route('expenses.destroy', editingExpense.value.id), {
    onSuccess: () => {
      isDeleteOpen.value = false;
      editingExpense.value = null;
    },
  });
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
              <Badge variant="secondary" class="bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400">
                <Receipt class="w-4 h-4 mr-1" />
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
            <Tabs v-model="activeTab" class="w-full">
              <TabsList class="grid w-full grid-cols-2">
                <TabsTrigger value="grid">Grid View</TabsTrigger>
                <TabsTrigger value="list">List View</TabsTrigger>
              </TabsList>

              <!-- Grid View -->
              <TabsContent value="grid" class="mt-0">
                <div class="space-y-6">
                  <!-- Search and Filters -->
                  <div class="flex flex-col md:flex-row gap-4 items-center">
                    <div class="relative flex-1">
                      <Search class="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400 w-4 h-4" />
                      <Input
                        v-model="searchQuery"
                        placeholder="Search operational expenses by title, description, or category..."
                        class="pl-10 h-12 bg-gray-50 dark:bg-gray-800 border-gray-200 dark:border-gray-700"
                      />
                    </div>

                    <div class="flex items-center gap-2">
                      <Filter class="w-4 h-4 text-gray-400" />
                      <Label class="text-sm font-medium text-gray-700 dark:text-gray-300">Category:</Label>
                      <Select v-model="filterCategory">
                        <SelectTrigger class="w-48">
                          <SelectValue placeholder="All categories" />
                        </SelectTrigger>
                        <SelectContent>
                          <SelectItem value="all">All categories</SelectItem>
                          <SelectItem v-for="category in props.categories" :key="category" :value="category">
                            {{ category }}
                          </SelectItem>
                        </SelectContent>
                      </Select>
                    </div>

                    <div class="flex items-center gap-2">
                      <Label class="text-sm font-medium text-gray-700 dark:text-gray-300">Sort by:</Label>
                      <Select v-model="sortBy">
                        <SelectTrigger class="w-32">
                          <SelectValue />
                        </SelectTrigger>
                        <SelectContent>
                          <SelectItem value="date">Date</SelectItem>
                          <SelectItem value="title">Title</SelectItem>
                          <SelectItem value="amount">Amount</SelectItem>
                          <SelectItem value="category">Category</SelectItem>
                        </SelectContent>
                      </Select>

                      <Button
                        @click="sortOrder = sortOrder === 'asc' ? 'desc' : 'asc'"
                        variant="outline"
                        size="sm"
                        class="px-3"
                      >
                        <i :class="['fas', sortOrder === 'asc' ? 'fa-arrow-up' : 'fa-arrow-down', 'text-sm']"></i>
                      </Button>
                    </div>
                  </div>

                  <!-- Expenses Grid -->
                  <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <Card
                      v-for="(expense, index) in filteredExpenses"
                      :key="expense.id"
                      class="border-0 shadow-xl hover:shadow-2xl transition-all duration-300 hover:-translate-y-2 bg-white dark:bg-gray-900 group"
                    >
                      <CardHeader class="pb-4">
                        <div class="flex items-start justify-between">
                          <div class="flex items-center space-x-3">
                            <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-blue-500 to-cyan-500 flex items-center justify-center shadow-lg">
                              <i :class="[getCategoryIcon(expense.category), 'text-white text-lg']"></i>
                            </div>
                            <div>
                              <CardTitle class="text-lg text-gray-900 dark:text-white group-hover:text-blue-600 dark:group-hover:text-cyan-400 transition-colors">
                                {{ expense.title }}
                              </CardTitle>
                              <CardDescription class="text-gray-600 dark:text-gray-400">
                                ID: {{ expense.id }}
                              </CardDescription>
                            </div>
                          </div>

                          <DropdownMenu>
                            <DropdownMenuTrigger as-child>
                              <Button variant="ghost" size="sm" class="h-8 w-8 p-0 opacity-0 group-hover:opacity-100 transition-opacity">
                                <MoreVertical class="h-4 w-4" />
                              </Button>
                            </DropdownMenuTrigger>
                            <DropdownMenuContent align="end">
                              <DropdownMenuItem @click="openView(expense)">
                                <i class="fas fa-eye mr-2"></i>
                                View Details
                              </DropdownMenuItem>
                              <DropdownMenuItem v-if="props.can.updateExpense" @click="openEdit(expense)">
                                <i class="fas fa-edit mr-2"></i>
                                Edit Expense
                              </DropdownMenuItem>
                              <DropdownMenuItem v-if="props.can.deleteExpense" @click="openDelete(expense)" class="text-red-600">
                                <i class="fas fa-trash mr-2"></i>
                                Delete Expense
                              </DropdownMenuItem>
                            </DropdownMenuContent>
                          </DropdownMenu>
                        </div>
                      </CardHeader>

                      <CardContent class="space-y-4">
                        <div class="grid grid-cols-2 gap-4 text-sm">
                          <div class="flex items-center space-x-2">
                            <i class="fas fa-tag text-gray-400 w-4"></i>
                            <span class="text-gray-600 dark:text-gray-400 truncate">{{ expense.category }}</span>
                          </div>
                          <div class="flex items-center space-x-2">
                            <DollarSign class="w-4 h-4 text-gray-400" />
                            <span class="text-gray-600 dark:text-gray-400 font-medium">{{ formatUGX(expense.amount) }}</span>
                          </div>
                          <div class="flex items-center space-x-2 col-span-2">
                            <Calendar class="w-4 h-4 text-gray-400" />
                            <span class="text-gray-600 dark:text-gray-400">{{ expense.date_formatted }}</span>
                          </div>
                          <div v-if="expense.description" class="flex items-start space-x-2 col-span-2">
                            <i class="fas fa-align-left text-gray-400 w-4 mt-0.5 flex-shrink-0" />
                            <span class="text-gray-600 dark:text-gray-400 line-clamp-2">{{ expense.description }}</span>
                          </div>
                        </div>

                        <div class="flex justify-between items-center pt-4 border-t border-gray-100 dark:border-gray-800">
                          <Button variant="outline" size="sm" @click="openView(expense)">
                            <i class="fas fa-eye mr-2"></i>
                            View Details
                          </Button>
                          <Badge variant="secondary" class="bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400">
                            {{ formatUGX(expense.amount) }}
                          </Badge>
                        </div>
                      </CardContent>
                    </Card>

                    <!-- Empty State -->
                    <div v-if="filteredExpenses.length === 0" class="col-span-full">
                      <Card class="border-0 shadow-xl bg-white dark:bg-gray-900">
                        <CardContent class="p-12 text-center">
                          <div class="w-24 h-24 mx-auto mb-6 rounded-full bg-gradient-to-br from-gray-100 to-gray-200 dark:from-gray-800 dark:to-gray-700 flex items-center justify-center">
                            <Receipt class="w-12 h-12 text-gray-400" />
                          </div>
                          <h3 class="text-xl font-semibold text-gray-900 dark:text-white mb-2">
                            {{ searchQuery || filterCategory !== 'all' ? 'No operational expenses found' : 'No operational expenses yet' }}
                          </h3>
                          <p class="text-gray-600 dark:text-gray-400 mb-6">
                            {{ searchQuery || filterCategory !== 'all' ? 'Try adjusting your search criteria' : 'Get started by adding your first operational expense' }}
                          </p>
                          <Button v-if="!searchQuery && filterCategory === 'all' && props.can.createExpense" @click="openCreate" class="bg-gradient-to-r from-blue-600 to-cyan-600 hover:from-blue-700 hover:to-cyan-700">
                            <Plus class="w-4 h-4 mr-2" />
                            Add First Operational Expense
                          </Button>
                          <Button v-else @click="searchQuery = ''; filterCategory = 'all'" variant="outline">
                            Clear Filters
                          </Button>
                        </CardContent>
                      </Card>
                    </div>
                  </div>
                </div>
              </TabsContent>

              <TabsContent value="list" class="mt-0">
                <div class="py-16 text-center text-gray-500 dark:text-gray-400">
                  <p>List view is currently unavailable.</p>
                </div>
              </TabsContent>
            </Tabs>
          </CardContent>
        </Card>
      </div>
    </div>

    <!-- Create Modal -->
    <Dialog :open="isCreateOpen" @update:open="(value) => isCreateOpen = value">
      <DialogContent class="max-w-md">
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
                <SelectItem v-for="category in props.categories" :key="category" :value="category">
                  {{ category }}
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
      <DialogContent class="max-w-md">
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
                <SelectItem v-for="category in props.categories" :key="category" :value="category">
                  {{ category }}
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
      <DialogContent class="max-w-md">
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
      <DialogContent class="max-w-2xl max-h-[80vh] overflow-y-auto">
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
