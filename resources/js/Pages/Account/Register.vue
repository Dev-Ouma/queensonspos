<script setup>
import StorefrontLayout from '@/Layouts/StorefrontLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

const form = useForm({ name: '', email: '', phone: '', password: '', password_confirmation: '' });
</script>

<template>
    <Head title="Create Account" />
    <StorefrontLayout>
        <div class="max-w-md mx-auto px-4 py-16">
            <div class="text-center mb-8">
                <h1 class="text-2xl md:text-3xl font-bold">Create an account</h1>
            </div>
            <form @submit.prevent="form.post('/account/register')" class="space-y-4">
                <div>
                    <label for="register-name" class="text-sm font-semibold">Full name</label>
                    <input id="register-name" v-model="form.name" autocomplete="name" required class="w-full rounded-lg border-gray-300 mt-1" />
                    <div v-if="form.errors.name" class="text-red-500 text-xs mt-1">{{ form.errors.name }}</div>
                </div>
                <div>
                    <label for="register-email" class="text-sm font-semibold">Email</label>
                    <input id="register-email" v-model="form.email" autocomplete="email" type="email" required class="w-full rounded-lg border-gray-300 mt-1" />
                    <div v-if="form.errors.email" class="text-red-500 text-xs mt-1">{{ form.errors.email }}</div>
                </div>
                <div>
                    <label for="register-phone" class="text-sm font-semibold">Phone</label>
                    <input id="register-phone" v-model="form.phone" autocomplete="tel" placeholder="07XX XXX XXX" class="w-full rounded-lg border-gray-300 mt-1" />
                </div>
                <div>
                    <label for="register-password" class="text-sm font-semibold">Password</label>
                    <input id="register-password" v-model="form.password" autocomplete="new-password" type="password" required class="w-full rounded-lg border-gray-300 mt-1" />
                    <div v-if="form.errors.password" class="text-red-500 text-xs mt-1">{{ form.errors.password }}</div>
                </div>
                <div>
                    <label for="register-password_confirmation" class="text-sm font-semibold">Confirm password</label>
                    <input id="register-password_confirmation" v-model="form.password_confirmation" autocomplete="new-password" type="password" required class="w-full rounded-lg border-gray-300 mt-1" />
                </div>
                <button :disabled="form.processing" class="w-full bg-emerald-600 hover:bg-emerald-700 text-white rounded-full py-3 text-sm font-semibold">Create account</button>
            </form>
            <p class="text-center text-sm text-gray-500 mt-6">
                Already registered? <Link href="/account/login" class="underline font-semibold text-navy-900">Sign in</Link>
            </p>
        </div>
    </StorefrontLayout>
</template>
