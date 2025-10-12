<script setup lang="ts">
import { Link, usePage, router } from '@inertiajs/vue3';
import { route } from 'ziggy-js';
import { Button } from '@/Components/ui/button';
import { computed, ref, onMounted } from 'vue';
import { useThemeStore } from '@/Stores/theme';

const themeStore = useThemeStore();

const props = defineProps<{
  title: string;
}>();

const page = usePage();

usePage().props.jetstream ??= {}; // For Breeze dark mode compat

const navigationItems = [
  { value: 'dashboard', label: 'Dashboard', href: '/dashboard', icon: 'fas fa-tachometer-alt' },
  { value: 'patients', label: 'Patients', href: '/patients', icon: 'fas fa-users' },
  { value: 'appointments', label: 'Appointments', href: '/appointments', icon: 'fas fa-calendar-check' },
  { value: 'treatments', label: 'Treatments', href: '/treatments', icon: 'fas fa-tooth' },
  { value: 'invoices', label: 'Billing', href: '/invoices', icon: 'fas fa-file-invoice-dollar' },
  { value: 'staff', label: 'Staff', href: '/staff', icon: 'fas fa-user-md' },
  { value: 'inventory', label: 'Inventory', href: '/inventory', icon: 'fas fa-boxes' },
  { value: 'prescriptions', label: 'Prescriptions', href: '/prescriptions', icon: 'fas fa-pills' },
  { value: 'reports', label: 'Reports', href: '/reports', icon: 'fas fa-chart-bar' },
];

const activeTab = computed(() => {
  for (const item of navigationItems) {
    if (page.url === item.href || page.url.startsWith(item.href + '/')) {
      return item.value;
    }
  }
  return 'dashboard';
});
</script>

<template>
  <div class="min-h-screen bg-background">
    <nav class="bg-primary text-primary-foreground shadow-lg">
      <div class="flex items-center justify-between p-4">
        <Link href="/dashboard" class="text-xl font-bold">Dental Clinic</Link>
        <div class="flex items-center space-x-4">
          <Button variant="ghost" @click="themeStore.toggleDarkMode" size="sm">
            <i :class="['fas', themeStore.isDark ? 'fa-sun text-yellow-300' : 'fa-moon']"></i>
          </Button>
          <Button variant="ghost" @click="router.post(route('logout'))">Logout</Button>
        </div>
      </div>
    </nav>
    <div class="flex">
      <aside class="hidden md:block w-64 bg-muted p-4">
        <nav class="space-y-1">
          <Link
            v-for="item in navigationItems"
            :key="item.value"
            :href="item.href"
            :class="[
              'flex items-center px-3 py-2 rounded-md text-sm font-medium transition-colors',
              activeTab === item.value
                ? 'bg-background text-foreground shadow-sm'
                : 'text-muted-foreground hover:text-foreground hover:bg-accent'
            ]"
          >
            <i :class="[item.icon, 'mr-2 w-4 h-4']"></i>
            {{ item.label }}
          </Link>
        </nav>
      </aside>
      <main class="flex-1 p-6 md:ml-0">
        <slot />
      </main>
    </div>
  </div>
</template>