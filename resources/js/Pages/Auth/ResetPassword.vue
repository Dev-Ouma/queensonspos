<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue';
import ResetPasswordForm from '@/Components/ResetPasswordForm.vue';
import { Head } from '@inertiajs/vue3';
defineProps({ email: { type: String, required: true }, token: { type: String, required: true } });
</script>

<template>
    <Head title="Create a new password" />
    <GuestLayout>
        <template #header-right><span class="text-xs font-semibold uppercase tracking-wider text-gray-500">Back office</span></template>
        <ResetPasswordForm :action="route('password.store')" :token="token" :email="email" confirm />
    </GuestLayout>
</template>
