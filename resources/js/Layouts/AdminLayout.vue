<script setup>
import { Link, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const page = usePage();
const user = computed(() => page.props.auth?.user);
const perms = computed(() => new Set(user.value?.permissions || []));
const flash = computed(() => page.props.flash?.success);
const errors = computed(() => Object.values(page.props.errors || {}));
// Start collapsed on phones and small tablets so content gets the width.
const collapsed = ref(typeof window !== 'undefined' && window.innerWidth < 1024);

const can = (p) => perms.value.has(p);

const sections = computed(() => [
    { label: 'Dashboard', href: '/admin', icon: 'M3 12l9-9 9 9M5 10v10h5v-6h4v6h5V10', show: can('dashboard.view') },
    { label: 'Products', href: '/admin/products', icon: 'M20 7l-8-4-8 4v10l8 4 8-4V7zM12 3v18', show: can('products.view') },
    { label: 'Categories', href: '/admin/categories', icon: 'M4 6h16M4 12h16M4 18h7', show: can('categories.view') },
    { label: 'Inventory', href: '/admin/inventory', icon: 'M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8', show: can('inventory.view') },
    { label: 'Stock Ledger', href: '/admin/inventory/ledger', icon: 'M9 17v-6h6v6M4 21h16a1 1 0 001-1V8l-9-5-9 5v12a1 1 0 001 1z', show: can('inventory.view') },
    { label: 'Orders', href: '/admin/orders', icon: 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2', show: can('orders.view') },
    { label: 'POS Terminal', href: '/pos', icon: 'M3 10h18M7 15h2m4 0h2M5 20h14a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z', show: can('pos.view') },
    { label: 'Deliveries', href: '/admin/deliveries', icon: 'M9 17a2 2 0 11-4 0 2 2 0 014 0zM19 17a2 2 0 11-4 0 2 2 0 014 0zM13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10a1 1 0 001 1h1m8-1a1 1 0 01-1 1H9m4-1V8a1 1 0 011-1h2.6a1 1 0 01.9.6l1.9 4.3a1 1 0 01.1.4V16a1 1 0 01-1 1h-1.5', show: can('orders.view') },
    { label: 'Transfers', href: '/admin/transfers', icon: 'M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4', show: can('inventory.view') },
    { label: 'Support', href: '/admin/tickets', icon: 'M8 10h8m-8 4h5m8-2a9 9 0 11-18 0 9 9 0 0118 0zM12 21l-3-3', show: can('customers.view') },
    { label: 'Customers', href: '/admin/customers', icon: 'M17 20h5v-2a3 3 0 00-5.4-1.8M9 20H4v-2a3 3 0 015.4-1.8M15 7a3 3 0 11-6 0 3 3 0 016 0z', show: can('customers.view') },
    { label: 'Suppliers', href: '/admin/suppliers', icon: 'M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5', show: can('suppliers.view') },
    { label: 'Purchases', href: '/admin/purchases', icon: 'M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z', show: can('purchases.view') },
    { label: 'Expenses', href: '/admin/expenses', icon: 'M12 8c-1.7 0-3 .9-3 2s1.3 2 3 2 3 .9 3 2-1.3 2-3 2m0-8c1.1 0 2.1.4 2.6 1M12 8V7m0 10v-1m9-4a9 9 0 11-18 0 9 9 0 0118 0z', show: can('finance.view') },
    { label: 'Reports', href: '/admin/reports', icon: 'M9 19v-6M13 19V5M17 19v-9M5 19h16', show: can('reports.view') },
    { label: 'Coupons', href: '/admin/coupons', icon: 'M15 5v2m0 4v2m0 4v2M5 5h14a2 2 0 012 2v3a2 2 0 000 4v3a2 2 0 01-2 2H5a2 2 0 01-2-2v-3a2 2 0 000-4V7a2 2 0 012-2z', show: can('marketing.view') },
    { label: 'Search Insights', href: '/admin/search', icon: 'M21 21l-4.3-4.3M10.5 18a7.5 7.5 0 100-15 7.5 7.5 0 000 15z', show: can('marketing.view') },
    { label: 'Reviews', href: '/admin/reviews', icon: 'M11.05 3.5a1 1 0 011.9 0l1.52 4.68a1 1 0 00.95.69h4.92a1 1 0 01.59 1.81l-3.98 2.89a1 1 0 00-.36 1.12l1.52 4.68a1 1 0 01-1.54 1.12l-3.98-2.9a1 1 0 00-1.18 0l-3.98 2.9a1 1 0 01-1.54-1.12l1.52-4.68a1 1 0 00-.36-1.12L2.07 10.7a1 1 0 01.59-1.8h4.92a1 1 0 00.95-.7l1.52-4.68z', show: can('products.view') },
    { label: 'Website CMS', href: '/admin/cms', icon: 'M4 5a1 1 0 011-1h14a1 1 0 011 1v2a1 1 0 01-1 1H5a1 1 0 01-1-1V5zM4 13a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H5a1 1 0 01-1-1v-6zM16 13h4v8h-4z', show: can('cms.view') },
    { label: 'Staff', href: '/admin/users', icon: 'M12 4.35a4 4 0 110 7.3M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.2', show: can('users.view') },
    { label: 'Settings', href: '/admin/settings', icon: 'M10.3 4.3a1.7 1.7 0 013.4 0 1.7 1.7 0 002.6 1.1 1.7 1.7 0 012.4 2.4 1.7 1.7 0 001 2.5 1.7 1.7 0 010 3.4 1.7 1.7 0 00-1 2.6 1.7 1.7 0 01-2.4 2.4 1.7 1.7 0 00-2.6 1 1.7 1.7 0 01-3.4 0 1.7 1.7 0 00-2.5-1 1.7 1.7 0 01-2.4-2.4 1.7 1.7 0 00-1.1-2.6 1.7 1.7 0 010-3.4 1.7 1.7 0 001.1-2.5 1.7 1.7 0 012.4-2.4 1.7 1.7 0 002.5-1.1zM15 12a3 3 0 11-6 0 3 3 0 016 0z', show: can('settings.view') },
    { label: 'Audit Trail', href: '/admin/audit', icon: 'M9 12h6m-6 4h6M9 8h6M5 21h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v14a2 2 0 002 2z', show: can('audit.view') },
    { label: 'System Guide', href: '/admin/guide', icon: 'M12 6.25c-2.4-1.6-5.6-1.6-8 0v12c2.4-1.6 5.6-1.6 8 0 2.4-1.6 5.6-1.6 8 0v-12c-2.4-1.6-5.6-1.6-8 0zm0 0v12', show: true },
].filter(s => s.show));

const current = computed(() => page.url);
</script>

<template>
    <div class="min-h-screen bg-gray-50 flex font-sans">
        <!-- Sidebar -->
        <aside :class="[collapsed ? 'w-16' : 'w-60', 'bg-navy-800 text-white flex flex-col transition-all shrink-0']">
            <div class="h-16 flex items-center justify-center border-b border-navy-600">
                <img src="/logo.svg" alt="" class="w-8 h-8" />
                <span v-if="!collapsed" class="font-display tracking-tight font-extrabold text-lg text-gold ml-2">queensons<span class="text-gold">.</span></span>
            </div>
            <nav class="flex-1 overflow-y-auto py-4 space-y-0.5">
                <Link v-for="s in sections" :key="s.href" :href="s.href"
                      :class="['flex items-center gap-3 px-4 py-2.5 text-sm transition',
                               current === s.href || (s.href !== '/admin' && current.startsWith(s.href))
                                   ? 'bg-navy-600 text-gold border-l-2 border-gold'
                                   : 'text-navy-100 hover:bg-navy-700 hover:text-white']">
                    <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.6" :d="s.icon"/></svg>
                    <span v-if="!collapsed" class="truncate">{{ s.label }}</span>
                </Link>
            </nav>
            <div class="border-t border-navy-600 py-3 space-y-0.5">
                <a href="/" target="_blank" rel="noopener" class="flex items-center gap-3 px-4 py-2 text-sm text-navy-100 hover:bg-navy-700 hover:text-gold transition">
                    <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-width="1.6" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0zM3.6 9h16.8M3.6 15h16.8M12 3a15 15 0 010 18"/></svg>
                    <span v-if="!collapsed" class="truncate">View Website ↗</span>
                </a>
                <a href="/account" target="_blank" rel="noopener" class="flex items-center gap-3 px-4 py-2 text-sm text-navy-100 hover:bg-navy-700 hover:text-gold transition">
                    <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-width="1.6" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                    <span v-if="!collapsed" class="truncate">Customer Portal ↗</span>
                </a>
            </div>
        </aside>

        <!-- Main -->
        <div class="flex-1 flex flex-col min-w-0">
            <header class="h-16 bg-white border-b border-gray-200 flex items-center justify-between px-6 sticky top-0 z-30">
                <button @click="collapsed = !collapsed" class="text-gray-500 hover:text-navy-700" aria-label="Toggle sidebar">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
                </button>
                <div class="flex items-center gap-4">
                    <Link href="/profile" class="text-sm text-gray-600 hover:underline" title="My profile">{{ user?.name }} <span class="hidden sm:inline text-gray-400">· {{ user?.role }}</span></Link>
                    <Link href="/logout" method="post" as="button" class="text-sm text-gray-500 hover:text-red-600 whitespace-nowrap">Sign out</Link>
                </div>
            </header>

            <div v-if="flash" class="bg-emerald-600 text-white text-sm px-6 py-2">{{ flash }}</div>
            <div v-if="errors.length" class="bg-red-50 border-b border-red-200 text-red-700 text-sm px-6 py-2">
                <div v-for="(e, i) in errors" :key="i">{{ e }}</div>
            </div>

            <main class="flex-1 p-6 overflow-x-auto">
                <slot />
            </main>
        </div>
    </div>
</template>
