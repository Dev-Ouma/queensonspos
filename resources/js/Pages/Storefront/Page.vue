<script setup>
import StorefrontLayout from '@/Layouts/StorefrontLayout.vue';
import { Head } from '@inertiajs/vue3';
defineProps({ page: Object });
</script>

<template>
    <Head :title="`${page.title} — QUEENSONS`" />
    <StorefrontLayout>
        <div class="max-w-3xl mx-auto px-4 py-14">
            <h1 class="font-display text-3xl mb-6">{{ page.title }}</h1>
            <div class="prose prose-sm text-gray-600 whitespace-pre-line">{{ page.body }}</div>
        </div>
    </StorefrontLayout>
</template>
