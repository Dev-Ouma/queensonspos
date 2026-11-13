<script setup>
import { useForm } from '@inertiajs/vue3';
import { ref, computed } from 'vue';

const props = defineProps({ action: String, token: String, email: String, confirm: Boolean });
const form = useForm({ token: props.token, email: props.email, password: '', password_confirmation: '' });
const show = ref(false);
const checks = computed(() => [
    { ok: form.password.length >= 8, label: 'At least 8 characters' },
    { ok: /[A-Za-z]/.test(form.password) && /\d/.test(form.password), label: 'Letters and numbers' },
]);
function submit() {
    if (!props.confirm) form.password_confirmation = form.password;
    form.post(props.action, { onFinish: () => form.reset('password', 'password_confirmation') });
}
const field = (err) => ['w-full rounded-xl px-4 py-3.5 text-base bg-gray-50 focus:bg-white',
    err ? 'border-red-600 focus:border-red-600 focus:ring-red-600' : 'border-gray-300 focus:border-navy-900 focus:ring-navy-900'];
</script>

<template>
    <h1 class="text-2xl md:text-[28px] font-bold text-center">Create a new password</h1>
    <p class="text-sm text-gray-600 text-center mt-2">for <b>{{ email }}</b></p>

    <form @submit.prevent="submit" class="mt-8 space-y-4">
        <p v-if="form.errors.email" class="rounded-xl bg-red-50 border border-red-200 text-red-700 text-sm p-3" role="alert">{{ form.errors.email }}</p>
        <div>
            <label for="reset-password" class="sr-only">New password</label>
            <div class="relative">
                <input id="reset-password" v-model="form.password" :type="show ? 'text' : 'password'" autocomplete="new-password" autofocus required
                       placeholder="New password" aria-describedby="reset-rules" :class="[...field(form.errors.password), 'pr-16']" />
                <button type="button" @click="show = !show" :aria-pressed="show" class="absolute right-3 top-1/2 -translate-y-1/2 text-sm font-semibold underline">{{ show ? 'Hide' : 'Show' }}</button>
            </div>
            <ul id="reset-rules" class="mt-2 space-y-0.5 text-xs">
                <li v-for="c in checks" :key="c.label" :class="c.ok ? 'text-green-700' : 'text-gray-500'">{{ c.ok ? '✓' : '•' }} {{ c.label }}</li>
            </ul>
            <p class="text-red-600 text-sm mt-1" role="alert">{{ form.errors.password }}</p>
        </div>
        <div v-if="confirm">
            <label for="reset-confirm" class="sr-only">Confirm new password</label>
            <input id="reset-confirm" v-model="form.password_confirmation" :type="show ? 'text' : 'password'" autocomplete="new-password" required
                   placeholder="Confirm new password" :class="field(form.errors.password_confirmation)" />
        </div>
        <button :disabled="form.processing" class="w-full bg-emerald-600 hover:bg-emerald-700 disabled:opacity-60 text-white rounded-full py-3.5 font-semibold">
            {{ form.processing ? 'Saving…' : 'Reset password' }}
        </button>
    </form>
</template>
