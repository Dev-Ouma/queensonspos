<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({ pages: Array, banners: Array, storefrontContent: Object });

/* Storefront content editor (hero, announcements, marquee, flash sale, testimonials, stats, socials) */
const sf = useForm(JSON.parse(JSON.stringify(props.storefrontContent)));
function saveStorefront() {
    sf.post('/admin/cms/storefront', { preserveScroll: true });
}
function addRow(list, row) { list.push(JSON.parse(JSON.stringify(row))); }
function removeRow(list, i) { list.splice(i, 1); }

const editingPage = ref(null);
const pageForm = useForm({ id: null, title: '', body: '', published: true });
function openPage(p = null) {
    editingPage.value = true;
    Object.assign(pageForm, p ? { id: p.id, title: p.title, body: p.body, published: p.published } : { id: null, title: '', body: '', published: true });
}

const editingBanner = ref(null);
const bannerForm = useForm({ id: null, title: '', subtitle: '', link: '', position: 0, active: true });
function openBanner(b = null) {
    editingBanner.value = true;
    Object.assign(bannerForm, b ? { id: b.id, title: b.title, subtitle: b.subtitle, link: b.link, position: b.position, active: b.active } : { id: null, title: '', subtitle: '', link: '', position: 0, active: true });
}
</script>

<template>
    <Head title="Website CMS — Admin" />
    <AdminLayout>
        <h1 class="text-xl font-bold text-navy-700 mb-6">Website Content</h1>

        <div class="grid lg:grid-cols-2 gap-6">
            <!-- Pages -->
            <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-5">
                <div class="flex justify-between items-center mb-3">
                    <h2 class="font-semibold text-sm">Static Pages</h2>
                    <button @click="openPage()" class="text-xs text-emerald-600 font-semibold">+ New page</button>
                </div>
                <div v-for="p in pages" :key="p.id" class="flex justify-between items-center py-2 border-b border-gray-50 text-sm">
                    <div>
                        {{ p.title }}
                        <span class="text-xs text-gray-400 ml-2">/page/{{ p.slug }}</span>
                    </div>
                    <div class="flex items-center gap-3">
                        <span :class="['text-xs rounded-full px-2 py-0.5', p.published ? 'bg-emerald-50 text-emerald-700' : 'bg-gray-100 text-gray-500']">{{ p.published ? 'Live' : 'Draft' }}</span>
                        <button @click="openPage(p)" class="text-xs text-navy-700 hover:underline">Edit</button>
                    </div>
                </div>
            </div>

            <!-- Banners -->
            <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-5">
                <div class="flex justify-between items-center mb-3">
                    <h2 class="font-semibold text-sm">Homepage Banners</h2>
                    <button @click="openBanner()" class="text-xs text-emerald-600 font-semibold">+ New banner</button>
                </div>
                <div v-for="b in banners" :key="b.id" class="flex justify-between items-center py-2 border-b border-gray-50 text-sm">
                    <div>
                        {{ b.title }}
                        <div class="text-xs text-gray-400">{{ b.subtitle }}</div>
                    </div>
                    <div class="flex items-center gap-3">
                        <span :class="['text-xs rounded-full px-2 py-0.5', b.active ? 'bg-emerald-50 text-emerald-700' : 'bg-gray-100 text-gray-500']">{{ b.active ? 'Active' : 'Hidden' }}</span>
                        <button @click="openBanner(b)" class="text-xs text-navy-700 hover:underline">Edit</button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Storefront content -->
        <form @submit.prevent="saveStorefront" class="mt-6 bg-white rounded-xl border border-gray-100 shadow-sm p-5 space-y-8">
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="font-semibold text-sm">Storefront Content</h2>
                    <p class="text-xs text-gray-400 mt-0.5">Hero slides, announcements, flash sale, testimonials, stats and social links shown on the website.</p>
                </div>
                <button :disabled="sf.processing" class="bg-navy-700 hover:bg-navy-600 text-white rounded-lg px-5 py-2 text-sm font-semibold disabled:opacity-50">Publish changes</button>
            </div>
            <div v-if="Object.keys(sf.errors).length" class="bg-red-50 text-red-600 text-xs rounded-lg p-3">
                <div v-for="(e, k) in sf.errors" :key="k">{{ e }}</div>
            </div>

            <!-- Hero slides -->
            <section>
                <div class="flex justify-between items-center mb-2">
                    <h3 class="text-xs font-semibold uppercase tracking-wider text-gray-500">Hero Slides</h3>
                    <button type="button" v-if="sf.hero_slides.length < 5" @click="addRow(sf.hero_slides, { img: '', eyebrow: '', line1: '', line2: '' })" class="text-xs text-emerald-600 font-semibold">+ Add slide</button>
                </div>
                <div v-for="(s, i) in sf.hero_slides" :key="i" class="grid md:grid-cols-[1fr_180px_150px_150px_auto] gap-2 mb-2 text-sm">
                    <input v-model="s.img" required placeholder="Image URL" class="rounded border-gray-200 text-xs" />
                    <input v-model="s.eyebrow" required placeholder="Eyebrow" class="rounded border-gray-200 text-xs" />
                    <input v-model="s.line1" required placeholder="Headline line 1" class="rounded border-gray-200 text-xs" />
                    <input v-model="s.line2" required placeholder="Line 2 (italic gold)" class="rounded border-gray-200 text-xs" />
                    <button type="button" @click="removeRow(sf.hero_slides, i)" :disabled="sf.hero_slides.length < 2" class="text-xs text-gray-400 hover:text-red-500 disabled:opacity-30">✕</button>
                </div>
            </section>

            <!-- Announcements + marquee -->
            <div class="grid md:grid-cols-2 gap-6">
                <section>
                    <div class="flex justify-between items-center mb-2">
                        <h3 class="text-xs font-semibold uppercase tracking-wider text-gray-500">Top-bar Announcements</h3>
                        <button type="button" v-if="sf.announcements.length < 8" @click="sf.announcements.push('')" class="text-xs text-emerald-600 font-semibold">+ Add</button>
                    </div>
                    <div v-for="(a, i) in sf.announcements" :key="i" class="flex gap-2 mb-2">
                        <input v-model="sf.announcements[i]" required class="flex-1 rounded border-gray-200 text-xs" />
                        <button type="button" @click="removeRow(sf.announcements, i)" :disabled="sf.announcements.length < 2" class="text-xs text-gray-400 hover:text-red-500 disabled:opacity-30">✕</button>
                    </div>
                </section>
                <section>
                    <div class="flex justify-between items-center mb-2">
                        <h3 class="text-xs font-semibold uppercase tracking-wider text-gray-500">Marquee Ribbon</h3>
                        <button type="button" v-if="sf.marquee.length < 10" @click="sf.marquee.push('')" class="text-xs text-emerald-600 font-semibold">+ Add</button>
                    </div>
                    <div v-for="(m, i) in sf.marquee" :key="i" class="flex gap-2 mb-2">
                        <input v-model="sf.marquee[i]" required class="flex-1 rounded border-gray-200 text-xs" />
                        <button type="button" @click="removeRow(sf.marquee, i)" :disabled="sf.marquee.length < 2" class="text-xs text-gray-400 hover:text-red-500 disabled:opacity-30">✕</button>
                    </div>
                </section>
            </div>

            <!-- Flash sale -->
            <section>
                <h3 class="text-xs font-semibold uppercase tracking-wider text-gray-500 mb-2">Flash Sale Band</h3>
                <div class="grid md:grid-cols-3 gap-2 text-sm">
                    <label class="flex items-center gap-2 text-xs text-gray-500 md:col-span-3">
                        <input type="checkbox" v-model="sf.flash_sale.enabled" class="rounded text-emerald-600" /> Show flash-sale countdown on homepage
                    </label>
                    <input v-model="sf.flash_sale.eyebrow" required placeholder="Eyebrow (Today Only)" class="rounded border-gray-200 text-xs" />
                    <input v-model="sf.flash_sale.title" required placeholder="Title (Midnight Edit)" class="rounded border-gray-200 text-xs" />
                    <input v-model="sf.flash_sale.highlight" required placeholder="Highlight (up to 20% off.)" class="rounded border-gray-200 text-xs" />
                    <input v-model="sf.flash_sale.subtitle" required placeholder="Subtitle" class="rounded border-gray-200 text-xs md:col-span-2" />
                    <div class="flex gap-2">
                        <input v-model="sf.flash_sale.cta" required placeholder="Button text" class="flex-1 rounded border-gray-200 text-xs" />
                        <input v-model="sf.flash_sale.link" required placeholder="/shop" class="flex-1 rounded border-gray-200 text-xs" />
                    </div>
                </div>
            </section>

            <!-- Testimonials -->
            <section>
                <div class="flex justify-between items-center mb-2">
                    <h3 class="text-xs font-semibold uppercase tracking-wider text-gray-500">Testimonials</h3>
                    <button type="button" v-if="sf.testimonials.length < 8" @click="addRow(sf.testimonials, { quote: '', name: '', city: '' })" class="text-xs text-emerald-600 font-semibold">+ Add</button>
                </div>
                <div v-for="(t, i) in sf.testimonials" :key="i" class="grid md:grid-cols-[1fr_160px_140px_auto] gap-2 mb-2 text-sm">
                    <input v-model="t.quote" required placeholder="Quote" class="rounded border-gray-200 text-xs" />
                    <input v-model="t.name" required placeholder="Name" class="rounded border-gray-200 text-xs" />
                    <input v-model="t.city" required placeholder="City" class="rounded border-gray-200 text-xs" />
                    <button type="button" @click="removeRow(sf.testimonials, i)" :disabled="sf.testimonials.length < 2" class="text-xs text-gray-400 hover:text-red-500 disabled:opacity-30">✕</button>
                </div>
            </section>

            <!-- Stats + socials -->
            <div class="grid md:grid-cols-2 gap-6">
                <section>
                    <h3 class="text-xs font-semibold uppercase tracking-wider text-gray-500 mb-2">Count-up Stats (4)</h3>
                    <div v-for="(s, i) in sf.stats" :key="i" class="grid grid-cols-[1fr_100px_60px] gap-2 mb-2">
                        <input v-model="s.label" required placeholder="Label" class="rounded border-gray-200 text-xs" />
                        <input v-model.number="s.target" required type="number" min="0" placeholder="Number" class="rounded border-gray-200 text-xs" />
                        <input v-model="s.suffix" placeholder="+" maxlength="3" class="rounded border-gray-200 text-xs" />
                    </div>
                </section>
                <section>
                    <h3 class="text-xs font-semibold uppercase tracking-wider text-gray-500 mb-2">Social Links & Live Sales</h3>
                    <div class="space-y-2">
                        <input v-model="sf.socials.instagram" placeholder="Instagram URL" class="w-full rounded border-gray-200 text-xs" />
                        <input v-model="sf.socials.tiktok" placeholder="TikTok URL" class="w-full rounded border-gray-200 text-xs" />
                        <input v-model="sf.socials.whatsapp" placeholder="WhatsApp URL (https://wa.me/…)" class="w-full rounded border-gray-200 text-xs" />
                        <label class="flex items-center gap-2 text-xs text-gray-500 pt-1">
                            <input type="checkbox" v-model="sf.social_proof_enabled" class="rounded text-emerald-600" />
                            Show live "recently bought" notifications on the website
                        </label>
                        <label class="block text-xs text-gray-500 pt-1">Free delivery threshold (cents)
                            <input v-model.number="sf.free_delivery_threshold_cents" type="number" min="0" class="w-full rounded border-gray-200 text-xs mt-1" />
                        </label>
                    </div>
                </section>
            </div>
        </form>

        <!-- Page modal -->
        <div v-if="editingPage" class="fixed inset-0 bg-black/40 flex items-center justify-center z-50 p-4" @click.self="editingPage = null">
            <div class="bg-white rounded-xl p-6 w-full max-w-2xl">
                <h2 class="font-semibold mb-4">{{ pageForm.id ? 'Edit' : 'New' }} Page</h2>
                <form @submit.prevent="pageForm.post('/admin/cms/pages', { preserveScroll: true, onSuccess: () => (editingPage = null) })" class="space-y-3 text-sm">
                    <input v-model="pageForm.title" required placeholder="Title" class="w-full rounded border-gray-200" />
                    <textarea v-model="pageForm.body" rows="10" placeholder="Content" class="w-full rounded border-gray-200"></textarea>
                    <label class="flex items-center gap-2"><input type="checkbox" v-model="pageForm.published" class="rounded text-emerald-600" /> Published</label>
                    <div class="flex justify-end gap-2">
                        <button type="button" @click="editingPage = null" class="text-gray-500 px-4 py-2">Cancel</button>
                        <button :disabled="pageForm.processing" class="bg-navy-700 text-white rounded-lg px-4 py-2 font-semibold">Save</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Banner modal -->
        <div v-if="editingBanner" class="fixed inset-0 bg-black/40 flex items-center justify-center z-50 p-4" @click.self="editingBanner = null">
            <div class="bg-white rounded-xl p-6 w-full max-w-lg">
                <h2 class="font-semibold mb-4">{{ bannerForm.id ? 'Edit' : 'New' }} Banner</h2>
                <form @submit.prevent="bannerForm.post('/admin/cms/banners', { preserveScroll: true, onSuccess: () => (editingBanner = null) })" class="space-y-3 text-sm">
                    <input v-model="bannerForm.title" required placeholder="Title" class="w-full rounded border-gray-200" />
                    <input v-model="bannerForm.subtitle" placeholder="Subtitle" class="w-full rounded border-gray-200" />
                    <input v-model="bannerForm.link" placeholder="Link e.g. /shop?category=dresses" class="w-full rounded border-gray-200" />
                    <div class="flex gap-4 items-center">
                        <input v-model.number="bannerForm.position" type="number" class="rounded border-gray-200 w-24" title="Position" />
                        <label class="flex items-center gap-2"><input type="checkbox" v-model="bannerForm.active" class="rounded text-emerald-600" /> Active</label>
                    </div>
                    <div class="flex justify-end gap-2">
                        <button type="button" @click="editingBanner = null" class="text-gray-500 px-4 py-2">Cancel</button>
                        <button :disabled="bannerForm.processing" class="bg-navy-700 text-white rounded-lg px-4 py-2 font-semibold">Save</button>
                    </div>
                </form>
            </div>
        </div>
    </AdminLayout>
</template>
