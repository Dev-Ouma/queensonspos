<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue';
import SignInForm from '@/Components/SignInForm.vue';
import { Head } from '@inertiajs/vue3';
</script>

<template>
    <Head title="Sign in" />
    <GuestLayout>
        <SignInForm action="/account/login" register-href="/account/register" forgot-href="/account/forgot-password" :status="$page.props.flash?.success" />
    </GuestLayout>
</template>
