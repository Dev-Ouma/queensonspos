<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue';
import ForgotPasswordForm from '@/Components/ForgotPasswordForm.vue';
import { Head } from '@inertiajs/vue3';
defineProps({ status: String });
</script>

<template>
    <Head title="Reset your password" />
    <GuestLayout>
        <ForgotPasswordForm action="/account/forgot-password" back-href="/account/login" :status="status" />
    </GuestLayout>
</template>
