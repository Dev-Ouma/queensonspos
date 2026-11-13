<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({ status: String });
const form = useForm({});
const sent = computed(() => props.status === 'verification-link-sent');
</script>

<template>
    <Head title="Verify your email" />
    <GuestLayout>
        <h1 class="text-2xl md:text-[28px] font-bold text-center">Check your email</h1>
        <p class="text-sm text-gray-600 text-center mt-2">We sent you a link to verify your email address. Open it to finish setting up your account.</p>
        <div v-if="sent" class="mt-6 rounded-xl bg-green-50 border border-green-200 text-green-800 text-sm p-3" role="status">A new verification link is on its way.</div>
        <form @submit.prevent="form.post(route('verification.send'))" class="mt-8">
            <button :disabled="form.processing" class="w-full bg-emerald-600 hover:bg-emerald-700 disabled:opacity-60 text-white rounded-full py-3.5 font-semibold">Resend verification email</button>
        </form>
        <p class="text-center text-sm mt-6">
            <Link :href="route('logout')" method="post" as="button" class="underline font-semibold">Sign out</Link>
        </p>
    </GuestLayout>
</template>
