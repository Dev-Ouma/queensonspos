<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import UpdatePasswordForm from './Partials/UpdatePasswordForm.vue';
import UpdateProfileInformationForm from './Partials/UpdateProfileInformationForm.vue';
import { Head } from '@inertiajs/vue3';

defineProps({ mustVerifyEmail: Boolean, status: String });
</script>

<template>
    <Head title="My profile — Admin" />
    <AdminLayout>
        <h1 class="text-xl font-bold">My profile</h1>
        <p class="text-sm text-gray-500 mb-6">Your staff sign-in details. Roles and branches are set by an administrator under Staff.</p>
        <div class="grid lg:grid-cols-2 gap-6 max-w-5xl">
            <div class="bg-white rounded-xl border border-gray-200 p-6">
                <UpdateProfileInformationForm :must-verify-email="mustVerifyEmail" :status="status" />
            </div>
            <div class="bg-white rounded-xl border border-gray-200 p-6">
                <UpdatePasswordForm />
            </div>
        </div>
    </AdminLayout>
</template>
