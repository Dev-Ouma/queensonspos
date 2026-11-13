<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $t) {
            $t->id();
            $t->string('reference')->unique();
            $t->string('subject_type'); // App\Models\Order | App\Models\PosSale
            $t->unsignedBigInteger('subject_id');
            $t->bigInteger('amount_cents');
            $t->string('currency', 3)->default('KES');
            $t->string('method'); // cash|mpesa|card|bank_transfer
            $t->string('provider')->nullable();
            $t->string('provider_txn_id')->nullable()->unique();
            $t->string('status')->default('pending'); // pending|verified|failed
            $t->jsonb('meta')->nullable();
            $t->timestamp('verified_at')->nullable();
            $t->timestamps();
            $t->index(['subject_type', 'subject_id']);
        });

        Schema::create('refunds', function (Blueprint $t) {
            $t->id();
            $t->string('reference')->unique();
            $t->foreignId('payment_id')->constrained()->restrictOnDelete();
            $t->bigInteger('amount_cents');
            $t->string('reason')->nullable();
            $t->string('status')->default('pending'); // pending|approved|completed|rejected
            $t->foreignId('approved_by')->nullable();
            $t->timestamps();
        });

        Schema::create('payment_webhook_events', function (Blueprint $t) {
            $t->id();
            $t->string('provider');
            $t->string('event_id')->unique();
            $t->jsonb('payload');
            $t->string('status')->default('received'); // received|processed|failed
            $t->timestamp('processed_at')->nullable();
            $t->timestamps();
        });

        Schema::create('pos_sessions', function (Blueprint $t) {
            $t->id();
            $t->foreignId('register_id')->constrained()->restrictOnDelete();
            $t->foreignId('user_id')->constrained()->restrictOnDelete();
            $t->bigInteger('opening_cash_cents')->default(0);
            $t->bigInteger('closing_cash_cents')->nullable();
            $t->bigInteger('expected_cash_cents')->nullable();
            $t->bigInteger('variance_cents')->nullable();
            $t->string('status')->default('open'); // open|closed
            $t->timestamp('opened_at');
            $t->timestamp('closed_at')->nullable();
            $t->timestamps();
        });

        Schema::create('pos_sales', function (Blueprint $t) {
            $t->id();
            $t->string('reference')->unique();
            $t->foreignId('pos_session_id')->constrained()->restrictOnDelete();
            $t->foreignId('customer_id')->nullable()->constrained()->nullOnDelete();
            $t->bigInteger('subtotal_cents');
            $t->bigInteger('discount_cents')->default(0);
            $t->bigInteger('total_cents');
            $t->string('status')->default('completed'); // completed|refunded|partially_refunded|voided
            $t->timestamps();
        });

        Schema::create('pos_sale_items', function (Blueprint $t) {
            $t->id();
            $t->foreignId('pos_sale_id')->constrained()->cascadeOnDelete();
            $t->foreignId('variant_id')->constrained('product_variants')->restrictOnDelete();
            $t->string('name_snapshot');
            $t->string('sku_snapshot');
            $t->integer('qty');
            $t->bigInteger('unit_price_cents');
            $t->bigInteger('cost_cents_snapshot')->default(0);
            $t->bigInteger('line_total_cents');
        });

        Schema::create('pos_cash_movements', function (Blueprint $t) {
            $t->id();
            $t->foreignId('pos_session_id')->constrained()->cascadeOnDelete();
            $t->string('type'); // cash_in|cash_out
            $t->bigInteger('amount_cents');
            $t->string('reason')->nullable();
            $t->foreignId('user_id');
            $t->timestamp('created_at')->useCurrent();
        });

        Schema::create('pos_returns', function (Blueprint $t) {
            $t->id();
            $t->string('reference')->unique();
            $t->foreignId('pos_sale_id')->constrained()->restrictOnDelete();
            $t->foreignId('pos_session_id')->constrained()->restrictOnDelete();
            $t->bigInteger('refund_cents');
            $t->string('reason')->nullable();
            $t->string('condition')->default('resellable'); // resellable|damaged
            $t->foreignId('user_id');
            $t->timestamps();
        });

        Schema::create('pos_return_items', function (Blueprint $t) {
            $t->id();
            $t->foreignId('pos_return_id')->constrained()->cascadeOnDelete();
            $t->foreignId('pos_sale_item_id')->constrained()->restrictOnDelete();
            $t->integer('qty');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pos_return_items');
        Schema::dropIfExists('pos_returns');
        Schema::dropIfExists('pos_cash_movements');
        Schema::dropIfExists('pos_sale_items');
        Schema::dropIfExists('pos_sales');
        Schema::dropIfExists('pos_sessions');
        Schema::dropIfExists('payment_webhook_events');
        Schema::dropIfExists('refunds');
        Schema::dropIfExists('payments');
    }
};
