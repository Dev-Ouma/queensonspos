<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import Pagination from '@/Components/Pagination.vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import { kes } from '@/money';

const props = defineProps({ deliveries: Object, filters: Object, statusCounts: Object });

const editing = ref(null);
const form = useForm({ status: '', courier: '', tracking_no: '', failure_reason: '' });

const badge = (s) => ({
    delivered: 'bg-emerald-50 text-emerald-700',
    dispatched: 'bg-navy-50 text-navy-700',
    pending: 'bg-amber-50 text-amber-700',
    failed: 'bg-red-50 text-red-700',
}[s] || 'bg-gray-100');

const nextStatuses = (s) => ({
    pending: ['pending', 'dispatched', 'failed'],
    dispatched: ['dispatched', 'delivered', 'failed'],
    delivered: ['delivered'],
    failed: ['failed', 'dispatched'],
}[s] || []);

function open(d) {
    editing.value = d.id;
    form.status = d.status;
    form.courier = d.courier || '';
    form.tracking_no = d.tracking_no || '';
    form.failure_reason = d.failure_reason || '';
    form.clearErrors();
}

function save(d) {
    form.patch(`/admin/deliveries/${d.id}`, {
        preserveScroll: true,
        onSuccess: () => { editing.value = null; },
    });
}

function filter(status) {
    router.get('/admin/deliveries', status ? { status } : {}, { preserveState: true });
}
</script>

<template>
    <Head title="Deliveries — Admin" />
    <AdminLayout>
        <div class="flex flex-wrap items-center justify-between gap-3 mb-6">
            <h1 class="text-xl font-bold text-navy-700">Deliveries</h1>
            <div class="flex gap-2 text-xs">
                <button @click="filter(null)" :class="['rounded-full px-3 py-1 border', !filters.status ? 'bg-navy-700 text-white border-navy-700' : 'border-gray-200 text-gray-500']">All</button>
                <button v-for="(c, s) in statusCounts" :key="s" @click="filter(s)"
                    :class="['rounded-full px-3 py-1 border capitalize', filters.status === s ? 'bg-navy-700 text-white border-navy-700' : 'border-gray-200 text-gray-500']">
                    {{ s }} ({{ c }})
                </button>
            </div>
        </div>

        <div class="space-y-3">
            <div v-for="d in deliveries.data" :key="d.id" class="bg-white rounded-xl border border-gray-100 shadow-sm p-5">
                <div class="flex flex-wrap items-center justify-between gap-2">
                    <div class="text-sm">
                        <span class="font-mono font-semibold">{{ d.order_ref }}</span>
                        <span class="text-gray-500 mx-2">{{ d.customer }}</span>
                        <span class="text-gray-400">{{ kes(d.total_cents) }}</span>
                    </div>
                    <div class="flex items-center gap-3">
                        <span :class="['text-xs rounded-full px-2 py-0.5 capitalize', badge(d.status)]">{{ d.status }}</span>
                        <button v-if="d.status !== 'delivered'" @click="editing === d.id ? (editing = null) : open(d)" class="text-xs text-navy-700 hover:underline">
                            {{ editing === d.id ? 'Cancel' : 'Update' }}
                        </button>
                    </div>
                </div>
                <div class="mt-2 text-xs text-gray-400 flex flex-wrap gap-4">
                    <span v-if="d.courier">Courier: <span class="text-gray-600">{{ d.courier }}</span></span>
                    <span v-if="d.tracking_no">Tracking: <span class="font-mono text-gray-600">{{ d.tracking_no }}</span></span>
                    <span v-if="d.dispatched_at">Dispatched {{ d.dispatched_at }}</span>
                    <span v-if="d.delivered_at">Delivered {{ d.delivered_at }}</span>
                    <span v-if="d.failure_reason" class="text-red-500">{{ d.failure_reason }}</span>
                </div>

                <form v-if="editing === d.id" @submit.prevent="save(d)" class="mt-4 border-t border-gray-50 pt-4 grid gap-3 sm:grid-cols-4">
                    <label class="text-xs text-gray-500">Status
                        <select v-model="form.status" class="mt-1 w-full rounded-lg border-gray-200 text-sm capitalize">
                            <option v-for="s in nextStatuses(d.status)" :key="s" :value="s">{{ s }}</option>
                        </select>
                    </label>
                    <label class="text-xs text-gray-500">Courier
                        <input v-model="form.courier" type="text" class="mt-1 w-full rounded-lg border-gray-200 text-sm" placeholder="e.g. Sendy Rider" />
                    </label>
                    <label class="text-xs text-gray-500">Tracking no.
                        <input v-model="form.tracking_no" type="text" class="mt-1 w-full rounded-lg border-gray-200 text-sm font-mono" />
                    </label>
                    <label v-if="form.status === 'failed'" class="text-xs text-gray-500">Failure reason
                        <input v-model="form.failure_reason" type="text" class="mt-1 w-full rounded-lg border-gray-200 text-sm" required />
                    </label>
                    <div class="sm:col-span-4 flex items-center gap-3">
                        <button type="submit" :disabled="form.processing" class="bg-navy-700 text-white text-sm rounded-lg px-4 py-2 hover:bg-navy-800 disabled:opacity-50">Save</button>
                        <p v-if="form.errors.status" class="text-xs text-red-500">{{ form.errors.status }}</p>
                        <p v-if="form.errors.failure_reason" class="text-xs text-red-500">{{ form.errors.failure_reason }}</p>
                    </div>
                </form>
            </div>
            <p v-if="!deliveries.data.length" class="text-gray-400 text-sm">No deliveries.</p>
        </div>
        <Pagination :links="deliveries.links" />
    </AdminLayout>
</template>
