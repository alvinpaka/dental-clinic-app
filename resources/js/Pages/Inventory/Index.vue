<script setup lang="ts">
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/Components/ui/card';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { Badge } from '@/Components/ui/badge';
import { Button } from '@/Components/ui/button';
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogDescription, DialogFooter } from '@/Components/ui/dialog';
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuTrigger } from '@/Components/ui/dropdown-menu';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/Components/ui/select';
import { Plus, Search, MoreVertical, Package, AlertTriangle, CheckCircle, ShoppingCart, TrendingUp, TrendingDown, Receipt  } from 'lucide-vue-next';
import AppLayout from '@/Layouts/AppLayout.vue';

interface InventoryItem {
  id: number;
  name: string;
  description?: string;
  quantity: number;
  unit_price: number;
  low_stock_threshold: number;
  category?: string;
  supplier?: string;
  expiry_date?: string;
  created_at?: string;
}

interface Props {
  items: {
    data: InventoryItem[];
    links: any[];
  };
  categories: Array<{
    id: number;
    name: string;
    description: string;
    examples: string[];
  }>;
  stats?: {
    total_items: number;
    low_stock_items: number;
    total_value: number;
    out_of_stock: number;
  };
}

const props = defineProps<Props>();

const searchQuery = ref('');
const categoryFilter = ref('');
const statusFilter = ref('');

// Filtered items
const filteredItems = computed(() => {
  let items = [...props.items.data];

  // Search filter
  if (searchQuery.value) {
    items = items.filter(item =>
      item.name.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
      (item.description && item.description.toLowerCase().includes(searchQuery.value.toLowerCase())) ||
      (item.category && item.category.toLowerCase().includes(searchQuery.value.toLowerCase()))
    );
  }

  // Category filter
  if (categoryFilter.value) {
    items = items.filter(item => item.category === categoryFilter.value);
  }

  // Status filter
  if (statusFilter.value) {
    switch (statusFilter.value) {
      case 'low_stock':
        items = items.filter(item => item.quantity <= item.low_stock_threshold && item.quantity > 0);
        break;
      case 'out_of_stock':
        items = items.filter(item => item.quantity === 0);
        break;
      case 'in_stock':
        items = items.filter(item => item.quantity > item.low_stock_threshold);
        break;
    }
  }

  return items.sort((a, b) => a.name.localeCompare(b.name));
});

// Unique categories from existing items
const categories = computed(() => {
  const cats = new Set(props.items.data.map(item => item.category).filter(Boolean));
  return Array.from(cats);
});

// All available categories for forms
const availableCategories = computed(() => {
  return (props.categories || []).map(cat => cat.name);
});

// Forms
const createForm = useForm({
  name: '',
  description: '',
  quantity: 0,
  unit_price: 0,
  low_stock_threshold: 5,
  category: '',
  supplier: '',
  expiry_date: '',
});

const editForm = useForm({
  name: '',
  description: '',
  quantity: 0,
  unit_price: 0,
  low_stock_threshold: 5,
  category: '',
  supplier: '',
  expiry_date: '',
});

// Modal states
const isCreateOpen = ref(false);
const isEditOpen = ref(false);
const isDeleteOpen = ref(false);
const isViewOpen = ref(false);
const editingItem = ref<InventoryItem | null>(null);
const viewingItem = ref<InventoryItem | null>(null);

// Event handlers
const openCreate = () => {
  createForm.reset();
  isCreateOpen.value = true;
};

const openEdit = (item: InventoryItem) => {
  editingItem.value = item;
  editForm.name = item.name;
  editForm.description = item.description || '';
  editForm.quantity = item.quantity;
  editForm.unit_price = item.unit_price;
  editForm.low_stock_threshold = item.low_stock_threshold;
  editForm.category = item.category || '';
  editForm.supplier = item.supplier || '';
  editForm.expiry_date = item.expiry_date ? item.expiry_date.split('T')[0] : '';
  isEditOpen.value = true;
};

const openDelete = (item: InventoryItem) => {
  editingItem.value = item;
  isDeleteOpen.value = true;
};

const openView = (item: InventoryItem) => {
  viewingItem.value = item;
  isViewOpen.value = true;
};

const submitCreate = () => {
  if (!createForm.name || !createForm.category || createForm.quantity < 0 || createForm.unit_price < 0) {
    alert('Please fill in all required fields with valid values');
    return;
  }

  createForm.post(route('inventory.store'), {
    onSuccess: () => {
      createForm.reset();
      isCreateOpen.value = false;
    },
  });
};

const submitEdit = () => {
  if (!editForm.name || !editForm.category || editForm.quantity < 0 || editForm.unit_price < 0) {
    alert('Please fill in all required fields with valid values');
    return;
  }

  if (editingItem.value) {
    editForm.put(route('inventory.update', editingItem.value.id), {
      onSuccess: () => {
        editForm.reset();
        isEditOpen.value = false;
        editingItem.value = null;
      },
    });
  }
};

const confirmDelete = () => {
  if (editingItem.value) {
    router.delete(route('inventory.destroy', editingItem.value.id), {
      onSuccess: () => {
        isDeleteOpen.value = false;
        editingItem.value = null;
      },
    });
  }
};

// Helper functions
const getStockStatus = (item: InventoryItem) => {
  if (item.quantity === 0) return { status: 'out_of_stock', label: 'Out of Stock', color: 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400' };
  if (item.quantity <= item.low_stock_threshold) return { status: 'low_stock', label: 'Low Stock', color: 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400' };
  return { status: 'in_stock', label: 'In Stock', color: 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400' };
};

const formatCurrency = (amount: number) => {
  return new Intl.NumberFormat('en-US', {
    style: 'currency',
    currency: 'UGX',
  }).format(amount);
};

const isExpiringSoon = (expiryDate: string) => {
  if (!expiryDate) return false;
  const expiry = new Date(expiryDate);
  const now = new Date();
  const diffTime = expiry.getTime() - now.getTime();
  const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
  return diffDays <= 30 && diffDays > 0;
};

const getTotalValue = (item: InventoryItem) => {
  return item.quantity * item.unit_price;
};
</script>

<template>
  <AppLayout title="Inventory">
    <Head title="Inventory" />

    <div class="min-h-screen bg-gradient-to-br from-blue-50 via-white to-cyan-50 dark:from-gray-900 dark:via-gray-800 dark:to-blue-900">
      <div class="container mx-auto px-4 py-8">
        <!-- Header Section -->
        <div class="mb-8">
          <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div>
              <h1 class="text-4xl font-bold bg-gradient-to-r from-blue-600 to-cyan-600 bg-clip-text text-transparent mb-2">
                Inventory Management
              </h1>
              <p class="text-gray-600 dark:text-gray-400 text-lg">
                Track and manage your dental supplies and equipment
              </p>
            </div>

            <div class="flex items-center gap-3">
              <Badge variant="secondary" class="bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400">
                <Package class="w-4 h-4 mr-1" />
                {{ props.items.data.length }} Total Items
              </Badge>

              <Button @click="openCreate" class="bg-gradient-to-r from-blue-600 to-cyan-600 hover:from-blue-700 hover:to-cyan-700 text-white shadow-lg hover:shadow-xl transition-all duration-300">
                <Plus class="w-4 h-4 mr-2" />
                Add Item
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
                  <p class="text-sm font-medium text-blue-700 dark:text-blue-300 mb-1">Total Items</p>
                  <p class="text-3xl font-bold text-blue-900 dark:text-blue-100 mb-1">{{ props.stats.total_items }}</p>
                  <p class="text-xs text-blue-600 dark:text-blue-400">In inventory</p>
                </div>
                <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-blue-500 to-blue-600 flex items-center justify-center shadow-lg">
                  <Package class="w-6 h-6 text-white" />
                </div>
              </div>
            </CardContent>
          </Card>

          <Card class="border-0 shadow-xl hover:shadow-2xl transition-all duration-300 hover:-translate-y-1 bg-gradient-to-br from-amber-50 to-amber-100 dark:from-amber-900/20 dark:to-amber-800/20">
            <CardContent class="p-6">
              <div class="flex items-center justify-between">
                <div>
                  <p class="text-sm font-medium text-amber-700 dark:text-amber-300 mb-1">Low Stock</p>
                  <p class="text-3xl font-bold text-amber-900 dark:text-amber-100 mb-1">{{ props.stats.low_stock_items }}</p>
                  <p class="text-xs text-amber-600 dark:text-amber-400">Need restocking</p>
                </div>
                <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-amber-500 to-amber-600 flex items-center justify-center shadow-lg">
                  <AlertTriangle class="w-6 h-6 text-white" />
                </div>
              </div>
            </CardContent>
          </Card>

          <Card class="border-0 shadow-xl hover:shadow-2xl transition-all duration-300 hover:-translate-y-1 bg-gradient-to-br from-green-50 to-green-100 dark:from-green-900/20 dark:to-green-800/20">
            <CardContent class="p-6">
              <div class="flex items-center justify-between">
                <div>
                  <p class="text-sm font-medium text-green-700 dark:text-green-300 mb-1">Total Value</p>
                  <p class="text-3xl font-bold text-green-900 dark:text-green-100 mb-1">{{ formatCurrency(props.stats.total_value) }}</p>
                  <p class="text-xs text-green-600 dark:text-green-400">Inventory value</p>
                </div>
                <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-green-500 to-green-600 flex items-center justify-center shadow-lg">
                  <Receipt class="w-6 h-6 text-white" />
                </div>
              </div>
            </CardContent>
          </Card>

          <Card class="border-0 shadow-xl hover:shadow-2xl transition-all duration-300 hover:-translate-y-1 bg-gradient-to-br from-red-50 to-red-100 dark:from-red-900/20 dark:to-red-800/20">
            <CardContent class="p-6">
              <div class="flex items-center justify-between">
                <div>
                  <p class="text-sm font-medium text-red-700 dark:text-red-300 mb-1">Out of Stock</p>
                  <p class="text-3xl font-bold text-red-900 dark:text-red-100 mb-1">{{ props.stats.out_of_stock }}</p>
                  <p class="text-xs text-red-600 dark:text-red-400">Items depleted</p>
                </div>
                <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-red-500 to-red-600 flex items-center justify-center shadow-lg">
                  <TrendingDown class="w-6 h-6 text-white" />
                </div>
              </div>
            </CardContent>
          </Card>
        </div>

        <!-- Search and Filters -->
        <Card class="border-0 shadow-xl bg-white dark:bg-gray-900 mb-8">
          <CardContent class="p-6">
            <div class="flex flex-col md:flex-row gap-4 items-center">
              <div class="relative flex-1">
                <Search class="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400 w-4 h-4" />
                <Input
                  v-model="searchQuery"
                  placeholder="Search inventory by name, description, or category..."
                  class="pl-10 h-12 bg-gray-50 dark:bg-gray-800 border-gray-200 dark:border-gray-700"
                />
              </div>

              <div class="flex items-center gap-2">
                <Select v-model="categoryFilter">
                  <SelectTrigger class="w-40 h-12">
                    <SelectValue placeholder="All Categories" />
                  </SelectTrigger>
                  <SelectContent v-if="props.categories && props.categories.length > 0">
                    <SelectItem v-for="category in props.categories" :key="category.id" :value="category.name">
                      <div class="flex flex-col">
                        <span class="font-medium">{{ category.name }}</span>
                        <span class="text-xs text-gray-500">{{ category.description }}</span>
                      </div>
                    </SelectItem>
                  </SelectContent>
                </Select>

                <Select v-model="statusFilter">
                  <SelectTrigger class="w-36 h-12">
                    <SelectValue placeholder="All Items" />
                  </SelectTrigger>
                  <SelectContent>
                    <SelectItem value="in_stock">In Stock</SelectItem>
                    <SelectItem value="low_stock">Low Stock</SelectItem>
                    <SelectItem value="out_of_stock">Out of Stock</SelectItem>
                  </SelectContent>
                </Select>
              </div>
            </div>
          </CardContent>
        </Card>

        <!-- Inventory Grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
          <Card
            v-for="(item, index) in filteredItems"
            :key="item.id"
            :class="[
              'border-0 shadow-xl hover:shadow-2xl transition-all duration-300 hover:-translate-y-2 bg-white dark:bg-gray-900 group',
              getStockStatus(item).status === 'out_of_stock' ? 'ring-2 ring-red-200 dark:ring-red-800' : '',
              getStockStatus(item).status === 'low_stock' ? 'ring-2 ring-amber-200 dark:ring-amber-800' : ''
            ]"
          >
            <CardHeader class="pb-4">
              <div class="flex items-start justify-between">
                <div class="flex items-center space-x-3">
                  <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-blue-500 to-cyan-500 flex items-center justify-center shadow-lg">
                    <Package class="w-6 h-6 text-white" />
                  </div>
                  <div>
                    <CardTitle class="text-lg text-gray-900 dark:text-white group-hover:text-blue-600 dark:group-hover:text-cyan-400 transition-colors line-clamp-2">
                      {{ item.name }}
                    </CardTitle>
                    <CardDescription class="text-gray-600 dark:text-gray-400">
                      ID: {{ item.id }}
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
                    <DropdownMenuItem @click="openView(item)">
                      <i class="fas fa-eye mr-2"></i>
                      View Details
                    </DropdownMenuItem>
                    <DropdownMenuItem @click="openEdit(item)">
                      <i class="fas fa-edit mr-2"></i>
                      Edit Item
                    </DropdownMenuItem>
                    <DropdownMenuItem @click="openDelete(item)" class="text-red-600">
                      <i class="fas fa-trash mr-2"></i>
                      Delete Item
                    </DropdownMenuItem>
                  </DropdownMenuContent>
                </DropdownMenu>
              </div>
            </CardHeader>

            <CardContent class="space-y-4">
              <div v-if="item.description" class="text-sm text-gray-600 dark:text-gray-400 line-clamp-2">
                {{ item.description }}
              </div>

              <div class="grid grid-cols-2 gap-4 text-sm">
                <div class="flex items-center space-x-2">
                  <i class="fas fa-hashtag text-gray-400 w-4"></i>
                  <span class="text-gray-600 dark:text-gray-400">{{ item.quantity }} in stock</span>
                </div>
                <div class="flex items-center space-x-2">
                  <Receipt class="w-4 h-4 text-gray-400" />
                  <span class="text-gray-600 dark:text-gray-400">{{ formatCurrency(item.unit_price) }}</span>
                </div>
                <div class="flex items-center space-x-2">
                  <i class="fas fa-chart-line text-gray-400 w-4"></i>
                  <span class="text-gray-600 dark:text-gray-400">Total: {{ formatCurrency(getTotalValue(item)) }}</span>
                </div>
                <div v-if="item.expiry_date" class="flex items-center space-x-2">
                  <i :class="['fas w-4', isExpiringSoon(item.expiry_date) ? 'fa-exclamation-triangle text-amber-500' : 'fa-calendar text-gray-400']"></i>
                  <span :class="['text-sm', isExpiringSoon(item.expiry_date) ? 'text-amber-600 dark:text-amber-400' : 'text-gray-600 dark:text-gray-400']">
                    Expires {{ new Date(item.expiry_date).toLocaleDateString() }}
                  </span>
                </div>
              </div>

              <div class="flex items-center justify-between">
                <Badge :class="getStockStatus(item).color" variant="secondary">
                  <i :class="['fas mr-1', getStockStatus(item).status === 'out_of_stock' ? 'fa-times-circle' : getStockStatus(item).status === 'low_stock' ? 'fa-exclamation-triangle' : 'fa-check-circle']"></i>
                  {{ getStockStatus(item).label }}
                </Badge>

                <span class="text-xs text-gray-500 dark:text-gray-400">
                  Low: {{ item.low_stock_threshold }}
                </span>
              </div>

              <div class="flex justify-between items-center pt-4 border-t border-gray-100 dark:border-gray-800">
                <Button variant="outline" size="sm" @click="openView(item)">
                  View Details
                </Button>
                <Button size="sm" @click="openEdit(item)">
                  <i class="fas fa-edit mr-2"></i>
                  Edit
                </Button>
              </div>
            </CardContent>
          </Card>

          <!-- Empty State -->
          <div v-if="filteredItems.length === 0" class="col-span-full">
            <Card class="border-0 shadow-xl bg-white dark:bg-gray-900">
              <CardContent class="p-12 text-center">
                <div class="w-24 h-24 mx-auto mb-6 rounded-full bg-gradient-to-br from-gray-100 to-gray-200 dark:from-gray-800 dark:to-gray-700 flex items-center justify-center">
                  <Package class="w-12 h-12 text-gray-400" />
                </div>
                <h3 class="text-xl font-semibold text-gray-900 dark:text-white mb-2">
                  {{ searchQuery || categoryFilter || statusFilter ? 'No items found' : 'No inventory items yet' }}
                </h3>
                <p class="text-gray-600 dark:text-gray-400 mb-6">
                  {{ searchQuery || categoryFilter || statusFilter ? 'Try adjusting your search criteria' : 'Get started by adding your first inventory item' }}
                </p>
                <Button v-if="!searchQuery && !categoryFilter && !statusFilter" @click="openCreate" class="bg-gradient-to-r from-blue-600 to-cyan-600 hover:from-blue-700 hover:to-cyan-700">
                  <Plus class="w-4 h-4 mr-2" />
                  Add First Item
                </Button>
                <Button v-else @click="searchQuery = ''; categoryFilter = ''; statusFilter = ''" variant="outline">
                  Clear Filters
                </Button>
              </CardContent>
            </Card>
          </div>
        </div>
      </div>
    </div>

    <!-- Create Item Modal -->
    <Dialog :open="isCreateOpen" @update:open="(value) => isCreateOpen = value">
      <DialogContent class="max-w-md">
        <DialogHeader>
          <DialogTitle class="text-2xl font-bold bg-gradient-to-r from-blue-600 to-cyan-600 bg-clip-text text-transparent">
            Add Inventory Item
          </DialogTitle>
          <DialogDescription class="text-gray-600 dark:text-gray-400">
            Add a new item to your inventory.
          </DialogDescription>
        </DialogHeader>

        <form @submit.prevent="submitCreate" class="space-y-6">
          <div class="space-y-2">
            <Label for="name" class="text-gray-700 dark:text-gray-300">Item Name</Label>
            <Input
              id="name"
              v-model="createForm.name"
              placeholder="Enter item name"
              class="h-12 bg-gray-50 dark:bg-gray-800 border-gray-200 dark:border-gray-700"
              required
            />
          </div>

          <div class="space-y-2">
            <Label for="description" class="text-gray-700 dark:text-gray-300">Description (Optional)</Label>
            <Input
              id="description"
              v-model="createForm.description"
              placeholder="Item description"
              class="h-12 bg-gray-50 dark:bg-gray-800 border-gray-200 dark:border-gray-700"
            />
          </div>

          <div class="grid grid-cols-2 gap-4">
            <div class="space-y-2">
              <Label for="quantity" class="text-gray-700 dark:text-gray-300">Quantity</Label>
              <Input
                id="quantity"
                type="number"
                v-model="createForm.quantity"
                placeholder="0"
                min="0"
                class="h-12 bg-gray-50 dark:bg-gray-800 border-gray-200 dark:border-gray-700"
                required
              />
            </div>

            <div class="space-y-2">
              <Label for="unit_price" class="text-gray-700 dark:text-gray-300">Unit Price (UGX)</Label>
              <Input
                id="unit_price"
                type="number"
                v-model="createForm.unit_price"
                placeholder="0.00"
                step="0.01"
                min="0"
                class="h-12 bg-gray-50 dark:bg-gray-800 border-gray-200 dark:border-gray-700"
                required
              />
            </div>
          </div>

          <div class="grid grid-cols-2 gap-4">
            <div class="space-y-2">
              <Label for="low_stock_threshold" class="text-gray-700 dark:text-gray-300">Low Stock Threshold</Label>
              <Input
                id="low_stock_threshold"
                type="number"
                v-model="createForm.low_stock_threshold"
                placeholder="5"
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
                <SelectContent v-if="props.categories && props.categories.length > 0">
                  <SelectItem v-for="category in props.categories" :key="category.id" :value="category.name">
                    <div class="flex flex-col">
                      <span class="font-medium">{{ category.name }}</span>
                      <span class="text-xs text-gray-500">{{ category.description }}</span>
                    </div>
                  </SelectItem>
                </SelectContent>
              </Select>
            </div>
          </div>

          <div class="grid grid-cols-2 gap-4">
            <div class="space-y-2">
              <Label for="supplier" class="text-gray-700 dark:text-gray-300">Supplier (Optional)</Label>
              <Input
                id="supplier"
                v-model="createForm.supplier"
                placeholder="Supplier name"
                class="h-12 bg-gray-50 dark:bg-gray-800 border-gray-200 dark:border-gray-700"
              />
            </div>

            <div class="space-y-2">
              <Label for="expiry_date" class="text-gray-700 dark:text-gray-300">Expiry Date (Optional)</Label>
              <Input
                id="expiry_date"
                type="date"
                v-model="createForm.expiry_date"
                class="h-12 bg-gray-50 dark:bg-gray-800 border-gray-200 dark:border-gray-700"
              />
            </div>
          </div>

          <DialogFooter class="gap-2">
            <Button type="button" variant="outline" @click="isCreateOpen = false">
              Cancel
            </Button>
            <Button
              type="submit"
              :disabled="createForm.processing"
              class="bg-gradient-to-r from-blue-600 to-cyan-600 hover:from-blue-700 hover:to-cyan-700"
            >
              <i v-if="createForm.processing" class="fas fa-spinner fa-spin mr-2"></i>
              <i v-else class="fas fa-plus mr-2"></i>
              {{ createForm.processing ? 'Adding...' : 'Add Item' }}
            </Button>
          </DialogFooter>
        </form>
      </DialogContent>
    </Dialog>

    <!-- Edit Item Modal -->
    <Dialog :open="isEditOpen" @update:open="(value) => isEditOpen = value">
      <DialogContent class="max-w-md">
        <DialogHeader>
          <DialogTitle class="text-2xl font-bold bg-gradient-to-r from-blue-600 to-cyan-600 bg-clip-text text-transparent">
            Edit Inventory Item
          </DialogTitle>
          <DialogDescription class="text-gray-600 dark:text-gray-400">
            Update the item information below.
          </DialogDescription>
        </DialogHeader>

        <form @submit.prevent="submitEdit" class="space-y-6">
          <div class="space-y-2">
            <Label for="edit-name" class="text-gray-700 dark:text-gray-300">Item Name</Label>
            <Input
              id="edit-name"
              v-model="editForm.name"
              placeholder="Enter item name"
              class="h-12 bg-gray-50 dark:bg-gray-800 border-gray-200 dark:border-gray-700"
              required
            />
          </div>

          <div class="space-y-2">
            <Label for="edit-description" class="text-gray-700 dark:text-gray-300">Description (Optional)</Label>
            <Input
              id="edit-description"
              v-model="editForm.description"
              placeholder="Item description"
              class="h-12 bg-gray-50 dark:bg-gray-800 border-gray-200 dark:border-gray-700"
            />
          </div>

          <div class="grid grid-cols-2 gap-4">
            <div class="space-y-2">
              <Label for="edit-quantity" class="text-gray-700 dark:text-gray-300">Quantity</Label>
              <Input
                id="edit-quantity"
                type="number"
                v-model="editForm.quantity"
                placeholder="0"
                min="0"
                class="h-12 bg-gray-50 dark:bg-gray-800 border-gray-200 dark:border-gray-700"
                required
              />
            </div>

            <div class="space-y-2">
              <Label for="edit-unit_price" class="text-gray-700 dark:text-gray-300">Unit Price (UGX)</Label>
              <Input
                id="edit-unit_price"
                type="number"
                v-model="editForm.unit_price"
                placeholder="0.00"
                step="0.01"
                min="0"
                class="h-12 bg-gray-50 dark:bg-gray-800 border-gray-200 dark:border-gray-700"
                required
              />
            </div>
          </div>

          <div class="grid grid-cols-2 gap-4">
            <div class="space-y-2">
              <Label for="edit-low_stock_threshold" class="text-gray-700 dark:text-gray-300">Low Stock Threshold</Label>
              <Input
                id="edit-low_stock_threshold"
                type="number"
                v-model="editForm.low_stock_threshold"
                placeholder="5"
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
                <SelectContent v-if="props.categories && props.categories.length > 0">
                  <SelectItem v-for="category in props.categories" :key="category.id" :value="category.name">
                    <div class="flex flex-col">
                      <span class="font-medium">{{ category.name }}</span>
                      <span class="text-xs text-gray-500">{{ category.description }}</span>
                    </div>
                  </SelectItem>
                </SelectContent>
              </Select>
            </div>
          </div>

          <div class="grid grid-cols-2 gap-4">
            <div class="space-y-2">
              <Label for="edit-supplier" class="text-gray-700 dark:text-gray-300">Supplier (Optional)</Label>
              <Input
                id="edit-supplier"
                v-model="editForm.supplier"
                placeholder="Supplier name"
                class="h-12 bg-gray-50 dark:bg-gray-800 border-gray-200 dark:border-gray-700"
              />
            </div>

            <div class="space-y-2">
              <Label for="edit-expiry_date" class="text-gray-700 dark:text-gray-300">Expiry Date (Optional)</Label>
              <Input
                id="edit-expiry_date"
                type="date"
                v-model="editForm.expiry_date"
                class="h-12 bg-gray-50 dark:bg-gray-800 border-gray-200 dark:border-gray-700"
              />
            </div>
          </div>

          <DialogFooter class="gap-2">
            <Button type="button" variant="outline" @click="isEditOpen = false">
              Cancel
            </Button>
            <Button
              type="submit"
              :disabled="editForm.processing"
              class="bg-gradient-to-r from-blue-600 to-cyan-600 hover:from-blue-700 hover:to-cyan-700"
            >
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
          <DialogTitle class="text-xl font-bold text-red-600">Delete Inventory Item</DialogTitle>
          <DialogDescription class="text-gray-600 dark:text-gray-400">
            This action cannot be undone. This will permanently delete the inventory item.
          </DialogDescription>
        </DialogHeader>

        <div class="py-4">
          <div class="flex items-center space-x-3 p-4 bg-red-50 dark:bg-red-900/20 rounded-lg border border-red-200 dark:border-red-800">
            <i class="fas fa-exclamation-triangle text-red-600 text-xl"></i>
            <div>
              <p class="font-medium text-red-800 dark:text-red-200">Delete "{{ editingItem?.name }}"?</p>
              <p class="text-sm text-red-600 dark:text-red-400">This will remove the item from inventory permanently.</p>
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
            Delete Item
          </Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>

    <!-- View Item Modal -->
    <Dialog :open="isViewOpen" @update:open="(value) => isViewOpen = value">
      <DialogContent class="max-w-2xl">
        <DialogHeader>
          <DialogTitle class="text-2xl font-bold bg-gradient-to-r from-blue-600 to-cyan-600 bg-clip-text text-transparent flex items-center">
            <Package class="w-6 h-6 mr-3" />
            {{ viewingItem?.name }}
          </DialogTitle>
          <DialogDescription class="text-gray-600 dark:text-gray-400">
            Item ID: {{ viewingItem?.id }}
          </DialogDescription>
        </DialogHeader>

        <div v-if="viewingItem" class="space-y-6">
          <!-- Basic Information -->
          <Card class="border-0 shadow-lg">
            <CardHeader class="pb-3">
              <CardTitle class="text-lg flex items-center">
                <i class="fas fa-info-circle mr-2 text-blue-600"></i>
                Basic Information
              </CardTitle>
            </CardHeader>
            <CardContent class="space-y-4">
              <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                  <Label class="text-sm font-medium text-gray-500 dark:text-gray-400">Item Name</Label>
                  <p class="text-lg font-semibold text-gray-900 dark:text-white">{{ viewingItem.name }}</p>
                </div>
                <div>
                  <Label class="text-sm font-medium text-gray-500 dark:text-gray-400">Category</Label>
                  <p class="text-lg font-semibold text-gray-900 dark:text-white">{{ viewingItem.category || 'N/A' }}</p>
                </div>
              </div>
              <div v-if="viewingItem.description">
                <Label class="text-sm font-medium text-gray-500 dark:text-gray-400">Description</Label>
                <p class="text-gray-700 dark:text-gray-300 mt-1">{{ viewingItem.description }}</p>
              </div>
            </CardContent>
          </Card>

          <!-- Inventory Details -->
          <Card class="border-0 shadow-lg">
            <CardHeader class="pb-3">
              <CardTitle class="text-lg flex items-center">
                <Receipt class="w-5 h-5 mr-2 text-green-600"></Receipt>
                Inventory Details
              </CardTitle>
            </CardHeader>
            <CardContent class="space-y-4">
              <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="text-center p-4 bg-blue-50 dark:bg-blue-900/20 rounded-lg">
                  <i class="fas fa-hashtag text-2xl text-blue-600 mb-2"></i>
                  <p class="text-sm text-blue-700 dark:text-blue-300">Current Stock</p>
                  <p class="text-2xl font-bold text-blue-900 dark:text-blue-100">{{ viewingItem.quantity }}</p>
                </div>
                <div class="text-center p-4 bg-green-50 dark:bg-green-900/20 rounded-lg">
                  <Receipt class="w-6 h-6 text-green-600 mb-2"></Receipt>
                  <p class="text-sm text-green-700 dark:text-green-300">Unit Price</p>
                  <p class="text-2xl font-bold text-green-900 dark:text-green-100">{{ formatCurrency(viewingItem.unit_price) }}</p>
                </div>
                <div class="text-center p-4 bg-purple-50 dark:bg-purple-900/20 rounded-lg">
                  <i class="fas fa-chart-line text-2xl text-purple-600 mb-2"></i>
                  <p class="text-sm text-purple-700 dark:text-purple-300">Total Value</p>
                  <p class="text-2xl font-bold text-purple-900 dark:text-purple-100">{{ formatCurrency(getTotalValue(viewingItem)) }}</p>
                </div>
              </div>
              <div class="flex items-center justify-between pt-4 border-t border-gray-100 dark:border-gray-800">
                <div>
                  <Label class="text-sm font-medium text-gray-500 dark:text-gray-400">Low Stock Threshold</Label>
                  <p class="text-lg font-semibold text-gray-900 dark:text-white">{{ viewingItem.low_stock_threshold }}</p>
                </div>
                <Badge :class="getStockStatus(viewingItem).color" variant="secondary">
                  <i :class="['fas mr-1', getStockStatus(viewingItem).status === 'out_of_stock' ? 'fa-times-circle' : getStockStatus(viewingItem).status === 'low_stock' ? 'fa-exclamation-triangle' : 'fa-check-circle']"></i>
                  {{ getStockStatus(viewingItem).label }}
                </Badge>
              </div>
            </CardContent>
          </Card>

          <!-- Additional Information -->
          <Card class="border-0 shadow-lg">
            <CardHeader class="pb-3">
              <CardTitle class="text-lg flex items-center">
                <i class="fas fa-plus-circle mr-2 text-indigo-600"></i>
                Additional Information
              </CardTitle>
            </CardHeader>
            <CardContent class="space-y-4">
              <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div v-if="viewingItem.supplier">
                  <Label class="text-sm font-medium text-gray-500 dark:text-gray-400">Supplier</Label>
                  <p class="text-gray-900 dark:text-white">{{ viewingItem.supplier }}</p>
                </div>
                <div v-if="viewingItem.expiry_date">
                  <Label class="text-sm font-medium text-gray-500 dark:text-gray-400">Expiry Date</Label>
                  <p :class="['font-medium', isExpiringSoon(viewingItem.expiry_date) ? 'text-amber-600 dark:text-amber-400' : 'text-gray-900 dark:text-white']">
                    {{ new Date(viewingItem.expiry_date).toLocaleDateString() }}
                    <span v-if="isExpiringSoon(viewingItem.expiry_date)" class="text-xs text-amber-600 dark:text-amber-400 ml-2">
                      (Expiring Soon)
                    </span>
                  </p>
                </div>
              </div>
              <div v-if="viewingItem.created_at">
                <Label class="text-sm font-medium text-gray-500 dark:text-gray-400">Date Added</Label>
                <p class="text-gray-900 dark:text-white">{{ new Date(viewingItem.created_at).toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' }) }}</p>
              </div>
            </CardContent>
          </Card>
        </div>

        <DialogFooter class="gap-2">
          <Button type="button" variant="outline" @click="isViewOpen = false">
            Close
          </Button>
          <Button @click="openEdit(viewingItem!); isViewOpen = false" class="bg-gradient-to-r from-blue-600 to-cyan-600 hover:from-blue-700 hover:to-cyan-700">
            <i class="fas fa-edit mr-2"></i>
            Edit Item
          </Button>
        </DialogFooter>
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