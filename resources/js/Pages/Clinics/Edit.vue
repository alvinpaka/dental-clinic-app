<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/Components/ui/card';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { Checkbox } from '@/Components/ui/checkbox';

interface Clinic {
  id: number;
  name: string;
  email: string;
  phone: string;
  address: string;
  is_active: boolean;
  subscription_status: string;
}

const props = defineProps<{
  clinic: Clinic;
}>();

const page = usePage();
const currentUser = computed(() => page.props.auth.user);

const isCurrentUserClinic = computed(() => {
  return currentUser.value?.clinic_id === props.clinic.id;
});

const form = useForm({
  name: props.clinic.name,
  email: props.clinic.email,
  phone: props.clinic.phone,
  address: props.clinic.address,
  is_active: props.clinic.is_active,
  subscription_status: props.clinic.subscription_status,
});

const submit = () => {
  form.put(route('clinics.update', props.clinic.id));
};
</script>

<template>
  <Head :title="`Edit ${clinic.name}`" />

  <AppLayout>
    <template #header>
      <div class="flex justify-between items-center">
        <div>
          <h1 class="text-4xl font-bold bg-gradient-to-r from-blue-600 to-cyan-600 bg-clip-text text-transparent mb-2">
            Edit Clinic
          </h1>
          <p class="text-gray-600 dark:text-gray-400 text-lg">
            Update clinic information and settings
          </p>
        </div>
        <Button @click="$inertia.visit(route('clinics.show', clinic.id))" variant="outline" class="text-gray-600 dark:text-gray-400 border-gray-300 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-800">
          Back to Clinic
        </Button>
      </div>
    </template>

    <div class="py-6">
      <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <Card class="bg-white dark:bg-gray-900 border-0 shadow-xl">
          <CardHeader>
            <CardTitle class="text-2xl text-gray-900 dark:text-white">Clinic Information</CardTitle>
            <CardDescription class="text-gray-600 dark:text-gray-400">
              Update the clinic details below
            </CardDescription>
          </CardHeader>
          <CardContent>
            <form @submit.prevent="submit" class="space-y-6">
              <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
              <div class="col-span-2 sm:col-span-1">
                <Label for="name">Clinic Name</Label>
                <Input
                  id="name"
                  v-model="form.name"
                  :class="{ 'border-red-500': form.errors.name }"
                  required
                />
                <p v-if="form.errors.name" class="mt-2 text-sm text-red-600">
                  {{ form.errors.name }}
                </p>
              </div>

                <div class="col-span-2 sm:col-span-1">
                  <Label for="email">Email Address</Label>
                  <Input
                    id="email"
                    type="email"
                    v-model="form.email"
                    :class="{ 'border-red-500': form.errors.email }"
                    required
                  />
                  <p v-if="form.errors.email" class="mt-2 text-sm text-red-600">
                    {{ form.errors.email }}
                  </p>
                </div>

                <div class="col-span-2 sm:col-span-1">
                  <Label for="phone">Phone Number</Label>
                  <Input
                    id="phone"
                    v-model="form.phone"
                    :class="{ 'border-red-500': form.errors.phone }"
                    required
                  />
                  <p v-if="form.errors.phone" class="mt-2 text-sm text-red-600">
                    {{ form.errors.phone }}
                  </p>
                </div>

                <div class="col-span-2 sm:col-span-1">
                  <Label for="subscription_status">Subscription Status</Label>
                  <select
                    id="subscription_status"
                    v-model="form.subscription_status"
                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm dark:bg-gray-800 dark:border-gray-600 dark:text-white"
                    required
                  >
                    <option value="active">Active</option>
                    <option value="trial">Trial</option>
                    <option value="expired">Expired</option>
                    <option value="cancelled">Cancelled</option>
                  </select>
                </div>

                <div class="col-span-2">
                  <Label for="address">Address</Label>
                  <textarea
                    id="address"
                    v-model="form.address"
                    rows="3"
                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm dark:bg-gray-800 dark:border-gray-600 dark:text-white"
                    :class="{ 'border-red-500': form.errors.address }"
                    required
                  ></textarea>
                  <p v-if="form.errors.address" class="mt-2 text-sm text-red-600">
                    {{ form.errors.address }}
                  </p>
                </div>

                <div class="col-span-2">
                  <div class="flex items-center space-x-2">
                    <Checkbox
                      id="is_active"
                      v-model:checked="form.is_active"
                    />
                    <Label for="is_active" class="text-sm font-medium text-gray-700 dark:text-gray-300">
                      Clinic is active
                    </Label>
                  </div>
                </div>
              </div>

              <div class="mt-6 flex justify-end space-x-3">
                <Button @click="$inertia.visit(route('clinics.show', clinic.id))" variant="outline" class="text-gray-600 dark:text-gray-400 border-gray-300 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-800">
                  Cancel
                </Button>
                <Button
                  type="submit"
                  :disabled="form.processing"
                  class="bg-gradient-to-r from-blue-600 to-cyan-600 hover:from-blue-700 hover:to-cyan-700 text-white shadow-lg hover:shadow-xl transition-all duration-300"
                >
                  Update Clinic
                </Button>
              </div>
            </form>
          </CardContent>
        </Card>
      </div>
    </div>
  </AppLayout>
</template>
