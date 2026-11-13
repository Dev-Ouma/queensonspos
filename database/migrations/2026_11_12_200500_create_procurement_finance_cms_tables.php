<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('suppliers', function (Blueprint $t) {
            $t->id();
            $t->string('name');
            $t->string('contact_person')->nullable();
            $t->string('phone')->nullable();
            $t->string('email')->nullable();
            $t->string('address')->nullable();
            $t->string('payment_terms')->nullable();
            $t->boolean('active')->default(true);
            $t->timestamps();
        });

        Schema::create('purchase_orders', function (Blueprint $t) {
            $t->id();
            $t->string('reference')->unique();
            $t->foreignId('supplier_id')->constrained()->restrictOnDelete();
            $t->foreignId('warehouse_id')->constrained()->restrictOnDelete();
            $t->string('status')->default('draft'); // draft|approved|sent|partially_received|received|closed|cancelled
            $t->bigInteger('total_cents')->default(0);
            $t->foreignId('created_by');
            $t->foreignId('approved_by')->nullable();
            $t->timestamps();
        });

        Schema::create('purchase_order_items', function (Blueprint $t) {
            $t->id();
            $t->foreignId('purchase_order_id')->constrained()->cascadeOnDelete();
            $t->foreignId('variant_id')->constrained('product_variants')->restrictOnDelete();
            $t->integer('qty_ordered');
            $t->integer('qty_received')->default(0);
            $t->bigInteger('unit_cost_cents');
        });

        Schema::create('goods_received_notes', function (Blueprint $t) {
            $t->id();
            $t->string('reference')->unique();
            $t->foreignId('purchase_order_id')->constrained()->restrictOnDelete();
            $t->string('supplier_delivery_ref')->nullable();
            $t->foreignId('received_by');
            $t->timestamps();
        });

        Schema::create('grn_items', function (Blueprint $t) {
            $t->id();
            $t->foreignId('goods_received_note_id')->constrained()->cascadeOnDelete();
            $t->foreignId('purchase_order_item_id')->constrained()->restrictOnDelete();
            $t->integer('qty_received');
            $t->integer('qty_damaged')->default(0);
        });

        Schema::create('expense_categories', function (Blueprint $t) {
            $t->id();
            $t->string('name')->unique();
            $t->timestamps();
        });

        Schema::create('expenses', function (Blueprint $t) {
            $t->id();
            $t->foreignId('expense_category_id')->constrained()->restrictOnDelete();
            $t->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
            $t->string('description');
            $t->bigInteger('amount_cents');
            $t->date('incurred_on');
            $t->string('status')->default('pending'); // pending|approved|rejected
            $t->foreignId('created_by');
            $t->foreignId('approved_by')->nullable();
            $t->timestamps();
        });

        Schema::create('loyalty_transactions', function (Blueprint $t) {
            $t->id();
            $t->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $t->integer('points'); // + earn, - redeem
            $t->string('reason');
            $t->string('subject_type')->nullable();
            $t->unsignedBigInteger('subject_id')->nullable();
            $t->timestamp('created_at')->useCurrent();
        });

        Schema::create('support_tickets', function (Blueprint $t) {
            $t->id();
            $t->string('reference')->unique();
            $t->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $t->string('subject');
            $t->string('status')->default('open'); // open|in_progress|resolved|closed
            $t->foreignId('assigned_to')->nullable();
            $t->timestamps();
        });

        Schema::create('ticket_messages', function (Blueprint $t) {
            $t->id();
            $t->foreignId('support_ticket_id')->constrained()->cascadeOnDelete();
            $t->string('sender_type'); // customer|staff
            $t->unsignedBigInteger('sender_id');
            $t->text('body');
            $t->timestamp('created_at')->useCurrent();
        });

        Schema::create('cms_pages', function (Blueprint $t) {
            $t->id();
            $t->string('title');
            $t->string('slug')->unique();
            $t->text('body')->nullable();
            $t->boolean('published')->default(false);
            $t->timestamps();
        });

        Schema::create('banners', function (Blueprint $t) {
            $t->id();
            $t->string('title');
            $t->string('subtitle')->nullable();
            $t->string('image_path')->nullable();
            $t->string('link')->nullable();
            $t->integer('position')->default(0);
            $t->boolean('active')->default(true);
            $t->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('banners');
        Schema::dropIfExists('cms_pages');
        Schema::dropIfExists('ticket_messages');
        Schema::dropIfExists('support_tickets');
        Schema::dropIfExists('loyalty_transactions');
        Schema::dropIfExists('expenses');
        Schema::dropIfExists('expense_categories');
        Schema::dropIfExists('grn_items');
        Schema::dropIfExists('goods_received_notes');
        Schema::dropIfExists('purchase_order_items');
        Schema::dropIfExists('purchase_orders');
        Schema::dropIfExists('suppliers');
    }
};
