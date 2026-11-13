<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customers', function (Blueprint $t) {
            $t->id();
            $t->string('name');
            $t->string('email')->unique();
            $t->string('phone')->nullable();
            $t->string('password');
            $t->integer('loyalty_points')->default(0);
            $t->string('status')->default('active');
            $t->timestamp('email_verified_at')->nullable();
            $t->rememberToken();
            $t->timestamps();
        });

        Schema::create('customer_addresses', function (Blueprint $t) {
            $t->id();
            $t->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $t->string('label')->default('Home');
            $t->string('line1');
            $t->string('line2')->nullable();
            $t->string('city');
            $t->string('phone')->nullable();
            $t->boolean('is_default')->default(false);
            $t->timestamps();
        });

        Schema::create('wishlist_items', function (Blueprint $t) {
            $t->id();
            $t->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $t->foreignId('product_id')->constrained()->cascadeOnDelete();
            $t->timestamps();
            $t->unique(['customer_id', 'product_id']);
        });

        Schema::create('coupons', function (Blueprint $t) {
            $t->id();
            $t->string('code')->unique();
            $t->string('type'); // percent|fixed|free_delivery
            $t->bigInteger('value');
            $t->bigInteger('min_order_cents')->default(0);
            $t->integer('max_uses')->nullable();
            $t->integer('used_count')->default(0);
            $t->timestamp('starts_at')->nullable();
            $t->timestamp('ends_at')->nullable();
            $t->boolean('active')->default(true);
            $t->timestamps();
        });

        Schema::create('orders', function (Blueprint $t) {
            $t->id();
            $t->string('reference')->unique();
            $t->foreignId('customer_id')->nullable()->constrained()->nullOnDelete();
            $t->string('guest_email')->nullable();
            $t->string('guest_name')->nullable();
            $t->string('guest_phone')->nullable();
            $t->string('status')->default('pending');
            $t->string('payment_status')->default('unpaid'); // unpaid|processing|paid|refunded|partially_refunded
            $t->bigInteger('subtotal_cents');
            $t->bigInteger('discount_cents')->default(0);
            $t->bigInteger('delivery_cents')->default(0);
            $t->bigInteger('total_cents');
            $t->foreignId('coupon_id')->nullable()->constrained()->nullOnDelete();
            $t->string('delivery_method')->default('delivery'); // delivery|pickup
            $t->jsonb('delivery_address')->nullable();
            $t->foreignId('warehouse_id')->nullable()->constrained()->nullOnDelete();
            $t->timestamp('placed_at')->nullable();
            $t->timestamps();
        });

        Schema::create('order_items', function (Blueprint $t) {
            $t->id();
            $t->foreignId('order_id')->constrained()->cascadeOnDelete();
            $t->foreignId('variant_id')->constrained('product_variants')->restrictOnDelete();
            $t->string('name_snapshot');
            $t->string('sku_snapshot');
            $t->integer('qty');
            $t->bigInteger('unit_price_cents');
            $t->bigInteger('cost_cents_snapshot')->default(0);
            $t->bigInteger('line_total_cents');
        });

        Schema::create('order_status_history', function (Blueprint $t) {
            $t->id();
            $t->foreignId('order_id')->constrained()->cascadeOnDelete();
            $t->string('from_status')->nullable();
            $t->string('to_status');
            $t->foreignId('user_id')->nullable();
            $t->string('note')->nullable();
            $t->timestamp('created_at')->useCurrent();
        });

        Schema::create('deliveries', function (Blueprint $t) {
            $t->id();
            $t->foreignId('order_id')->constrained()->cascadeOnDelete();
            $t->string('courier')->nullable();
            $t->string('tracking_no')->nullable();
            $t->string('status')->default('pending'); // pending|dispatched|delivered|failed
            $t->string('failure_reason')->nullable();
            $t->timestamp('dispatched_at')->nullable();
            $t->timestamp('delivered_at')->nullable();
            $t->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('deliveries');
        Schema::dropIfExists('order_status_history');
        Schema::dropIfExists('order_items');
        Schema::dropIfExists('orders');
        Schema::dropIfExists('coupons');
        Schema::dropIfExists('wishlist_items');
        Schema::dropIfExists('customer_addresses');
        Schema::dropIfExists('customers');
    }
};
