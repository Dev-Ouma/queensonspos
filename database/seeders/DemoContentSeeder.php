<?php

namespace Database\Seeders;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Collection;
use App\Models\Customer;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderStatusHistory;
use App\Models\Payment;
use App\Models\PosSale;
use App\Models\PosSaleItem;
use App\Models\PosSession;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\ProductVariant;
use App\Models\Register;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\InventoryService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class DemoContentSeeder extends Seeder
{
    private const U = 'https://images.unsplash.com/';

    private const SUFFIX = '?auto=format&fit=crop&w=900&q=70';

    public function run(): void
    {
        $this->attachImages();
        $this->extraProducts();
        $this->demoSalesHistory();
        $this->demoExpenses();
    }

    private function img(string $id): string
    {
        return self::U.$id.self::SUFFIX;
    }

    private function attachImages(): void
    {
        $map = [
            'classic-wrap-dress' => 'photo-1595777457583-95e059d581b8',
            'satin-slip-dress' => 'photo-1566174053879-31528523f8ae',
            'chiffon-blouse' => 'photo-1551163943-3f6a855d1153',
            'ribbed-knit-top' => 'photo-1515372039744-b8f02a3ae446',
            'high-waist-tailored-trousers' => 'photo-1594633312681-425c7b97ccd1',
            'slim-fit-jeans' => 'photo-1541099649105-f69ad21f3246',
            'pleated-midi-skirt' => 'photo-1583496661160-fb5886a0aaaa',
            'classic-blazer' => 'photo-1591047139829-d91aecb6caea',
            'power-suit-set' => 'photo-1507679799987-c73779587ccf',
            'weekend-maxi-dress' => 'photo-1496747611176-843222e1e57c',
            'evening-gown' => 'photo-1539533018447-63fcce2678e3',
            'block-heel-pumps' => 'photo-1543163521-1bf539c55dd2',
            'leather-tote-bag' => 'photo-1584917865442-de89df76afd3',
            'gold-tone-necklace-set' => 'photo-1515562141207-7a88fb7ce338',
        ];

        foreach ($map as $slug => $photo) {
            $product = Product::where('slug', $slug)->first();
            if ($product && ! $product->images()->exists()) {
                ProductImage::create([
                    'product_id' => $product->id,
                    'path' => $this->img($photo),
                    'alt' => $product->name,
                    'position' => 0,
                ]);
            }
        }
    }

    private function extraProducts(): void
    {
        $extra = [
            ['Silk Scarf Collection', 'Accessories', 1500, 600, ['One Size'], ['Emerald', 'Gold', 'Navy'], 'photo-1601924994987-69e26d50dc26'],
            ['Denim Jacket', 'Jackets & Coats', 4800, 2400, ['S', 'M', 'L'], ['Light Blue', 'Dark Indigo'], 'photo-1551537482-f2075a1d41f2'],
            ['White Canvas Sneakers', 'Shoes', 3200, 1500, ['36', '37', '38', '39', '40'], ['White'], 'photo-1549298916-b41d501d3772'],
            ['Crossbody Mini Bag', 'Handbags', 2900, 1300, ['One Size'], ['Black', 'Tan', 'Red'], 'photo-1548036328-c9fa89d128fa'],
            ['Linen Palazzo Trousers', 'Trousers', 3400, 1600, ['S', 'M', 'L', 'XL'], ['Beige', 'White'], 'photo-1509551388413-e18d0ac5d495'],
            ['Floral Wrap Skirt', 'Skirts', 2600, 1100, ['S', 'M', 'L'], ['Floral'], 'photo-1583846717393-dc2412c95ed7'],
            ['Statement Earrings', 'Accessories', 1200, 450, ['One Size'], ['Gold', 'Silver'], 'photo-1535632066927-ab7c9ab60908'],
            ['Cocktail Midi Dress', 'Occasion Wear', 6800, 3300, ['S', 'M', 'L'], ['Emerald', 'Black', 'Wine'], 'photo-1572804013309-59a88b7e92f1'],
        ];

        $brand = Brand::where('slug', 'queensons')->first();
        $inv = app(InventoryService::class);
        $whId = Warehouse::where('code', 'MAIN')->value('id');
        $newArrivals = Collection::where('slug', 'new-arrivals')->first();
        $nextSku = Product::count() + 1;

        foreach ($extra as [$name, $cat, $priceKes, $costKes, $sizes, $colors, $photo]) {
            $slug = Str::slug($name);
            if (Product::where('slug', $slug)->exists()) {
                continue;
            }
            $product = Product::create([
                'brand_id' => $brand->id,
                'name' => $name,
                'slug' => $slug,
                'sku_root' => 'QNS-'.str_pad((string) $nextSku++, 4, '0', STR_PAD_LEFT),
                'description' => "Elegant {$name} from the QUEENSONS collection. Affordable premium quality, designed for style beyond seasons.",
                'care_instructions' => 'Machine wash cold. Do not bleach. Iron on low heat.',
                'status' => 'published',
                'featured' => false,
                'published_at' => now(),
            ]);
            $category = Category::where('name', $cat)->first();
            if ($category) {
                $product->categories()->sync([$category->id]);
            }
            $newArrivals?->products()->attach($product->id);
            ProductImage::create(['product_id' => $product->id, 'path' => $this->img($photo), 'alt' => $name, 'position' => 0]);

            $v = 0;
            foreach ($colors as $color) {
                foreach ($sizes as $size) {
                    $v++;
                    $variant = ProductVariant::create([
                        'product_id' => $product->id,
                        'sku' => $product->sku_root.'-'.strtoupper(substr(preg_replace('/[^A-Za-z0-9]/', '', $color), 0, 3)).'-'.preg_replace('/[^A-Za-z0-9]/', '', $size),
                        'barcode' => '591'.str_pad((string) ($nextSku * 1000 + $v), 10, '0', STR_PAD_LEFT),
                        'size' => $size,
                        'color' => $color,
                        'price_cents' => $priceKes * 100,
                        'cost_cents' => $costKes * 100,
                    ]);
                    $inv->record($variant->id, $whId, random_int(8, 35), 'opening_balance', reason: 'Initial stock');
                }
            }
        }
    }

    private function demoSalesHistory(): void
    {
        if (Order::where('reference', 'like', 'QNS-DEMO-%')->exists()) {
            return;
        }

        $inv = app(InventoryService::class);
        $whId = Warehouse::where('code', 'MAIN')->value('id');
        $customer = Customer::first();
        $cashier = User::where('email', 'cashier@queensons.com')->first();
        $register = Register::first();
        $variants = ProductVariant::with('product')->inRandomOrder()->limit(40)->get();

        // --- Online orders over the last 14 days ---
        $n = 0;
        foreach (range(13, 0) as $daysAgo) {
            $ordersToday = random_int(1, 3);
            for ($i = 0; $i < $ordersToday; $i++) {
                $placedAt = now()->subDays($daysAgo)->setTime(random_int(9, 19), random_int(0, 59));
                $picked = $variants->random(random_int(1, 3));
                $subtotal = 0;
                $order = Order::create([
                    'reference' => 'QNS-DEMO-'.str_pad((string) ++$n, 4, '0', STR_PAD_LEFT),
                    'customer_id' => random_int(0, 1) ? $customer->id : null,
                    'guest_name' => 'Demo Guest',
                    'guest_email' => 'guest'.$n.'@example.com',
                    'guest_phone' => '07'.random_int(10000000, 99999999),
                    'status' => $daysAgo > 2 ? 'completed' : 'paid',
                    'payment_status' => 'paid',
                    'subtotal_cents' => 0,
                    'delivery_cents' => 30000,
                    'total_cents' => 0,
                    'delivery_method' => 'delivery',
                    'delivery_address' => ['line1' => 'Demo Street '.$n, 'city' => 'Nairobi'],
                    'warehouse_id' => $whId,
                    'placed_at' => $placedAt,
                    'created_at' => $placedAt,
                ]);
                foreach ($picked as $variant) {
                    $qty = random_int(1, 2);
                    $subtotal += $variant->price_cents * $qty;
                    OrderItem::create([
                        'order_id' => $order->id,
                        'variant_id' => $variant->id,
                        'name_snapshot' => $variant->product->name,
                        'sku_snapshot' => $variant->sku,
                        'qty' => $qty,
                        'unit_price_cents' => $variant->price_cents,
                        'cost_cents_snapshot' => $variant->cost_cents,
                        'line_total_cents' => $variant->price_cents * $qty,
                    ]);
                    try {
                        $inv->record($variant->id, $whId, -$qty, 'online_sale', Order::class, $order->id);
                    } catch (\RuntimeException) {
                        // Skip deduction when demo stock runs out; order record stands.
                    }
                }
                $order->update(['subtotal_cents' => $subtotal, 'total_cents' => $subtotal + 30000]);
                OrderStatusHistory::create(['order_id' => $order->id, 'to_status' => 'paid', 'created_at' => $placedAt]);
                Payment::create([
                    'reference' => 'PAY-'.strtoupper(Str::ulid()),
                    'subject_type' => Order::class,
                    'subject_id' => $order->id,
                    'amount_cents' => $subtotal + 30000,
                    'method' => ['mpesa', 'mpesa', 'card'][random_int(0, 2)],
                    'provider' => 'daraja',
                    'provider_txn_id' => 'DEMO'.strtoupper(Str::random(8)),
                    'status' => 'verified',
                    'verified_at' => $placedAt,
                    'created_at' => $placedAt,
                ]);
            }
        }

        // --- One closed POS session with sales spread across days ---
        foreach (range(10, 1) as $daysAgo) {
            $openedAt = now()->subDays($daysAgo)->setTime(8, 30);
            $session = PosSession::create([
                'register_id' => $register->id,
                'user_id' => $cashier->id,
                'opening_cash_cents' => 500000,
                'status' => 'closed',
                'opened_at' => $openedAt,
                'closed_at' => (clone $openedAt)->setTime(18, 0),
                'created_at' => $openedAt,
            ]);
            $cashTotal = 0;
            foreach (range(1, random_int(2, 5)) as $s) {
                $variant = $variants->random();
                $qty = random_int(1, 2);
                $soldAt = (clone $openedAt)->addMinutes(random_int(30, 540));
                $sale = PosSale::create([
                    'reference' => 'POS-DEMO-'.$daysAgo.'-'.$s.strtoupper(Str::random(3)),
                    'pos_session_id' => $session->id,
                    'subtotal_cents' => $variant->price_cents * $qty,
                    'total_cents' => $variant->price_cents * $qty,
                    'status' => 'completed',
                    'created_at' => $soldAt,
                ]);
                PosSaleItem::create([
                    'pos_sale_id' => $sale->id,
                    'variant_id' => $variant->id,
                    'name_snapshot' => $variant->product->name,
                    'sku_snapshot' => $variant->sku,
                    'qty' => $qty,
                    'unit_price_cents' => $variant->price_cents,
                    'cost_cents_snapshot' => $variant->cost_cents,
                    'line_total_cents' => $variant->price_cents * $qty,
                ]);
                $method = ['cash', 'mpesa', 'card'][random_int(0, 2)];
                if ($method === 'cash') {
                    $cashTotal += $sale->total_cents;
                }
                Payment::create([
                    'reference' => 'PAY-'.strtoupper(Str::ulid()),
                    'subject_type' => PosSale::class,
                    'subject_id' => $sale->id,
                    'amount_cents' => $sale->total_cents,
                    'method' => $method,
                    'status' => 'verified',
                    'verified_at' => $soldAt,
                    'created_at' => $soldAt,
                ]);
                try {
                    $inv->record($variant->id, $whId, -$qty, 'pos_sale', PosSale::class, $sale->id, $cashier->id);
                } catch (\RuntimeException) {
                    // Demo stock exhausted; sale record stands.
                }
            }
            $session->update([
                'closing_cash_cents' => 500000 + $cashTotal,
                'expected_cash_cents' => 500000 + $cashTotal,
                'variance_cents' => 0,
            ]);
        }
    }

    private function demoExpenses(): void
    {
        if (Expense::where('description', 'like', '[demo]%')->exists()) {
            return;
        }
        $admin = User::where('email', 'admin@queensons.com')->first();
        $finance = User::where('email', 'finance@queensons.com')->first();
        $items = [
            ['Rent', '[demo] Flagship store rent — November', 8500000],
            ['Utilities', '[demo] Electricity & water', 420000],
            ['Salaries', '[demo] Staff payroll — November', 18000000],
            ['Delivery', '[demo] Courier invoices week 45', 650000],
            ['Marketing', '[demo] Instagram ads — new arrivals', 300000],
            ['Operations', '[demo] Packaging materials', 185000],
        ];
        foreach ($items as [$cat, $desc, $cents]) {
            Expense::create([
                'expense_category_id' => ExpenseCategory::where('name', $cat)->value('id'),
                'description' => $desc,
                'amount_cents' => $cents,
                'incurred_on' => now()->subDays(random_int(1, 12)),
                'status' => 'approved',
                'created_by' => $admin->id,
                'approved_by' => $finance->id,
            ]);
        }
    }
}
