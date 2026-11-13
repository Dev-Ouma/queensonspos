<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue';
import ResetPasswordForm from '@/Components/ResetPasswordForm.vue';
import { Head } from '@inertiajs/vue3';
defineProps({ token: String, email: String });
</script>

<template>
    <Head title="Create a new password" />
    <GuestLayout>
        <ResetPasswordForm action="/account/reset-password" :token="token" :email="email" />
    </GuestLayout>
</template>
