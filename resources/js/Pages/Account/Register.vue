<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ref, computed } from 'vue';

const form = useForm({ first_name: '', last_name: '', email: '', phone: '', password: '' });
const showPassword = ref(false);

const checks = computed(() => [
    { ok: form.password.length >= 8, label: 'At least 8 characters' },
    { ok: /[A-Za-z]/.test(form.password) && /\d/.test(form.password), label: 'Letters and numbers' },
]);

const input = (err) => ['w-full rounded-xl px-4 py-3 text-base bg-gray-50 focus:bg-white',
    err ? 'border-red-600 focus:border-red-600 focus:ring-red-600' : 'border-gray-300 focus:border-navy-900 focus:ring-navy-900'];
</script>

<template>
    <Head title="Create an account" />
    <GuestLayout wide>
        <template #header-right>
            <span class="text-sm">Already have an account? <Link href="/account/login" class="underline font-semibold">Sign in</Link></span>
        </template>

        <div class="grid lg:grid-cols-2 gap-12 items-start">
            <div class="hidden lg:block rounded-3xl overflow-hidden relative h-[620px]">
                <img src="https://images.unsplash.com/photo-1483985988355-763728e1935b?q=75&w=1000&auto=format&fit=crop" alt="" class="w-full h-full object-cover" />
                <div class="absolute inset-0 bg-gradient-to-t from-navy-900/70 to-transparent"></div>
                <div class="absolute bottom-8 left-8 right-8 text-white">
                    <div class="text-2xl font-bold">Shop smarter with an account</div>
                    <ul class="mt-3 text-sm space-y-1 text-white/90">
                        <li>Track purchases and deliveries</li>
                        <li>Watch items and save searches</li>
                        <li>Earn loyalty points on every order</li>
                    </ul>
                </div>
            </div>

            <div class="max-w-[440px] w-full mx-auto lg:mx-0">
                <h1 class="text-2xl md:text-[28px] font-bold">Create an account</h1>
                <div class="mt-5 inline-flex rounded-full border border-gray-300 p-1 text-sm" role="tablist" aria-label="Account type">
                    <span role="tab" aria-selected="true" class="rounded-full bg-navy-900 text-white px-5 py-1.5 font-semibold">Personal</span>
                </div>

                <form @submit.prevent="form.post('/account/register', { onFinish: () => form.reset('password') })" class="mt-6 space-y-4" novalidate>
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label for="reg-first" class="sr-only">First name</label>
                            <input id="reg-first" v-model="form.first_name" autocomplete="given-name" placeholder="First name" required :class="input(form.errors.first_name)" />
                            <p class="text-red-600 text-xs mt-1">{{ form.errors.first_name }}</p>
                        </div>
                        <div>
                            <label for="reg-last" class="sr-only">Last name</label>
                            <input id="reg-last" v-model="form.last_name" autocomplete="family-name" placeholder="Last name" required :class="input(form.errors.last_name)" />
                            <p class="text-red-600 text-xs mt-1">{{ form.errors.last_name }}</p>
                        </div>
                    </div>
                    <div>
                        <label for="reg-email" class="sr-only">Email</label>
                        <input id="reg-email" v-model="form.email" type="email" autocomplete="email" placeholder="Email" required :class="input(form.errors.email)" />
                        <p class="text-red-600 text-xs mt-1">{{ form.errors.email }}</p>
                    </div>
                    <div>
                        <label for="reg-phone" class="sr-only">Phone number (optional)</label>
                        <input id="reg-phone" v-model="form.phone" type="tel" autocomplete="tel" placeholder="Phone number (optional, for M-Pesa)" :class="input(form.errors.phone)" />
                        <p class="text-red-600 text-xs mt-1">{{ form.errors.phone }}</p>
                    </div>
                    <div>
                        <label for="reg-password" class="sr-only">Password</label>
                        <div class="relative">
                            <input id="reg-password" v-model="form.password" :type="showPassword ? 'text' : 'password'" autocomplete="new-password"
                                   placeholder="Password" required aria-describedby="reg-password-rules" :class="[...input(form.errors.password), 'pr-16']" />
                            <button type="button" @click="showPassword = !showPassword" :aria-pressed="showPassword"
                                    class="absolute right-3 top-1/2 -translate-y-1/2 text-sm font-semibold underline">{{ showPassword ? 'Hide' : 'Show' }}</button>
                        </div>
                        <ul id="reg-password-rules" class="mt-2 space-y-0.5 text-xs">
                            <li v-for="c in checks" :key="c.label" :class="c.ok ? 'text-green-700' : 'text-gray-500'">{{ c.ok ? '✓' : '•' }} {{ c.label }}</li>
                        </ul>
                        <p class="text-red-600 text-xs mt-1">{{ form.errors.password }}</p>
                    </div>

                    <p class="text-xs text-gray-600 leading-relaxed">
                        By selecting <b>Create personal account</b>, you agree to our <a href="/page/terms" class="underline">Terms</a>
                        and acknowledge our <a href="/page/privacy" class="underline">Privacy Notice</a>.
                    </p>
                    <button :disabled="form.processing" class="w-full bg-emerald-600 hover:bg-emerald-700 disabled:opacity-60 text-white rounded-full py-3.5 font-semibold">
                        {{ form.processing ? 'Creating account…' : 'Create personal account' }}
                    </button>
                </form>
            </div>
        </div>
    </GuestLayout>
</template>
