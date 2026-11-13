<?php

namespace Database\Seeders;

use App\Models\Banner;
use App\Models\Branch;
use App\Models\Brand;
use App\Models\Category;
use App\Models\CmsPage;
use App\Models\Collection;
use App\Models\Coupon;
use App\Models\Customer;
use App\Models\ExpenseCategory;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Register;
use App\Models\Setting;
use App\Models\Supplier;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\InventoryService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->seedRbac();
        $this->seedBusiness();
        $this->seedCatalog();
        $this->seedCms();
        $this->call([DemoContentSeeder::class, ModuleDataSeeder::class, UatTopUpSeeder::class]);
    }

    private function seedRbac(): void
    {
        $modules = [
            'dashboard', 'products', 'categories', 'inventory', 'orders', 'pos', 'customers',
            'suppliers', 'purchases', 'finance', 'reports', 'marketing', 'cms', 'users', 'settings', 'audit',
        ];
        $actions = ['view', 'create', 'edit', 'delete', 'approve', 'export'];
        foreach ($modules as $m) {
            foreach ($actions as $a) {
                Permission::findOrCreate("$m.$a");
            }
        }
        foreach (['orders.refund', 'pos.void', 'pos.discount', 'inventory.adjust', 'products.publish'] as $p) {
            Permission::findOrCreate($p);
        }

        $all = Permission::pluck('name')->all();
        $roleMatrix = [
            'Super Administrator' => $all,
            'Business Owner' => $all,
            'General Manager' => array_filter($all, fn ($p) => ! str_starts_with($p, 'settings.') || $p === 'settings.view'),
            'Store Manager' => ['dashboard.view', 'products.view', 'inventory.view', 'inventory.edit', 'inventory.adjust', 'orders.view', 'orders.edit', 'pos.view', 'pos.create', 'pos.void', 'pos.discount', 'customers.view', 'reports.view', 'users.view'],
            'Ecommerce Manager' => ['dashboard.view', 'products.view', 'products.create', 'products.edit', 'products.publish', 'categories.view', 'categories.create', 'categories.edit', 'orders.view', 'orders.edit', 'marketing.view', 'marketing.create', 'marketing.edit', 'cms.view', 'cms.create', 'cms.edit', 'reports.view', 'customers.view'],
            'Inventory Manager' => ['dashboard.view', 'inventory.view', 'inventory.create', 'inventory.edit', 'inventory.adjust', 'products.view', 'purchases.view', 'purchases.create', 'reports.view'],
            'Procurement Officer' => ['dashboard.view', 'suppliers.view', 'suppliers.create', 'suppliers.edit', 'purchases.view', 'purchases.create', 'purchases.edit', 'inventory.view'],
            'Finance Officer' => ['dashboard.view', 'finance.view', 'finance.create', 'finance.edit', 'finance.approve', 'orders.view', 'orders.refund', 'reports.view', 'reports.export'],
            'Cashier' => ['pos.view', 'pos.create', 'products.view', 'customers.view'],
            'Sales Assistant' => ['products.view', 'customers.view', 'inventory.view'],
            'Customer Support' => ['customers.view', 'customers.edit', 'orders.view'],
            'Marketing Manager' => ['dashboard.view', 'marketing.view', 'marketing.create', 'marketing.edit', 'cms.view', 'cms.create', 'cms.edit', 'customers.view', 'reports.view'],
            'Delivery Officer' => ['orders.view', 'orders.edit'],
            'Auditor' => array_values(array_filter($all, fn ($p) => str_ends_with($p, '.view'))) + ['audit.view'],
        ];
        foreach ($roleMatrix as $role => $perms) {
            Role::findOrCreate($role)->syncPermissions($perms);
        }
    }

    private function seedBusiness(): void
    {
        $branch = Branch::firstOrCreate(['code' => 'HQ'], ['name' => 'QUEENSONS Flagship', 'address' => 'Nairobi CBD', 'phone' => '+254700000000']);
        $wh = Warehouse::firstOrCreate(['code' => 'MAIN'], ['branch_id' => $branch->id, 'name' => 'Main Warehouse']);
        Register::firstOrCreate(['branch_id' => $branch->id, 'name' => 'Register 1']);

        foreach ([
            ['Amina Super', 'admin@queensons.com', 'Super Administrator'],
            ['Grace Owner', 'owner@queensons.com', 'Business Owner'],
            ['Mercy Manager', 'manager@queensons.com', 'General Manager'],
            ['Cathy Cashier', 'cashier@queensons.com', 'Cashier'],
            ['Faith Finance', 'finance@queensons.com', 'Finance Officer'],
            ['Irene Inventory', 'inventory@queensons.com', 'Inventory Manager'],
        ] as [$name, $email, $role]) {
            $u = User::firstOrCreate(['email' => $email], [
                'name' => $name, 'password' => Hash::make('password'), 'branch_id' => $branch->id,
                'email_verified_at' => now(),
            ]);
            $u->syncRoles([$role]);
        }

        Customer::firstOrCreate(['email' => 'jane@example.com'], [
            'name' => 'Jane Wanjiku', 'phone' => '+254711111111', 'password' => Hash::make('password'),
            'email_verified_at' => now(),
        ]);

        foreach ([
            ['business_name', 'QUEENSONS'],
            ['tagline', 'Style Beyond Seasons.'],
            ['currency', 'KES'],
            ['timezone', 'Africa/Nairobi'],
            ['delivery_fee_cents', '30000'],
            ['vat_percent', '16'],
            ['loyalty_earn_per_cents', '10000'],
        ] as [$k, $v]) {
            Setting::firstOrCreate(['key' => $k], ['value' => $v]);
        }

        foreach (['Rent', 'Utilities', 'Salaries', 'Delivery', 'Marketing', 'Operations'] as $c) {
            ExpenseCategory::firstOrCreate(['name' => $c]);
        }

        Supplier::firstOrCreate(['name' => 'Milano Fashion Imports'], [
            'contact_person' => 'Luca M.', 'phone' => '+254722000001', 'email' => 'sales@milanoimports.co.ke', 'payment_terms' => 'Net 30',
        ]);
        Supplier::firstOrCreate(['name' => 'Eastleigh Textiles Ltd'], [
            'contact_person' => 'Hassan A.', 'phone' => '+254722000002', 'email' => 'orders@eastleightextiles.co.ke', 'payment_terms' => 'Cash on delivery',
        ]);

        Coupon::firstOrCreate(['code' => 'KARIBU10'], ['type' => 'percent', 'value' => 10, 'min_order_cents' => 200000, 'active' => true]);
    }

    private function seedCatalog(): void
    {
        $brand = Brand::firstOrCreate(['slug' => 'queensons'], ['name' => 'QUEENSONS']);

        $cats = [];
        foreach ([
            'Dresses', 'Tops & Blouses', 'Trousers', 'Jeans', 'Skirts', 'Jackets & Coats',
            'Office Wear', 'Casual Wear', 'Occasion Wear', 'Shoes', 'Handbags', 'Accessories',
        ] as $i => $name) {
            $cats[$name] = Category::firstOrCreate(['slug' => Str::slug($name)], ['name' => $name, 'position' => $i]);
        }

        $newArrivals = Collection::firstOrCreate(['slug' => 'new-arrivals'], ['name' => 'New Arrivals', 'published' => true]);
        $bestSellers = Collection::firstOrCreate(['slug' => 'best-sellers'], ['name' => 'Best Sellers', 'published' => true]);
        Collection::firstOrCreate(['slug' => 'office-collection'], ['name' => 'Office Collection', 'published' => true]);

        $inv = app(InventoryService::class);
        $whId = Warehouse::where('code', 'MAIN')->value('id');

        $catalog = [
            ['Classic Wrap Dress', 'Dresses', 4500, 2200, ['S', 'M', 'L', 'XL'], ['Royal Navy', 'Emerald', 'Black']],
            ['Satin Slip Dress', 'Dresses', 5200, 2600, ['S', 'M', 'L'], ['Champagne', 'Black']],
            ['Chiffon Blouse', 'Tops & Blouses', 2800, 1300, ['S', 'M', 'L', 'XL'], ['White', 'Blush', 'Navy']],
            ['Ribbed Knit Top', 'Tops & Blouses', 2200, 1000, ['S', 'M', 'L'], ['Black', 'Cream']],
            ['High-Waist Tailored Trousers', 'Trousers', 3800, 1800, ['S', 'M', 'L', 'XL'], ['Black', 'Beige']],
            ['Slim Fit Jeans', 'Jeans', 3500, 1700, ['26', '28', '30', '32'], ['Indigo', 'Black']],
            ['Pleated Midi Skirt', 'Skirts', 3200, 1500, ['S', 'M', 'L'], ['Emerald', 'Navy', 'Gold']],
            ['Classic Blazer', 'Jackets & Coats', 6500, 3200, ['S', 'M', 'L', 'XL'], ['Black', 'Navy', 'Beige']],
            ['Power Suit Set', 'Office Wear', 9800, 5000, ['S', 'M', 'L'], ['Navy', 'Grey']],
            ['Weekend Maxi Dress', 'Casual Wear', 3900, 1900, ['S', 'M', 'L', 'XL'], ['Floral', 'Emerald']],
            ['Evening Gown', 'Occasion Wear', 12500, 6000, ['S', 'M', 'L'], ['Champagne', 'Emerald', 'Black']],
            ['Block Heel Pumps', 'Shoes', 4200, 2000, ['36', '37', '38', '39', '40'], ['Black', 'Nude']],
            ['Leather Tote Bag', 'Handbags', 5500, 2700, ['One Size'], ['Tan', 'Black']],
            ['Gold-Tone Necklace Set', 'Accessories', 1800, 700, ['One Size'], ['Gold']],
        ];

        foreach ($catalog as $i => [$name, $cat, $priceKes, $costKes, $sizes, $colors]) {
            $slug = Str::slug($name);
            if (Product::where('slug', $slug)->exists()) {
                continue;
            }
            $product = Product::create([
                'brand_id' => $brand->id,
                'name' => $name,
                'slug' => $slug,
                'sku_root' => 'QNS-'.str_pad((string) ($i + 1), 4, '0', STR_PAD_LEFT),
                'description' => "Elegant {$name} from the QUEENSONS collection. Affordable premium quality, designed for style beyond seasons.",
                'care_instructions' => 'Machine wash cold. Do not bleach. Iron on low heat.',
                'status' => 'published',
                'featured' => $i < 6,
                'published_at' => now(),
            ]);
            $product->categories()->sync([$cats[$cat]->id]);
            if ($i < 8) {
                $newArrivals->products()->attach($product->id);
            }
            if ($i % 3 === 0) {
                $bestSellers->products()->attach($product->id);
            }

            $v = 0;
            foreach ($colors as $color) {
                foreach ($sizes as $size) {
                    $v++;
                    $variant = ProductVariant::create([
                        'product_id' => $product->id,
                        'sku' => $product->sku_root.'-'.strtoupper(substr(preg_replace('/[^A-Za-z0-9]/', '', $color), 0, 3)).'-'.preg_replace('/[^A-Za-z0-9]/', '', $size),
                        'barcode' => '590'.str_pad((string) (($i + 1) * 1000 + $v), 10, '0', STR_PAD_LEFT),
                        'size' => $size,
                        'color' => $color,
                        'price_cents' => $priceKes * 100,
                        'cost_cents' => $costKes * 100,
                    ]);
                    $inv->record($variant->id, $whId, random_int(5, 30), 'opening_balance', reason: 'Initial stock');
                }
            }
        }
    }

    private function seedCms(): void
    {
        foreach ([
            ['About QUEENSONS', 'about', "QUEENSONS is a modern fashion retail brand offering affordable premium women's fashion. Style Beyond Seasons."],
            ['Terms & Conditions', 'terms', 'Standard terms and conditions apply to all purchases.'],
            ['Privacy Policy', 'privacy', 'We respect your privacy and protect your personal data in line with Kenyan data protection law.'],
            ['Return Policy', 'returns', 'Returns accepted within 7 days with receipt. Items must be unworn with tags attached.'],
            ['Shipping Policy', 'shipping', 'Nairobi delivery within 24 hours. Countrywide delivery 2-4 business days.'],
            ['FAQ', 'faq', 'Frequently asked questions about shopping with QUEENSONS.'],
        ] as [$title, $slug, $body]) {
            CmsPage::firstOrCreate(['slug' => $slug], ['title' => $title, 'body' => $body, 'published' => true]);
        }

        Banner::firstOrCreate(['title' => 'New Season Arrivals'], [
            'subtitle' => 'Discover elegant pieces for every occasion', 'link' => '/shop?collection=new-arrivals', 'position' => 1,
        ]);
        Banner::firstOrCreate(['title' => 'Office Edit'], [
            'subtitle' => 'Power dressing, affordable premium', 'link' => '/shop?category=office-wear', 'position' => 2,
        ]);
    }
}
