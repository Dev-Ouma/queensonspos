<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import Pagination from '@/Components/Pagination.vue';
import { Head, router, useForm, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import { kes } from '@/money';

defineProps({ expenses: Object, categories: Array, branches: Array });
const page = usePage();
const canApprove = computed(() => (page.props.auth.user?.permissions || []).includes('finance.approve'));

const form = useForm({ expense_category_id: null, branch_id: null, description: '', amount: null, incurred_on: new Date().toISOString().slice(0, 10) });
</script>

<template>
    <Head title="Expenses — Admin" />
    <AdminLayout>
        <h1 class="text-xl font-bold text-navy-700 mb-6">Expenses</h1>

        <form @submit.prevent="form.post('/admin/expenses', { onSuccess: () => form.reset('description', 'amount') })"
              class="bg-white rounded-xl border border-gray-100 shadow-sm p-4 mb-6 flex flex-wrap gap-3 items-end text-sm">
            <select v-model="form.expense_category_id" required class="rounded border-gray-200 text-sm">
                <option :value="null">Category…</option>
                <option v-for="c in categories" :key="c.id" :value="c.id">{{ c.name }}</option>
            </select>
            <input v-model="form.description" required placeholder="Description" class="rounded border-gray-200 text-sm flex-1 min-w-[180px]" />
            <input v-model.number="form.amount" required type="number" step="0.01" placeholder="Amount KES" class="rounded border-gray-200 text-sm w-32" />
            <input v-model="form.incurred_on" required type="date" class="rounded border-gray-200 text-sm" />
            <select v-model="form.branch_id" class="rounded border-gray-200 text-sm">
                <option :value="null">No branch</option>
                <option v-for="b in branches" :key="b.id" :value="b.id">{{ b.name }}</option>
            </select>
            <button :disabled="form.processing" class="bg-emerald-600 text-white rounded-lg px-4 py-2 font-semibold">Record</button>
        </form>

        <div class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-x-auto">
            <table class="w-full text-sm">
                <thead><tr class="text-left text-xs text-gray-400 uppercase border-b"><th class="p-3">Description</th><th>Category</th><th>Branch</th><th class="text-right">Amount</th><th>Date</th><th>Status</th><th v-if="canApprove"></th></tr></thead>
                <tbody>
                    <tr v-for="e in expenses.data" :key="e.id" class="border-b border-gray-50">
                        <td class="p-3">{{ e.description }}</td>
                        <td class="text-xs">{{ e.category }}</td>
                        <td class="text-xs">{{ e.branch }}</td>
                        <td class="text-right">{{ kes(e.amount_cents) }}</td>
                        <td class="text-xs text-gray-400">{{ e.incurred_on }}</td>
                        <td><span :class="['text-xs rounded-full px-2 py-0.5', e.status === 'approved' ? 'bg-emerald-50 text-emerald-700' : e.status === 'rejected' ? 'bg-red-50 text-red-600' : 'bg-amber-50 text-amber-700']">{{ e.status }}</span></td>
                        <td v-if="canApprove" class="text-right pr-3 whitespace-nowrap">
                            <template v-if="e.status === 'pending'">
                                <button @click="router.patch(`/admin/expenses/${e.id}`, { status: 'approved' }, { preserveScroll: true })" class="text-xs text-emerald-600 hover:underline mr-2">Approve</button>
                                <button @click="router.patch(`/admin/expenses/${e.id}`, { status: 'rejected' }, { preserveScroll: true })" class="text-xs text-red-500 hover:underline">Reject</button>
                            </template>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
        <Pagination :links="expenses.links" />
    </AdminLayout>
</template>
