<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue';
import ForgotPasswordForm from '@/Components/ForgotPasswordForm.vue';
import { Head } from '@inertiajs/vue3';
defineProps({ status: String });
</script>

<template>
    <Head title="Staff password reset" />
    <GuestLayout>
        <template #header-right><span class="text-xs font-semibold uppercase tracking-wider text-gray-500">Back office</span></template>
        <ForgotPasswordForm :action="route('password.email')" :back-href="route('login')" :status="status" />
    </GuestLayout>
</template>
