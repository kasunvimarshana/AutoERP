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
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->uuid('parent_id')->nullable();
            $table->foreign('parent_id')->references('id')->on('accounts')->nullOnDelete();

            $table->string('type', 30);
            $table->string('code', 50);
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('currency_code', 3)->default('USD');
            $table->decimal('balance', 19, 4)->default(0);
            $table->boolean('is_active')->default(true);
            $table->boolean('is_system')->default(false);

            $table->timestamps();
            $table->softDeletes();

            $table->unique(['tenant_id', 'code']);
            $table->index(['tenant_id', 'type']);
            $table->index(['tenant_id', 'parent_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('accounts');
    }
};
