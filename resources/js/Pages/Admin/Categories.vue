<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head, router, useForm } from '@inertiajs/vue3';

defineProps({ categories: Array });
const form = useForm({ name: '', parent_id: null });
</script>

<template>
    <Head title="Categories — Admin" />
    <AdminLayout>
        <h1 class="text-xl font-bold text-navy-700 mb-6">Categories</h1>

        <form @submit.prevent="form.post('/admin/categories', { onSuccess: () => form.reset() })" class="flex gap-3 mb-6">
            <input v-model="form.name" required placeholder="New category name" class="rounded-lg border-gray-200 text-sm" />
            <button class="bg-emerald-600 text-white rounded-lg px-4 py-2 text-sm font-semibold">Add</button>
        </form>

        <div class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-x-auto">
            <table class="w-full text-sm">
                <thead><tr class="text-left text-xs text-gray-400 uppercase border-b"><th class="p-3">Name</th><th>Slug</th><th>Products</th><th>Published</th><th></th></tr></thead>
                <tbody>
                    <tr v-for="c in categories" :key="c.id" class="border-b border-gray-50">
                        <td class="p-3 font-medium">{{ c.name }}</td>
                        <td class="text-xs text-gray-400">{{ c.slug }}</td>
                        <td>{{ c.products_count }}</td>
                        <td>
                            <button @click="router.patch(`/admin/categories/${c.id}`, { name: c.name, published: !c.published }, { preserveScroll: true })"
                                    :class="['text-xs rounded-full px-2 py-0.5', c.published ? 'bg-emerald-50 text-emerald-700' : 'bg-gray-100 text-gray-500']">
                                {{ c.published ? 'Published' : 'Hidden' }}
                            </button>
                        </td>
                        <td class="text-right pr-3">
                            <button v-if="!c.products_count" @click="router.delete(`/admin/categories/${c.id}`, { preserveScroll: true })" class="text-xs text-red-500 hover:underline">Delete</button>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </AdminLayout>
</template>
