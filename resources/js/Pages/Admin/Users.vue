<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

defineProps({ users: Array, roles: Array, branches: Array });
const editing = ref(false);
const form = useForm({ id: null, name: '', email: '', phone: '', role: '', branch_id: null, status: 'active', password: '' });

function open(u = null) {
    editing.value = true;
    Object.assign(form, u ? { id: u.id, name: u.name, email: u.email, phone: u.phone, role: u.role, branch_id: u.branch_id, status: u.status, password: '' }
                          : { id: null, name: '', email: '', phone: '', role: '', branch_id: null, status: 'active', password: '' });
}
</script>

<template>
    <Head title="Staff — Admin" />
    <AdminLayout>
        <div class="flex items-center justify-between mb-6">
            <h1 class="text-xl font-bold text-navy-700">Staff & Roles</h1>
            <button @click="open()" class="bg-emerald-600 text-white rounded-lg px-4 py-2 text-sm font-semibold">+ New Staff</button>
        </div>

        <div class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-x-auto">
            <table class="w-full text-sm">
                <thead><tr class="text-left text-xs text-gray-400 uppercase border-b"><th class="p-3">Name</th><th>Email</th><th>Role</th><th>Branch</th><th>Status</th><th></th></tr></thead>
                <tbody>
                    <tr v-for="u in users" :key="u.id" class="border-b border-gray-50">
                        <td class="p-3 font-medium">{{ u.name }}</td>
                        <td class="text-xs">{{ u.email }}</td>
                        <td><span class="text-xs bg-navy-50 rounded-full px-2 py-0.5">{{ u.role }}</span></td>
                        <td class="text-xs">{{ u.branch }}</td>
                        <td><span :class="['text-xs rounded-full px-2 py-0.5', u.status === 'active' ? 'bg-emerald-50 text-emerald-700' : 'bg-gray-100 text-gray-500']">{{ u.status }}</span></td>
                        <td class="text-right pr-3"><button @click="open(u)" class="text-xs text-navy-700 hover:underline">Edit</button></td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div v-if="editing" class="fixed inset-0 bg-black/40 flex items-center justify-center z-50" @click.self="editing = false">
            <div class="bg-white rounded-xl p-6 w-full max-w-lg">
                <h2 class="font-semibold mb-4">{{ form.id ? 'Edit' : 'New' }} Staff Account</h2>
                <form @submit.prevent="form.post('/admin/users', { preserveScroll: true, onSuccess: () => (editing = false) })" class="grid grid-cols-2 gap-3 text-sm">
                    <input v-model="form.name" required placeholder="Full name *" class="rounded border-gray-200" />
                    <input v-model="form.email" required type="email" placeholder="Email *" class="rounded border-gray-200" />
                    <input v-model="form.phone" placeholder="Phone" class="rounded border-gray-200" />
                    <select v-model="form.role" required class="rounded border-gray-200">
                        <option value="">Role *</option>
                        <option v-for="r in roles" :key="r" :value="r">{{ r }}</option>
                    </select>
                    <select v-model="form.branch_id" class="rounded border-gray-200">
                        <option :value="null">No branch</option>
                        <option v-for="b in branches" :key="b.id" :value="b.id">{{ b.name }}</option>
                    </select>
                    <select v-model="form.status" class="rounded border-gray-200">
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                    </select>
                    <input v-model="form.password" type="password" :placeholder="form.id ? 'New password (optional)' : 'Password *'" class="col-span-2 rounded border-gray-200" />
                    <div v-if="form.errors.email" class="col-span-2 text-red-500 text-xs">{{ form.errors.email }}</div>
                    <div class="col-span-2 flex justify-end gap-2">
                        <button type="button" @click="editing = false" class="text-gray-500 px-4 py-2">Cancel</button>
                        <button :disabled="form.processing" class="bg-navy-700 text-white rounded-lg px-4 py-2 font-semibold">Save</button>
                    </div>
                </form>
            </div>
        </div>
    </AdminLayout>
</template>
