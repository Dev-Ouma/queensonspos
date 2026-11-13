<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventory_balances', function (Blueprint $t) {
            $t->id();
            $t->foreignId('variant_id')->constrained('product_variants')->cascadeOnDelete();
            $t->foreignId('warehouse_id')->constrained()->restrictOnDelete();
            $t->integer('on_hand')->default(0);
            $t->integer('reserved')->default(0);
            $t->integer('damaged')->default(0);
            $t->timestamps();
            $t->unique(['variant_id', 'warehouse_id']);
        });

        Schema::create('inventory_transactions', function (Blueprint $t) {
            $t->id();
            $t->string('reference')->unique();
            $t->foreignId('variant_id')->constrained('product_variants')->restrictOnDelete();
            $t->foreignId('warehouse_id')->constrained()->restrictOnDelete();
            $t->integer('qty_delta');
            $t->string('movement_type'); // purchase_receipt|online_sale|pos_sale|customer_return|supplier_return|adjustment|transfer_out|transfer_in|damaged|write_off|opening_balance
            $t->string('subject_type')->nullable();
            $t->unsignedBigInteger('subject_id')->nullable();
            $t->foreignId('user_id')->nullable();
            $t->string('reason')->nullable();
            $t->timestamp('created_at')->useCurrent();
            $t->index(['variant_id', 'warehouse_id']);
            $t->index(['subject_type', 'subject_id']);
        });

        Schema::create('stock_reservations', function (Blueprint $t) {
            $t->id();
            $t->foreignId('variant_id')->constrained('product_variants')->cascadeOnDelete();
            $t->foreignId('warehouse_id')->constrained()->restrictOnDelete();
            $t->string('cart_token')->nullable()->index();
            $t->foreignId('order_id')->nullable();
            $t->integer('qty');
            $t->string('status')->default('held'); // held|converted|released
            $t->timestamp('expires_at');
            $t->timestamps();
        });

        Schema::create('stock_transfers', function (Blueprint $t) {
            $t->id();
            $t->string('reference')->unique();
            $t->foreignId('from_warehouse_id')->constrained('warehouses')->restrictOnDelete();
            $t->foreignId('to_warehouse_id')->constrained('warehouses')->restrictOnDelete();
            $t->string('status')->default('draft'); // draft|approved|dispatched|received|closed
            $t->foreignId('created_by');
            $t->timestamps();
        });

        Schema::create('stock_transfer_items', function (Blueprint $t) {
            $t->id();
            $t->foreignId('stock_transfer_id')->constrained()->cascadeOnDelete();
            $t->foreignId('variant_id')->constrained('product_variants')->restrictOnDelete();
            $t->integer('qty');
            $t->integer('received_qty')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_transfer_items');
        Schema::dropIfExists('stock_transfers');
        Schema::dropIfExists('stock_reservations');
        Schema::dropIfExists('inventory_transactions');
        Schema::dropIfExists('inventory_balances');
    }
};
