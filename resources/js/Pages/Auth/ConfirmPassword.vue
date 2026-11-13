<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

const form = useForm({ password: '' });
const show = ref(false);
</script>

<template>
    <Head title="Confirm your password" />
    <GuestLayout>
        <h1 class="text-2xl md:text-[28px] font-bold text-center">Confirm it's you</h1>
        <p class="text-sm text-gray-600 text-center mt-2">This is a secure area. Enter your password to continue.</p>
        <form @submit.prevent="form.post(route('password.confirm'), { onFinish: () => form.reset() })" class="mt-8 space-y-4">
            <div>
                <label for="confirm-password" class="sr-only">Password</label>
                <div class="relative">
                    <input id="confirm-password" v-model="form.password" :type="show ? 'text' : 'password'" autocomplete="current-password" autofocus required placeholder="Password"
                           :class="['w-full rounded-xl px-4 py-3.5 pr-16 text-base bg-gray-50 focus:bg-white',
                                    form.errors.password ? 'border-red-600 focus:border-red-600 focus:ring-red-600' : 'border-gray-300 focus:border-navy-900 focus:ring-navy-900']" />
                    <button type="button" @click="show = !show" :aria-pressed="show" class="absolute right-3 top-1/2 -translate-y-1/2 text-sm font-semibold underline">{{ show ? 'Hide' : 'Show' }}</button>
                </div>
                <p class="text-red-600 text-sm mt-1.5" role="alert">{{ form.errors.password }}</p>
            </div>
            <button :disabled="form.processing" class="w-full bg-emerald-600 hover:bg-emerald-700 disabled:opacity-60 text-white rounded-full py-3.5 font-semibold">Confirm</button>
        </form>
    </GuestLayout>
</template>
