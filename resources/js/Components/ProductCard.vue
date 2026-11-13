<script setup>
import { Link } from '@inertiajs/vue3';
import { kes, img } from '@/money';

const props = defineProps({ product: Object });

const discount = props.product.compare_at_cents
    ? Math.round((1 - props.product.price_cents / props.product.compare_at_cents) * 100)
    : 0;
</script>

<template>
    <Link :href="`/product/${product.slug}`" class="group block">
        <div class="aspect-[3/4] bg-gradient-to-br from-navy-50 to-gray-100 rounded-xl overflow-hidden relative">
            <img v-if="product.image" :src="img(product.image)" :alt="product.name"
                 class="w-full h-full object-cover group-hover:scale-105 transition duration-700" />
            <div v-else class="w-full h-full flex items-center justify-center">
                <span class="font-display text-3xl text-navy-100 select-none">Q</span>
            </div>

            <!-- Badges -->
            <div class="absolute top-3 left-3 flex flex-col gap-1.5">
                <span v-if="!product.in_stock" class="bg-navy-900/90 text-white text-[10px] tracking-[0.15em] uppercase px-2.5 py-1 rounded-full">Sold out</span>
                <span v-else-if="discount" class="bg-gold text-navy-900 text-[10px] tracking-[0.15em] uppercase px-2.5 py-1 rounded-full font-semibold">−{{ discount }}%</span>
                <span v-if="product.is_new" class="bg-white/90 text-navy-800 text-[10px] tracking-[0.15em] uppercase px-2.5 py-1 rounded-full font-semibold">New</span>
            </div>

            <!-- Hover action -->
            <div class="absolute inset-x-3 bottom-3 translate-y-3 opacity-0 group-hover:translate-y-0 group-hover:opacity-100 transition duration-300">
                <span class="block bg-white/95 backdrop-blur text-navy-800 text-xs font-semibold tracking-[0.15em] uppercase text-center rounded-full py-2.5 shadow-lg">
                    View Piece
                </span>
            </div>
        </div>
        <div class="mt-3">
            <div class="text-sm font-medium group-hover:text-gold-dark transition">{{ product.name }}</div>
            <div class="flex items-center gap-2 mt-1">
                <span class="text-sm font-semibold text-navy-700">{{ kes(product.price_cents) }}</span>
                <span v-if="product.compare_at_cents" class="text-xs text-gray-400 line-through">{{ kes(product.compare_at_cents) }}</span>
            </div>
            <div v-if="product.colors?.length > 1" class="text-[11px] text-gray-400 mt-0.5">{{ product.colors.length }} colours</div>
        </div>
    </Link>
</template>
