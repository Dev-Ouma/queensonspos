<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import Pagination from '@/Components/Pagination.vue';
import { Head, router } from '@inertiajs/vue3';
defineProps({ reviews: Object });
</script>

<template>
    <Head title="Reviews — Admin" />
    <AdminLayout>
        <h1 class="text-xl font-bold text-navy-700 mb-6">Product Reviews</h1>
        <div class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-x-auto">
            <table class="w-full text-sm">
                <thead><tr class="text-left text-xs text-gray-400 uppercase border-b"><th class="p-3">Product</th><th>Customer</th><th>Rating</th><th>Review</th><th>Status</th><th></th></tr></thead>
                <tbody>
                    <tr v-for="r in reviews.data" :key="r.id" class="border-b border-gray-50">
                        <td class="p-3">{{ r.product }}</td>
                        <td class="text-xs">{{ r.customer }}</td>
                        <td class="text-gold-dark">{{ '★'.repeat(r.rating) }}</td>
                        <td class="text-xs text-gray-500 max-w-sm truncate">{{ r.body }}</td>
                        <td><span :class="['text-xs rounded-full px-2 py-0.5', r.status === 'published' ? 'bg-emerald-50 text-emerald-700' : r.status === 'rejected' ? 'bg-red-50 text-red-600' : 'bg-amber-50 text-amber-700']">{{ r.status }}</span></td>
                        <td class="text-right pr-3 whitespace-nowrap">
                            <template v-if="r.status === 'pending'">
                                <button @click="router.patch(`/admin/reviews/${r.id}`, { status: 'published' }, { preserveScroll: true })" class="text-xs text-emerald-600 hover:underline mr-2">Publish</button>
                                <button @click="router.patch(`/admin/reviews/${r.id}`, { status: 'rejected' }, { preserveScroll: true })" class="text-xs text-red-500 hover:underline">Reject</button>
                            </template>
                        </td>
                    </tr>
                    <tr v-if="!reviews.data.length"><td colspan="6" class="p-6 text-center text-gray-400">No reviews yet.</td></tr>
                </tbody>
            </table>
        </div>
        <Pagination :links="reviews.links" />
    </AdminLayout>
</template>
