<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head } from '@inertiajs/vue3';
import { ref } from 'vue';

const open = ref('getting-started');

const sections = [
    {
        id: 'getting-started',
        icon: '🚀',
        title: 'Getting Started',
        intro: 'What QUEENSONS is and how the pieces fit together.',
        blocks: [
            { h: 'One system, three faces', p: 'QUEENSONS runs as one platform with three entry points: the public website (/) where customers shop, this admin panel (/admin) where the business is managed, and the POS terminal (/pos) used at the physical shop. All three share the same products, stock, customers and sales records — a sale anywhere updates stock everywhere, instantly.' },
            { h: 'Signing in', p: 'Staff sign in at /login with the email and password created for them under Staff. What you can see and do is controlled by your role — a Cashier sees only the POS, while the Business Owner sees everything. If a menu item is missing, you do not have that permission; ask an administrator.' },
            { h: 'The golden rule', p: 'Never track sales, stock or money outside this system. Every shilling in should be a recorded sale or payment; every item that moves should be a stock transaction. The reports are only as truthful as what is recorded.' },
        ],
    },
    {
        id: 'products',
        icon: '👗',
        title: 'Products & Catalogue',
        intro: 'Creating products, variants, categories and collections.',
        blocks: [
            { h: 'Products vs variants', p: 'A product (e.g. "Classic Blazer") is the item customers browse. Variants are the actual sellable units — each size/colour combination with its own SKU, barcode, price, cost and stock. Stock is ALWAYS tracked per variant: a blazer in Navy/M is different stock from Navy/L.' },
            { h: 'Creating a product', p: 'Products → New Product. Fill in name, SKU root and description, tick its categories, then add one variant row per size/colour. Set the selling price and the cost price (what you paid) — cost powers the profit reports. Save as Draft first; switch to Published when ready for the website.' },
            { h: 'Pricing', p: 'Prices are entered in KES. The cost price is never shown to customers; it is used to compute gross profit. Update the cost when supplier prices change — goods receiving also updates it automatically from the purchase order cost.' },
            { h: 'Categories & collections', p: 'Categories (Dresses, Shoes…) drive the website navigation and filters — manage them under Categories. Collections (New Arrivals, Office Collection…) are curated groups used for marketing. A product can be in several of each. New business lines (men\'s, kids\') are added as categories — no developer needed.' },
            { h: 'Reviews', p: 'Customer reviews arrive as "pending" and are invisible until you publish them under Reviews. Reject anything abusive; publish honest feedback, good or bad.' },
        ],
    },
    {
        id: 'inventory',
        icon: '📦',
        title: 'Inventory & Stock',
        intro: 'How stock moves, and how to keep it accurate.',
        blocks: [
            { h: 'How stock changes', p: 'Stock only changes through recorded movements: goods received from a supplier (+), a website sale (−), a POS sale (−), a customer return (+), or a manual adjustment (±). Every movement is written to the Stock Ledger permanently — nothing is ever edited or deleted there.' },
            { h: 'On hand vs reserved vs available', p: 'On hand is what is physically in the warehouse. Reserved is stock held for customers who are mid-checkout online (released automatically after 15 minutes if they abandon). Available = on hand − reserved − damaged. The website and POS can only sell what is available, so two customers can never buy the last piece twice.' },
            { h: 'Adjustments', p: 'Found a counting error, damage or theft? Inventory → Adjust on the affected row. Enter a positive or negative quantity and a reason — the reason is mandatory and permanently recorded with your name. Large or frequent adjustments will be questioned in audit.' },
            { h: 'Low stock alerts', p: 'The dashboard flags any variant at 5 or fewer available. Use it as the daily reorder list.' },
        ],
    },
    {
        id: 'purchasing',
        icon: '🚚',
        title: 'Suppliers & Purchasing',
        intro: 'Ordering stock and receiving goods correctly.',
        blocks: [
            { h: 'The flow', p: 'Supplier → Purchase Order → Goods Received → Stock updated. Create suppliers once under Suppliers. Raise a PO under Purchases listing each variant, quantity and agreed unit cost. When the delivery arrives, open the PO and record what actually arrived.' },
            { h: 'Receiving goods', p: 'Count the delivery before receiving it in the system. Enter received quantity and, separately, any damaged pieces — damaged items go into the damaged bucket, not sellable stock. Receiving updates stock immediately and sets the variant\'s cost price to the PO cost. Partial deliveries are fine: receive what came, the PO stays "partially received" until complete.' },
            { h: 'Never skip the PO', p: 'Do not receive stock with manual adjustments to "save time" — you lose the cost tracking, the supplier history and the audit trail. Adjustments are for corrections, not deliveries.' },
        ],
    },
    {
        id: 'orders',
        icon: '🛍️',
        title: 'Online Orders & Fulfilment',
        intro: 'Processing website orders from payment to delivery.',
        blocks: [
            { h: 'Order lifecycle', p: 'Orders move through fixed stages: awaiting payment → paid → confirmed → processing → packed → ready for dispatch → dispatched → out for delivery → delivered → completed. You move an order forward from its detail page; the system refuses invalid jumps so nothing is skipped.' },
            { h: 'Payment first', p: 'An order is only worked on once payment shows "paid". Payment is verified against the provider (M-Pesa callback) — a customer screenshot is NOT proof of payment. If a payment looks wrong, involve the Finance Officer before dispatching anything.' },
            { h: 'Daily routine', p: 'Each morning filter Orders by status "paid" — those need confirming and packing. Update the status at every physical step so the customer\'s tracking page stays truthful. Record the courier and move to dispatched when it leaves.' },
            { h: 'Cancellations & refunds', p: 'A paid order that is cancelled must be refunded through Finance and its stock returns automatically when the return is processed. Refunds always reference the original payment — never pay a refund outside the system.' },
        ],
    },
    {
        id: 'pos',
        icon: '🧾',
        title: 'POS — Shop Sales',
        intro: 'Daily routine for cashiers at the physical shop.',
        blocks: [
            { h: 'Opening the register', p: 'Start of shift: open POS (/pos), select your register and enter the opening cash float exactly as counted. You cannot sell without an open session, and a register can only have one open session at a time.' },
            { h: 'Making a sale', p: 'Scan the barcode or search by name/SKU, confirm size and colour with the customer, adjust quantity, pick the payment method (cash, M-Pesa, card) and complete. The receipt is printable immediately and stock is deducted at that moment — the website cannot oversell what you just sold.' },
            { h: 'Discounts', p: 'Only staff with discount permission can apply one. Every discount is recorded against your name on the sale.' },
            { h: 'Returns & exchanges', p: 'Use Return in the POS with the original receipt number — the system finds the sale, limits the return to what was actually bought, and restocks resellable items (damaged ones go to the damaged bucket). Refund is calculated from the original price paid. No receipt reference, no return.' },
            { h: 'Closing the register', p: 'End of shift: count the drawer and enter the actual cash. The system compares it with expected cash (opening float + cash sales + cash in − cash out − cash refunds) and records the variance. Repeated variances are reviewed by management, so count carefully.' },
        ],
    },
    {
        id: 'finance',
        icon: '💰',
        title: 'Finance & Reports',
        intro: 'Expenses, profit and the numbers that matter.',
        blocks: [
            { h: 'Revenue ≠ profit', p: 'The dashboard separates sales (money in) from gross profit (sales minus the cost of the goods sold). Reports adds approved expenses to show the net result. If cost prices are wrong on products, the profit numbers are wrong — keep them current.' },
            { h: 'Recording expenses', p: 'Rent, salaries, transport, airtime — record every business expense under Expenses with its category and date. Expenses enter as "pending" and only count in reports once a Finance Officer approves them.' },
            { h: 'Reports', p: 'Reports lets you pick any date range and shows revenue by channel (online vs POS), product margins and payment-method mix, with CSV export for the accountant. Export respects the filters you set.' },
            { h: 'Reconciliation', p: 'Daily: POS register variances should be zero. Weekly: compare M-Pesa statements with verified payments in the system. Anything unmatched is investigated immediately, not month-end.' },
        ],
    },
    {
        id: 'marketing',
        icon: '📣',
        title: 'Marketing & Website',
        intro: 'Coupons, banners and website content.',
        blocks: [
            { h: 'Coupons', p: 'Create promo codes under Coupons: percentage off, fixed KES off, or free delivery, with optional minimum order, usage cap and date window. Customers enter the code in the cart. Deactivate a code anytime — already-placed orders keep their discount.' },
            { h: 'Website content', p: 'Website CMS manages homepage banners and static pages (About, FAQ, policies) without a developer. Pages saved as Draft are invisible to customers until published.' },
            { h: 'Loyalty', p: 'Customers automatically earn 1 point per KES 100 paid online. Points show on their account and in the Customers list — use them for campaigns and rewards.' },
        ],
    },
    {
        id: 'admin',
        icon: '🔐',
        title: 'Staff, Security & Audit',
        intro: 'Accounts, permissions and accountability.',
        blocks: [
            { h: 'Roles', p: 'Each staff member gets exactly one role (Cashier, Store Manager, Finance Officer…). The role decides every screen and button they can use. Give people the least access they need to do their job — you can always add more.' },
            { h: 'Account hygiene', p: 'One account per person, never shared — the audit trail records who did what, and a shared login makes that meaningless. When someone leaves, set their account to Inactive the same day; do not delete it, history must keep pointing at them.' },
            { h: 'Audit trail', p: 'Every important change — prices, stock adjustments, sales, refunds, settings — is logged automatically with user, time and what changed, under Audit Trail. It cannot be edited. Auditors get the read-only Auditor role.' },
            { h: 'Passwords', p: 'Minimum 8 characters. Reset a forgotten password from the Staff screen. Never write passwords down or reuse them from other sites.' },
        ],
    },
    {
        id: 'troubleshooting',
        icon: '🛠️',
        title: 'Troubleshooting & FAQ',
        intro: 'Common situations and what to do.',
        blocks: [
            { h: '"Out of stock" but the item is on the shelf', p: 'Check Inventory: stock may be reserved by an online checkout (frees itself within 15 minutes) or sitting in the damaged bucket. If the count is genuinely wrong, record an adjustment with a clear reason.' },
            { h: 'Customer paid but order shows unpaid', p: 'M-Pesa callbacks can take a minute. If it stays unpaid, the Finance Officer verifies against the M-Pesa statement before anyone manually confirms a payment. Never dispatch on a screenshot.' },
            { h: 'Wrong price sold at POS', p: 'Prices cannot be edited on a completed sale. Process a return of the item, then resell at the correct price — both steps stay on record.' },
            { h: 'Register variance at closing', p: 'Recount first. Check cash-in/cash-out entries were recorded. If a genuine variance remains, close with the real amount — hiding it is worse than reporting it.' },
            { h: 'Need a new category / branch / staff role scope', p: 'Categories and staff are self-service under their menus. New branches and registers are set up under Settings by an administrator.' },
            { h: 'Something looks broken', p: 'Note exactly what you did, the time, and any error text, then contact STACKGee support. Do not retry destructive actions repeatedly.' },
        ],
    },
];
</script>

<template>
    <Head title="System Guide — Admin" />
    <AdminLayout>
        <div class="max-w-4xl">
            <div class="bg-navy-700 text-white rounded-2xl p-8 mb-8">
                <div class="text-gold text-xs tracking-[0.25em] uppercase mb-2">QUEENSONS Platform</div>
                <h1 class="font-display text-3xl">System Guide</h1>
                <p class="text-navy-100 text-sm mt-2 max-w-2xl">
                    How to run the business on this platform — products, stock, sales, money and people.
                    Written for every role; you only need the sections matching your daily work.
                </p>
            </div>

            <!-- Quick nav -->
            <div class="flex flex-wrap gap-2 mb-8">
                <button v-for="s in sections" :key="s.id" @click="open = s.id"
                        :class="['text-xs rounded-full px-3 py-1.5 border transition',
                                 open === s.id ? 'bg-navy-700 text-white border-navy-700' : 'border-gray-200 text-gray-600 hover:border-navy-700']">
                    {{ s.icon }} {{ s.title }}
                </button>
            </div>

            <!-- Sections -->
            <div class="space-y-4">
                <div v-for="s in sections" :key="s.id" :id="s.id"
                     class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden">
                    <button @click="open = open === s.id ? '' : s.id" class="w-full flex items-center justify-between p-5 text-left">
                        <div class="flex items-center gap-3">
                            <span class="text-2xl">{{ s.icon }}</span>
                            <div>
                                <div class="font-semibold text-navy-700">{{ s.title }}</div>
                                <div class="text-xs text-gray-400">{{ s.intro }}</div>
                            </div>
                        </div>
                        <svg :class="['w-5 h-5 text-gray-400 transition-transform', open === s.id ? 'rotate-180' : '']" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                        </svg>
                    </button>
                    <div v-show="open === s.id" class="px-5 pb-6 space-y-5 border-t border-gray-50 pt-4">
                        <div v-for="(b, i) in s.blocks" :key="i">
                            <h3 class="text-sm font-semibold text-navy-700 flex items-center gap-2">
                                <span class="w-1.5 h-1.5 rounded-full bg-gold inline-block"></span>{{ b.h }}
                            </h3>
                            <p class="text-sm text-gray-600 leading-relaxed mt-1 ml-3.5">{{ b.p }}</p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="text-center text-xs text-gray-400 mt-10 pb-6">
                QUEENSONS Retail Platform · Need help? Contact STACKGee Technologies support.
            </div>
        </div>
    </AdminLayout>
</template>
