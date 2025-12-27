<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { ref, watch } from 'vue';
import { Button } from '@/Components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/Components/ui/card';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/Components/ui/select';
import { Badge } from '@/Components/ui/badge';
import { ChevronDown, ChevronUp, Search, Filter, Calendar, User, Activity, Shield, Eye, Clock, MapPin } from 'lucide-vue-next';

interface AuditLog {
  id: number;
  action: string;
  subject_type: string;
  subject_id: number;
  metadata: any;
  new_values: any;
  ip_address: string;
  user_agent: string;
  created_at: string;
  user: {
    id: number;
    name: string;
    email: string;
  };
  clinic?: {
    id: number;
    name: string;
  };
  subject?: any;
}

const props = defineProps<{
  auditLogs: {
    data: AuditLog[];
    links: any;
    meta: any;
  };
  filters: {
    action?: string;
    date_from?: string;
    date_to?: string;
  };
  actions: string[];
}>();

const formatDate = (dateString: string) => {
  return new Date(dateString).toLocaleString();
};

const getSubjectName = (log: AuditLog) => {
  if (log.subject) {
    return log.subject.name || log.subject.email || `ID: ${log.subject_id}`;
  }
  return `ID: ${log.subject_id}`;
};

const getActionColor = (action: string) => {
  const colorMap: Record<string, string> = {
    'created': 'bg-green-100 text-green-800 dark:bg-green-900/20 dark:text-green-400',
    'updated': 'bg-blue-100 text-blue-800 dark:bg-blue-900/20 dark:text-blue-400',
    'deleted': 'bg-red-100 text-red-800 dark:bg-red-900/20 dark:text-red-400',
    'login': 'bg-purple-100 text-purple-800 dark:bg-purple-900/20 dark:text-purple-400',
    'logout': 'bg-gray-100 text-gray-800 dark:bg-gray-900/20 dark:text-gray-400',
  };
  return colorMap[action] || 'bg-gray-100 text-gray-800 dark:bg-gray-900/20 dark:text-gray-400';
};

const getActionIcon = (action: string) => {
  const iconMap: Record<string, string> = {
    'created': 'Plus',
    'updated': 'Edit',
    'deleted': 'Trash2',
    'login': 'LogIn',
    'logout': 'LogOut',
  };
  return iconMap[action] || 'Activity';
};

const formatAction = (action: string) => {
  return action.charAt(0).toUpperCase() + action.slice(1);
};

const showDetails = ref<number | null>(null);
const showFilters = ref(true);

const toggleDetails = (id: number) => {
  showDetails.value = showDetails.value === id ? null : id;
};

const toggleFilters = () => {
  showFilters.value = !showFilters.value;
};
</script>

<template>
  <Head title="Audit Logs" />

  <AppLayout title="Audit Logs">
    <template #header>
      <div class="container mx-auto px-4 py-8">
        <div class="flex justify-between items-center">
          <div class="flex items-center space-x-4">
            <div>
              <h1 class="text-2xl font-bold text-[#045c4b] dark:text-white flex items-center">
                <Shield class="w-6 h-6 mr-2 text-[#045c4b] dark:text-white" />
                Audit Logs
              </h1>
              <p class="text-gray-600 dark:text-gray-400 text-sm mt-1">
                Track all system activities and changes
              </p>
            </div>
          </div>
          <Badge variant="outline" class="bg-blue-50 text-blue-700 border-blue-200 dark:bg-blue-900/20 dark:text-blue-400 dark:border-blue-800">
            <Activity class="w-4 h-4 mr-2" />
            {{ auditLogs.data?.length || 0 }} Logs
          </Badge>
        </div>
      </div>
    </template>

    <div class="py-6">
      <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <!-- Filters Card -->
        <Card class="border-0 shadow-xl bg-white dark:bg-gray-900 mb-6">
          <CardHeader>
            <div class="flex items-center justify-between w-full cursor-pointer" @click="toggleFilters">
              <div class="flex items-center space-x-3">
                <Filter class="w-5 h-5 text-blue-600 dark:text-blue-400" />
                <CardTitle class="text-lg">Filters</CardTitle>
              </div>
              <component :is="showFilters ? ChevronUp : ChevronDown" class="w-4 h-4 transition-transform" />
            </div>
            
            <div v-show="showFilters" class="pt-4">
              <form method="GET" class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div class="space-y-2">
                  <Label for="action" class="text-sm font-medium text-gray-700 dark:text-gray-300 flex items-center">
                    <Activity class="w-4 h-4 mr-2" />
                    Action
                  </Label>
                  <Select name="action" :value="filters.action">
                    <SelectTrigger class="w-full">
                      <SelectValue placeholder="All Actions" />
                    </SelectTrigger>
                    <SelectContent>
                      <SelectItem value="all">All Actions</SelectItem>
                      <SelectItem v-for="action in actions" :key="action" :value="action">
                        {{ formatAction(action) }}
                      </SelectItem>
                    </SelectContent>
                  </Select>
                </div>

                <div class="space-y-2">
                  <Label for="date_from" class="text-sm font-medium text-gray-700 dark:text-gray-300 flex items-center">
                    <Calendar class="w-4 h-4 mr-2" />
                    From Date
                  </Label>
                  <Input
                    type="date"
                    id="date_from"
                    name="date_from"
                    :value="filters.date_from"
                    class="w-full"
                  />
                </div>

                <div class="space-y-2">
                  <Label for="date_to" class="text-sm font-medium text-gray-700 dark:text-gray-300 flex items-center">
                    <Calendar class="w-4 h-4 mr-2" />
                    To Date
                  </Label>
                  <Input
                    type="date"
                    id="date_to"
                    name="date_to"
                    :value="filters.date_to"
                    class="w-full"
                  />
                </div>

                <div class="flex items-end">
                  <Button
                    type="submit"
                    class="w-full bg-[#045c4b] hover:bg-[#045c4b]/90 text-white shadow-lg hover:shadow-xl transition-all duration-200"
                  >
                    <Search class="w-4 h-4 mr-2" />
                    Apply Filters
                  </Button>
                </div>
              </form>
            </div>
          </CardHeader>
        </Card>

        <!-- Audit Logs Cards -->
        <div class="space-y-4">
          <div v-for="log in auditLogs.data" :key="log.id" class="group">
            <Card class="border-0 shadow-lg hover:shadow-xl transition-all duration-200 bg-white dark:bg-gray-900 overflow-hidden">
              <CardContent class="p-6">
                <!-- Main Log Info -->
                <div class="flex items-start justify-between">
                  <div class="flex items-start space-x-4 flex-1">
                    <!-- Action Icon & Badge -->
                    <div class="flex-shrink-0">
                      <div class="w-12 h-12 rounded-xl bg-gradient-to-br flex items-center justify-center shadow-md"
                           :class="getActionColor(log.action)">
                        <component :is="getActionIcon(log.action)" class="w-6 h-6" />
                      </div>
                    </div>

                    <!-- Log Details -->
                    <div class="flex-1 min-w-0">
                      <div class="flex items-center space-x-3 mb-2">
                        <Badge :class="getActionColor(log.action)" class="capitalize">
                          {{ formatAction(log.action) }}
                        </Badge>
                        <span class="text-sm text-gray-500 dark:text-gray-400 flex items-center">
                          <Clock class="w-3 h-3 mr-1" />
                          {{ formatDate(log.created_at) }}
                        </span>
                      </div>

                      <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-1">
                        {{ formatAction(log.action) }} {{ log.subject_type?.replace('App\\Models\\', '') || 'Item' }}
                      </h3>

                      <!-- User Info -->
                      <div class="flex items-center space-x-4 text-sm text-gray-600 dark:text-gray-400 mb-2">
                        <div class="flex items-center space-x-1">
                          <User class="w-4 h-4" />
                          <span>{{ log.user.name }}</span>
                        </div>
                        <span class="text-gray-400">•</span>
                        <span>{{ log.user.email }}</span>
                        <span v-if="log.clinic" class="flex items-center space-x-1">
                          <MapPin class="w-4 h-4" />
                          <span>{{ log.clinic.name }}</span>
                        </span>
                      </div>

                      <!-- Subject Info -->
                      <div class="text-sm text-gray-700 dark:text-gray-300">
                        Target: <span class="font-medium">{{ getSubjectName(log) }}</span>
                      </div>
                    </div>
                  </div>

                  <!-- Actions -->
                  <div class="flex items-center space-x-2">
                    <Button
                      variant="ghost"
                      size="sm"
                      @click="toggleDetails(log.id)"
                      class="text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200"
                    >
                      <Eye class="w-4 h-4" />
                    </Button>
                  </div>
                </div>

                <!-- Expandable Details -->
                <div v-show="showDetails === log.id" class="mt-4 pt-4 border-t border-gray-200 dark:border-gray-700">
                  <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                    <div>
                      <h4 class="font-medium text-gray-900 dark:text-white mb-2">Technical Details</h4>
                      <div class="space-y-1 text-gray-600 dark:text-gray-400">
                        <p><strong>IP Address:</strong> {{ log.ip_address }}</p>
                        <p><strong>User Agent:</strong> {{ log.user_agent }}</p>
                      </div>
                    </div>
                    
                    <div v-if="log.new_values">
                      <h4 class="font-medium text-gray-900 dark:text-white mb-2">Changes Made</h4>
                      <div class="bg-gray-50 dark:bg-gray-800 rounded-lg p-3">
                        <pre class="text-xs text-gray-700 dark:text-gray-300 whitespace-pre-wrap">{{ JSON.stringify(log.new_values, null, 2) }}</pre>
                      </div>
                    </div>
                  </div>
                </div>
              </CardContent>
            </Card>
          </div>
        </div>

        <!-- Pagination -->
        <div v-if="auditLogs.data && auditLogs.data.length > 0" class="mt-8 flex justify-between items-center">
          <div class="text-sm text-gray-700 dark:text-gray-300">
            Showing {{ auditLogs.meta?.from || 0 }} to {{ auditLogs.meta?.to || 0 }} of {{ auditLogs.meta?.total || 0 }} results
          </div>
          <div class="flex space-x-2">
            <Link
              v-for="link in (auditLogs.links || [])"
              :key="link.label"
              :href="link.url || '#'"
              :class="[
                'px-3 py-2 text-sm rounded-md transition-colors',
                link.active
                  ? 'bg-blue-600 text-white'
                  : link.url
                  ? 'bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300 border border-gray-300 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-700'
                  : 'bg-gray-100 dark:bg-gray-800 text-gray-400 cursor-not-allowed'
              ]"
              v-html="link.label"
            />
          </div>
        </div>

        <!-- Empty State -->
        <div v-else class="text-center py-12">
          <div class="w-16 h-16 mx-auto bg-gray-100 dark:bg-gray-800 rounded-full flex items-center justify-center mb-4">
            <Activity class="w-8 h-8 text-gray-400 dark:text-gray-600" />
          </div>
          <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-2">No audit logs found</h3>
          <p class="text-gray-600 dark:text-gray-400">No system activities match your current filters.</p>
        </div>
      </div>
    </div>
  </AppLayout>
</template>
