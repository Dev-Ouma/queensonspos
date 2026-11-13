<script setup>
import { Link, router, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import { kes, img, fallbackImage } from '@/money';

const props = defineProps({
    product: Object,
    layout: { type: String, default: 'grid' }, // grid | list | rail
});

const page = usePage();
const customer = computed(() => page.props.auth?.customer);
const freeAt = computed(() => page.props.storefront?.free_delivery_threshold_cents ?? 500000);

const p = props.product;
const discount = p.compare_at_cents ? Math.round((1 - p.price_cents / p.compare_at_cents) * 100) : 0;
const photo = img(p.image) || fallbackImage(p.id);

function watch() {
    if (!customer.value) return router.visit('/account/login');
    router.post('/account/wishlist/toggle', { product_id: p.id }, { preserveScroll: true });
}
</script>

<template>
    <div :class="['group relative', layout === 'list' ? 'flex gap-4 border-b border-gray-200 py-4' : '']">
        <Link :href="`/product/${p.slug}`"
              :class="['block relative bg-gray-100 rounded-xl overflow-hidden shrink-0',
                       layout === 'list' ? 'w-40 h-40 md:w-56 md:h-56' : 'aspect-square']">
            <img :src="photo" :alt="p.name" loading="lazy"
                 class="w-full h-full object-cover group-hover:scale-[1.03] transition duration-300" />
            <span v-if="!p.in_stock" class="absolute top-2 left-2 bg-navy-900 text-white text-[11px] font-semibold px-2 py-0.5 rounded">Sold out</span>
        </Link>
        <button @click="watch" aria-label="Add to watchlist"
                :class="['absolute w-8 h-8 rounded-full bg-white shadow flex items-center justify-center hover:text-gold-dark',
                         layout === 'list' ? 'top-6 left-[8.5rem] md:left-[12.5rem]' : 'top-2 right-2']">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 21s-7.5-4.6-9.5-9.2C1 8 3.4 4.5 7 4.5c2 0 3.6 1.1 5 2.8 1.4-1.7 3-2.8 5-2.8 3.6 0 6 3.5 4.5 7.3C19.5 16.4 12 21 12 21z"/></svg>
        </button>

        <div :class="layout === 'list' ? 'flex-1 min-w-0' : 'mt-2'">
            <Link :href="`/product/${p.slug}`" class="block text-[13px] md:text-sm leading-snug text-navy-900 hover:underline line-clamp-2">
                <span v-if="p.is_new" class="text-[11px] font-bold uppercase text-emerald-700 mr-1">New listing</span>{{ p.name }}
            </Link>
            <div class="text-xs text-gray-500 mt-0.5">Brand new<span v-if="p.colors?.length > 1"> · {{ p.colors.length }} colours</span></div>
            <div :class="['font-bold text-navy-900 mt-1', layout === 'rail' ? 'text-base' : 'text-lg']">{{ kes(p.price_cents) }}</div>
            <div v-if="discount" class="text-xs text-gray-500">
                <span class="line-through">{{ kes(p.compare_at_cents) }}</span>
                <span class="text-red-600 font-semibold ml-1">{{ discount }}% off</span>
            </div>
            <div v-if="layout !== 'rail'" class="text-xs mt-1" :class="p.price_cents >= freeAt ? 'text-green-700 font-semibold' : 'text-gray-500'">
                {{ p.price_cents >= freeAt ? 'Free delivery' : 'Delivery from KES 300' }}
            </div>
            <div v-if="p.sold" class="text-xs font-bold text-navy-900 mt-0.5">{{ p.sold }} sold</div>
            <div v-if="layout === 'list'" class="text-xs text-gray-500 mt-1">Buy it now · Pay with M-Pesa or card</div>
        </div>
    </div>
</template>
