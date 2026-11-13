<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import Pagination from '@/Components/Pagination.vue';
import { Head, router } from '@inertiajs/vue3';
import { reactive } from 'vue';

const props = defineProps({ logs: Object, filters: Object });
const f = reactive({ ...props.filters });
const apply = () => router.get('/admin/audit', f.action ? { action: f.action } : {}, { preserveState: true });
</script>

<template>
    <Head title="Audit Trail — Admin" />
    <AdminLayout>
        <h1 class="text-xl font-bold text-navy-700 mb-6">Audit Trail <span class="text-xs font-normal text-gray-400">(append-only)</span></h1>
        <input v-model="f.action" @keyup.enter="apply" placeholder="Filter by action…" class="rounded-lg border-gray-200 text-sm mb-4" />

        <div class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-x-auto">
            <table class="w-full text-sm">
                <thead><tr class="text-left text-xs text-gray-400 uppercase border-b"><th class="p-3">When</th><th>User</th><th>Action</th><th>Subject</th><th>Changes</th><th>IP</th></tr></thead>
                <tbody>
                    <tr v-for="l in logs.data" :key="l.id" class="border-b border-gray-50 align-top">
                        <td class="p-3 text-xs text-gray-400 whitespace-nowrap">{{ l.at }}</td>
                        <td class="text-xs font-medium">{{ l.user }}</td>
                        <td><span class="text-xs bg-navy-50 rounded px-2 py-0.5">{{ l.action }}</span></td>
                        <td class="text-xs">{{ l.subject }}</td>
                        <td class="text-[11px] text-gray-500 font-mono max-w-md break-all">{{ l.changes ? JSON.stringify(l.changes) : '' }}</td>
                        <td class="text-xs text-gray-400">{{ l.ip }}</td>
                    </tr>
                </tbody>
            </table>
        </div>
        <Pagination :links="logs.links" />
    </AdminLayout>
</template>
