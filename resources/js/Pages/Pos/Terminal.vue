<script setup>
import { Head, Link, router, useForm, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import { kes } from '@/money';

const props = defineProps({ session: Object, registers: Array, canDiscount: Boolean });
const page = usePage();
const flash = computed(() => page.props.flash?.success);
const lastSale = computed(() => page.props.flash?.lastSale);
const errors = computed(() => Object.values(page.props.errors || {}));

// --- open register ---
const openForm = useForm({ register_id: null, opening_cash: 0 });

// --- cart ---
const cart = ref([]);
const q = ref('');
const results = ref([]);
let searchTimer = null;

function doSearch() {
    clearTimeout(searchTimer);
    searchTimer = setTimeout(async () => {
        if (q.value.length < 2) { results.value = []; return; }
        const r = await fetch(`/pos/search?q=${encodeURIComponent(q.value)}`, { headers: { Accept: 'application/json' } });
        results.value = await r.json();
    }, 200);
}
function addToCart(v) {
    const existing = cart.value.find(l => l.variant_id === v.variant_id);
    if (existing) existing.qty++;
    else cart.value.push({ ...v, qty: 1 });
    q.value = '';
    results.value = [];
}
const subtotal = computed(() => cart.value.reduce((s, l) => s + l.price_cents * l.qty, 0));
const discount = ref(0);
const total = computed(() => Math.max(0, subtotal.value - Math.round(discount.value * 100)));
const method = ref('cash');
const txnId = ref('');

function completeSale() {
    router.post('/pos/sale', {
        lines: cart.value.map(l => ({ variant_id: l.variant_id, qty: l.qty })),
        payment_method: method.value,
        discount: discount.value,
        provider_txn_id: txnId.value || null,
    }, {
        preserveScroll: true,
        onSuccess: () => { cart.value = []; discount.value = 0; txnId.value = ''; },
    });
}

// --- returns ---
const showReturn = ref(false);
const lookupRef = ref('');
const foundSale = ref(null);
const returnItems = ref([]);
const returnCondition = ref('resellable');
const returnReason = ref('');

async function lookup() {
    const r = await fetch(`/pos/sale-lookup?reference=${encodeURIComponent(lookupRef.value)}`, { headers: { Accept: 'application/json' } });
    if (!r.ok) { foundSale.value = null; return; }
    foundSale.value = await r.json();
    returnItems.value = foundSale.value.items.map(i => ({ ...i, return_qty: 0 }));
}
function submitReturn() {
    router.post('/pos/return', {
        sale_id: foundSale.value.id,
        items: returnItems.value.filter(i => i.return_qty > 0).map(i => ({ pos_sale_item_id: i.pos_sale_item_id, qty: i.return_qty })),
        condition: returnCondition.value,
        reason: returnReason.value,
    }, { preserveScroll: true, onSuccess: () => { showReturn.value = false; foundSale.value = null; lookupRef.value = ''; } });
}

// --- cash / close ---
const showClose = ref(false);
const actualCash = ref(0);
const showCash = ref(false);
const cashForm = useForm({ type: 'cash_in', amount: null, reason: '' });
</script>

<template>
    <Head title="POS — QUEENSONS" />
    <div class="min-h-screen bg-gray-100 font-sans">
        <!-- POS header -->
        <header class="bg-navy-800 text-white h-14 flex items-center justify-between px-4">
            <div class="flex items-center gap-3">
                <img src="/logo.svg" alt="" class="w-7 h-7" />
                <span class="font-display tracking-widest text-gold">QUEENSONS</span>
                <span class="text-xs bg-navy-600 rounded px-2 py-0.5">POS</span>
            </div>
            <div v-if="session" class="text-xs text-navy-100">
                {{ session.branch }} · {{ session.register }} · open since {{ session.opened_at }}
            </div>
            <div class="flex items-center gap-3 text-xs">
                <Link href="/admin" class="hover:text-gold">Admin</Link>
                <Link href="/logout" method="post" as="button" class="hover:text-red-300">Sign out</Link>
            </div>
        </header>

        <div v-if="flash" class="bg-emerald-600 text-white text-sm px-4 py-2 flex justify-between items-center">
            <span>{{ flash }}</span>
            <a v-if="lastSale" :href="`/pos/receipt/${lastSale.reference}`" target="_blank" class="underline font-semibold">Print receipt →</a>
        </div>
        <div v-if="errors.length" class="bg-red-600 text-white text-sm px-4 py-2">
            <div v-for="(e, i) in errors" :key="i">{{ e }}</div>
        </div>

        <!-- No session: open register -->
        <div v-if="!session" class="max-w-md mx-auto mt-20 bg-white rounded-xl shadow p-8">
            <h1 class="font-semibold text-lg mb-4">Open Register</h1>
            <form @submit.prevent="openForm.post('/pos/session/open')" class="space-y-4">
                <select v-model="openForm.register_id" required class="w-full rounded border-gray-200">
                    <option :value="null">Select register…</option>
                    <option v-for="r in registers" :key="r.id" :value="r.id">{{ r.label }}</option>
                </select>
                <div>
                    <label class="text-xs text-gray-500">Opening cash float (KES)</label>
                    <input v-model.number="openForm.opening_cash" type="number" step="0.01" min="0" required class="w-full rounded border-gray-200 mt-1" />
                </div>
                <button :disabled="openForm.processing" class="w-full bg-emerald-600 text-white rounded-lg py-3 font-semibold">Open Register</button>
            </form>
        </div>

        <!-- Terminal -->
        <div v-else class="flex flex-col lg:flex-row gap-4 p-4 max-w-7xl mx-auto">
            <!-- Left: search + results -->
            <div class="flex-1">
                <div class="bg-white rounded-xl shadow-sm p-4">
                    <input v-model="q" @input="doSearch" autofocus placeholder="Scan barcode or search product / SKU…"
                           class="w-full rounded-lg border-gray-200 text-lg py-3" />
                    <div class="mt-3 grid md:grid-cols-2 gap-2">
                        <button v-for="r in results" :key="r.variant_id" @click="addToCart(r)" :disabled="r.available < 1"
                                class="text-left border border-gray-100 rounded-lg p-3 hover:border-emerald-600 disabled:opacity-40 transition">
                            <div class="font-medium text-sm">{{ r.name }}</div>
                            <div class="text-xs text-gray-400">{{ r.color }} / {{ r.size }} · {{ r.sku }}</div>
                            <div class="flex justify-between mt-1 text-sm">
                                <span class="font-bold text-navy-700">{{ kes(r.price_cents) }}</span>
                                <span :class="r.available <= 5 ? 'text-amber-600' : 'text-gray-400'" class="text-xs">{{ r.available }} in stock</span>
                            </div>
                        </button>
                    </div>
                    <p v-if="q.length >= 2 && !results.length" class="text-sm text-gray-400 mt-3">No matches.</p>
                </div>

                <!-- Session stats + actions -->
                <div class="grid grid-cols-2 md:grid-cols-4 gap-3 mt-4">
                    <div class="bg-white rounded-xl shadow-sm p-4 text-center">
                        <div class="text-xs text-gray-400">Sales today</div>
                        <div class="font-bold text-navy-700">{{ session.sales_count }}</div>
                    </div>
                    <div class="bg-white rounded-xl shadow-sm p-4 text-center">
                        <div class="text-xs text-gray-400">Takings</div>
                        <div class="font-bold text-emerald-700">{{ kes(session.sales_total_cents) }}</div>
                    </div>
                    <button @click="showReturn = true" class="bg-white rounded-xl shadow-sm p-4 text-center hover:ring-2 ring-navy-700">
                        <div class="text-xs text-gray-400">Returns</div>
                        <div class="font-bold text-navy-700">↩ Process</div>
                    </button>
                    <button @click="showCash = true" class="bg-white rounded-xl shadow-sm p-4 text-center hover:ring-2 ring-navy-700">
                        <div class="text-xs text-gray-400">Cash drawer</div>
                        <div class="font-bold text-navy-700">± Cash</div>
                    </button>
                </div>
                <button @click="showClose = true" class="mt-3 text-xs text-gray-400 hover:text-red-500 underline">Close register / end shift</button>
            </div>

            <!-- Right: cart -->
            <div class="lg:w-96 bg-white rounded-xl shadow-sm p-4 h-fit sticky top-4">
                <h2 class="font-semibold mb-3">Current Sale</h2>
                <div v-if="!cart.length" class="text-gray-400 text-sm py-10 text-center">Cart is empty — scan or search.</div>
                <div v-else class="space-y-2 max-h-72 overflow-y-auto">
                    <div v-for="(l, i) in cart" :key="l.variant_id" class="flex items-center gap-2 text-sm border-b border-gray-50 pb-2">
                        <div class="flex-1 min-w-0">
                            <div class="truncate">{{ l.name }}</div>
                            <div class="text-xs text-gray-400">{{ l.color }}/{{ l.size }} · {{ kes(l.price_cents) }}</div>
                        </div>
                        <input v-model.number="l.qty" type="number" min="1" class="w-14 rounded border-gray-200 text-xs text-center" />
                        <span class="font-semibold w-20 text-right">{{ kes(l.price_cents * l.qty) }}</span>
                        <button @click="cart.splice(i, 1)" class="text-red-400">✕</button>
                    </div>
                </div>

                <div v-if="cart.length" class="mt-4 space-y-2 text-sm">
                    <div class="flex justify-between"><span>Subtotal</span><span>{{ kes(subtotal) }}</span></div>
                    <div v-if="canDiscount" class="flex justify-between items-center">
                        <span>Discount (KES)</span>
                        <input v-model.number="discount" type="number" min="0" step="0.01" class="w-24 rounded border-gray-200 text-xs text-right" />
                    </div>
                    <div class="flex justify-between text-lg font-bold border-t pt-2"><span>TOTAL</span><span class="text-emerald-700">{{ kes(total) }}</span></div>

                    <div class="grid grid-cols-2 gap-2 mt-2">
                        <button v-for="m in ['cash', 'mpesa', 'card', 'bank_transfer']" :key="m" @click="method = m"
                                :class="['rounded-lg border py-2 text-xs uppercase font-semibold', method === m ? 'border-emerald-600 bg-emerald-50 text-emerald-700' : 'border-gray-200']">
                            {{ m.replace('_', ' ') }}
                        </button>
                    </div>
                    <input v-if="method !== 'cash'" v-model="txnId" placeholder="Provider txn code (e.g. M-Pesa ref)" class="w-full rounded border-gray-200 text-xs mt-1" />

                    <button @click="completeSale" class="w-full bg-navy-700 hover:bg-navy-600 text-white rounded-xl py-4 text-base font-bold mt-2">
                        Complete Sale — {{ kes(total) }}
                    </button>
                </div>
            </div>
        </div>

        <!-- Return modal -->
        <div v-if="showReturn" class="fixed inset-0 bg-black/40 flex items-center justify-center z-50 p-4" @click.self="showReturn = false">
            <div class="bg-white rounded-xl p-6 w-full max-w-lg">
                <h2 class="font-semibold mb-4">Process Return</h2>
                <div class="flex gap-2 mb-4">
                    <input v-model="lookupRef" placeholder="Original sale reference (POS-…)" class="flex-1 rounded border-gray-200 text-sm font-mono" />
                    <button @click="lookup" class="bg-navy-700 text-white rounded-lg px-4 text-sm">Find</button>
                </div>
                <div v-if="foundSale" class="space-y-3">
                    <div class="text-xs text-gray-500">{{ foundSale.reference }} · {{ kes(foundSale.total_cents) }} · {{ foundSale.status }}</div>
                    <div v-for="i in returnItems" :key="i.pos_sale_item_id" class="flex items-center gap-2 text-sm">
                        <span class="flex-1">{{ i.name }} (sold {{ i.qty }})</span>
                        <input v-model.number="i.return_qty" type="number" min="0" :max="i.qty" class="w-16 rounded border-gray-200 text-xs text-center" />
                    </div>
                    <div class="flex gap-3 items-center">
                        <select v-model="returnCondition" class="rounded border-gray-200 text-sm">
                            <option value="resellable">Resellable — restock</option>
                            <option value="damaged">Damaged — do not restock</option>
                        </select>
                        <input v-model="returnReason" placeholder="Reason" class="flex-1 rounded border-gray-200 text-sm" />
                    </div>
                    <button @click="submitReturn" class="w-full bg-emerald-600 text-white rounded-lg py-2.5 text-sm font-semibold">Process Return & Refund</button>
                </div>
            </div>
        </div>

        <!-- Cash movement modal -->
        <div v-if="showCash" class="fixed inset-0 bg-black/40 flex items-center justify-center z-50" @click.self="showCash = false">
            <div class="bg-white rounded-xl p-6 w-full max-w-sm">
                <h2 class="font-semibold mb-4">Cash In / Out</h2>
                <form @submit.prevent="cashForm.post('/pos/cash-movement', { preserveScroll: true, onSuccess: () => (showCash = false) })" class="space-y-3 text-sm">
                    <select v-model="cashForm.type" class="w-full rounded border-gray-200">
                        <option value="cash_in">Cash IN (add to drawer)</option>
                        <option value="cash_out">Cash OUT (remove from drawer)</option>
                    </select>
                    <input v-model.number="cashForm.amount" type="number" step="0.01" min="0.01" required placeholder="Amount KES" class="w-full rounded border-gray-200" />
                    <input v-model="cashForm.reason" required placeholder="Reason" class="w-full rounded border-gray-200" />
                    <button :disabled="cashForm.processing" class="w-full bg-navy-700 text-white rounded-lg py-2.5 font-semibold">Record</button>
                </form>
            </div>
        </div>

        <!-- Close register modal -->
        <div v-if="showClose" class="fixed inset-0 bg-black/40 flex items-center justify-center z-50" @click.self="showClose = false">
            <div class="bg-white rounded-xl p-6 w-full max-w-sm">
                <h2 class="font-semibold mb-2">Close Register</h2>
                <p class="text-xs text-gray-500 mb-4">Count the drawer and enter the actual cash. Variance is recorded.</p>
                <input v-model.number="actualCash" type="number" step="0.01" min="0" placeholder="Actual cash counted (KES)" class="w-full rounded border-gray-200 text-sm mb-3" />
                <button @click="router.post('/pos/session/close', { actual_cash: actualCash }, { onSuccess: () => (showClose = false) })"
                        class="w-full bg-red-600 text-white rounded-lg py-2.5 text-sm font-semibold">Close Register</button>
            </div>
        </div>
    </div>
</template>
