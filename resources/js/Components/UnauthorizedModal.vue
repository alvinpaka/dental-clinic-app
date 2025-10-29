<script setup lang="ts">
import { ref, computed } from 'vue';
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogDescription, DialogFooter } from '@/Components/ui/dialog';
import { Button } from '@/Components/ui/button';
import { ShieldAlert, Clock, AlertTriangle } from 'lucide-vue-next';

interface UnauthorizedModalProps {
  show: boolean;
  title?: string;
  message?: string;
  requiredRole?: string;
  resource?: string;
}

const props = withDefaults(defineProps<UnauthorizedModalProps>(), {
  title: 'Access Denied',
  message: 'You do not have permission to access this page.',
  requiredRole: '',
  resource: ''
});

const emit = defineEmits<{
  close: [];
}>();

const closeModal = () => {
  emit('close');
};

const getIconColor = computed(() => {
  return 'text-red-500 dark:text-red-400';
});

const getIcon = computed(() => {
  if (props.requiredRole) {
    return ShieldAlert;
  }
  return AlertTriangle;
});

const formatRequiredRole = computed(() => {
  if (!props.requiredRole) return '';
  return props.requiredRole.charAt(0).toUpperCase() + props.requiredRole.slice(1);
});

const formatResource = computed(() => {
  if (!props.resource) return '';
  return props.resource.charAt(0).toUpperCase() + props.resource.slice(1);
});
</script>

<template>
  <Dialog :open="show" @update:open="closeModal">
    <DialogContent class="max-w-md mx-auto">
      <DialogHeader class="text-center">
        <div class="mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-full bg-gradient-to-br from-red-50 to-red-100 dark:from-red-900/20 dark:to-red-800/20 border-2 border-red-200 dark:border-red-800">
          <component :is="getIcon" :class="['h-8 w-8', getIconColor]" />
        </div>
        <DialogTitle class="text-2xl font-bold text-gray-900 dark:text-white mb-2">
          {{ title }}
        </DialogTitle>
        <DialogDescription class="text-gray-600 dark:text-gray-400 text-center leading-relaxed">
          {{ message }}
        </DialogDescription>

        <!-- Additional Information -->
        <div v-if="requiredRole || resource" class="mt-4 space-y-2">
          <div v-if="requiredRole" class="flex items-center justify-center gap-2 text-sm">
            <ShieldAlert class="w-4 h-4 text-blue-500" />
            <span class="text-gray-700 dark:text-gray-300">Required Role:</span>
            <span class="font-medium text-red-600 dark:text-red-400 bg-red-50 dark:bg-red-900/20 px-2 py-1 rounded-md">
              {{ formatRequiredRole }}
            </span>
          </div>
          <div v-if="resource" class="flex items-center justify-center gap-2 text-sm">
            <Clock class="w-4 h-4 text-purple-500" />
            <span class="text-gray-700 dark:text-gray-300">Resource:</span>
            <span class="font-medium text-purple-600 dark:text-purple-400 bg-purple-50 dark:bg-purple-900/20 px-2 py-1 rounded-md">
              {{ formatResource }}
            </span>
          </div>
        </div>
      </DialogHeader>

      <DialogFooter class="flex-col gap-2 sm:flex-col">
        <Button @click="closeModal" variant="outline" class="w-full">
          Close
        </Button>
      </DialogFooter>
    </DialogContent>
  </Dialog>
</template>

<style scoped>
/* Custom animations for the modal */
:deep(.dialog-content) {
  animation: modalSlideIn 0.3s ease-out;
}

@keyframes modalSlideIn {
  from {
    opacity: 0;
    transform: scale(0.95) translateY(-10px);
  }
  to {
    opacity: 1;
    transform: scale(1) translateY(0);
  }
}

/* Enhanced gradient backgrounds */
.bg-gradient-to-br {
  background-image: linear-gradient(to bottom right, var(--tw-gradient-stops));
}
</style>
