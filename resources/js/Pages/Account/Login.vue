<script setup>
import StorefrontLayout from '@/Layouts/StorefrontLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

const form = useForm({ email: '', password: '' });
</script>

<template>
    <Head title="Sign In — QUEENSONS" />
    <StorefrontLayout>
        <div class="max-w-md mx-auto px-4 py-16">
            <div class="text-center mb-8">
                <img src="/logo.svg" alt="QUEENSONS crown logo" class="w-14 h-14 mx-auto mb-4 animate-float" />
                <p class="eyebrow text-gold-dark mb-2">Your Account</p>
                <h1 class="font-display text-3xl">Welcome back</h1>
                <div class="rule-gold max-w-[100px] mx-auto mt-4"></div>
            </div>
            <form @submit.prevent="form.post('/account/login')" class="space-y-4">
                <div>
                    <label class="text-xs text-gray-500">Email</label>
                    <input v-model="form.email" type="email" required class="w-full rounded border-gray-200 mt-1" />
                    <div v-if="form.errors.email" class="text-red-500 text-xs mt-1">{{ form.errors.email }}</div>
                </div>
                <div>
                    <label class="text-xs text-gray-500">Password</label>
                    <input v-model="form.password" type="password" required class="w-full rounded border-gray-200 mt-1" />
                </div>
                <button :disabled="form.processing" class="w-full bg-navy-700 hover:bg-navy-600 text-white rounded-full py-3 text-sm font-semibold">Sign In</button>
            </form>
            <p class="text-center text-sm text-gray-500 mt-6">
                New to QUEENSONS? <Link href="/account/register" class="text-emerald-600 hover:underline">Create an account</Link>
            </p>
        </div>
    </StorefrontLayout>
</template>
