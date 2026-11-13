<script setup>
import { Link } from '@inertiajs/vue3';
defineProps({ links: Array });
</script>

<template>
    <nav v-if="links && links.length > 3" class="flex flex-wrap gap-1 mt-4">
        <template v-for="(l, i) in links" :key="i">
            <Link v-if="l.url" :href="l.url" preserve-scroll
                  :class="['px-3 py-1.5 text-sm rounded border', l.active ? 'bg-navy-700 text-white border-navy-700' : 'bg-white text-gray-700 border-gray-200 hover:border-navy-700']"
                  v-html="l.label" />
            <span v-else class="px-3 py-1.5 text-sm text-gray-400" v-html="l.label" />
        </template>
    </nav>
</template>
