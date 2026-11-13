<script setup>
import { Link } from '@inertiajs/vue3';
</script>

<template>
    <div class="min-h-screen grid lg:grid-cols-2 bg-white font-sans text-navy-900">
        <!-- Brand panel -->
        <div class="hidden lg:flex relative flex-col justify-between bg-navy-900 text-white p-12 overflow-hidden">
            <div class="absolute -top-24 -right-24 w-96 h-96 rounded-full bg-navy-700/60 blur-3xl"></div>
            <div class="absolute -bottom-32 -left-16 w-80 h-80 rounded-full bg-gold/10 blur-3xl"></div>

            <Link href="/" class="relative flex items-center gap-3">
                <img src="/logo.svg" alt="QUEENSONS crown logo" class="w-10 h-10" />
                <span class="font-display text-2xl tracking-widest">QUEENSONS</span>
            </Link>

            <div class="relative">
                <p class="eyebrow text-gold mb-4">Back Office</p>
                <h1 class="font-display text-4xl leading-snug">
                    Style beyond<br /><span class="italic text-gold-light">seasons.</span>
                </h1>
                <p class="text-navy-100/80 text-sm mt-5 max-w-sm leading-relaxed">
                    One platform for the storefront, point of sale, inventory and finance —
                    unified stock, every channel.
                </p>
            </div>

            <div class="relative text-xs text-navy-100/60 tracking-wide">
                © {{ new Date().getFullYear() }} QUEENSONS · Affordable Premium Fashion
            </div>
        </div>

        <!-- Form panel -->
        <div class="flex flex-col items-center justify-center px-6 py-12">
            <Link href="/" class="lg:hidden flex items-center gap-2.5 mb-8">
                <img src="/logo.svg" alt="QUEENSONS crown logo" class="w-10 h-10" />
                <span class="font-display text-2xl tracking-widest text-navy-700">QUEENSONS</span>
            </Link>

            <div class="w-full sm:max-w-md">
                <div class="hidden lg:flex items-center gap-3 mb-8">
                    <img src="/logo.svg" alt="" class="w-9 h-9" />
                    <div class="rule-gold flex-1"></div>
                </div>
                <slot />
            </div>
        </div>
    </div>
</template>
