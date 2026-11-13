<script setup>
import AccountShell from '@/Components/AccountShell.vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({ customer: Object, addresses: Array });

const editingInfo = ref(false);
const info = useForm({ name: props.customer.name, phone: props.customer.phone || '' });
function saveInfo() {
    info.patch('/account/profile', { preserveScroll: true, onSuccess: () => { editingInfo.value = false; } });
}

const addingAddress = ref(false);
const address = useForm({ label: 'Home', line1: '', line2: '', city: 'Nairobi', phone: '', is_default: false });
function saveAddress() {
    address.post('/account/addresses', { preserveScroll: true, onSuccess: () => { address.reset(); addingAddress.value = false; } });
}

const editingPassword = ref(false);
const pw = useForm({ current_password: '', password: '', password_confirmation: '' });
function savePassword() {
    pw.put('/account/password', {
        preserveScroll: true,
        onSuccess: () => { pw.reset(); editingPassword.value = false; },
        onFinish: () => pw.reset('current_password', 'password', 'password_confirmation'),
    });
}

const field = 'w-full rounded-xl border-gray-300 text-sm focus:border-navy-900 focus:ring-navy-900';
</script>

<template>
    <Head title="Account settings" />
    <AccountShell>
        <div class="space-y-6">
            <!-- Personal info -->
            <section id="personal" class="border border-gray-200 rounded-2xl p-5 scroll-mt-40">
                <div class="flex items-center justify-between">
                    <h2 class="text-xl font-bold">Personal info</h2>
                    <button v-if="!editingInfo" @click="editingInfo = true" class="text-sm underline">Edit</button>
                </div>
                <dl v-if="!editingInfo" class="mt-4 grid sm:grid-cols-[160px_1fr] gap-y-3 text-sm">
                    <dt class="text-gray-500">Name</dt><dd>{{ customer.name }}</dd>
                    <dt class="text-gray-500">Email</dt><dd>{{ customer.email }}</dd>
                    <dt class="text-gray-500">Phone number</dt><dd>{{ customer.phone || 'Not added' }}</dd>
                </dl>
                <form v-else @submit.prevent="saveInfo" class="mt-4 grid sm:grid-cols-2 gap-4 max-w-xl">
                    <label class="block text-sm">Name<input v-model="info.name" autocomplete="name" :class="[field, 'mt-1']" />
                        <span class="text-red-600 text-xs">{{ info.errors.name }}</span></label>
                    <label class="block text-sm">Phone number<input v-model="info.phone" type="tel" autocomplete="tel" :class="[field, 'mt-1']" />
                        <span class="text-red-600 text-xs">{{ info.errors.phone }}</span></label>
                    <p class="sm:col-span-2 text-xs text-gray-500">Email: {{ customer.email }}. Contact us to change it.</p>
                    <div class="flex gap-2">
                        <button :disabled="info.processing" class="bg-emerald-600 hover:bg-emerald-700 text-white rounded-full px-6 py-2 text-sm font-semibold">Save</button>
                        <button type="button" @click="editingInfo = false; info.reset()" class="border-2 border-gray-300 rounded-full px-6 py-2 text-sm font-semibold">Cancel</button>
                    </div>
                </form>
            </section>

            <!-- Addresses -->
            <section id="addresses" class="border border-gray-200 rounded-2xl p-5 scroll-mt-40">
                <div class="flex items-center justify-between">
                    <h2 class="text-xl font-bold">Addresses</h2>
                    <button v-if="!addingAddress" @click="addingAddress = true" class="text-sm underline">Add address</button>
                </div>
                <div v-if="addresses.length" class="mt-4 grid sm:grid-cols-2 gap-3">
                    <div v-for="a in addresses" :key="a.id" class="rounded-xl border border-gray-200 p-4 text-sm">
                        <div class="flex items-center gap-2">
                            <b>{{ a.label }}</b>
                            <span v-if="a.is_default" class="rounded-full bg-gray-100 px-2 py-0.5 text-[11px] font-semibold">Default</span>
                        </div>
                        <div class="mt-1 text-gray-700">{{ a.line1 }}<span v-if="a.line2">, {{ a.line2 }}</span><br />{{ a.city }}</div>
                        <div v-if="a.phone" class="text-gray-500">{{ a.phone }}</div>
                        <div class="mt-3 flex gap-4">
                            <button v-if="!a.is_default" @click="router.post(`/account/addresses/${a.id}/default`, {}, { preserveScroll: true })" class="underline">Set as default</button>
                            <button @click="router.delete(`/account/addresses/${a.id}`, { preserveScroll: true })" class="underline">Remove</button>
                        </div>
                    </div>
                </div>
                <p v-else-if="!addingAddress" class="mt-3 text-sm text-gray-500">No saved addresses yet.</p>
                <form v-if="addingAddress" @submit.prevent="saveAddress" class="mt-4 grid sm:grid-cols-2 gap-4 max-w-xl">
                    <label class="block text-sm">Label<input v-model="address.label" placeholder="Home, Office…" :class="[field, 'mt-1']" />
                        <span class="text-red-600 text-xs">{{ address.errors.label }}</span></label>
                    <label class="block text-sm">Phone (optional)<input v-model="address.phone" type="tel" autocomplete="tel" :class="[field, 'mt-1']" /></label>
                    <label class="block text-sm sm:col-span-2">Street address<input v-model="address.line1" autocomplete="address-line1" :class="[field, 'mt-1']" />
                        <span class="text-red-600 text-xs">{{ address.errors.line1 }}</span></label>
                    <label class="block text-sm">Building, floor (optional)<input v-model="address.line2" autocomplete="address-line2" :class="[field, 'mt-1']" /></label>
                    <label class="block text-sm">City / Town<input v-model="address.city" autocomplete="address-level2" :class="[field, 'mt-1']" />
                        <span class="text-red-600 text-xs">{{ address.errors.city }}</span></label>
                    <label class="flex items-center gap-2 text-sm sm:col-span-2"><input type="checkbox" v-model="address.is_default" class="rounded border-gray-400 text-navy-900 focus:ring-navy-900" /> Make this my default address</label>
                    <div class="flex gap-2">
                        <button :disabled="address.processing" class="bg-emerald-600 hover:bg-emerald-700 text-white rounded-full px-6 py-2 text-sm font-semibold">Save address</button>
                        <button type="button" @click="addingAddress = false; address.reset()" class="border-2 border-gray-300 rounded-full px-6 py-2 text-sm font-semibold">Cancel</button>
                    </div>
                </form>
            </section>

            <!-- Security -->
            <section id="security" class="border border-gray-200 rounded-2xl p-5 scroll-mt-40">
                <div class="flex items-center justify-between">
                    <h2 class="text-xl font-bold">Sign in and security</h2>
                    <button v-if="!editingPassword" @click="editingPassword = true" class="text-sm underline">Change password</button>
                </div>
                <dl v-if="!editingPassword" class="mt-4 grid sm:grid-cols-[160px_1fr] gap-y-3 text-sm">
                    <dt class="text-gray-500">Email</dt><dd>{{ customer.email }}</dd>
                    <dt class="text-gray-500">Password</dt><dd>••••••••</dd>
                </dl>
                <form v-else @submit.prevent="savePassword" class="mt-4 grid gap-4 max-w-sm">
                    <label class="block text-sm">Current password<input v-model="pw.current_password" type="password" autocomplete="current-password" :class="[field, 'mt-1']" />
                        <span class="text-red-600 text-xs">{{ pw.errors.current_password }}</span></label>
                    <label class="block text-sm">New password<input v-model="pw.password" type="password" autocomplete="new-password" :class="[field, 'mt-1']" />
                        <span class="text-red-600 text-xs">{{ pw.errors.password }}</span></label>
                    <label class="block text-sm">Confirm new password<input v-model="pw.password_confirmation" type="password" autocomplete="new-password" :class="[field, 'mt-1']" /></label>
                    <div class="flex gap-2">
                        <button :disabled="pw.processing" class="bg-emerald-600 hover:bg-emerald-700 text-white rounded-full px-6 py-2 text-sm font-semibold">Change password</button>
                        <button type="button" @click="editingPassword = false; pw.reset()" class="border-2 border-gray-300 rounded-full px-6 py-2 text-sm font-semibold">Cancel</button>
                    </div>
                </form>
            </section>
        </div>
    </AccountShell>
</template>
