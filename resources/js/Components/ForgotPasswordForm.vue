<script setup>
import { Link, useForm } from '@inertiajs/vue3';

const props = defineProps({ action: String, backHref: String, status: String });
const form = useForm({ email: '' });
</script>

<template>
    <h1 class="text-2xl md:text-[28px] font-bold text-center">Reset your password</h1>
    <p class="text-sm text-gray-600 text-center mt-2">Enter the email on your account and we'll send you a link to reset your password.</p>

    <div v-if="status" class="mt-6 rounded-xl bg-green-50 border border-green-200 text-green-800 text-sm p-3" role="status">{{ status }}</div>

    <form @submit.prevent="form.post(action)" class="mt-8 space-y-4">
        <div>
            <label for="forgot-email" class="sr-only">Email</label>
            <input id="forgot-email" v-model="form.email" type="email" autocomplete="username" autofocus required placeholder="Email"
                   :aria-invalid="!!form.errors.email" aria-describedby="forgot-email-error"
                   :class="['w-full rounded-xl px-4 py-3.5 text-base bg-gray-50 focus:bg-white',
                            form.errors.email ? 'border-red-600 focus:border-red-600 focus:ring-red-600' : 'border-gray-300 focus:border-navy-900 focus:ring-navy-900']" />
            <p id="forgot-email-error" class="text-red-600 text-sm mt-1.5" role="alert">{{ form.errors.email }}</p>
        </div>
        <button :disabled="form.processing" class="w-full bg-emerald-600 hover:bg-emerald-700 disabled:opacity-60 text-white rounded-full py-3.5 font-semibold">
            {{ form.processing ? 'Sending…' : 'Send reset link' }}
        </button>
    </form>
    <p class="text-center text-sm mt-6"><Link :href="backHref" class="underline font-semibold">Back to sign in</Link></p>
</template>
