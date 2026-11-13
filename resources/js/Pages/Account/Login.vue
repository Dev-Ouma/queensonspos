<script setup>
import StorefrontLayout from '@/Layouts/StorefrontLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

const form = useForm({ email: '', password: '' });
</script>

<template>
    <Head title="Sign In" />
    <StorefrontLayout>
        <div class="max-w-md mx-auto px-4 py-16">
            <div class="text-center mb-8">
                <h1 class="text-2xl md:text-3xl font-bold">Sign in to your account</h1>
            </div>
            <form @submit.prevent="form.post('/account/login')" class="space-y-4">
                <div>
                    <label for="login-email" class="text-sm font-semibold">Email</label>
                    <input id="login-email" v-model="form.email" autocomplete="email" type="email" required class="w-full rounded-lg border-gray-300 mt-1" />
                    <div v-if="form.errors.email" class="text-red-500 text-xs mt-1">{{ form.errors.email }}</div>
                </div>
                <div>
                    <label for="login-password" class="text-sm font-semibold">Password</label>
                    <input id="login-password" v-model="form.password" autocomplete="current-password" type="password" required class="w-full rounded-lg border-gray-300 mt-1" />
                </div>
                <button :disabled="form.processing" class="w-full bg-emerald-600 hover:bg-emerald-700 text-white rounded-full py-3 text-sm font-semibold">Sign in</button>
            </form>
            <p class="text-center text-sm text-gray-500 mt-6">
                New to QUEENSONS? <Link href="/account/register" class="underline font-semibold text-navy-900">Create an account</Link>
            </p>
        </div>
    </StorefrontLayout>
</template>
