<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue';
import SignInForm from '@/Components/SignInForm.vue';
import { Head } from '@inertiajs/vue3';

defineProps({ canResetPassword: Boolean, status: String });
</script>

<template>
    <Head title="Staff sign in" />
    <GuestLayout>
        <template #header-right>
            <span class="text-xs font-semibold uppercase tracking-wider text-gray-500">Back office</span>
        </template>
        <SignInForm :action="route('login')" title="Sign in to the back office" :status="status"
                    :forgot-href="canResetPassword ? route('password.request') : undefined" />
        <p class="text-center text-sm text-gray-500 mt-8">Shopping with us? <a href="/account/login" class="underline font-semibold text-navy-900">Customer sign in</a></p>
    </GuestLayout>
</template>
