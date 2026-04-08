<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('accounts', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->unsignedBigInteger('tenant_id')->index();
            $table->uuid('parent_id')->nullable();
            $table->string('code', 50);
            $table->string('name', 200);
            // asset, liability, equity, revenue, expense
            $table->string('type', 30);
            // current_asset, fixed_asset, current_liability, long_term_liability, etc.
            $table->string('sub_type', 50)->nullable();
            // debit, credit
            $table->string('normal_balance', 10);
            $table->string('currency_code', 10)->default('USD');
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            // System accounts cannot be deleted
            $table->boolean('is_system')->default(false);
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['tenant_id', 'code']);
            $table->index(['tenant_id', 'type']);

            $table->foreign('parent_id')->references('id')->on('accounts')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('accounts');
    }
};
