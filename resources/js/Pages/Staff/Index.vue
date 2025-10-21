<script setup lang="ts">
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/Components/ui/card';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { Badge } from '@/Components/ui/badge';
import { Button } from '@/Components/ui/button';
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogDescription, DialogFooter } from '@/Components/ui/dialog';
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuTrigger } from '@/Components/ui/dropdown-menu';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/Components/ui/tabs';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/Components/ui/select';
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

// Props
const props = defineProps<{
  staff: {
    data: Staff[];
    current_page: number;
    per_page: number;
    total: number;
  };
  roles: Role[];
}>();

// Reactive data
const activeTab = ref('grid');
const searchQuery = ref('');
const roleFilter = ref('all');

// Modal states
const isCreateOpen = ref(false);
const isEditOpen = ref(false);
const isEditRolesOpen = ref(false);
const isDeleteOpen = ref(false);
const isViewOpen = ref(false);

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
  let filtered = props.staff.data;

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
  viewingStaff.value = staff;
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
            <Tabs v-model="activeTab" class="w-full">
              <TabsList class="grid w-full grid-cols-2">
                <TabsTrigger value="grid">Grid View</TabsTrigger>
                <TabsTrigger value="list">List View</TabsTrigger>
              </TabsList>

              <!-- Grid View -->
              <TabsContent value="grid" class="mt-6">
                <div class="space-y-6">
                  <!-- Search and Filters -->
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
                        <SelectItem value="all">All Roles</SelectItem>
                        <SelectItem v-for="role in props.roles" :key="role.id" :value="role.name">
                          {{ role.name }}
                        </SelectItem>
                      </SelectContent>
                    </Select>
                  </div>

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
                              <DropdownMenuItem @click="openView(member)">
                                <Eye class="w-4 h-4 mr-2" />
                                View Profile
                              </DropdownMenuItem>
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
                            class="text-xs px-3 py-1 font-medium rounded-md shadow-sm"
                          >
                            {{ role.name }}
                          </Badge>
                        </div>
                      </CardContent>
                    </Card>
                  </div>
                </div>
              </TabsContent>

              <!-- List View -->
              <TabsContent value="list" class="mt-6">
                <div class="space-y-4">
                  <!-- Search and Filters -->
                  <div class="flex flex-col md:flex-row gap-4 items-center">
                    <div class="relative flex-1">
                      <Search class="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400 w-4 h-4" />
                      <Input
                        v-model="searchQuery"
                        placeholder="Search staff..."
                        class="pl-10 h-12 bg-gray-50 dark:bg-gray-800 border-gray-200 dark:border-gray-700"
                      />
                    </div>

                    <Select v-model="roleFilter">
                      <SelectTrigger class="w-48 h-12">
                        <SelectValue placeholder="Filter by role" />
                      </SelectTrigger>
                      <SelectContent>
                        <SelectItem value="all">All Roles</SelectItem>
                        <SelectItem v-for="role in props.roles" :key="role.id" :value="role.name">
                          {{ role.name }}
                        </SelectItem>
                      </SelectContent>
                    </Select>
                  </div>

                  <!-- Staff List -->
                  <div class="space-y-3">
                    <Card
                      v-for="(member, index) in filteredStaff"
                      :key="member.id"
                      class="border hover:shadow-md transition-shadow"
                    >
                      <CardContent class="p-4">
                        <div class="flex items-center justify-between">
                          <div class="flex items-center space-x-3">
                            <div class="w-10 h-10 rounded-full bg-gradient-to-br from-blue-500 to-cyan-500 flex items-center justify-center shadow-lg">
                              <span class="text-white font-bold text-sm">{{ getInitials(member.name) }}</span>
                            </div>
                            <div>
                              <h4 class="font-medium text-gray-900 dark:text-white">
                                {{ member.name }}
                              </h4>
                              <p class="text-sm text-gray-600 dark:text-gray-400">
                                {{ member.email }} • Joined {{ formatDate(member.created_at || '') }}
                              </p>
                            </div>
                          </div>

                          <div class="flex items-center space-x-2">
                            <div class="flex flex-wrap gap-1">
                              <Badge
                                v-for="role in member.roles.slice(0, 2)"
                                :key="role.id"
                                :class="getRoleColor(role.name)"
                                variant="secondary"
                                class="text-xs px-3 py-1 font-medium rounded-md shadow-sm"
                              >
                                {{ role.name }}
                              </Badge>
                              <Badge
                                v-if="member.roles.length > 2"
                                variant="outline"
                                class="text-xs px-3 py-1 font-medium rounded-md shadow-sm border-gray-300 text-gray-600 dark:border-gray-600 dark:text-gray-400"
                              >
                                +{{ member.roles.length - 2 }}
                              </Badge>
                            </div>

                            <DropdownMenu>
                              <DropdownMenuTrigger as-child @click.stop>
                                <Button variant="ghost" size="sm" class="h-8 w-8 p-0">
                                  <MoreVertical class="h-4 w-4" />
                                </Button>
                              </DropdownMenuTrigger>
                              <DropdownMenuContent align="end">
                                <DropdownMenuItem @click.stop="openView(member)">
                                  <Eye class="w-4 h-4 mr-2" />
                                  View Profile
                                </DropdownMenuItem>
                                <DropdownMenuItem @click.stop="openEdit(member)">
                                  <Edit class="w-4 h-4 mr-2" />
                                  Edit Profile
                                </DropdownMenuItem>
                                <DropdownMenuItem @click.stop="openEditRoles(member)">
                                  <Shield class="w-4 h-4 mr-2" />
                                  Manage Roles
                                </DropdownMenuItem>
                                <DropdownMenuItem @click.stop="openDelete(member)" class="text-red-600">
                                  <Trash class="w-4 h-4 mr-2" />
                                  Remove Staff
                                </DropdownMenuItem>
                              </DropdownMenuContent>
                            </DropdownMenu>
                          </div>
                        </div>
                      </CardContent>
                    </Card>
                  </div>
                </div>
              </TabsContent>
            </Tabs>
          </CardContent>
        </Card>
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
