<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('brands', function (Blueprint $t) {
            $t->id();
            $t->string('name');
            $t->string('slug')->unique();
            $t->boolean('active')->default(true);
            $t->timestamps();
        });

        Schema::create('categories', function (Blueprint $t) {
            $t->id();
            $t->foreignId('parent_id')->nullable()->constrained('categories')->nullOnDelete();
            $t->string('name');
            $t->string('slug')->unique();
            $t->integer('position')->default(0);
            $t->boolean('published')->default(true);
            $t->string('seo_title')->nullable();
            $t->string('seo_description')->nullable();
            $t->timestamps();
        });

        Schema::create('products', function (Blueprint $t) {
            $t->id();
            $t->foreignId('brand_id')->nullable()->constrained()->nullOnDelete();
            $t->string('name');
            $t->string('slug')->unique();
            $t->string('sku_root')->unique();
            $t->text('description')->nullable();
            $t->text('care_instructions')->nullable();
            $t->jsonb('attributes')->nullable();
            $t->string('status')->default('draft'); // draft|published|archived
            $t->boolean('featured')->default(false);
            $t->integer('low_stock_threshold')->default(5);
            $t->string('seo_title')->nullable();
            $t->string('seo_description')->nullable();
            $t->timestamp('published_at')->nullable();
            $t->timestamps();
        });

        Schema::create('category_product', function (Blueprint $t) {
            $t->foreignId('category_id')->constrained()->cascadeOnDelete();
            $t->foreignId('product_id')->constrained()->cascadeOnDelete();
            $t->primary(['category_id', 'product_id']);
        });

        Schema::create('product_variants', function (Blueprint $t) {
            $t->id();
            $t->foreignId('product_id')->constrained()->cascadeOnDelete();
            $t->string('sku')->unique();
            $t->string('barcode')->nullable()->index();
            $t->string('size')->nullable();
            $t->string('color')->nullable();
            $t->bigInteger('price_cents');
            $t->bigInteger('compare_at_cents')->nullable();
            $t->bigInteger('cost_cents')->default(0);
            $t->boolean('active')->default(true);
            $t->timestamps();
        });

        Schema::create('product_images', function (Blueprint $t) {
            $t->id();
            $t->foreignId('product_id')->constrained()->cascadeOnDelete();
            $t->string('path');
            $t->string('alt')->nullable();
            $t->integer('position')->default(0);
            $t->timestamps();
        });

        Schema::create('collections', function (Blueprint $t) {
            $t->id();
            $t->string('name');
            $t->string('slug')->unique();
            $t->text('description')->nullable();
            $t->boolean('published')->default(false);
            $t->timestamps();
        });

        Schema::create('collection_product', function (Blueprint $t) {
            $t->foreignId('collection_id')->constrained()->cascadeOnDelete();
            $t->foreignId('product_id')->constrained()->cascadeOnDelete();
            $t->primary(['collection_id', 'product_id']);
        });

        Schema::create('product_reviews', function (Blueprint $t) {
            $t->id();
            $t->foreignId('product_id')->constrained()->cascadeOnDelete();
            $t->foreignId('customer_id');
            $t->unsignedTinyInteger('rating');
            $t->text('body')->nullable();
            $t->string('status')->default('pending'); // pending|published|rejected
            $t->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_reviews');
        Schema::dropIfExists('collection_product');
        Schema::dropIfExists('collections');
        Schema::dropIfExists('product_images');
        Schema::dropIfExists('product_variants');
        Schema::dropIfExists('category_product');
        Schema::dropIfExists('products');
        Schema::dropIfExists('categories');
        Schema::dropIfExists('brands');
    }
};
