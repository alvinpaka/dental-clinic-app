<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { Button } from '@/Components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/Components/ui/card';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import AppLayout from '@/Layouts/AppLayout.vue';
import { computed } from 'vue';

const props = defineProps<{
  user: {
    id: number;
    name: string;
    email: string;
    email_verified_at?: string;
    roles?: Array<{
      id: number;
      name: string;
      display_name?: string;
    }>;
  };
}>();

const form = useForm({
  name: props.user.name,
  email: props.user.email,
});

const submit = () => {
  form.patch(route('profile.update'), {
    onSuccess: () => {
      // Form will automatically reset and show success message via flash
      form.reset();
    },
    onError: () => {
      // Handle validation errors - they're automatically handled by the form
    },
  });
};

// Helper function to format role names
const formatRoleName = (roleName: string): string => {
  const roleMap: Record<string, string> = {
    'admin': 'Administrator',
    'receptionist': 'Receptionist',
    'dentist': 'Dentist',
    'assistant': 'Assistant',
  };

  return roleMap[roleName] || roleName.charAt(0).toUpperCase() + roleName.slice(1);
};

// Computed property for account type display
const accountType = computed(() => {
  if (!props.user.roles || props.user.roles.length === 0) {
    return 'User';
  }

  const sortedRoles = [...props.user.roles].sort((a, b) => a.name.localeCompare(b.name));

  if (sortedRoles.length === 1) {
    return props.user.roles[0].display_name || formatRoleName(props.user.roles[0].name);
  }

  if (sortedRoles.length === 2) {
    const role1 = sortedRoles[0].display_name || formatRoleName(sortedRoles[0].name);
    const role2 = sortedRoles[1].display_name || formatRoleName(sortedRoles[1].name);
    return `${role1} & ${role2}`;
  }

  // For 3 or more roles, show primary role + count
  const primaryRole = sortedRoles[0].display_name || formatRoleName(sortedRoles[0].name);
  return `${primaryRole} & ${sortedRoles.length - 1} more`;
});
</script>

<template>
  <AppLayout title="Profile">
    <div class="space-y-6">
      <div>
        <h2 class="text-3xl font-bold tracking-tight">Profile Settings</h2>
        <p class="text-muted-foreground">
          Manage your account settings and preferences.
        </p>
      </div>

      <!-- Success Message -->
      <div v-if="$page.props.flash?.success" class="p-4 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-lg">
        <div class="flex items-center space-x-2 text-green-800 dark:text-green-200">
          <i class="fas fa-check-circle"></i>
          <span class="text-sm font-medium">{{ $page.props.flash.success }}</span>
        </div>
      </div>

      <!-- Error Message -->
      <div v-if="$page.props.flash?.error" class="p-4 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg">
        <div class="flex items-center space-x-2 text-red-800 dark:text-red-200">
          <i class="fas fa-exclamation-triangle"></i>
          <span class="text-sm font-medium">{{ $page.props.flash.error }}</span>
        </div>
      </div>

      <div class="grid gap-6">
        <!-- Profile Information Card -->
        <Card>
          <CardHeader>
            <CardTitle>Personal Information</CardTitle>
            <CardDescription>
              Update your personal details and contact information.
            </CardDescription>
          </CardHeader>
          <CardContent class="space-y-6">
            <form @submit.prevent="submit" class="space-y-4">
              <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <!-- Name Field -->
                <div class="space-y-2">
                  <Label for="name">Full Name</Label>
                  <Input
                    id="name"
                    v-model="form.name"
                    :class="{ 'border-red-500 focus:border-red-500 focus:ring-red-500': form.errors.name }"
                    placeholder="Enter your full name"
                    required
                  />
                  <p v-if="form.errors.name" class="text-sm text-red-500">
                    {{ form.errors.name }}
                  </p>
                </div>

                <!-- Email Field -->
                <div class="space-y-2">
                  <Label for="email">Email Address</Label>
                  <Input
                    id="email"
                    v-model="form.email"
                    type="email"
                    :class="{ 'border-red-500 focus:border-red-500 focus:ring-red-500': form.errors.email }"
                    placeholder="Enter your email address"
                    required
                  />
                  <p v-if="form.errors.email" class="text-sm text-red-500">
                    {{ form.errors.email }}
                  </p>
                </div>
              </div>

              <!-- Email Verification Status -->
              <div v-if="user.email_verified_at" class="flex items-center space-x-2 text-sm text-green-600 dark:text-green-400">
                <i class="fas fa-check-circle"></i>
                <span>Email verified</span>
              </div>
              <div v-else class="flex items-center space-x-2 text-sm text-yellow-600 dark:text-yellow-400">
                <i class="fas fa-exclamation-triangle"></i>
                <span>Email not verified</span>
              </div>

              <!-- Submit Button -->
              <div class="flex justify-end space-x-4">
                <Button
                  type="button"
                  variant="outline"
                  @click="form.reset()"
                  :disabled="form.processing"
                >
                  Cancel
                </Button>
                <Button
                  type="submit"
                  :disabled="form.processing || !hasChanges"
                  class="min-w-32"
                >
                  <i v-if="form.processing" class="fas fa-spinner fa-spin mr-2"></i>
                  {{ form.processing ? 'Saving...' : 'Save Changes' }}
                </Button>
              </div>
            </form>
          </CardContent>
        </Card>

        <!-- Account Security Card -->
        <Card>
          <CardHeader>
            <CardTitle>Account Security</CardTitle>
            <CardDescription>
              Manage your password and security settings.
            </CardDescription>
          </CardHeader>
          <CardContent class="space-y-4">
            <div class="flex items-center justify-between p-4 border rounded-lg">
              <div class="space-y-1">
                <p class="text-sm font-medium">Password</p>
                <p class="text-sm text-muted-foreground">
                  Last updated recently
                </p>
              </div>
              <Button variant="outline" size="sm">
                <i class="fas fa-key mr-2"></i>
                Change Password
              </Button>
            </div>

            <div class="flex items-center justify-between p-4 border rounded-lg">
              <div class="space-y-1">
                <p class="text-sm font-medium">Two-Factor Authentication</p>
                <p class="text-sm text-muted-foreground">
                  Add an extra layer of security to your account
                </p>
              </div>
              <Button variant="outline" size="sm">
                <i class="fas fa-shield-alt mr-2"></i>
                Enable 2FA
              </Button>
            </div>
          </CardContent>
        </Card>

        <!-- Account Information Card -->
        <Card>
          <CardHeader>
            <CardTitle>Account Information</CardTitle>
            <CardDescription>
              View your account details and membership information.
            </CardDescription>
          </CardHeader>
          <CardContent class="space-y-4">
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
              <div class="space-y-2">
                <Label class="text-sm font-medium">Account ID</Label>
                <p class="text-sm text-muted-foreground">#{{ user.id }}</p>
              </div>

              <div class="space-y-2">
                <Label class="text-sm font-medium">Member Since</Label>
                <p class="text-sm text-muted-foreground">
                  {{ new Date().toLocaleDateString() }}
                </p>
              </div>

              <div class="space-y-2">
                <Label class="text-sm font-medium">Account Type</Label>
                <p class="text-sm text-muted-foreground">{{ accountType }}</p>
              </div>

              <div class="space-y-2">
                <Label class="text-sm font-medium">Status</Label>
                <p class="text-sm text-green-600 dark:text-green-400">Active</p>
              </div>
            </div>
          </CardContent>
        </Card>
      </div>
    </div>
  </AppLayout>
</template>
