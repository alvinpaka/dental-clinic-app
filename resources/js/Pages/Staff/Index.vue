<script setup lang="ts">
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import { ref, computed, watch } from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/Components/ui/card';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { Badge } from '@/Components/ui/badge';
import { Button } from '@/Components/ui/button';
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogDescription, DialogFooter } from '@/Components/ui/dialog';
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuTrigger } from '@/Components/ui/dropdown-menu';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/Components/ui/select';
import Pagination from '@/Components/ui/Pagination.vue';
import { Users, Plus, Edit, Trash, Shield, Search, MoreVertical, Eye, UserPlus, Calendar, Mail } from 'lucide-vue-next';

// Types
interface Staff {
  id: number;
  name: string;
  email: string;
  email_verified_at: string | null;
  created_at: string;
  roles: Role[];
}

interface Role {
  id: number;
  name: string;
}

interface PaginationLink {
  url: string | null;
  label: string;
  active: boolean;
}

interface PaginationMeta {
  current_page?: number;
  last_page?: number;
  per_page?: number;
  total?: number;
  from?: number;
  to?: number;
}

interface Filters {
  search?: string;
  role?: string;
  sort_by?: string;
  sort_order?: 'asc' | 'desc';
  per_page?: number;
  page?: number;
  total?: number;
  from?: number;
  to?: number;
}

// Props
const props = defineProps<{
  staff: {
    data: Staff[];
    links?: PaginationLink[];
    meta?: PaginationMeta;
  };
  roles: Role[];
  filters?: Filters;
}>();

// Reactive data
const searchQuery = ref(props.filters?.search ?? '');
const roleFilter = ref(props.filters?.role ?? 'all');
const sortBy = ref(props.filters?.sort_by ?? 'name');
const sortOrder = ref<'asc' | 'desc'>(props.filters?.sort_order ?? 'asc');
const currentPage = ref(props.filters?.page ?? props.staff?.meta?.current_page ?? 1);

const paginationLinks = computed(() => props.staff?.links || []);
const totalStaff = computed(() => props.filters?.total ?? props.staff?.meta?.total ?? props.staff?.data.length ?? 0);

const listPerPageOptions = [10, 20, 30, 50];
const listViewPerPage = ref((props.filters?.per_page ?? props.staff?.meta?.per_page ?? 10).toString());

// Watchers for filter changes
watch([searchQuery, roleFilter, sortBy, sortOrder, listViewPerPage], () => {
  currentPage.value = 1;
  fetchStaff();
});

watch(() => props.filters, (newFilters) => {
  if (newFilters) {
    searchQuery.value = newFilters.search ?? '';
    roleFilter.value = newFilters.role ?? 'all';
    sortBy.value = newFilters.sort_by ?? 'name';
    sortOrder.value = newFilters.sort_order ?? 'asc';
    currentPage.value = newFilters.page ?? 1;
    
    if (newFilters.per_page && newFilters.per_page.toString() !== listViewPerPage.value) {
      listViewPerPage.value = newFilters.per_page.toString();
    }
  }
}, { deep: true, immediate: true });

// Fetch staff with current filters
function fetchStaff(overrides = {}) {
  const params = {
    search: searchQuery.value,
    role: roleFilter.value,
    sort_by: sortBy.value,
    sort_order: sortOrder.value,
    per_page: Number(listViewPerPage.value),
    page: currentPage.value,
    ...overrides
  };

  router.get(route('staff.index'), params, {
    preserveState: true,
    preserveScroll: true,
    replace: true,
  });
}

// Toggle sort order
function toggleSort(column: string) {
  if (sortBy.value === column) {
    sortOrder.value = sortOrder.value === 'asc' ? 'desc' : 'asc';
  } else {
    sortBy.value = column;
    sortOrder.value = 'asc';
  }
}

// Handle pagination
function goToPage(link: PaginationLink) {
  if (!link.url || link.active) return;
  
  try {
    const url = new URL(link.url, window.location.origin);
    const page = url.searchParams.get('page');
    if (page) {
      currentPage.value = parseInt(page, 10);
      fetchStaff({ page: currentPage.value });
    }
  } catch (e) {
    console.error('Error parsing pagination URL:', e);
  }
}

// Modal states
const isCreateOpen = ref(false);
const isEditOpen = ref(false);
const isEditRolesOpen = ref(false);
const isViewOpen = ref(false);
const selectedStaff = ref<Staff | null>(null);
const isDeleteOpen = ref(false);

// Form data
const createForm = useForm({
  name: '',
  email: '',
  password: '',
  password_confirmation: '',
  role_ids: []
});

const editForm = useForm({
  name: '',
  email: '',
  role_ids: []
});

const editRolesForm = useForm({
  role_ids: []
});

// Current staff being edited/deleted/viewed
const editingStaff = ref<Staff | null>(null);
const deletingStaff = ref<Staff | null>(null);
const viewingStaff = ref<Staff | null>(null);

// Filtered staff based on search and role filter
const filteredStaff = computed(() => {
  let filtered = [...(props.staff?.data || [])];

  // Search filter
  if (searchQuery.value) {
    const query = searchQuery.value.toLowerCase();
    filtered = filtered.filter(staff =>
      staff.name.toLowerCase().includes(query) ||
      staff.email.toLowerCase().includes(query) ||
      staff.roles.some(role => role.name.toLowerCase().includes(query))
    );
  }

  // Role filter
  if (roleFilter.value && roleFilter.value !== 'all') {
    filtered = filtered.filter(staff =>
      staff.roles.some(role => role.name === roleFilter.value)
    );
  }

  return filtered;
});

const paginationSummary = computed(() => {
  const meta = props.staff?.meta;
  if (meta) {
    return {
      from: meta.from ?? 0,
      to: meta.to ?? 0,
      total: meta.total ?? totalStaff.value,
    };
  }

  const count = filteredStaff.value.length;
  return {
    from: count ? 1 : 0,
    to: count,
    total: totalStaff.value,
  };
});

// Modal functions
const openCreate = () => {
  createForm.reset();
  createForm.role_ids = [];
  isCreateOpen.value = true;
};

const openEdit = (staff: Staff) => {
  editingStaff.value = staff;
  editForm.name = staff.name;
  editForm.email = staff.email;
  editForm.role_ids = staff.roles.map(r => r.id);
  isEditOpen.value = true;
};

const openEditRoles = (staff: Staff) => {
  editingStaff.value = staff;
  editRolesForm.role_ids = staff.roles.map(r => r.id);
  isEditRolesOpen.value = true;
};

const openDelete = (staff: Staff) => {
  deletingStaff.value = staff;
  isDeleteOpen.value = true;
};

const openView = (staff: Staff) => {
  selectedStaff.value = staff;
  isViewOpen.value = true;
};

// Form submission functions
const submitCreate = (event: Event) => {
  event.preventDefault();

  if (!createForm.name || !createForm.email || !createForm.password) {
    alert('Please fill in all required fields');
    return;
  }

  console.log('Creating staff member...');

  createForm.post('/staff', {
    onSuccess: () => {
      console.log('Staff created successfully');
      isCreateOpen.value = false;
      createForm.reset();
      window.location.reload();
    },
    onError: (errors) => {
      console.error('Failed to create staff:', errors);
      alert('Failed to create staff member');
    }
  });
};

const submitEdit = (event: Event) => {
  event.preventDefault();

  if (!editingStaff.value) {
    alert('No staff member selected');
    return;
  }

  const staffId = editingStaff.value.id;
  console.log('Updating staff ID:', staffId);

  editForm.put(`/staff/${staffId}`, {
    onSuccess: () => {
      console.log('Staff updated successfully');
      isEditOpen.value = false;
      editingStaff.value = null;
      window.location.reload();
    },
    onError: (errors) => {
      console.error('Failed to update staff:', errors);
      alert('Failed to update staff member');
    }
  });
};

const submitEditRoles = (event: Event) => {
  event.preventDefault();

  if (!editingStaff.value) {
    alert('No staff member selected');
    return;
  }

  const staffId = editingStaff.value.id;
  console.log('Updating roles for staff ID:', staffId);

  editRolesForm.put(`/staff/${staffId}/update-roles`, {
    onSuccess: () => {
      console.log('Staff roles updated successfully');
      isEditRolesOpen.value = false;
      editingStaff.value = null;
      window.location.reload();
    },
    onError: (errors) => {
      console.error('Failed to update staff roles:', errors);
      alert('Failed to update staff roles');
    }
  });
};

const confirmDelete = (event: Event) => {
  event.preventDefault();

  if (!deletingStaff.value) {
    alert('No staff member selected');
    return;
  }

  const staffId = deletingStaff.value.id;
  console.log('Deleting staff ID:', staffId);

  router.delete(`/staff/${staffId}`, {
    onSuccess: () => {
      console.log('Staff deleted successfully');
      isDeleteOpen.value = false;
      deletingStaff.value = null;
      window.location.reload();
    },
    onError: (errors) => {
      console.error('Failed to delete staff:', errors);
      alert('Failed to delete staff member');
    }
  });
};

// Helper functions
const getRoleColor = (roleName: string) => {
  switch (roleName.toLowerCase()) {
    case 'dentist': return 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400';
    case 'assistant': return 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400';
    case 'admin': return 'bg-purple-100 text-purple-700 dark:bg-purple-900/30 dark:text-purple-400';
    case 'receptionist': return 'bg-orange-100 text-orange-700 dark:bg-orange-900/30 dark:text-orange-400';
    default: return 'bg-gray-100 text-gray-700 dark:bg-gray-900/30 dark:text-gray-400';
  }
};

const getInitials = (name: string) => {
  return name.split(' ').map(n => n[0]).join('').toUpperCase().slice(0, 2);
};

const formatDate = (dateString: string) => {
  if (!dateString) return 'Never';
  return new Date(dateString).toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
  });
};
</script>
<template>
  <AppLayout title="Staff Management">
    <Head title="Staff Management" />

    <div class="min-h-screen bg-gradient-to-br from-blue-50 via-white to-cyan-50 dark:from-gray-900 dark:via-gray-800 dark:to-blue-900">
      <div class="container mx-auto px-4 py-8">
        <!-- Header Section -->
        <div class="mb-8">
          <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div>
              <h1 class="text-4xl font-bold bg-gradient-to-r from-blue-600 to-cyan-600 bg-clip-text text-transparent mb-2">
                Staff Management
              </h1>
              <p class="text-gray-600 dark:text-gray-400 text-lg">
                Manage your dental practice team members and their roles
              </p>
            </div>

            <div class="flex items-center gap-3">
              <Badge variant="secondary" class="bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400 px-3 py-1 font-medium rounded-md shadow-sm">
                <Users class="w-4 h-4 mr-2" />
                {{ props.staff.total }} Team Members
              </Badge>

              <Button @click="openCreate" class="bg-gradient-to-r from-blue-600 to-cyan-600 hover:from-blue-700 hover:to-cyan-700 text-white shadow-lg hover:shadow-xl transition-all duration-300">
                <Plus class="w-4 h-4 mr-2" />
                Add Staff Member
              </Button>
            </div>
          </div>
        </div>

        <!-- Main Content -->
        <Card class="border-0 shadow-xl bg-white dark:bg-gray-900 mb-8">
          <CardHeader class="pb-4">
            <div>
              <CardTitle class="text-2xl text-gray-900 dark:text-white">Staff Members</CardTitle>
              <CardDescription class="text-gray-600 dark:text-gray-400">
                View and manage all staff members
              </CardDescription>
            </div>
          </CardHeader>

          <CardContent>
            <div class="space-y-6">
              <div class="flex flex-col lg:flex-row gap-4 lg:items-center">
                <div class="relative flex-1 w-full">
                  <Search class="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400 w-4 h-4" />
                  <Input
                    v-model="searchQuery"
                    @update:model-value="() => fetchStaff()"
                    placeholder="Search staff by name or email..."
                    class="pl-10 w-full"
                  />
                </div>
                <div class="w-full lg:w-64">
                  <Select v-model="roleFilter" @update:model-value="() => fetchStaff()">
                    <SelectTrigger>
                      <SelectValue placeholder="Filter by role" />
                    </SelectTrigger>
                    <SelectContent>
                      <SelectItem value="all">All Roles</SelectItem>
                      <SelectItem v-for="role in roles" :key="role.id" :value="role.id">
                        {{ role.name }}
                      </SelectItem>
                    </SelectContent>
                  </Select>
                </div>
              </div>

              <div class="overflow-x-auto border border-gray-200 dark:border-gray-800 rounded-xl shadow-sm">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-800">
                  <thead class="bg-gray-50 dark:bg-gray-800/70">
                    <tr>
                      <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-600 dark:text-gray-300 cursor-pointer" @click="toggleSort('name')">
                        <div class="flex items-center gap-1">
                          <span>Staff Member</span>
                          <span v-if="sortBy === 'name'" class="text-blue-600 dark:text-blue-400">
                            {{ sortOrder === 'asc' ? '↑' : '↓' }}
                          </span>
                        </div>
                      </th>
                      <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-600 dark:text-gray-300 cursor-pointer" @click="toggleSort('email')">
                        <div class="flex items-center gap-1">
                          <span>Contact</span>
                          <span v-if="sortBy === 'email'" class="text-blue-600 dark:text-blue-400">
                            {{ sortOrder === 'asc' ? '↑' : '↓' }}
                          </span>
                        </div>
                      </th>
                      <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-600 dark:text-gray-300">
                        Role
                      </th>
                      <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-600 dark:text-gray-300 cursor-pointer" @click="toggleSort('created_at')">
                        <div class="flex items-center gap-1">
                          <span>Joined Date</span>
                          <span v-if="sortBy === 'created_at'" class="text-blue-600 dark:text-blue-400">
                            {{ sortOrder === 'asc' ? '↑' : '↓' }}
                          </span>
                        </div>
                      </th>
                      <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-gray-600 dark:text-gray-300">Actions</th>
                    </tr>
                  </thead>
                  <tbody class="bg-white dark:bg-gray-900 divide-y divide-gray-100 dark:divide-gray-800">
                    <tr
                      v-for="staffMember in filteredStaff"
                      :key="staffMember.id"
                      class="hover:bg-gray-50 dark:hover:bg-gray-800/60 transition-colors cursor-pointer"
                      @click="openView(staffMember)"
                    >
                      <td class="px-4 py-4 align-top">
                        <div class="flex items-center gap-3">
                          <div class="w-10 h-10 rounded-full bg-gradient-to-br from-blue-500 to-cyan-500 flex items-center justify-center shadow-lg">
                            <span class="text-white font-semibold text-sm">{{ getInitials(staffMember.name) }}</span>
                          </div>
                          <div>
                            <p class="font-semibold text-gray-900 dark:text-white">{{ staffMember.name }}</p>
                            <p class="text-xs text-gray-500 dark:text-gray-400">ID: {{ staffMember.id }}</p>
                          </div>
                        </div>
                      </td>
                      <td class="px-4 py-4 align-top text-sm text-gray-600 dark:text-gray-400">
                        <div class="flex flex-col gap-1">
                          <span class="flex items-center gap-2">
                            <Mail class="w-4 h-4 text-gray-400" />
                            {{ staffMember.email }}
                          </span>
                          <span class="flex items-center gap-2">
                            <UserPlus class="w-4 h-4 text-gray-400" />
                            {{ staffMember.email_verified_at ? 'Verified' : 'Not Verified' }}
                          </span>
                        </div>
                      </td>
                      <td class="px-4 py-4 align-top text-sm text-gray-600 dark:text-gray-400">
                        <div class="flex flex-wrap gap-2">
                          <Badge
                            v-for="role in staffMember.roles"
                            :key="role.id"
                            :class="getRoleColor(role.name)"
                          >
                            <Shield class="w-3 h-3 mr-1" />
                            {{ role.name.charAt(0).toUpperCase() + role.name.slice(1) }}
                          </Badge>
                        </div>
                      </td>
                      <td class="px-4 py-4 align-top text-sm text-gray-600 dark:text-gray-400">
                        <div class="flex items-center gap-2">
                          <Calendar class="w-4 h-4 text-gray-400" />
                          {{ formatDate(staffMember.created_at) }}
                        </div>
                      </td>
                      <td class="px-4 py-4 align-top" @click.stop>
                        <div class="flex items-center justify-end">
                          <DropdownMenu>
                            <DropdownMenuTrigger as-child>
                              <Button variant="ghost" size="sm" class="h-8 w-8 p-0">
                                <MoreVertical class="h-4 w-4" />
                              </Button>
                            </DropdownMenuTrigger>
                            <DropdownMenuContent align="end" class="w-48">
                              <DropdownMenuItem @click="openView(staffMember)">
                                <Eye class="w-4 h-4 mr-2" />
                                View Details
                              </DropdownMenuItem>
                              <DropdownMenuItem @click="openEdit(staffMember)">
                                <Edit class="w-4 h-4 mr-2" />
                                Edit Staff
                              </DropdownMenuItem>
                              <DropdownMenuItem @click="openEditRoles(staffMember)">
                                <Shield class="w-4 h-4 mr-2" />
                                Manage Roles
                              </DropdownMenuItem>
                              <DropdownMenuItem 
                                @click="openDelete(staffMember)" 
                                class="text-red-600"
                              >
                                <Trash class="w-4 h-4 mr-2" />
                                Delete Staff
                              </DropdownMenuItem>
                            </DropdownMenuContent>
                          </DropdownMenu>
                        </div>
                      </td>
                    </tr>
                    <tr v-if="filteredStaff.length === 0">
                      <td colspan="5" class="px-6 py-12 text-center">
                        <div class="flex flex-col items-center gap-4 text-gray-600 dark:text-gray-400">
                          <Users class="w-10 h-10 text-gray-400" />
                          <div>
                            <p class="text-xl font-semibold text-gray-900 dark:text-white">
                              {{ searchQuery || roleFilter !== 'all' ? 'No staff members found' : 'No staff members yet' }}
                            </p>
                            <p class="text-sm">
                              {{ searchQuery || roleFilter !== 'all' ? 'Try adjusting your search criteria' : 'Get started by adding your first staff member.' }}
                            </p>
                          </div>
                          <div class="flex gap-2">
                            <Button @click.stop="openCreate" class="bg-gradient-to-r from-blue-600 to-cyan-600 hover:from-blue-700 hover:to-cyan-700">
                              <Plus class="w-4 h-4 mr-2" />
                              Add Staff Member
                            </Button>
                            <Button v-if="searchQuery || roleFilter !== 'all'" @click.stop="() => { searchQuery.value = ''; roleFilter.value = 'all'; }" variant="outline">
                              Clear Filters
                            </Button>
                          </div>
                        </div>
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
              <div v-if="paginationLinks.length > 0" class="flex flex-col md:flex-row md:items-center md:justify-between items-start gap-4">
                <div class="flex-1">
                  <Pagination
                    v-if="paginationLinks.length > 1"
                    :links="paginationLinks"
                    :from="props.filters?.from ?? staff.meta?.from ?? 1"
                    :to="props.filters?.to ?? staff.meta?.to ?? staff.data.length"
                    :total="totalStaff"
                    item-name="staff members"
                    @page-change="goToPage"
                    class="mt-2 sm:mt-0"
                  />
                </div>
                <div class="flex items-center gap-2">
                  <Label for="per-page" class="text-sm text-gray-600 dark:text-gray-400 whitespace-nowrap">Per page:</Label>
                  <Select v-model="listViewPerPage" @update:model-value="(value) => { listViewPerPage = value; fetchStaff(); }">
                    <SelectTrigger class="h-8 w-20">
                      <SelectValue>{{ listViewPerPage }}</SelectValue>
                    </SelectTrigger>
                    <SelectContent>
                      <SelectItem v-for="option in listPerPageOptions" :key="option" :value="option.toString()">
                        {{ option }}
                      </SelectItem>
                    </SelectContent>
                  </Select>
                </div>
              </div>
            </div>
          </CardContent>
        </Card>
      </div>
    </div>

    <!-- Create Staff Modal -->
    <Dialog :open="isCreateOpen" @update:open="(value) => isCreateOpen = value">
      <DialogContent class="max-w-4xl">
        <DialogHeader>
          <DialogTitle class="text-2xl font-bold bg-gradient-to-r from-blue-600 to-cyan-600 bg-clip-text text-transparent">
            Add New Staff Member
          </DialogTitle>
          <DialogDescription class="text-gray-600 dark:text-gray-400">
            Create a new staff account with appropriate roles.
          </DialogDescription>
        </DialogHeader>

        <form @submit.prevent="submitCreate" class="space-y-6">
          <div class="space-y-2">
            <Label for="name" class="text-gray-700 dark:text-gray-300">Full Name</Label>
            <Input
              id="name"
              v-model="createForm.name"
              placeholder="Enter full name"
              class="h-12 bg-gray-50 dark:bg-gray-800 border-gray-200 dark:border-gray-700"
              required
            />
          </div>

          <div class="space-y-2">
            <Label for="email" class="text-gray-700 dark:text-gray-300">Email Address</Label>
            <Input
              id="email"
              type="email"
              v-model="createForm.email"
              placeholder="staff@example.com"
              class="h-12 bg-gray-50 dark:bg-gray-800 border-gray-200 dark:border-gray-700"
              required
            />
          </div>

          <div class="grid grid-cols-2 gap-4">
            <div class="space-y-2">
              <Label for="password" class="text-gray-700 dark:text-gray-300">Password</Label>
              <Input
                id="password"
                type="password"
                v-model="createForm.password"
                placeholder="Create password"
                class="h-12 bg-gray-50 dark:bg-gray-800 border-gray-200 dark:border-gray-700"
                required
              />
            </div>

            <div class="space-y-2">
              <Label for="password_confirmation" class="text-gray-700 dark:text-gray-300">Confirm Password</Label>
              <Input
                id="password_confirmation"
                type="password"
                v-model="createForm.password_confirmation"
                placeholder="Confirm password"
                class="h-12 bg-gray-50 dark:bg-gray-800 border-gray-200 dark:border-gray-700"
                required
              />
            </div>
          </div>

          <div class="space-y-2">
            <Label class="text-gray-700 dark:text-gray-300">Roles</Label>
            <div class="grid grid-cols-2 gap-2">
              <label
                v-for="role in props.roles"
                :key="role.id"
                class="flex items-center space-x-2 p-3 border rounded-lg hover:bg-gray-50 dark:hover:bg-gray-800 cursor-pointer"
              >
                <input
                  type="checkbox"
                  :value="role.id"
                  v-model="createForm.role_ids"
                  class="rounded border-gray-300 dark:border-gray-600"
                />
                <span class="text-sm text-gray-700 dark:text-gray-300">{{ role.name }}</span>
              </label>
            </div>
          </div>

          <DialogFooter class="gap-2">
            <Button type="button" variant="outline" @click="isCreateOpen = false">
              Cancel
            </Button>
            <Button
              type="submit"
              :disabled="createForm.processing"
              class="bg-blue-600 hover:bg-blue-700 text-white"
            >
              <i v-if="createForm.processing" class="fas fa-spinner fa-spin mr-2"></i>
              <i v-else class="fas fa-user-plus mr-2"></i>
              {{ createForm.processing ? 'Creating...' : 'Create Staff Member' }}
            </Button>
          </DialogFooter>
        </form>
      </DialogContent>
    </Dialog>

    <!-- Edit Staff Modal -->
    <Dialog :open="isEditOpen" @update:open="(value) => isEditOpen = value">
      <DialogContent class="max-w-4xl">
        <DialogHeader>
          <DialogTitle class="text-2xl font-bold bg-gradient-to-r from-blue-600 to-cyan-600 bg-clip-text text-transparent">
            Edit Staff Member
          </DialogTitle>
          <DialogDescription class="text-gray-600 dark:text-gray-400">
            Update the staff member's information.
          </DialogDescription>
        </DialogHeader>

        <form @submit.prevent="submitEdit" class="space-y-6">
          <div class="space-y-2">
            <Label for="edit-name" class="text-gray-700 dark:text-gray-300">Full Name</Label>
            <Input
              id="edit-name"
              v-model="editForm.name"
              placeholder="Enter full name"
              class="h-12 bg-gray-50 dark:bg-gray-800 border-gray-200 dark:border-gray-700"
              required
            />
          </div>

          <div class="space-y-2">
            <Label for="edit-email" class="text-gray-700 dark:text-gray-300">Email Address</Label>
            <Input
              id="edit-email"
              type="email"
              v-model="editForm.email"
              placeholder="staff@example.com"
              class="h-12 bg-gray-50 dark:bg-gray-800 border-gray-200 dark:border-gray-700"
              required
            />
          </div>

          <div class="space-y-2">
            <Label class="text-gray-700 dark:text-gray-300">Roles</Label>
            <div class="grid grid-cols-2 gap-2">
              <label
                v-for="role in props.roles"
                :key="role.id"
                class="flex items-center space-x-2 p-3 border rounded-lg hover:bg-gray-50 dark:hover:bg-gray-800 cursor-pointer"
              >
                <input
                  type="checkbox"
                  :value="role.id"
                  v-model="editForm.role_ids"
                  class="rounded border-gray-300 dark:border-gray-600"
                />
                <span class="text-sm text-gray-700 dark:text-gray-300">{{ role.name }}</span>
              </label>
            </div>
          </div>

          <DialogFooter class="gap-2">
            <Button type="button" variant="outline" @click="isEditOpen = false">
              Cancel
            </Button>
            <Button
              type="submit"
              :disabled="editForm.processing"
              class="bg-blue-600 hover:bg-blue-700 text-white"
            >
              <i v-if="editForm.processing" class="fas fa-spinner fa-spin mr-2"></i>
              <i v-else class="fas fa-save mr-2"></i>
              {{ editForm.processing ? 'Saving...' : 'Save Changes' }}
            </Button>
          </DialogFooter>
        </form>
      </DialogContent>
    </Dialog>

    <!-- Edit Roles Modal -->
    <Dialog :open="isEditRolesOpen" @update:open="(value) => isEditRolesOpen = value">
      <DialogContent class="max-w-4xl">
        <DialogHeader>
          <DialogTitle class="text-2xl font-bold bg-gradient-to-r from-blue-600 to-cyan-600 bg-clip-text text-transparent">
            Manage Roles
          </DialogTitle>
          <DialogDescription class="text-gray-600 dark:text-gray-400">
            Update roles for {{ editingStaff?.name }}
          </DialogDescription>
        </DialogHeader>

        <form @submit.prevent="submitEditRoles" class="space-y-6">
          <div class="space-y-2">
            <Label class="text-gray-700 dark:text-gray-300">Assign Roles</Label>
            <div class="grid grid-cols-2 gap-2">
              <label
                v-for="role in props.roles"
                :key="role.id"
                class="flex items-center space-x-2 p-3 border rounded-lg hover:bg-gray-50 dark:hover:bg-gray-800 cursor-pointer"
              >
                <input
                  type="checkbox"
                  :value="role.id"
                  v-model="editRolesForm.role_ids"
                  class="rounded border-gray-300 dark:border-gray-600"
                />
                <span class="text-sm text-gray-700 dark:text-gray-300">{{ role.name }}</span>
              </label>
            </div>
          </div>

          <DialogFooter class="gap-2">
            <Button type="button" variant="outline" @click="isEditRolesOpen = false">
              Cancel
            </Button>
            <Button
              type="submit"
              :disabled="editRolesForm.processing"
              class="bg-gradient-to-r from-blue-600 to-cyan-600 hover:from-blue-700 hover:to-cyan-700"
            >
              <i v-if="editRolesForm.processing" class="fas fa-spinner fa-spin mr-2"></i>
              <i v-else class="fas fa-shield-alt mr-2"></i>
              {{ editRolesForm.processing ? 'Updating...' : 'Update Roles' }}
            </Button>
          </DialogFooter>
        </form>
      </DialogContent>
    </Dialog>

    <!-- Delete Modal -->
    <Dialog :open="isDeleteOpen" @update:open="(value) => isDeleteOpen = value">
      <DialogContent class="max-w-4xl">
        <DialogHeader>
          <DialogTitle class="text-xl font-bold text-red-600">Remove Staff Member</DialogTitle>
          <DialogDescription class="text-gray-600 dark:text-gray-400">
            This action cannot be undone. This will permanently remove the staff member from the system.
          </DialogDescription>
        </DialogHeader>

        <div class="py-4">
          <div class="flex items-center space-x-3 p-4 bg-red-50 dark:bg-red-900/20 rounded-lg border border-red-200 dark:border-red-800">
            <i class="fas fa-exclamation-triangle text-red-600 text-xl"></i>
            <div>
              <p class="font-medium text-red-800 dark:text-red-200">Remove {{ deletingStaff?.name }}?</p>
              <p class="text-sm text-red-600 dark:text-red-400">This will revoke their access and remove all associated data.</p>
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
            <i class="fas fa-user-times mr-2"></i>
            Remove Staff Member
          </Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>

    <!-- View Staff Modal -->
    <Dialog :open="isViewOpen" @update:open="(value) => isViewOpen = value">
      <DialogContent class="max-w-2xl">
        <DialogHeader>
          <DialogTitle class="text-2xl font-bold bg-gradient-to-r from-blue-600 to-cyan-600 bg-clip-text text-transparent">
            Staff Details
          </DialogTitle>
        </DialogHeader>
        
        <div v-if="selectedStaff" class="space-y-6">
          <div class="flex items-start gap-6">
            <div class="w-20 h-20 rounded-full bg-gradient-to-br from-blue-500 to-cyan-500 flex items-center justify-center shadow-lg">
              <span class="text-white font-semibold text-2xl">{{ getInitials(selectedStaff.name) }}</span>
            </div>
            <div class="flex-1">
              <h3 class="text-xl font-bold text-gray-900 dark:text-white">{{ selectedStaff.name }}</h3>
              <p class="text-gray-600 dark:text-gray-400">{{ selectedStaff.email }}</p>
              <div class="mt-2 flex flex-wrap gap-2">
                <Badge 
                  v-for="role in selectedStaff.roles" 
                  :key="role.id"
                  :class="getRoleColor(role.name)"
                >
                  <Shield class="w-3 h-3 mr-1" />
                  {{ role.name.charAt(0).toUpperCase() + role.name.slice(1) }}
                </Badge>
              </div>
            </div>
          </div>

          <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
              <h4 class="text-sm font-medium text-gray-500 dark:text-gray-400 mb-2">Contact Information</h4>
              <div class="space-y-2">
                <p class="flex items-center gap-2 text-gray-700 dark:text-gray-300">
                  <Mail class="w-4 h-4 text-gray-400" />
                  {{ selectedStaff.email }}
                </p>
                <p class="flex items-center gap-2 text-gray-700 dark:text-gray-300">
                  <UserPlus class="w-4 h-4 text-gray-400" />
                  {{ selectedStaff.email_verified_at ? 'Email Verified' : 'Email Not Verified' }}
                </p>
              </div>
            </div>

            <div>
              <h4 class="text-sm font-medium text-gray-500 dark:text-gray-400 mb-2">Account Information</h4>
              <div class="space-y-2">
                <p class="text-gray-700 dark:text-gray-300">
                  <span class="font-medium">Member Since:</span> {{ formatDate(selectedStaff.created_at) }}
                </p>
                <p class="text-gray-700 dark:text-gray-300">
                  <span class="font-medium">Status: </span>
                  <span :class="selectedStaff.email_verified_at ? 'text-green-600' : 'text-yellow-600'">
                    {{ selectedStaff.email_verified_at ? 'Active' : 'Pending Verification' }}
                  </span>
                </p>
              </div>
            </div>
          </div>
        </div>

        <DialogFooter>
          <Button type="button" variant="outline" @click="isViewOpen = false">Close</Button>
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
</style>
