<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('saved_searches', function (Blueprint $t) {
            $t->id();
            $t->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $t->string('label', 160);
            $t->json('filters');
            $t->string('filters_hash', 64);
            $t->timestamps();
            $t->unique(['customer_id', 'filters_hash']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('saved_searches');
    }
};
