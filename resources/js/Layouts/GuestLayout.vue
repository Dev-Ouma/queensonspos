<script setup>
import { Link } from '@inertiajs/vue3';

defineProps({ wide: Boolean });
</script>

<template>
    <div class="min-h-screen flex flex-col bg-white font-sans text-navy-900">
        <!-- Minimal header: logo only, like a focused sign-in flow -->
        <header class="px-6 md:px-10 py-5 flex items-center justify-between">
            <Link href="/" class="flex items-center gap-2" aria-label="QUEENSONS home">
                <img src="/logo.svg" alt="" class="w-9 h-9" />
                <span class="font-display text-2xl tracking-tight font-extrabold">queensons<span class="text-gold">.</span></span>
            </Link>
            <slot name="header-right" />
        </header>

        <main class="flex-1 flex justify-center px-6 pt-6 pb-16">
            <div :class="['w-full', wide ? 'max-w-5xl' : 'max-w-[400px]']">
                <slot />
            </div>
        </main>

        <footer class="border-t border-gray-200 px-6 py-5 text-[11px] text-gray-500 text-center">
            Copyright © {{ new Date().getFullYear() }} QUEENSONS. All rights reserved.
            <Link href="/page/terms" class="underline ml-2">Terms</Link>
            <Link href="/page/privacy" class="underline ml-2">Privacy</Link>
        </footer>
    </div>
</template>
