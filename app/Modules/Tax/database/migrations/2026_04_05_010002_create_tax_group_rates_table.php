<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('tax_group_rates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('tax_group_id')->constrained('tax_groups')->cascadeOnDelete();
            $table->string('tax_rate_code');
            $table->string('tax_rate_name');
            $table->decimal('rate', 8, 4);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->boolean('is_compound')->default(false);
            $table->timestamps();
            $table->softDeletes();
            $table->index(['tax_group_id', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tax_group_rates');
    }
};
