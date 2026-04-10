<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tax_codes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->string('code', 30);
            $table->string('name', 100);
            $table->decimal('rate', 8, 4);
            $table->enum('type', ['sales', 'purchase', 'both']);
            $table->foreignId('account_id')->constrained('chart_of_accounts')->restrictOnDelete();
            $table->boolean('is_compound')->default(false);
            $table->boolean('is_active')->default(true);
            $table->unique(['tenant_id', 'code']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tax_codes');
    }
};
