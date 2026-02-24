<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pos_loyalty_programs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('tenant_id')->index();
            $table->string('name', 150);
            // Points earned per 1 unit of currency spent (DECIMAL(18,8) for BCMath)
            $table->decimal('points_per_currency_unit', 18, 8)->default(1);
            // How many points equal 1 unit of currency discount
            $table->decimal('redemption_rate', 18, 8)->default(100);
            $table->boolean('is_active')->default(true);
            $table->text('description')->nullable();
            $table->softDeletes();
            $table->timestamps();

            $table->index(['tenant_id', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pos_loyalty_programs');
    }
};
