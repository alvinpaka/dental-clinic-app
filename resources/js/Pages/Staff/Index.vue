<script setup lang="ts">
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/Components/ui/card';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { Badge } from '@/Components/ui/badge';
import { Button } from '@/Components/ui/button';
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogDescription, DialogFooter } from '@/Components/ui/dialog';
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuTrigger } from '@/Components/ui/dropdown-menu';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/Components/ui/select';
import { Plus, Search, MoreVertical, Users, UserPlus, Shield, Mail, Calendar, Edit, Trash } from 'lucide-vue-next';
import AppLayout from '@/Layouts/AppLayout.vue';

interface Role {
  id: number;
  name: string;
}

interface Staff {
  id: number;
  name: string;
  email: string;
  roles: Role[];
  created_at?: string;
  last_login?: string;
}

interface Props {
  staff: {
    data: Staff[];
    links: any[];
  };
  roles: Role[];
  stats?: {
    total_staff: number;
    active_staff: number;
    dentists: number;
    assistants: number;
  };
}

const props = defineProps<Props>();

const searchQuery = ref('');
const roleFilter = ref('');

// Filtered staff
const filteredStaff = computed(() => {
  let staff = [...props.staff.data];

  // Search filter
  if (searchQuery.value) {
    staff = staff.filter(member =>
      member.name.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
      member.email.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
      member.roles.some(role => role.name.toLowerCase().includes(searchQuery.value.toLowerCase()))
    );
  }

  // Role filter
  if (roleFilter.value) {
    staff = staff.filter(member =>
      member.roles.some(role => role.name === roleFilter.value)
    );
  }

  return staff.sort((a, b) => a.name.localeCompare(b.name));
});

// Forms
const createForm = useForm({
  name: '',
  email: '',
  password: '',
  password_confirmation: '',
  role_ids: [] as number[],
});

const editForm = useForm({
  name: '',
  email: '',
  role_ids: [] as number[],
});

const editRolesForm = useForm({
  role_ids: [] as number[],
});

// Modal states
const isCreateOpen = ref(false);
const isEditOpen = ref(false);
const isDeleteOpen = ref(false);
const isEditRolesOpen = ref(false);
const editingStaff = ref<Staff | null>(null);
const deletingStaff = ref<Staff | null>(null);

// Event handlers
const openCreate = () => {
  createForm.reset();
  isCreateOpen.value = true;
};

const openEdit = (staff: Staff) => {
  editingStaff.value = staff;
  editForm.name = staff.name;
  editForm.email = staff.email;
  editForm.role_ids = staff.roles.map(r => r.id);
  isEditOpen.value = true;
};

const openDelete = (staff: Staff) => {
  deletingStaff.value = staff;
  isDeleteOpen.value = true;
};

const openEditRoles = (staff: Staff) => {
  editingStaff.value = staff;
  editRolesForm.role_ids = staff.roles.map(r => r.id);
  isEditRolesOpen.value = true;
};

const submitCreate = () => {
  if (!createForm.name || !createForm.email || !createForm.password) {
    alert('Please fill in all required fields');
    return;
  }

  createForm.post(route('staff.store'), {
    onSuccess: () => {
      createForm.reset();
      isCreateOpen.value = false;
    },
  });
};

const submitEdit = () => {
  if (!editForm.name || !editForm.email) {
    alert('Please fill in all required fields');
    return;
  }

  if (editingStaff.value) {
    editForm.put(route('staff.update', editingStaff.value.id), {
      onSuccess: () => {
        editForm.reset();
        isEditOpen.value = false;
        editingStaff.value = null;
      },
    });
  }
};

const submitEditRoles = () => {
  if (editingStaff.value) {
    editRolesForm.put(route('staff.update-roles', editingStaff.value.id), {
      onSuccess: () => {
        editRolesForm.reset();
        isEditRolesOpen.value = false;
        editingStaff.value = null;
      },
    });
  }
};

const confirmDelete = () => {
  if (deletingStaff.value) {
    router.delete(route('staff.destroy', deletingStaff.value.id), {
      onSuccess: () => {
        isDeleteOpen.value = false;
        deletingStaff.value = null;
      },
    });
  }
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
  <AppLayout title="Staff">
    <Head title="Staff" />

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
              <Badge variant="secondary" class="bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400">
                <Users class="w-4 h-4 mr-1" />
                {{ props.staff.data.length }} Team Members
              </Badge>

              <Button @click="openCreate" class="bg-gradient-to-r from-blue-600 to-cyan-600 hover:from-blue-700 hover:to-cyan-700 text-white shadow-lg hover:shadow-xl transition-all duration-300">
                <Plus class="w-4 h-4 mr-2" />
                Add Staff Member
              </Button>
            </div>
          </div>
        </div>

        <!-- Stats Overview -->
        <div v-if="props.stats" class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
          <Card class="border-0 shadow-xl hover:shadow-2xl transition-all duration-300 hover:-translate-y-1 bg-gradient-to-br from-blue-50 to-blue-100 dark:from-blue-900/20 dark:to-blue-800/20">
            <CardContent class="p-6">
              <div class="flex items-center justify-between">
                <div>
                  <p class="text-sm font-medium text-blue-700 dark:text-blue-300 mb-1">Total Staff</p>
                  <p class="text-3xl font-bold text-blue-900 dark:text-blue-100 mb-1">{{ props.stats.total_staff }}</p>
                  <p class="text-xs text-blue-600 dark:text-blue-400">All team members</p>
                </div>
                <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-blue-500 to-blue-600 flex items-center justify-center shadow-lg">
                  <Users class="w-6 h-6 text-white" />
                </div>
              </div>
            </CardContent>
          </Card>

          <Card class="border-0 shadow-xl hover:shadow-2xl transition-all duration-300 hover:-translate-y-1 bg-gradient-to-br from-green-50 to-green-100 dark:from-green-900/20 dark:to-green-800/20">
            <CardContent class="p-6">
              <div class="flex items-center justify-between">
                <div>
                  <p class="text-sm font-medium text-green-700 dark:text-green-300 mb-1">Active Staff</p>
                  <p class="text-3xl font-bold text-green-900 dark:text-green-100 mb-1">{{ props.stats.active_staff }}</p>
                  <p class="text-xs text-green-600 dark:text-green-400">Currently active</p>
                </div>
                <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-green-500 to-green-600 flex items-center justify-center shadow-lg">
                  <UserPlus class="w-6 h-6 text-white" />
                </div>
              </div>
            </CardContent>
          </Card>

          <Card class="border-0 shadow-xl hover:shadow-2xl transition-all duration-300 hover:-translate-y-1 bg-gradient-to-br from-purple-50 to-purple-100 dark:from-purple-900/20 dark:to-purple-800/20">
            <CardContent class="p-6">
              <div class="flex items-center justify-between">
                <div>
                  <p class="text-sm font-medium text-purple-700 dark:text-purple-300 mb-1">Dentists</p>
                  <p class="text-3xl font-bold text-purple-900 dark:text-purple-100 mb-1">{{ props.stats.dentists }}</p>
                  <p class="text-xs text-purple-600 dark:text-purple-400">Licensed dentists</p>
                </div>
                <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-purple-500 to-purple-600 flex items-center justify-center shadow-lg">
                  <Shield class="w-6 h-6 text-white" />
                </div>
              </div>
            </CardContent>
          </Card>

          <Card class="border-0 shadow-xl hover:shadow-2xl transition-all duration-300 hover:-translate-y-1 bg-gradient-to-br from-orange-50 to-orange-100 dark:from-orange-900/20 dark:to-orange-800/20">
            <CardContent class="p-6">
              <div class="flex items-center justify-between">
                <div>
                  <p class="text-sm font-medium text-orange-700 dark:text-orange-300 mb-1">Assistants</p>
                  <p class="text-3xl font-bold text-orange-900 dark:text-orange-100 mb-1">{{ props.stats.assistants }}</p>
                  <p class="text-xs text-orange-600 dark:text-orange-400">Dental assistants</p>
                </div>
                <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-orange-500 to-orange-600 flex items-center justify-center shadow-lg">
                  <Calendar class="w-6 h-6 text-white" />
                </div>
              </div>
            </CardContent>
          </Card>
        </div>

        <!-- Search and Filters -->
        <Card class="border-0 shadow-xl bg-white dark:bg-gray-900 mb-8">
          <CardContent class="p-6">
            <div class="flex flex-col md:flex-row gap-4 items-center">
              <div class="relative flex-1">
                <Search class="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400 w-4 h-4" />
                <Input
                  v-model="searchQuery"
                  placeholder="Search staff by name, email, or role..."
                  class="pl-10 h-12 bg-gray-50 dark:bg-gray-800 border-gray-200 dark:border-gray-700"
                />
              </div>

              <Select v-model="roleFilter">
                <SelectTrigger class="w-48 h-12">
                  <SelectValue placeholder="Filter by role" />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem value="">All Roles</SelectItem>
                  <SelectItem v-for="role in props.roles" :key="role.id" :value="role.name">
                    {{ role.name }}
                  </SelectItem>
                </SelectContent>
              </Select>
            </div>
          </CardContent>
        </Card>

        <!-- Staff Grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
          <Card
            v-for="(member, index) in filteredStaff"
            :key="member.id"
            class="border-0 shadow-xl hover:shadow-2xl transition-all duration-300 hover:-translate-y-2 bg-white dark:bg-gray-900 group"
          >
            <CardHeader class="pb-4">
              <div class="flex items-start justify-between">
                <div class="flex items-center space-x-3">
                  <div class="w-12 h-12 rounded-full bg-gradient-to-br from-blue-500 to-cyan-500 flex items-center justify-center shadow-lg">
                    <span class="text-white font-bold text-lg">{{ getInitials(member.name) }}</span>
                  </div>
                  <div>
                    <CardTitle class="text-lg text-gray-900 dark:text-white group-hover:text-blue-600 dark:group-hover:text-cyan-400 transition-colors">
                      {{ member.name }}
                    </CardTitle>
                    <CardDescription class="text-gray-600 dark:text-gray-400">
                      ID: {{ member.id }}
                    </CardDescription>
                  </div>
                </div>

                <DropdownMenu>
                  <DropdownMenuTrigger as-child>
                    <Button variant="ghost" size="sm" class="h-8 w-8 p-0 opacity-0 group-hover:opacity-100 transition-opacity">
                      <MoreVertical class="h-4 w-4" />
                    </Button>
                  </DropdownMenuTrigger>
                  <DropdownMenuContent align="end">
                    <DropdownMenuItem @click="openEdit(member)">
                      <Edit class="w-4 h-4 mr-2" />
                      Edit Profile
                    </DropdownMenuItem>
                    <DropdownMenuItem @click="openEditRoles(member)">
                      <Shield class="w-4 h-4 mr-2" />
                      Manage Roles
                    </DropdownMenuItem>
                    <DropdownMenuItem @click="openDelete(member)" class="text-red-600">
                      <Trash class="w-4 h-4 mr-2" />
                      Remove Staff
                    </DropdownMenuItem>
                  </DropdownMenuContent>
                </DropdownMenu>
              </div>
            </CardHeader>

            <CardContent class="space-y-4">
              <div class="flex items-center space-x-2 text-sm">
                <Mail class="w-4 h-4 text-gray-400" />
                <span class="text-gray-600 dark:text-gray-400">{{ member.email }}</span>
              </div>

              <div class="flex items-center space-x-2 text-sm">
                <Calendar class="w-4 h-4 text-gray-400" />
                <span class="text-gray-600 dark:text-gray-400">Joined {{ formatDate(member.created_at || '') }}</span>
              </div>

              <div class="flex flex-wrap gap-2">
                <Badge
                  v-for="role in member.roles"
                  :key="role.id"
                  :class="getRoleColor(role.name)"
                  variant="secondary"
                  class="text-xs"
                >
                  {{ role.name }}
                </Badge>
              </div>

              <div class="flex justify-between items-center pt-4 border-t border-gray-100 dark:border-gray-800">
                <Button variant="outline" size="sm" as-child>
                  <Link :href="route('staff.show', member.id)">
                    View Profile
                  </Link>
                </Button>
                <Button size="sm" @click="openEditRoles(member)">
                  <Shield class="w-4 h-4 mr-2" />
                  Roles
                </Button>
              </div>
            </CardContent>
          </Card>

          <!-- Empty State -->
          <div v-if="filteredStaff.length === 0" class="col-span-full">
            <Card class="border-0 shadow-xl bg-white dark:bg-gray-900">
              <CardContent class="p-12 text-center">
                <div class="w-24 h-24 mx-auto mb-6 rounded-full bg-gradient-to-br from-gray-100 to-gray-200 dark:from-gray-800 dark:to-gray-700 flex items-center justify-center">
                  <Users class="w-12 h-12 text-gray-400" />
                </div>
                <h3 class="text-xl font-semibold text-gray-900 dark:text-white mb-2">
                  {{ searchQuery || roleFilter ? 'No staff members found' : 'No staff members yet' }}
                </h3>
                <p class="text-gray-600 dark:text-gray-400 mb-6">
                  {{ searchQuery || roleFilter ? 'Try adjusting your search criteria' : 'Get started by adding your first team member' }}
                </p>
                <Button v-if="!searchQuery && !roleFilter" @click="openCreate" class="bg-gradient-to-r from-blue-600 to-cyan-600 hover:from-blue-700 hover:to-cyan-700">
                  <Plus class="w-4 h-4 mr-2" />
                  Add First Staff Member
                </Button>
                <Button v-else @click="searchQuery = ''; roleFilter = ''" variant="outline">
                  Clear Filters
                </Button>
              </CardContent>
            </Card>
          </div>
        </div>
      </div>
    </div>

    <!-- Create Staff Modal -->
    <Dialog :open="isCreateOpen" @update:open="(value) => isCreateOpen = value">
      <DialogContent class="max-w-md">
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
              class="bg-gradient-to-r from-blue-600 to-cyan-600 hover:from-blue-700 hover:to-cyan-700"
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
      <DialogContent class="max-w-md">
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
              class="bg-gradient-to-r from-blue-600 to-cyan-600 hover:from-blue-700 hover:to-cyan-700"
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
      <DialogContent class="max-w-md">
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
      <DialogContent class="max-w-md">
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
  </AppLayout>
</template>

<style scoped>
.bg-clip-text {
  -webkit-background-clip: text;
  background-clip: text;
  -webkit-text-fill-color: transparent;
}
</style>