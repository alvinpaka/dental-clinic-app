<script setup lang="ts">
import { computed } from 'vue';
import { Button } from './button';
import { ChevronLeft, ChevronRight, MoreHorizontal } from 'lucide-vue-next';

interface PaginationLink {
  url: string | null;
  label: string;
  active: boolean;
}

const props = defineProps<{
  links: PaginationLink[];
  from?: number;
  to?: number;
  total?: number;
  itemName?: string;
}>();

const emit = defineEmits(['pageChange']);

const handlePageChange = (link: PaginationLink) => {
  if (link.url && !link.active) {
    emit('pageChange', link);
  }
};

const formatLabel = (label: string) => {
  if (label.includes('Previous')) return 'Previous';
  if (label.includes('Next')) return 'Next';
  if (label.includes('…')) return '…';
  return label;
};

const showEllipsis = computed(() => {
  return props.links.some(link => link.label.includes('…'));
});
</script>

<template>
  <div v-if="links.length > 1" class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
    <p v-if="from && to && total" class="text-sm text-gray-600 dark:text-gray-400">
      Showing
      <span class="font-medium text-gray-900 dark:text-white">{{ from }}</span>
      to
      <span class="font-medium text-gray-900 dark:text-white">{{ to }}</span>
      of
      <span class="font-medium text-gray-900 dark:text-white">{{ total }}</span>
      {{ itemName || 'items' }}
    </p>
    
    <div class="flex flex-wrap items-center gap-2">
      <Button
        v-for="(link, index) in links"
        :key="index"
        variant="outline"
        size="sm"
        class="min-w-[2.5rem] justify-center h-9 px-2"
        :class="[
          link.active 
            ? 'bg-blue-600 text-white hover:bg-blue-700 dark:bg-blue-600 dark:hover:bg-blue-700 border-blue-600' 
            : 'hover:bg-gray-100 dark:hover:bg-gray-800',
          !link.url ? 'opacity-50 cursor-not-allowed' : 'cursor-pointer'
        ]"
        :disabled="!link.url || link.active"
        @click="handlePageChange(link)"
      >
        <template v-if="link.label.includes('Previous')">
          <ChevronLeft class="w-4 h-4 mr-1" />
          <span>{{ formatLabel(link.label) }}</span>
        </template>
        <template v-else-if="link.label.includes('Next')">
          <span>{{ formatLabel(link.label) }}</span>
          <ChevronRight class="w-4 h-4 ml-1" />
        </template>
        <template v-else>
          <span>{{ formatLabel(link.label) }}</span>
        </template>
      </Button>
    </div>
  </div>
</template>
