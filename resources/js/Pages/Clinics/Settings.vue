<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { ref } from 'vue';

interface Clinic {
    id: number;
    name: string;
    email: string;
    phone: string;
    address: string;
    logo_path?: string;
    primary_color: string;
    secondary_color: string;
    timezone: string;
    currency: string;
    business_hours: Array<{
        day: string;
        open: string;
        close: string;
        closed: boolean;
    }>;
    settings: {
        appointment_reminder_hours?: number;
        payment_reminder_days?: number;
        auto_invoice?: boolean;
        patient_portal?: boolean;
    };
}

interface Timezone {
    name: string;
    offset: string;
}

defineProps<{
    clinic: Clinic;
    timezones: string[];
    currencies: Record<string, string>;
}>();

const form = useForm({
    name: clinic.name,
    email: clinic.email,
    phone: clinic.phone,
    address: clinic.address,
    primary_color: clinic.primary_color,
    secondary_color: clinic.secondary_color,
    timezone: clinic.timezone,
    currency: clinic.currency,
    business_hours: clinic.business_hours || [
        { day: 'monday', open: '09:00', close: '17:00', closed: false },
        { day: 'tuesday', open: '09:00', close: '17:00', closed: false },
        { day: 'wednesday', open: '09:00', close: '17:00', closed: false },
        { day: 'thursday', open: '09:00', close: '17:00', closed: false },
        { day: 'friday', open: '09:00', close: '17:00', closed: false },
        { day: 'saturday', open: '09:00', close: '12:00', closed: false },
        { day: 'sunday', open: '00:00', close: '00:00', closed: true }
    ],
    settings: {
        appointment_reminder_hours: clinic.settings?.appointment_reminder_hours || 24,
        payment_reminder_days: clinic.settings?.payment_reminder_days || 7,
        auto_invoice: clinic.settings?.auto_invoice || false,
        patient_portal: clinic.settings?.patient_portal || false,
    },
    logo: null as File | null,
});

const brandingForm = useForm({
    primary_color: clinic.primary_color,
    secondary_color: clinic.secondary_color,
    logo: null as File | null,
});

const activeTab = ref('general');
const days = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];
const dayNames = {
    monday: 'Monday',
    tuesday: 'Tuesday',
    wednesday: 'Wednesday',
    thursday: 'Thursday',
    friday: 'Friday',
    saturday: 'Saturday',
    sunday: 'Sunday'
};

const submitSettings = () => {
    form.post(route('clinics.settings.update', clinic.id), {
        preserveScroll: true,
        onSuccess: () => {
            // Update clinic colors in real-time
            document.documentElement.style.setProperty('--primary-color', form.primary_color);
            document.documentElement.style.setProperty('--secondary-color', form.secondary_color);
        },
    });
};

const submitBranding = () => {
    brandingForm.post(route('clinics.branding.update', clinic.id), {
        preserveScroll: true,
        onSuccess: () => {
            window.location.reload();
        },
    });
};

const toggleBusinessDay = (index: number) => {
    form.business_hours[index].closed = !form.business_hours[index].closed;
};

const previewColors = () => {
    document.documentElement.style.setProperty('--primary-color', brandingForm.primary_color);
    document.documentElement.style.setProperty('--secondary-color', brandingForm.secondary_color);
};
</script>

<template>
    <Head :title="`Settings - ${clinic.name}`" />

    <AppLayout>
        <template #header>
            <div class="flex justify-between items-center">
                <h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">
                    Settings - {{ clinic.name }}
                </h2>
                <Link
                    :href="route('clinics.index')"
                    class="text-blue-600 dark:text-blue-400 hover:text-blue-900 dark:hover:text-blue-300"
                >
                    Back to Clinics
                </Link>
            </div>
        </template>

        <div class="py-6">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
                <!-- Tab Navigation -->
                <div class="border-b border-gray-200 dark:border-gray-700 mb-6">
                    <nav class="-mb-px flex space-x-8">
                        <button
                            v-for="tab in ['general', 'branding', 'business-hours', 'notifications']"
                            :key="tab"
                            @click="activeTab = tab"
                            :class="[
                                'whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm',
                                activeTab === tab
                                    ? 'border-primary-500 text-primary-600 dark:text-primary-400'
                                    : 'border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 hover:border-gray-300 dark:hover:border-gray-600'
                            ]"
                        >
                            {{ tab.split('-').map(word => word.charAt(0).toUpperCase() + word.slice(1)).join(' ') }}
                        </button>
                    </nav>
                </div>

                <!-- General Settings -->
                <div v-if="activeTab === 'general'" class="bg-white dark:bg-gray-800 shadow-sm rounded-lg p-6">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-6">General Settings</h3>
                    <form @submit.prevent="submitSettings" class="space-y-6">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Clinic Name</label>
                                <input v-model="form.name" type="text" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm dark:bg-gray-700 dark:text-gray-200">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Email</label>
                                <input v-model="form.email" type="email" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm dark:bg-gray-700 dark:text-gray-200">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Phone</label>
                                <input v-model="form.phone" type="tel" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm dark:bg-gray-700 dark:text-gray-200">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Timezone</label>
                                <select v-model="form.timezone" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm dark:bg-gray-700 dark:text-gray-200">
                                    <option v-for="tz in timezones" :key="tz" :value="tz">{{ tz }}</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Currency</label>
                                <select v-model="form.currency" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm dark:bg-gray-700 dark:text-gray-200">
                                    <option v-for="(name, code) in currencies" :key="code" :value="code">{{ name }}</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Primary Color</label>
                                <div class="flex items-center space-x-2">
                                    <input v-model="form.primary_color" type="color" class="w-10 h-10 rounded">
                                    <input v-model="form.primary_color" type="text" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm dark:bg-gray-700 dark:text-gray-200">
                                </div>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Secondary Color</label>
                                <div class="flex items-center space-x-2">
                                    <input v-model="form.secondary_color" type="color" class="w-10 h-10 rounded">
                                    <input v-model="form.secondary_color" type="text" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm dark:bg-gray-700 dark:text-gray-200">
                                </div>
                            </div>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Address</label>
                            <textarea v-model="form.address" rows="3" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm dark:bg-gray-700 dark:text-gray-200"></textarea>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Logo</label>
                            <input type="file" @change="form.logo = $event.target.files[0]" accept="image/*" class="mt-1 block w-full dark:bg-gray-700 dark:text-gray-200">
                        </div>

                        <div class="flex justify-end">
                            <button type="submit" :disabled="form.processing" class="bg-blue-600 dark:bg-blue-700 text-white px-4 py-2 rounded-md hover:bg-blue-700 dark:hover:bg-blue-600">
                                Save Settings
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Business Hours -->
                <div v-if="activeTab === 'business-hours'" class="bg-white dark:bg-gray-800 shadow-sm rounded-lg p-6">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-6">Business Hours</h3>
                    <div class="space-y-4">
                        <div v-for="(hours, index) in form.business_hours" :key="hours.day" class="flex items-center justify-between p-4 border dark:border-gray-600 rounded-lg">
                            <div class="flex items-center space-x-4">
                                <input
                                    type="checkbox"
                                    :checked="!hours.closed"
                                    @change="toggleBusinessDay(index)"
                                    class="rounded border-gray-300 dark:border-gray-600 dark:bg-gray-700"
                                />
                                <span class="font-medium text-gray-900 dark:text-gray-100">{{ dayNames[hours.day] }}</span>
                            </div>
                            <div v-if="!hours.closed" class="flex items-center space-x-2">
                                <input
                                    v-model="hours.open"
                                    type="time"
                                    class="rounded border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200"
                                />
                                <span class="text-gray-700 dark:text-gray-300">to</span>
                                <input
                                    v-model="hours.close"
                                    type="time"
                                    class="rounded border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200"
                                />
                            </div>
                            <span v-else class="text-gray-500 dark:text-gray-400">Closed</span>
                        </div>
                    </div>
                    <div class="mt-6 flex justify-end">
                        <button @click="submitSettings" :disabled="form.processing" class="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700">
                            Save Business Hours
                        </button>
                    </div>
                </div>

                <!-- Notification Settings -->
                <div v-if="activeTab === 'notifications'" class="bg-white shadow-sm rounded-lg p-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-6">Notification Settings</h3>
                    <div class="space-y-4">
                        <div>
                            <label class="flex items-center">
                                <input v-model="form.settings.auto_invoice" type="checkbox" class="rounded border-gray-300">
                                <span class="ml-2">Automatically create invoices after treatments</span>
                            </label>
                        </div>
                        <div>
                            <label class="flex items-center">
                                <input v-model="form.settings.patient_portal" type="checkbox" class="rounded border-gray-300">
                                <span class="ml-2">Enable patient portal access</span>
                            </label>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">
                                Appointment reminder hours before
                            </label>
                            <input v-model.number="form.settings.appointment_reminder_hours" type="number" min="1" max="168" class="mt-1 block w-32 rounded-md border-gray-300 shadow-sm">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">
                                Payment reminder days after due date
                            </label>
                            <input v-model.number="form.settings.payment_reminder_days" type="number" min="1" max="30" class="mt-1 block w-32 rounded-md border-gray-300 shadow-sm">
                        </div>
                    </div>
                    <div class="mt-6 flex justify-end">
                        <button @click="submitSettings" :disabled="form.processing" class="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700">
                            Save Notification Settings
                        </button>
                    </div>
                </div>

                <!-- Branding Settings -->
                <div v-if="activeTab === 'branding'" class="bg-white shadow-sm rounded-lg p-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-6">Branding Settings</h3>
                    <form @submit.prevent="submitBranding" class="space-y-6">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Primary Color</label>
                                <div class="flex items-center space-x-2">
                                    <input v-model="brandingForm.primary_color" type="color" @input="previewColors" class="w-10 h-10 rounded">
                                    <input v-model="brandingForm.primary_color" type="text" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                                </div>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Secondary Color</label>
                                <div class="flex items-center space-x-2">
                                    <input v-model="brandingForm.secondary_color" type="color" @input="previewColors" class="w-10 h-10 rounded">
                                    <input v-model="brandingForm.secondary_color" type="text" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                                </div>
                            </div>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700">Logo</label>
                            <input type="file" @change="brandingForm.logo = $event.target.files[0]" accept="image/*" class="mt-1 block w-full">
                            <div v-if="clinic.logo_path" class="mt-2">
                                <img :src="clinic.logo_path" :alt="clinic.name" class="h-20 w-20 object-contain">
                            </div>
                        </div>

                        <div class="bg-gray-50 p-4 rounded-lg">
                            <h4 class="font-medium text-gray-900 mb-2">Brand Preview</h4>
                            <div class="flex items-center space-x-4">
                                <div class="w-12 h-12 rounded-lg" :style="{ backgroundColor: brandingForm.primary_color }"></div>
                                <div class="w-12 h-12 rounded-lg" :style="{ backgroundColor: brandingForm.secondary_color }"></div>
                                <div class="text-sm">
                                    <p>Primary: {{ brandingForm.primary_color }}</p>
                                    <p>Secondary: {{ brandingForm.secondary_color }}</p>
                                </div>
                            </div>
                        </div>

                        <div class="flex justify-end">
                            <button type="submit" :disabled="brandingForm.processing" class="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700">
                                Save Branding
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<style>
:root {
    --primary-color: v-bind('clinic.primary_color');
    --secondary-color: v-bind('clinic.secondary_color');
}
</style>