<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/Components/ui/card';
import { Badge } from '@/Components/ui/badge';
import { Button } from '@/Components/ui/button';
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuTrigger } from '@/Components/ui/dropdown-menu';
import { Building2, Plus, Eye, Edit, Settings, Users, MoreVertical } from 'lucide-vue-next';

interface Clinic {
    id: number;
    name: string;
    email: string;
    phone: string;
    address: string;
    is_active: boolean;
    subscription_status: string;
    users_count: number;
    created_at: string;
}

const props = defineProps<{
    clinics: Clinic[];
}>();

const page = usePage();
const currentUser = computed(() => page.props.auth.user);

const canManageClinics = computed(() => {
    const permissions = currentUser.value?.permissions || [];
    const roles = currentUser.value?.roles || [];
    
    return permissions.includes('manage-clinics') || 
           roles.some((r: any) => r?.name === 'super-admin');
});

const currentClinic = computed(() => {
    if (!currentUser.value?.clinic_id) return null;
    return props.clinics.find(clinic => clinic.id === currentUser.value.clinic_id);
});

const currentClinicName = computed(() => {
    return currentClinic.value?.name || 'No Clinic Assigned';
});
</script>

<template>
    <Head title="Clinics" />

    <AppLayout title="Clinics">
        <template #header>
            <div class="px-6 py-4">
                <div class="flex justify-between items-center">
                    <div class="flex items-center space-x-4">
                        <div>
                            <h1 class="text-4xl font-bold bg-gradient-to-r from-blue-600 to-cyan-600 bg-clip-text text-transparent mb-2">
                                {{ currentClinicName }}
                            </h1>
                            <p class="text-gray-600 dark:text-gray-400 text-lg">
                                Manage dental clinic locations and settings
                            </p>
                        </div>
                    </div>

                    <div class="flex items-center gap-4">
                        <Badge variant="secondary" class="bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400 px-3 py-1 font-medium rounded-md shadow-sm">
                            <Building2 class="w-4 h-4 mr-2" />
                            {{ props.clinics.length }} Clinics
                        </Badge>

                        <Link v-if="canManageClinics" :href="route('clinics.create')" class="inline-flex items-center bg-gradient-to-r from-blue-600 to-cyan-600 hover:from-blue-700 hover:to-cyan-700 text-white shadow-lg hover:shadow-xl transition-all duration-300 px-4 py-2 rounded-md">
                            <Plus class="w-4 h-4 mr-2" />
                            Add New Clinic
                        </Link>
                    </div>
                </div>
            </div>
        </template>

        <div class="py-6">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
                <!-- Main Content -->
                <Card class="border-0 shadow-xl bg-white dark:bg-gray-900 mb-8">
                    <CardHeader class="pb-4">
                        <div>
                            <CardTitle class="text-2xl text-gray-900 dark:text-white">All Clinics</CardTitle>
                            <CardDescription class="text-gray-600 dark:text-gray-400">
                                View and manage all clinic locations
                            </CardDescription>
                        </div>
                    </CardHeader>

                    <CardContent>
                        <div class="overflow-hidden shadow ring-1 ring-black ring-opacity-5 dark:ring-gray-600 md:rounded-lg">
                            <table class="min-w-full divide-y divide-gray-300 dark:divide-gray-600">
                                <thead class="bg-gray-50 dark:bg-gray-800">
                                    <tr>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                            Name
                                        </th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                            Email
                                        </th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                            Phone
                                        </th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                            Status
                                        </th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                            Users
                                        </th>
                                        <th scope="col" class="relative px-6 py-3">
                                            <span class="sr-only">Actions</span>
                                        </th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white dark:bg-gray-900 divide-y divide-gray-200 dark:divide-gray-700">
                                    <tr v-for="clinic in props.clinics" :key="clinic.id" class="hover:bg-gray-50 dark:hover:bg-gray-800 transition-colors">
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm font-medium text-gray-900 dark:text-white">
                                                {{ clinic.name }}
                                            </div>
                                            <div class="text-sm text-gray-500 dark:text-gray-400">
                                                {{ clinic.address }}
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm text-gray-900 dark:text-gray-100">{{ clinic.email }}</div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm text-gray-900 dark:text-gray-100">{{ clinic.phone }}</div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="flex gap-2">
                                                <Badge
                                                    :variant="clinic.is_active ? 'default' : 'secondary'"
                                                    :class="clinic.is_active 
                                                        ? 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400' 
                                                        : 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400'"
                                                >
                                                    {{ clinic.is_active ? 'Active' : 'Inactive' }}
                                                </Badge>
                                                <Badge
                                                    :variant="clinic.subscription_status === 'active' ? 'default' : 'secondary'"
                                                    :class="clinic.subscription_status === 'active'
                                                        ? 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400'
                                                        : clinic.subscription_status === 'trial'
                                                        ? 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400'
                                                        : 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400'"
                                                >
                                                    {{ clinic.subscription_status }}
                                                </Badge>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="flex items-center gap-2">
                                                <Users class="w-4 h-4 text-gray-400" />
                                                <span class="text-sm text-gray-900 dark:text-gray-100">{{ clinic.users_count }}</span>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                            <DropdownMenu>
                                                <DropdownMenuTrigger as-child>
                                                    <Button variant="ghost" size="sm">
                                                        <MoreVertical class="h-4 w-4" />
                                                    </Button>
                                                </DropdownMenuTrigger>
                                                <DropdownMenuContent align="end">
                                                    <DropdownMenuItem @click="$inertia.visit(route('clinics.show', clinic.id))">
                                                        <Eye class="mr-2 h-4 w-4" />
                                                        View
                                                    </DropdownMenuItem>
                                                    <DropdownMenuItem @click="$inertia.visit(route('clinics.edit', clinic.id))">
                                                        <Edit class="mr-2 h-4 w-4" />
                                                        Edit
                                                    </DropdownMenuItem>
                                                    <DropdownMenuItem @click="$inertia.visit(route('clinics.settings', clinic.id))">
                                                        <Settings class="mr-2 h-4 w-4" />
                                                        Settings
                                                    </DropdownMenuItem>
                                                </DropdownMenuContent>
                                            </DropdownMenu>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </div>
    </AppLayout>
</template>
