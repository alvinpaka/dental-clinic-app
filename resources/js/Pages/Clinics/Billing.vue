<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { loadStripe } from '@stripe/stripe-js';

interface Clinic {
    id: number;
    name: string;
    subscription_plan: string;
    subscription_status: string;
    subscription_ends_at?: string;
    stripe_customer_id?: string;
    users: Array<any>;
    invoices: Array<any>;
}

interface SubscriptionPlan {
    id: string;
    name: string;
    price: number;
    features: string[];
    stripe_price_id: string;
    limits: {
        users: number;
        patients: number;
        storage: string;
    };
}

defineProps<{
    clinic: Clinic;
    subscriptionDetails?: any;
    stripePublicKey: string;
}>();

const plans: SubscriptionPlan[] = [
    {
        id: 'basic',
        name: 'Basic',
        price: 99,
        features: [
            'Up to 5 users',
            '500 patients',
            '5GB storage',
            'Basic support',
            'Standard features'
        ],
        stripe_price_id: 'price_basic_monthly',
        limits: { users: 5, patients: 500, storage: '5GB' }
    },
    {
        id: 'pro',
        name: 'Professional',
        price: 199,
        features: [
            'Up to 15 users',
            '2000 patients',
            '20GB storage',
            'Priority support',
            'Advanced analytics'
        ],
        stripe_price_id: 'price_pro_monthly',
        limits: { users: 15, patients: 2000, storage: '20GB' }
    },
    {
        id: 'enterprise',
        name: 'Enterprise',
        price: 399,
        features: [
            'Unlimited users',
            'Unlimited patients',
            '50GB storage',
            '24/7 support',
            'Custom features'
        ],
        stripe_price_id: 'price_enterprise_monthly',
        limits: { users: 0, patients: 0, storage: '50GB' }
    }
];

const subscribe = async (plan: SubscriptionPlan) => {
    try {
        const response = await fetch(route('billing.checkout', clinic.id), {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
            },
            body: JSON.stringify({
                price_id: plan.stripe_price_id,
                plan: plan.id
            })
        });

        const { sessionId } = await response.json();
        const stripe = await loadStripe(stripePublicKey);
        await stripe?.redirectToCheckout({ sessionId });
    } catch (error) {
        console.error('Subscription error:', error);
    }
};

const cancelSubscription = async () => {
    if (confirm('Are you sure you want to cancel this subscription?')) {
        try {
            const response = await fetch(route('billing.cancel', clinic.id), {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
                }
            });

            if (response.ok) {
                window.location.reload();
            }
        } catch (error) {
            console.error('Cancellation error:', error);
        }
    }
};
</script>

<template>
    <Head :title="`Billing - ${clinic.name}`" />

    <AppLayout>
        <template #header>
            <div class="flex justify-between items-center">
                <h2 class="text-xl font-semibold leading-tight text-gray-800">
                    Billing - {{ clinic.name }}
                </h2>
                <Link
                    :href="route('clinics.index')"
                    class="text-blue-600 hover:text-blue-900"
                >
                    Back to Clinics
                </Link>
            </div>
        </template>

        <div class="py-6">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
                <!-- Current Subscription -->
                <div class="bg-white shadow-sm rounded-lg p-6 mb-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Current Subscription</h3>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <div class="text-sm text-gray-500">Plan</div>
                            <div class="text-lg font-semibold capitalize">{{ clinic.subscription_plan }}</div>
                        </div>
                        <div>
                            <div class="text-sm text-gray-500">Status</div>
                            <div class="text-lg font-semibold capitalize">{{ clinic.subscription_status }}</div>
                        </div>
                        <div>
                            <div class="text-sm text-gray-500">Renews</div>
                            <div class="text-lg font-semibold">
                                {{ clinic.subscription_ends_at ? new Date(clinic.subscription_ends_at).toLocaleDateString() : 'Never' }}
                            </div>
                        </div>
                    </div>
                    
                    <div v-if="clinic.subscription_status === 'active'" class="mt-4">
                        <button
                            @click="cancelSubscription"
                            class="bg-red-600 text-white px-4 py-2 rounded-md hover:bg-red-700"
                        >
                            Cancel Subscription
                        </button>
                    </div>
                </div>

                <!-- Subscription Plans -->
                <div class="bg-white shadow-sm rounded-lg p-6 mb-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-6">Upgrade Plan</h3>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <div
                            v-for="plan in plans"
                            :key="plan.id"
                            :class="[
                                'border rounded-lg p-6',
                                clinic.subscription_plan === plan.id ? 'border-blue-500 bg-blue-50' : 'border-gray-200'
                            ]"
                        >
                            <h4 class="text-xl font-semibold mb-2">{{ plan.name }}</h4>
                            <div class="text-3xl font-bold text-gray-900 mb-4">
                                ${{ plan.price }}/month
                            </div>
                            <ul class="space-y-2 mb-6">
                                <li v-for="feature in plan.features" :key="feature" class="flex items-center">
                                    <CheckIcon class="h-5 w-5 text-green-500 mr-2" />
                                    {{ feature }}
                                </li>
                            </ul>
                            <button
                                @click="subscribe(plan)"
                                :class="[
                                    'w-full px-4 py-2 rounded-md font-medium',
                                    clinic.subscription_plan === plan.id
                                        ? 'bg-gray-600 text-white cursor-not-allowed'
                                        : 'bg-blue-600 text-white hover:bg-blue-700'
                                ]"
                                :disabled="clinic.subscription_plan === plan.id"
                            >
                                {{ clinic.subscription_plan === plan.id ? 'Current Plan' : 'Subscribe' }}
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Billing History -->
                <div class="bg-white shadow-sm rounded-lg p-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Billing History</h3>
                    <div v-if="clinic.invoices.length === 0" class="text-gray-500">
                        No invoices yet.
                    </div>
                    <table v-else class="min-w-full divide-y divide-gray-200">
                        <thead>
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Amount</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            <tr v-for="invoice in clinic.invoices" :key="invoice.id">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    {{ new Date(invoice.created_at).toLocaleDateString() }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    ${{ (invoice.amount / 100).toFixed(2) }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span :class="[
                                        'px-2 py-1 text-xs rounded-full',
                                        invoice.status === 'paid' ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800'
                                    ]">
                                        {{ invoice.status }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <a :href="invoice.invoice_pdf" target="_blank" class="text-blue-600 hover:text-blue-900">
                                        Download
                                    </a>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </AppLayout>
</template>