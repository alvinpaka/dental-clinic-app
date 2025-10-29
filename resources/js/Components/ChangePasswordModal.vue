<script setup lang="ts">
import { ref } from 'vue';
import { useForm } from '@inertiajs/vue3';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogHeader,
  DialogTitle,
  DialogTrigger,
} from '@/Components/ui/dialog';

const isOpen = ref(false);
const showSuccess = ref(false);

const form = useForm({
  current_password: '',
  password: '',
  password_confirmation: '',
});

const onOpenChange = (open: boolean) => {
  if (open) {
    isOpen.value = true;
    return;
  }

  closeModal();
};

const submit = () => {
  form.put(route('password.update'), {
    onSuccess: () => {
      form.reset();
      showSuccess.value = true;
      // Close modal after showing success message for 1.5 seconds
      setTimeout(() => {
        isOpen.value = false;
        showSuccess.value = false;
      }, 1500);
    },
    onError: () => {
      // Handle validation errors - they're automatically handled by the form
    },
  });
};

const closeModal = () => {
  isOpen.value = false;
  showSuccess.value = false;
  form.reset();
};
</script>

<template>
  <Dialog :open="isOpen" @update:open="onOpenChange">
    <DialogTrigger as-child>
      <Button variant="outline" size="sm" @click="isOpen = true">
        <i class="fas fa-key mr-2"></i>
        Change Password
      </Button>
    </DialogTrigger>
    <DialogContent class="sm:max-w-md">
      <DialogHeader>
        <DialogTitle>Change Password</DialogTitle>
        <DialogDescription>
          Ensure your account is using a long, random password to stay secure.
        </DialogDescription>
      </DialogHeader>

      <!-- Success Message -->
      <div v-if="showSuccess" class="p-4 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-lg mb-4">
        <div class="flex items-center space-x-2 text-green-800 dark:text-green-200">
          <i class="fas fa-check-circle"></i>
          <span class="text-sm font-medium">Password updated successfully!</span>
        </div>
      </div>

      <!-- Form (hidden when success is shown) -->
      <form v-if="!showSuccess" @submit.prevent="submit" class="space-y-4">
        <div class="space-y-2">
          <Label for="current_password">Current Password</Label>
          <Input
            id="current_password"
            v-model="form.current_password"
            type="password"
            :class="{ 'border-red-500 focus:border-red-500 focus:ring-red-500': form.errors.current_password }"
            placeholder="Enter your current password"
            required
          />
          <p v-if="form.errors.current_password" class="text-sm text-red-500">
            {{ form.errors.current_password }}
          </p>
        </div>

        <div class="space-y-2">
          <Label for="password">New Password</Label>
          <Input
            id="password"
            v-model="form.password"
            type="password"
            :class="{ 'border-red-500 focus:border-red-500 focus:ring-red-500': form.errors.password }"
            placeholder="Enter your new password"
            required
          />
          <p v-if="form.errors.password" class="text-sm text-red-500">
            {{ form.errors.password }}
          </p>
        </div>

        <div class="space-y-2">
          <Label for="password_confirmation">Confirm New Password</Label>
          <Input
            id="password_confirmation"
            v-model="form.password_confirmation"
            type="password"
            :class="{ 'border-red-500 focus:border-red-500 focus:ring-red-500': form.errors.password_confirmation }"
            placeholder="Confirm your new password"
            required
          />
          <p v-if="form.errors.password_confirmation" class="text-sm text-red-500">
            {{ form.errors.password_confirmation }}
          </p>
        </div>

        <div class="flex justify-end space-x-2 pt-4">
          <Button
            type="button"
            variant="outline"
            @click="closeModal"
            :disabled="form.processing"
          >
            Cancel
          </Button>
          <Button
            type="submit"
            :disabled="form.processing"
          >
            <i v-if="form.processing" class="fas fa-spinner fa-spin mr-2"></i>
            {{ form.processing ? 'Saving...' : 'Save Password' }}
          </Button>
        </div>
      </form>
    </DialogContent>
  </Dialog>
</template>
