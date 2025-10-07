<script setup lang="ts">
import { Link, usePage, router } from '@inertiajs/vue3';
import { route } from 'ziggy-js';
import { useThemeStore } from '@/Stores/theme';
import { Button } from '@/Components/ui/button';
import { Moon, Sun } from 'lucide-vue-next';

const { isDark, toggleDarkMode, initTheme } = useThemeStore();
initTheme();

const props = defineProps<{
  title: string;
}>();

const page = usePage();

usePage().props.jetstream ??= {}; // For Breeze dark mode compat

const isActive = (path: string) => {
  return page.url === path || page.url.startsWith(path + '/');
};
</script>

<template>
  <div class="min-h-screen bg-background">
    <nav class="bg-primary text-primary-foreground shadow-lg">
      <div class="flex items-center justify-between p-4">
        <Link href="/dashboard" class="text-xl font-bold">Dental Clinic</Link>
        <div class="flex items-center space-x-4">
          <Button variant="ghost" @click="toggleDarkMode" size="sm">
            <Sun v-if="isDark" class="h-4 w-4" />
            <Moon v-else class="h-4 w-4" />
          </Button>
          <Button variant="ghost" @click="router.post(route('logout'))">Logout</Button>
        </div>
      </div>
    </nav>
    <div class="flex">
      <aside class="hidden md:block w-64 bg-muted p-4">
        <nav class="space-y-2">
          <Link 
            href="/dashboard" 
            :class="[
              'block p-2 rounded hover:bg-accent transition-colors', 
              { 'bg-accent text-accent-foreground font-semibold': isActive('/dashboard') }
            ]"
          >
            Dashboard
          </Link>
          <Link 
            href="/patients" 
            :class="[
              'block p-2 rounded hover:bg-accent transition-colors', 
              { 'bg-accent text-accent-foreground font-semibold': isActive('/patients') }
            ]"
          >
            Patients
          </Link>
          <Link 
            href="/appointments" 
            :class="[
              'block p-2 rounded hover:bg-accent transition-colors', 
              { 'bg-accent text-accent-foreground font-semibold': isActive('/appointments') }
            ]"
          >
            Appointments
          </Link>
          <Link 
            href="/treatments" 
            :class="[
              'block p-2 rounded hover:bg-accent transition-colors', 
              { 'bg-accent text-accent-foreground font-semibold': isActive('/treatments') }
            ]"
          >
            Treatments
          </Link>
          <Link 
            href="/invoices" 
            :class="[
              'block p-2 rounded hover:bg-accent transition-colors', 
              { 'bg-accent text-accent-foreground font-semibold': isActive('/invoices') }
            ]"
          >
            Billing
          </Link>
          <Link 
            href="/staff" 
            :class="[
              'block p-2 rounded hover:bg-accent transition-colors', 
              { 'bg-accent text-accent-foreground font-semibold': isActive('/staff') }
            ]"
          >
            Staff
          </Link>
          <Link 
            href="/inventory" 
            :class="[
              'block p-2 rounded hover:bg-accent transition-colors', 
              { 'bg-accent text-accent-foreground font-semibold': isActive('/inventory') }
            ]"
          >
            Inventory
          </Link>
          <Link 
            href="/prescriptions" 
            :class="[
              'block p-2 rounded hover:bg-accent transition-colors', 
              { 'bg-accent text-accent-foreground font-semibold': isActive('/prescriptions') }
            ]"
          >
            Prescriptions
          </Link>
          <Link 
            href="/reports" 
            :class="[
              'block p-2 rounded hover:bg-accent transition-colors', 
              { 'bg-accent text-accent-foreground font-semibold': isActive('/reports') }
            ]"
          >
            Reports
          </Link>
        </nav>
      </aside>
      <main class="flex-1 p-6 md:ml-0">
        <slot />
      </main>
    </div>
  </div>
</template>