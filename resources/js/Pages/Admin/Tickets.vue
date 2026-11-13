<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import Pagination from '@/Components/Pagination.vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import { reactive, ref } from 'vue';

const props = defineProps({ tickets: Object, filters: Object });
const f = reactive({ ...props.filters });
const apply = () => router.get('/admin/tickets', f.status ? { status: f.status } : {}, { preserveState: true });

const openTicket = ref(null);
const reply = useForm({ body: '', status: 'in_progress' });
function sendReply() {
    reply.post(`/admin/tickets/${openTicket.value.id}/reply`, {
        preserveScroll: true,
        onSuccess: () => { reply.reset('body'); openTicket.value = null; },
    });
}
const badge = (s) => ({ open: 'bg-red-50 text-red-600', in_progress: 'bg-amber-50 text-amber-700', resolved: 'bg-emerald-50 text-emerald-700', closed: 'bg-gray-100 text-gray-500' }[s]);
</script>

<template>
    <Head title="Support Tickets — Admin" />
    <AdminLayout>
        <h1 class="text-xl font-bold text-navy-700 mb-6">Customer Support</h1>

        <select v-model="f.status" @change="apply" class="rounded-lg border-gray-200 text-sm mb-4">
            <option value="">All statuses</option>
            <option value="open">Open</option>
            <option value="in_progress">In progress</option>
            <option value="resolved">Resolved</option>
            <option value="closed">Closed</option>
        </select>

        <div class="space-y-3">
            <div v-for="t in tickets.data" :key="t.id" class="bg-white rounded-xl border border-gray-100 shadow-sm p-5">
                <div class="flex flex-wrap items-center justify-between gap-2">
                    <div>
                        <span class="font-mono text-xs text-gray-400">{{ t.reference }}</span>
                        <span class="font-semibold ml-2">{{ t.subject }}</span>
                    </div>
                    <div class="flex items-center gap-3">
                        <span :class="['text-xs rounded-full px-2 py-0.5 capitalize', badge(t.status)]">{{ t.status.replaceAll('_', ' ') }}</span>
                        <button @click="openTicket = openTicket?.id === t.id ? null : t; reply.status = t.status === 'open' ? 'in_progress' : t.status"
                                class="text-xs text-navy-700 hover:underline">{{ openTicket?.id === t.id ? 'Close' : 'View / Reply' }}</button>
                    </div>
                </div>
                <div class="text-xs text-gray-400 mt-1">{{ t.customer }} · {{ t.email }} · {{ t.created }}</div>

                <div v-if="openTicket?.id === t.id" class="mt-4 border-t pt-4">
                    <div class="space-y-2 mb-4 max-h-60 overflow-y-auto">
                        <div v-for="(m, i) in t.messages" :key="i"
                             :class="['text-sm rounded-lg p-3 max-w-xl', m.sender === 'customer' ? 'bg-gray-50' : 'bg-navy-50 ml-8']">
                            <span class="text-[10px] uppercase text-gray-400 block mb-0.5">{{ m.sender }} · {{ m.at }}</span>
                            {{ m.body }}
                        </div>
                    </div>
                    <form @submit.prevent="sendReply" class="flex flex-wrap gap-2">
                        <input v-model="reply.body" required placeholder="Write a reply…" class="flex-1 min-w-[240px] rounded border-gray-200 text-sm" />
                        <select v-model="reply.status" class="rounded border-gray-200 text-sm">
                            <option value="in_progress">In progress</option>
                            <option value="resolved">Resolved</option>
                            <option value="closed">Closed</option>
                        </select>
                        <button :disabled="reply.processing" class="bg-navy-700 text-white rounded-lg px-4 py-2 text-sm font-semibold">Send</button>
                    </form>
                </div>
            </div>
            <p v-if="!tickets.data.length" class="text-gray-400 text-sm">No tickets.</p>
        </div>
        <Pagination :links="tickets.links" />
    </AdminLayout>
</template>
