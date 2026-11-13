<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

defineProps({ suppliers: Array });
const editing = ref(false);
const form = useForm({ id: null, name: '', contact_person: '', phone: '', email: '', address: '', payment_terms: '', active: true });

function open(s = null) {
    editing.value = true;
    Object.assign(form, s || { id: null, name: '', contact_person: '', phone: '', email: '', address: '', payment_terms: '', active: true });
}
function save() {
    form.post('/admin/suppliers', { preserveScroll: true, onSuccess: () => (editing.value = false) });
}
</script>

<template>
    <Head title="Suppliers — Admin" />
    <AdminLayout>
        <div class="flex items-center justify-between mb-6">
            <h1 class="text-xl font-bold text-navy-700">Suppliers</h1>
            <button @click="open()" class="bg-emerald-600 text-white rounded-lg px-4 py-2 text-sm font-semibold">+ New Supplier</button>
        </div>

        <div class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-x-auto">
            <table class="w-full text-sm">
                <thead><tr class="text-left text-xs text-gray-400 uppercase border-b"><th class="p-3">Name</th><th>Contact</th><th>Phone</th><th>Email</th><th>Terms</th><th>POs</th><th>Status</th><th></th></tr></thead>
                <tbody>
                    <tr v-for="s in suppliers" :key="s.id" class="border-b border-gray-50">
                        <td class="p-3 font-medium">{{ s.name }}</td>
                        <td class="text-xs">{{ s.contact_person }}</td>
                        <td class="text-xs">{{ s.phone }}</td>
                        <td class="text-xs">{{ s.email }}</td>
                        <td class="text-xs">{{ s.payment_terms }}</td>
                        <td>{{ s.purchase_orders_count }}</td>
                        <td><span :class="['text-xs rounded-full px-2 py-0.5', s.active ? 'bg-emerald-50 text-emerald-700' : 'bg-gray-100 text-gray-500']">{{ s.active ? 'Active' : 'Inactive' }}</span></td>
                        <td class="text-right pr-3"><button @click="open(s)" class="text-xs text-navy-700 hover:underline">Edit</button></td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div v-if="editing" class="fixed inset-0 bg-black/40 flex items-center justify-center z-50" @click.self="editing = false">
            <div class="bg-white rounded-xl p-6 w-full max-w-lg">
                <h2 class="font-semibold mb-4">{{ form.id ? 'Edit' : 'New' }} Supplier</h2>
                <form @submit.prevent="save" class="grid grid-cols-2 gap-3 text-sm">
                    <input v-model="form.name" required placeholder="Supplier name *" class="col-span-2 rounded border-gray-200" />
                    <input v-model="form.contact_person" placeholder="Contact person" class="rounded border-gray-200" />
                    <input v-model="form.phone" placeholder="Phone" class="rounded border-gray-200" />
                    <input v-model="form.email" type="email" placeholder="Email" class="rounded border-gray-200" />
                    <input v-model="form.payment_terms" placeholder="Payment terms" class="rounded border-gray-200" />
                    <input v-model="form.address" placeholder="Address" class="col-span-2 rounded border-gray-200" />
                    <label class="flex items-center gap-2"><input type="checkbox" v-model="form.active" class="rounded text-emerald-600" /> Active</label>
                    <div class="col-span-2 flex justify-end gap-2">
                        <button type="button" @click="editing = false" class="text-gray-500 px-4 py-2">Cancel</button>
                        <button :disabled="form.processing" class="bg-navy-700 text-white rounded-lg px-4 py-2 font-semibold">Save</button>
                    </div>
                </form>
            </div>
        </div>
    </AdminLayout>
</template>
