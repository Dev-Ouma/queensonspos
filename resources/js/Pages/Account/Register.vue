<script setup>
import StorefrontLayout from '@/Layouts/StorefrontLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

const form = useForm({ name: '', email: '', phone: '', password: '', password_confirmation: '' });
</script>

<template>
    <Head title="Create Account — QUEENSONS" />
    <StorefrontLayout>
        <div class="max-w-md mx-auto px-4 py-16">
            <div class="text-center mb-8">
                <img src="/logo.svg" alt="QUEENSONS crown logo" class="w-14 h-14 mx-auto mb-4 animate-float" />
                <p class="eyebrow text-gold-dark mb-2">Join Queensons</p>
                <h1 class="font-display text-3xl">Create your account</h1>
                <div class="rule-gold max-w-[100px] mx-auto mt-4"></div>
            </div>
            <form @submit.prevent="form.post('/account/register')" class="space-y-4">
                <div>
                    <label class="text-xs text-gray-500">Full name</label>
                    <input v-model="form.name" required class="w-full rounded border-gray-200 mt-1" />
                    <div v-if="form.errors.name" class="text-red-500 text-xs mt-1">{{ form.errors.name }}</div>
                </div>
                <div>
                    <label class="text-xs text-gray-500">Email</label>
                    <input v-model="form.email" type="email" required class="w-full rounded border-gray-200 mt-1" />
                    <div v-if="form.errors.email" class="text-red-500 text-xs mt-1">{{ form.errors.email }}</div>
                </div>
                <div>
                    <label class="text-xs text-gray-500">Phone</label>
                    <input v-model="form.phone" placeholder="07XX XXX XXX" class="w-full rounded border-gray-200 mt-1" />
                </div>
                <div>
                    <label class="text-xs text-gray-500">Password</label>
                    <input v-model="form.password" type="password" required class="w-full rounded border-gray-200 mt-1" />
                    <div v-if="form.errors.password" class="text-red-500 text-xs mt-1">{{ form.errors.password }}</div>
                </div>
                <div>
                    <label class="text-xs text-gray-500">Confirm password</label>
                    <input v-model="form.password_confirmation" type="password" required class="w-full rounded border-gray-200 mt-1" />
                </div>
                <button :disabled="form.processing" class="w-full bg-emerald-600 hover:bg-emerald-700 text-white rounded-full py-3 text-sm font-semibold">Create Account</button>
            </form>
            <p class="text-center text-sm text-gray-500 mt-6">
                Already registered? <Link href="/account/login" class="text-emerald-600 hover:underline">Sign in</Link>
            </p>
        </div>
    </StorefrontLayout>
</template>
