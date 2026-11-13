<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

const props = defineProps({ product: Object, brands: Array, categories: Array, collections: Array });

const form = useForm({
    name: props.product?.name || '',
    sku_root: props.product?.sku_root || '',
    brand_id: props.product?.brand_id || null,
    description: props.product?.description || '',
    care_instructions: props.product?.care_instructions || '',
    status: props.product?.status || 'draft',
    featured: props.product?.featured || false,
    low_stock_threshold: props.product?.low_stock_threshold ?? 5,
    seo_title: props.product?.seo_title || '',
    seo_description: props.product?.seo_description || '',
    category_ids: props.product?.category_ids || [],
    collection_ids: props.product?.collection_ids || [],
    variants: props.product?.variants || [],
});

function addVariant() {
    form.variants.push({ id: null, sku: '', barcode: '', size: '', color: '', price: 0, cost: 0, active: true, stock: 0 });
}
function submit() {
    props.product ? form.put(`/admin/products/${props.product.id}`) : form.post('/admin/products');
}
</script>

<template>
    <Head :title="(product ? 'Edit' : 'New') + ' Product — Admin'" />
    <AdminLayout>
        <div class="flex items-center justify-between mb-6">
            <h1 class="text-xl font-bold text-navy-700">{{ product ? `Edit: ${product.name}` : 'New Product' }}</h1>
            <Link href="/admin/products" class="text-sm text-gray-500 hover:text-navy-700">← Back</Link>
        </div>

        <form @submit.prevent="submit" class="grid lg:grid-cols-3 gap-6">
            <div class="lg:col-span-2 space-y-6">
                <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-5 space-y-4">
                    <div class="grid md:grid-cols-2 gap-4">
                        <div>
                            <label class="text-xs text-gray-500">Product name *</label>
                            <input v-model="form.name" required class="w-full rounded border-gray-200 text-sm mt-1" />
                            <div v-if="form.errors.name" class="text-red-500 text-xs mt-1">{{ form.errors.name }}</div>
                        </div>
                        <div>
                            <label class="text-xs text-gray-500">SKU root *</label>
                            <input v-model="form.sku_root" required class="w-full rounded border-gray-200 text-sm mt-1 font-mono" />
                            <div v-if="form.errors.sku_root" class="text-red-500 text-xs mt-1">{{ form.errors.sku_root }}</div>
                        </div>
                    </div>
                    <div>
                        <label class="text-xs text-gray-500">Description</label>
                        <textarea v-model="form.description" rows="4" class="w-full rounded border-gray-200 text-sm mt-1"></textarea>
                    </div>
                    <div>
                        <label class="text-xs text-gray-500">Care instructions</label>
                        <textarea v-model="form.care_instructions" rows="2" class="w-full rounded border-gray-200 text-sm mt-1"></textarea>
                    </div>
                </div>

                <!-- Variants -->
                <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-5">
                    <div class="flex justify-between items-center mb-3">
                        <h2 class="font-semibold text-sm">Variants (size / colour / price)</h2>
                        <button type="button" @click="addVariant" class="text-xs text-emerald-600 font-semibold">+ Add variant</button>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full text-xs">
                            <thead><tr class="text-left text-gray-400 uppercase"><th class="p-1">SKU</th><th>Barcode</th><th>Size</th><th>Colour</th><th>Price KES</th><th>Cost KES</th><th>Stock</th><th>Active</th></tr></thead>
                            <tbody>
                                <tr v-for="(v, i) in form.variants" :key="i" class="border-t border-gray-50">
                                    <td class="p-1"><input v-model="v.sku" class="rounded border-gray-200 text-xs w-32 font-mono" /></td>
                                    <td class="p-1"><input v-model="v.barcode" class="rounded border-gray-200 text-xs w-28" /></td>
                                    <td class="p-1"><input v-model="v.size" class="rounded border-gray-200 text-xs w-16" /></td>
                                    <td class="p-1"><input v-model="v.color" class="rounded border-gray-200 text-xs w-24" /></td>
                                    <td class="p-1"><input v-model.number="v.price" type="number" step="0.01" class="rounded border-gray-200 text-xs w-24" /></td>
                                    <td class="p-1"><input v-model.number="v.cost" type="number" step="0.01" class="rounded border-gray-200 text-xs w-24" /></td>
                                    <td class="p-1 text-center text-gray-500">{{ v.stock }}</td>
                                    <td class="p-1 text-center"><input v-model="v.active" type="checkbox" class="rounded text-emerald-600" /></td>
                                </tr>
                            </tbody>
                        </table>
                        <p v-if="!form.variants.length" class="text-gray-400 text-sm py-3">No variants. Add at least one sellable variant.</p>
                    </div>
                    <p class="text-[11px] text-gray-400 mt-2">Stock is managed in Inventory (receipts, adjustments) — not edited here.</p>
                </div>

                <!-- SEO -->
                <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-5 space-y-3">
                    <h2 class="font-semibold text-sm">SEO</h2>
                    <input v-model="form.seo_title" placeholder="SEO title" class="w-full rounded border-gray-200 text-sm" />
                    <textarea v-model="form.seo_description" placeholder="Meta description" rows="2" class="w-full rounded border-gray-200 text-sm"></textarea>
                </div>
            </div>

            <!-- Sidebar -->
            <div class="space-y-6">
                <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-5 space-y-4">
                    <div>
                        <label class="text-xs text-gray-500">Status</label>
                        <select v-model="form.status" class="w-full rounded border-gray-200 text-sm mt-1">
                            <option value="draft">Draft</option>
                            <option value="published">Published</option>
                            <option value="archived">Archived</option>
                        </select>
                    </div>
                    <label class="flex items-center gap-2 text-sm">
                        <input v-model="form.featured" type="checkbox" class="rounded text-emerald-600" /> Featured on homepage
                    </label>
                    <div>
                        <label class="text-xs text-gray-500">Brand</label>
                        <select v-model="form.brand_id" class="w-full rounded border-gray-200 text-sm mt-1">
                            <option :value="null">—</option>
                            <option v-for="b in brands" :key="b.id" :value="b.id">{{ b.name }}</option>
                        </select>
                    </div>
                    <div>
                        <label class="text-xs text-gray-500">Low stock threshold</label>
                        <input v-model.number="form.low_stock_threshold" type="number" class="w-full rounded border-gray-200 text-sm mt-1" />
                    </div>
                </div>

                <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-5">
                    <h2 class="font-semibold text-sm mb-2">Categories</h2>
                    <label v-for="c in categories" :key="c.id" class="flex items-center gap-2 text-sm py-0.5">
                        <input type="checkbox" :value="c.id" v-model="form.category_ids" class="rounded text-emerald-600" /> {{ c.name }}
                    </label>
                </div>

                <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-5">
                    <h2 class="font-semibold text-sm mb-2">Collections</h2>
                    <label v-for="c in collections" :key="c.id" class="flex items-center gap-2 text-sm py-0.5">
                        <input type="checkbox" :value="c.id" v-model="form.collection_ids" class="rounded text-emerald-600" /> {{ c.name }}
                    </label>
                </div>

                <button :disabled="form.processing" class="w-full bg-navy-700 hover:bg-navy-600 text-white rounded-lg py-3 text-sm font-semibold">
                    {{ product ? 'Save Changes' : 'Create Product' }}
                </button>
            </div>
        </form>
    </AdminLayout>
</template>
