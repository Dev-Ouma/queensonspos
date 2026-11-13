<script setup>
import { Head } from '@inertiajs/vue3';
import { onMounted } from 'vue';
import { kes } from '@/money';

defineProps({ sale: Object });
onMounted(() => setTimeout(() => window.print(), 300));
</script>

<template>
    <Head :title="`Receipt ${sale.reference}`" />
    <div class="max-w-xs mx-auto p-6 font-mono text-xs text-black bg-white">
        <div class="text-center mb-4">
            <img src="/logo.svg" alt="" class="w-12 h-12 mx-auto mb-1" />
            <div class="text-base font-bold tracking-widest">QUEENSONS</div>
            <div>Style Beyond Seasons.</div>
            <div class="mt-1">{{ sale.branch }}</div>
        </div>
        <div class="border-t border-b border-dashed border-black py-2 mb-2">
            <div>Receipt: {{ sale.reference }}</div>
            <div>Date: {{ sale.at }}</div>
            <div>Cashier: {{ sale.cashier }}</div>
        </div>
        <table class="w-full">
            <tbody>
                <tr v-for="(i, idx) in sale.items" :key="idx">
                    <td class="align-top pr-1">{{ i.qty }}×</td>
                    <td class="align-top">{{ i.name_snapshot }}<br /><span class="text-[10px]">{{ i.sku_snapshot }}</span></td>
                    <td class="align-top text-right whitespace-nowrap">{{ (i.line_total_cents / 100).toFixed(2) }}</td>
                </tr>
            </tbody>
        </table>
        <div class="border-t border-dashed border-black mt-2 pt-2 space-y-0.5">
            <div class="flex justify-between"><span>Subtotal</span><span>{{ (sale.subtotal_cents / 100).toFixed(2) }}</span></div>
            <div v-if="sale.discount_cents" class="flex justify-between"><span>Discount</span><span>-{{ (sale.discount_cents / 100).toFixed(2) }}</span></div>
            <div class="flex justify-between font-bold text-sm"><span>TOTAL</span><span>{{ kes(sale.total_cents) }}</span></div>
            <div class="flex justify-between uppercase"><span>Paid via</span><span>{{ sale.method }}</span></div>
        </div>
        <div class="text-center mt-4">
            <div>Thank you for shopping with us!</div>
            <div class="mt-1">Returns within 7 days with this receipt.</div>
        </div>
    </div>
</template>
