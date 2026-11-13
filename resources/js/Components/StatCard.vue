<script setup>
defineProps({ label: String, value: String, sub: String, accent: { type: String, default: 'navy' } });
</script>

<template>
    <div class="lift bg-white rounded-xl border border-gray-100 shadow-sm p-5">
        <div class="text-xs uppercase tracking-wide text-gray-500">{{ label }}</div>
        <div :class="['text-2xl font-bold mt-1', accent === 'emerald' ? 'text-emerald-600' : accent === 'gold' ? 'text-gold-dark' : 'text-navy-700']">{{ value }}</div>
        <div v-if="sub" class="text-xs text-gray-400 mt-1">{{ sub }}</div>
    </div>
</template>
