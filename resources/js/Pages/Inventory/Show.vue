<script setup>
import { ref, computed } from 'vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import { route } from 'ziggy-js';
import AppLayout from '@/Layouts/AppLayout.vue';
import { Button } from '@/Components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/Components/ui/card';
import { Label } from '@/Components/ui/label';
import { Input } from '@/Components/ui/input';
import { Textarea } from '@/Components/ui/textarea';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/Components/ui/dialog';
import { format } from 'date-fns';
import { ArrowLeft } from 'lucide-vue-next';

const props = defineProps({
  item: {
    type: Object,
    required: true
  },
  recentTransactions: {
    type: Array,
    default: () => []
  },
  stats: {
    type: Object,
    required: true
  }
});

// Modals
const showRestockModal = ref(false);
const showUseItemModal = ref(false);

// Forms
const restockForm = useForm({
  quantity: 1,
  notes: ''
});

const useItemForm = useForm({
  quantity: 1,
  notes: ''
});

// Format currency
const formatCurrency = (amount) => {
  return `UGX ${parseInt(amount).toLocaleString()}`;
};

// Format date
const formatDate = (dateString) => {
  if (!dateString) return 'N/A';
  return format(new Date(dateString), 'MMM d, yyyy');
};

// Format date and time
const formatDateTime = (dateString) => {
  if (!dateString) return 'N/A';
  return format(new Date(dateString), 'MMM d, yyyy h:mm a');
};

// Check if item is expiring soon (within 30 days)
const isExpiringSoon = (expiryDate) => {
  if (!expiryDate) return false;
  const today = new Date();
  const expiry = new Date(expiryDate);
  const diffTime = expiry.getTime() - today.getTime();
  const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
  return diffDays > 0 && diffDays <= 30;
};

// Get stock status text
const getStockStatusText = (item) => {
  if (item.quantity <= 0) {
    return 'Out of Stock';
  } else if (item.quantity <= item.low_stock_threshold) {
    return 'Low Stock';
  } else {
    return 'In Stock';
  }
};

// Format transaction type
const formatTransactionType = (type) => {
  const types = {
    'restock': 'Restocked',
    'usage': 'Used',
    'adjustment_increase': 'Adjusted (Increased)',
    'adjustment_decrease': 'Adjusted (Decreased)'
  };
  return types[type] || type;
};

// Open restock modal
const openRestockModal = () => {
  restockForm.reset();
  restockForm.quantity = 1;
  showRestockModal.value = true;
};

// Open use item modal
const openUseItemModal = () => {
  useItemForm.reset();
  useItemForm.quantity = 1;
  showUseItemModal.value = true;
};

// Submit restock
const submitRestock = () => {
  restockForm.post(route('inventory.restock', props.item.id), {
    preserveScroll: true,
    onSuccess: () => {
      showRestockModal.value = false;
      // Refresh the page to get updated data
      router.visit(route('inventory.show', props.item.id), {
        only: ['item', 'recentTransactions', 'stats'],
        preserveState: true,
        preserveScroll: true
      });
    }
  });
};

// Submit use item
const submitUseItem = () => {
  if (props.item.quantity <= 0) return;
  
  useItemForm.post(route('inventory.use', props.item.id), {
    preserveScroll: true,
    onSuccess: () => {
      showUseItemModal.value = false;
      // Refresh the page to get updated data
      router.visit(route('inventory.show', props.item.id), {
        only: ['item', 'recentTransactions', 'stats'],
        preserveState: true,
        preserveScroll: true
      });
    }
  });
};

// Check permissions
const can = {
  update: computed(() => true), // You should replace this with your actual permission check
  delete: computed(() => true)  // You should replace this with your actual permission check
};
</script>

<template>
  <AppLayout title="Inventory Item Details">
    <template #header>
      <div class="flex items-center justify-between">
        <div class="flex items-center space-x-4">
          <Button 
            variant="ghost" 
            size="sm"
            class="flex items-center gap-2"
            @click="$inertia.visit(route('inventory.index'))"
          >
            <ArrowLeft class="h-4 w-4" />
            Back to Inventory
          </Button>
          <h2 class="text-2xl font-semibold text-gray-900 dark:text-white">
            {{ item.name }}
          </h2>
        </div>
        <div class="flex space-x-2">
          <Button 
            v-if="can.update"
            @click="$inertia.visit(route('inventory.edit', item.id))"
            class="inline-flex items-center px-4 py-2 text-sm font-medium text-white bg-blue-600 border border-transparent rounded-md shadow-sm hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
          >
            Edit Item
          </Button>
        </div>
      </div>
    </template>

    <div class="py-6">
      <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
        <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
          <!-- Main Content -->
          <div class="lg:col-span-2 space-y-6">
            <!-- Item Details Card -->
            <div class="bg-white shadow sm:rounded-lg dark:bg-gray-800">
              <div class="px-4 py-5 sm:px-6 border-b border-gray-200 dark:border-gray-700">
                <h3 class="text-lg font-medium leading-6 text-gray-900 dark:text-white">
                  Item Information
                </h3>
              </div>
              <div class="px-4 py-5 sm:p-6">
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                  <div>
                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">
                      Name
                    </dt>
                    <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100">
                      {{ item.name }}
                    </dd>
                  </div>
                  <div>
                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">
                      Category
                    </dt>
                    <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100">
                      {{ item.category || 'N/A' }}
                    </dd>
                  </div>
                  <div>
                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">
                      Current Stock
                    </dt>
                    <dd class="mt-1">
                      <span 
                        :class="{
                          'px-2 inline-flex text-xs leading-5 font-semibold rounded-full': true,
                          'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200': item.quantity <= 0,
                          'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200': item.quantity > 0 && item.quantity <= item.low_stock_threshold,
                          'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200': item.quantity > item.low_stock_threshold
                        }"
                      >
                        {{ item.quantity }} {{ item.unit || 'units' }}
                      </span>
                    </dd>
                  </div>
                  <div>
                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">
                      Low Stock Threshold
                    </dt>
                    <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100">
                      {{ item.low_stock_threshold }} {{ item.unit || 'units' }}
                    </dd>
                  </div>
                  <div>
                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">
                      Unit Price
                    </dt>
                    <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100">
                      {{ formatCurrency(item.unit_price) }}
                    </dd>
                  </div>
                  <div>
                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">
                      Total Value
                    </dt>
                    <dd class="mt-1 text-sm font-medium text-gray-900 dark:text-white">
                      {{ formatCurrency(item.quantity * item.unit_price) }}
                    </dd>
                  </div>
                  <div class="sm:col-span-2">
                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">
                      Description
                    </dt>
                    <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100">
                      {{ item.description || 'No description provided.' }}
                    </dd>
                  </div>
                  <div v-if="item.supplier">
                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">
                      Supplier
                    </dt>
                    <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100">
                      {{ item.supplier }}
                    </dd>
                  </div>
                  <div v-if="item.expiry_date">
                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">
                      Expiry Date
                    </dt>
                    <dd 
                      class="mt-1 text-sm"
                      :class="isExpiringSoon(item.expiry_date) ? 'text-red-600 dark:text-red-400 font-medium' : 'text-gray-900 dark:text-gray-100'"
                    >
                      {{ formatDate(item.expiry_date) }}
                      <span 
                        v-if="isExpiringSoon(item.expiry_date)" 
                        class="ml-2 inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200"
                      >
                        Expiring Soon
                      </span>
                    </dd>
                  </div>
                </div>
              </div>
            </div>

            <!-- Quick Actions Card -->
            <div class="bg-white shadow sm:rounded-lg dark:bg-gray-800">
              <div class="px-4 py-5 sm:px-6 border-b border-gray-200 dark:border-gray-700">
                <h3 class="text-lg font-medium leading-6 text-gray-900 dark:text-white">
                  Quick Actions
                </h3>
              </div>
              <div class="p-6">
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                  <button
                    @click="openRestockModal"
                    class="flex items-center justify-center px-4 py-3 text-sm font-medium text-white bg-blue-600 border border-transparent rounded-md shadow-sm hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
                  >
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                    </svg>
                    Restock Item
                  </button>
                  <button
                    @click="openUseItemModal"
                    :disabled="item.quantity <= 0"
                    :class="{
                      'flex items-center justify-center px-4 py-3 text-sm font-medium text-white border border-transparent rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-offset-2': true,
                      'bg-green-600 hover:bg-green-700 focus:ring-green-500': item.quantity > 0,
                      'bg-gray-400 cursor-not-allowed': item.quantity <= 0
                    }"
                  >
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                    </svg>
                    Use Item
                  </button>
                </div>
              </div>
            </div>

            <!-- Recent Activity -->
            <div class="bg-white shadow sm:rounded-lg dark:bg-gray-800">
              <div class="px-4 py-5 sm:px-6 border-b border-gray-200 dark:border-gray-700">
                <div class="flex items-center justify-between">
                  <h3 class="text-lg font-medium leading-6 text-gray-900 dark:text-white">
                    Recent Activity
                  </h3>
                  <span class="text-sm text-gray-500 dark:text-gray-400">
                    {{ stats.total_transactions }} total transactions
                  </span>
                </div>
              </div>
              <div class="divide-y divide-gray-200 dark:divide-gray-700">
                <div v-if="recentTransactions.length > 0">
                  <div 
                    v-for="transaction in recentTransactions" 
                    :key="transaction.id"
                    class="px-4 py-4 sm:px-6 hover:bg-gray-50 dark:hover:bg-gray-700/50"
                  >
                    <div class="flex items-center">
                      <div class="flex-shrink-0">
                        <span 
                          class="flex items-center justify-center w-10 h-10 rounded-full"
                          :class="{
                            'bg-green-100 text-green-600 dark:bg-green-900/30 dark:text-green-400': transaction.type === 'restock',
                            'bg-blue-100 text-blue-600 dark:bg-blue-900/30 dark:text-blue-400': transaction.type === 'usage',
                            'bg-yellow-100 text-yellow-600 dark:bg-yellow-900/30 dark:text-yellow-400': transaction.type === 'adjustment_increase' || transaction.type === 'adjustment_decrease'
                          }"
                        >
                          <svg 
                            v-if="transaction.type === 'restock'" 
                            class="w-5 h-5" 
                            fill="none" 
                            stroke="currentColor" 
                            viewBox="0 0 24 24" 
                            xmlns="http://www.w3.org/2000/svg"
                          >
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                          </svg>
                          <svg 
                            v-else-if="transaction.type === 'usage'" 
                            class="w-5 h-5" 
                            fill="none" 
                            stroke="currentColor" 
                            viewBox="0 0 24 24" 
                            xmlns="http://www.w3.org/2000/svg"
                          >
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4"></path>
                          </svg>
                          <svg 
                            v-else 
                            class="w-5 h-5" 
                            fill="none" 
                            stroke="currentColor" 
                            viewBox="0 0 24 24" 
                            xmlns="http://www.w3.org/2000/svg"
                          >
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                          </svg>
                        </span>
                      </div>
                      <div class="flex-1 min-w-0 ml-4">
                        <div class="flex items-center justify-between text-sm">
                          <p class="font-medium text-gray-900 truncate dark:text-white">
                            {{ formatTransactionType(transaction.type) }}
                          </p>
                          <p 
                            class="ml-2 font-medium"
                            :class="{
                              'text-green-600 dark:text-green-400': transaction.type === 'restock' || transaction.type === 'adjustment_increase',
                              'text-red-600 dark:text-red-400': transaction.type === 'usage' || transaction.type === 'adjustment_decrease'
                            }"
                          >
                            {{ transaction.type === 'restock' || transaction.type === 'adjustment_increase' ? '+' : '-' }}{{ transaction.quantity }} {{ item.unit || 'units' }}
                          </p>
                        </div>
                        <div class="flex justify-between mt-1">
                          <p class="text-sm text-gray-500 dark:text-gray-400">
                            {{ formatDate(transaction.created_at) }}
                          </p>
                          <p class="text-sm text-gray-500 dark:text-gray-400">
                            {{ transaction.notes || 'No notes' }}
                          </p>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
                <div v-else class="px-4 py-12 text-center">
                  <svg class="w-12 h-12 mx-auto text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                  </svg>
                  <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-white">No activity yet</h3>
                  <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Get started by restocking or using this item.</p>
                </div>
              </div>
            </div>
          </div>

          <!-- Sidebar -->
          <div class="space-y-6">
            <!-- Stock Status -->
            <div class="overflow-hidden bg-white shadow sm:rounded-lg dark:bg-gray-800">
              <div class="px-4 py-5 sm:p-6">
                <h3 class="text-lg font-medium leading-6 text-gray-900 dark:text-white">
                  Stock Status
                </h3>
                <div class="mt-2 space-y-4">
                  <!-- First Row: Stock Status -->
                  <div class="sm:flex sm:items-center">
                    <div class="flex items-center">
                      <span 
                        class="flex items-center justify-center w-12 h-12 rounded-full"
                        :class="{
                          'bg-red-100 text-red-600 dark:bg-red-900/30 dark:text-red-400': item.quantity <= 0,
                          'bg-yellow-100 text-yellow-600 dark:bg-yellow-900/30 dark:text-yellow-400': item.quantity > 0 && item.quantity <= item.low_stock_threshold,
                          'bg-green-100 text-green-600 dark:bg-green-900/30 dark:text-green-400': item.quantity > item.low_stock_threshold
                        }"
                      >
                        <svg 
                          v-if="item.quantity <= 0" 
                          class="w-6 h-6" 
                          fill="none" 
                          stroke="currentColor" 
                          viewBox="0 0 24 24" 
                          xmlns="http://www.w3.org/2000/svg"
                        >
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        <svg 
                          v-else-if="item.quantity <= item.low_stock_threshold" 
                          class="w-6 h-6" 
                          fill="none" 
                          stroke="currentColor" 
                          viewBox="0 0 24 24" 
                          xmlns="http://www.w3.org/2000/svg"
                        >
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                        </svg>
                        <svg 
                          v-else 
                          class="w-6 h-6" 
                          fill="none" 
                          stroke="currentColor" 
                          viewBox="0 0 24 24" 
                          xmlns="http://www.w3.org/2000/svg"
                        >
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                        </svg>
                      </span>
                    </div>
                    <div class="mt-3 sm:mt-0 sm:ml-4">
                      <p class="text-xl font-semibold text-gray-900 dark:text-white">
                        {{ item.quantity }} {{ item.unit || 'units' }}
                      </p>
                      <p 
                        class="text-sm font-medium"
                        :class="{
                          'text-red-600 dark:text-red-400': item.quantity <= 0,
                          'text-yellow-600 dark:text-yellow-400': item.quantity > 0 && item.quantity <= item.low_stock_threshold,
                          'text-green-600 dark:text-green-400': item.quantity > item.low_stock_threshold
                        }"
                      >
                        {{ getStockStatusText(item) }}
                      </p>
                    </div>
                  </div>

                  <!-- Second Row: Low Stock Threshold -->
                  <div>
                    <div class="flex items-center">
                      <span class="text-sm font-medium text-gray-500 dark:text-gray-400">
                        Low stock threshold:
                      </span>
                      <span class="ml-2 text-sm font-medium text-gray-900 dark:text-white">
                        {{ item.low_stock_threshold }} {{ item.unit || 'units' }}
                      </span>
                    </div>
                    <div class="mt-2">
                      <div class="w-full bg-gray-200 rounded-full h-2.5 dark:bg-gray-700">
                        <div 
                          class="h-2.5 rounded-full"
                          :class="{
                            'bg-red-600': item.quantity <= 0,
                            'bg-yellow-500': item.quantity > 0 && item.quantity <= item.low_stock_threshold,
                            'bg-green-600': item.quantity > item.low_stock_threshold
                          }"
                          :style="`width: ${Math.min(100, (item.quantity / (item.low_stock_threshold * 2)) * 100)}%`"
                        ></div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <!-- Item Stats -->
            <div class="overflow-hidden bg-white shadow sm:rounded-lg dark:bg-gray-800">
              <div class="px-4 py-5 sm:p-6">
                <h3 class="text-lg font-medium leading-6 text-gray-900 dark:text-white">
                  Item Statistics
                </h3>
                <dl class="mt-5 grid grid-cols-1 gap-5">
                  <div class="px-4 py-5 bg-gray-50 dark:bg-gray-700 rounded-lg overflow-hidden sm:p-6">
                    <dt class="text-sm font-medium text-gray-500 truncate dark:text-gray-300">
                      Total Value
                    </dt>
                    <dd class="mt-1 text-xl font-semibold text-gray-900 dark:text-white">
                      {{ formatCurrency(item.quantity * item.unit_price) }}
                    </dd>
                  </div>
                  <div class="px-4 py-5 bg-gray-50 dark:bg-gray-700 rounded-lg overflow-hidden sm:p-6">
                    <dt class="text-sm font-medium text-gray-500 truncate dark:text-gray-300">
                      Unit Price
                    </dt>
                    <dd class="mt-1 text-xl font-semibold text-gray-900 dark:text-white">
                      {{ formatCurrency(item.unit_price) }}
                    </dd>
                  </div>
                  <div class="px-4 py-5 bg-gray-50 dark:bg-gray-700 rounded-lg overflow-hidden sm:p-6">
                    <dt class="text-sm font-medium text-gray-500 truncate dark:text-gray-300">
                      Last Restocked
                    </dt>
                    <dd class="mt-1 text-sm font-medium text-gray-900 dark:text-white">
                      {{ stats.last_restocked ? formatDate(stats.last_restocked) : 'Never' }}
                    </dd>
                  </div>
                  <div class="px-4 py-5 bg-gray-50 dark:bg-gray-700 rounded-lg overflow-hidden sm:p-6">
                    <dt class="text-sm font-medium text-gray-500 truncate dark:text-gray-300">
                      Last Used
                    </dt>
                    <dd class="mt-1 text-sm font-medium text-gray-900 dark:text-white">
                      {{ stats.last_used ? formatDate(stats.last_used) : 'Never' }}
                    </dd>
                  </div>
                </dl>
              </div>
            </div>

            <!-- Item Meta -->
            <div class="overflow-hidden bg-white shadow sm:rounded-lg dark:bg-gray-800">
              <div class="px-4 py-5 sm:px-6 border-b border-gray-200 dark:border-gray-700">
                <h3 class="text-lg font-medium leading-6 text-gray-900 dark:text-white">
                  Item Details
                </h3>
              </div>
              <div class="px-4 py-5 sm:p-6">
                <dl class="space-y-4">
                  <div>
                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">
                      Created
                    </dt>
                    <dd class="mt-1 text-sm text-gray-900 dark:text-white">
                      {{ formatDateTime(item.created_at) }}
                    </dd>
                  </div>
                  <div>
                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">
                      Last Updated
                    </dt>
                    <dd class="mt-1 text-sm text-gray-900 dark:text-white">
                      {{ formatDateTime(item.updated_at) }}
                    </dd>
                  </div>
                  <div v-if="item.expiry_date">
                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">
                      Expiry Date
                    </dt>
                    <dd 
                      class="mt-1 text-sm"
                      :class="isExpiringSoon(item.expiry_date) ? 'text-red-600 dark:text-red-400 font-medium' : 'text-gray-900 dark:text-white'"
                    >
                      {{ formatDate(item.expiry_date) }}
                      <span 
                        v-if="isExpiringSoon(item.expiry_date)" 
                        class="ml-2 inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200"
                      >
                        Expiring Soon
                      </span>
                    </dd>
                  </div>
                </dl>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Restock Modal -->
    <Dialog :open="showRestockModal" @update:open="showRestockModal = $event">
      <DialogContent>
        <DialogHeader>
          <DialogTitle>Restock Item</DialogTitle>
          <DialogDescription>
            Add stock to {{ item.name }}
          </DialogDescription>
        </DialogHeader>
        <div class="space-y-4 py-2">
          <div>
            <Label for="restockQuantity">Quantity to Add</Label>
            <Input
              id="restockQuantity"
              v-model="restockForm.quantity"
              type="number"
              min="1"
              class="mt-1 block w-full"
            />
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
              Current stock: {{ item.quantity }} {{ item.unit || 'units' }}
            </p>
          </div>
          <div>
            <Label for="restockNotes">Notes (Optional)</Label>
            <Textarea
              id="restockNotes"
              v-model="restockForm.notes"
              rows="3"
              class="mt-1 block w-full"
              placeholder="Add any notes about this restock..."
            />
          </div>
        </div>
        <DialogFooter>
          <Button variant="outline" @click="showRestockModal = false" class="dark:border-gray-600 dark:text-gray-200 dark:hover:bg-gray-700">
            Cancel
          </Button>
          <Button 
            @click="submitRestock" 
            :disabled="restockForm.processing"
            class="bg-blue-600 hover:bg-blue-700 dark:bg-blue-700 dark:hover:bg-blue-600 text-white"
          >
            <span v-if="restockForm.processing" class="flex items-center">
              <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
              </svg>
              Processing...
            </span>
            <span v-else>Add to Stock</span>
          </Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>

    <!-- Use Item Modal -->
    <Dialog :open="showUseItemModal" @update:open="showUseItemModal = $event">
      <DialogContent>
        <DialogHeader>
          <DialogTitle>Use Item</DialogTitle>
          <DialogDescription>
            Record usage of {{ item.name }}
          </DialogDescription>
        </DialogHeader>
        <div class="space-y-4 py-2">
          <div>
            <Label for="useQuantity">Quantity to Use</Label>
            <Input
              id="useQuantity"
              v-model="useItemForm.quantity"
              type="number"
              :min="1"
              :max="item.quantity"
              class="mt-1 block w-full"
            />
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
              Current stock: {{ item.quantity }} {{ item.unit || 'units' }}
            </p>
          </div>
          <div>
            <Label for="useNotes">Notes (Optional)</Label>
            <Textarea
              id="useNotes"
              v-model="useItemForm.notes"
              rows="3"
              class="mt-1 block w-full"
              placeholder="Add any notes about this usage..."
            />
          </div>
        </div>
        <DialogFooter>
          <Button variant="outline" @click="showUseItemModal = false">
            Cancel
          </Button>
          <Button 
            @click="submitUseItem" 
            :disabled="useItemForm.processing || item.quantity <= 0"
            :class="{
              'opacity-50 cursor-not-allowed': item.quantity <= 0,
              'bg-green-600 hover:bg-green-700 dark:bg-green-700 dark:hover:bg-green-600 text-white': item.quantity > 0,
              'bg-gray-400 dark:bg-gray-500': item.quantity <= 0
            }"
          >
            <span v-if="useItemForm.processing" class="flex items-center">
              <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
              </svg>
              Processing...
            </span>
            <span v-else>Record Usage</span>
          </Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  </AppLayout>
</template>