<script setup>
/**
 * Two-step, identifier-first sign in: email, then password.
 * Shared by the customer portal and the staff back office.
 */
import { Link, useForm } from '@inertiajs/vue3';
import { ref, nextTick } from 'vue';

const props = defineProps({
    action: { type: String, required: true },
    title: { type: String, default: 'Sign in to your account' },
    registerHref: String,
    registerLabel: { type: String, default: 'Create account' },
    registerPrompt: { type: String, default: 'New to QUEENSONS?' },
    forgotHref: String,
    status: String,
});

const form = useForm({ email: '', password: '', remember: true });
const step = ref(1);
const showPassword = ref(false);
const emailError = ref('');
const passwordInput = ref(null);
const emailInput = ref(null);

async function next() {
    emailError.value = '';
    if (!/^\S+@\S+\.\S+$/.test(form.email.trim())) {
        emailError.value = 'Enter a valid email address.';
        return;
    }
    step.value = 2;
    await nextTick();
    passwordInput.value?.focus();
}
async function back() {
    step.value = 1;
    form.password = '';
    form.clearErrors();
    await nextTick();
    emailInput.value?.focus();
}
function submit() {
    form.post(props.action, {
        onFinish: () => form.reset('password'),
        onError: (errors) => { if (errors.email && !errors.password) step.value = 2; },
    });
}
</script>

<template>
    <div class="text-center">
        <h1 class="text-2xl md:text-[28px] font-bold leading-tight">{{ title }}</h1>
        <p v-if="registerHref" class="text-sm mt-2">
            {{ registerPrompt }} <Link :href="registerHref" class="underline font-semibold">{{ registerLabel }}</Link>
        </p>
    </div>

    <div v-if="status" class="mt-6 rounded-xl bg-green-50 border border-green-200 text-green-800 text-sm p-3">{{ status }}</div>

    <!-- Step 1: email -->
    <form v-if="step === 1" @submit.prevent="next" class="mt-8 space-y-4" novalidate>
        <div>
            <label for="signin-email" class="sr-only">Email</label>
            <input id="signin-email" ref="emailInput" v-model="form.email" type="email" autocomplete="username" autofocus
                   placeholder="Email" :aria-invalid="!!(emailError || form.errors.email)" aria-describedby="signin-email-error"
                   :class="['w-full rounded-xl px-4 py-3.5 text-base bg-gray-50 focus:bg-white',
                            emailError || form.errors.email ? 'border-red-600 focus:border-red-600 focus:ring-red-600' : 'border-gray-300 focus:border-navy-900 focus:ring-navy-900']" />
            <p id="signin-email-error" class="text-red-600 text-sm mt-1.5" role="alert">{{ emailError || form.errors.email }}</p>
        </div>
        <button class="w-full bg-emerald-600 hover:bg-emerald-700 text-white rounded-full py-3.5 font-semibold">Continue</button>
    </form>

    <!-- Step 2: password -->
    <form v-else @submit.prevent="submit" class="mt-8 space-y-4">
        <div class="flex items-center justify-between gap-3 rounded-xl border border-gray-200 px-4 py-3 text-sm">
            <span class="truncate font-semibold">{{ form.email }}</span>
            <button type="button" @click="back" class="underline shrink-0">Switch account</button>
        </div>
        <div>
            <label for="signin-password" class="sr-only">Password</label>
            <div class="relative">
                <input id="signin-password" ref="passwordInput" v-model="form.password" :type="showPassword ? 'text' : 'password'"
                       autocomplete="current-password" required placeholder="Password"
                       :aria-invalid="!!(form.errors.email || form.errors.password)" aria-describedby="signin-password-error"
                       :class="['w-full rounded-xl px-4 py-3.5 pr-16 text-base bg-gray-50 focus:bg-white',
                                form.errors.email || form.errors.password ? 'border-red-600 focus:border-red-600 focus:ring-red-600' : 'border-gray-300 focus:border-navy-900 focus:ring-navy-900']" />
                <button type="button" @click="showPassword = !showPassword" :aria-pressed="showPassword"
                        class="absolute right-3 top-1/2 -translate-y-1/2 text-sm font-semibold underline">{{ showPassword ? 'Hide' : 'Show' }}</button>
            </div>
            <p id="signin-password-error" class="text-red-600 text-sm mt-1.5" role="alert">{{ form.errors.password || form.errors.email }}</p>
        </div>
        <button :disabled="form.processing" class="w-full bg-emerald-600 hover:bg-emerald-700 disabled:opacity-60 text-white rounded-full py-3.5 font-semibold">
            {{ form.processing ? 'Signing in…' : 'Sign in' }}
        </button>
        <div class="flex items-center justify-between text-sm">
            <label class="flex items-center gap-2 cursor-pointer">
                <input type="checkbox" v-model="form.remember" class="rounded border-gray-400 text-navy-900 focus:ring-navy-900" />
                Stay signed in
            </label>
            <Link v-if="forgotHref" :href="forgotHref" class="underline">Forgot password?</Link>
        </div>
        <p v-if="form.remember" class="text-xs text-gray-500">Using a public or shared device? Uncheck to protect your account.</p>
    </form>
</template>
