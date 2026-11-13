<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('branches', function (Blueprint $t) {
            $t->id();
            $t->string('name');
            $t->string('code')->unique();
            $t->string('address')->nullable();
            $t->string('phone')->nullable();
            $t->boolean('active')->default(true);
            $t->timestamps();
        });

        Schema::create('warehouses', function (Blueprint $t) {
            $t->id();
            $t->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
            $t->string('name');
            $t->string('code')->unique();
            $t->boolean('active')->default(true);
            $t->timestamps();
        });

        Schema::create('registers', function (Blueprint $t) {
            $t->id();
            $t->foreignId('branch_id')->constrained()->restrictOnDelete();
            $t->string('name');
            $t->boolean('active')->default(true);
            $t->timestamps();
        });

        Schema::create('settings', function (Blueprint $t) {
            $t->id();
            $t->string('key')->unique();
            $t->text('value')->nullable();
            $t->timestamps();
        });

        Schema::create('audit_logs', function (Blueprint $t) {
            $t->id();
            $t->foreignId('user_id')->nullable();
            $t->string('action');
            $t->string('subject_type')->nullable();
            $t->unsignedBigInteger('subject_id')->nullable();
            $t->jsonb('changes')->nullable();
            $t->string('ip', 45)->nullable();
            $t->timestamp('created_at')->useCurrent();
            $t->index(['subject_type', 'subject_id']);
        });

        Schema::table('users', function (Blueprint $t) {
            $t->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
            $t->string('phone')->nullable();
            $t->string('status')->default('active');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $t) {
            $t->dropConstrainedForeignId('branch_id');
            $t->dropColumn(['phone', 'status']);
        });
        Schema::dropIfExists('audit_logs');
        Schema::dropIfExists('settings');
        Schema::dropIfExists('registers');
        Schema::dropIfExists('warehouses');
        Schema::dropIfExists('branches');
    }
};
