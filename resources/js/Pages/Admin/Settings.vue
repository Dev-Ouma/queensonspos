<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';

const props = defineProps({ settings: Object, branches: Array });
const form = useForm({ settings: { ...props.settings } });

const fields = [
    ['business_name', 'Business name'],
    ['tagline', 'Tagline'],
    ['currency', 'Currency'],
    ['timezone', 'Timezone'],
    ['delivery_fee_cents', 'Delivery fee (cents)'],
    ['vat_percent', 'VAT %'],
    ['loyalty_earn_per_cents', 'Loyalty: cents per 1 point'],
];
</script>

<template>
    <Head title="Settings — Admin" />
    <AdminLayout>
        <h1 class="text-xl font-bold text-navy-700 mb-6">System Settings</h1>

        <div class="grid lg:grid-cols-2 gap-6">
            <form @submit.prevent="form.post('/admin/settings')" class="bg-white rounded-xl border border-gray-100 shadow-sm p-5 space-y-4">
                <h2 class="font-semibold text-sm">Business Configuration</h2>
                <div v-for="[key, label] in fields" :key="key">
                    <label class="text-xs text-gray-500">{{ label }}</label>
                    <input v-model="form.settings[key]" class="w-full rounded border-gray-200 text-sm mt-1" />
                </div>
                <button :disabled="form.processing" class="bg-navy-700 text-white rounded-lg px-5 py-2 text-sm font-semibold">Save Settings</button>
            </form>

            <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-5">
                <h2 class="font-semibold text-sm mb-4">Branches & Warehouses</h2>
                <div v-for="b in branches" :key="b.id" class="border border-gray-100 rounded-lg p-4 mb-3 text-sm">
                    <div class="font-medium">{{ b.name }} <span class="text-xs text-gray-400 font-mono">{{ b.code }}</span></div>
                    <div class="text-xs text-gray-500">{{ b.address }} · {{ b.phone }}</div>
                    <div class="mt-2 text-xs">
                        <span class="text-gray-400">Warehouses:</span>
                        <span v-for="w in b.warehouses" :key="w.id" class="bg-navy-50 rounded px-2 py-0.5 ml-1">{{ w.name }}</span>
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
