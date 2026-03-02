<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('discount_rules', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->index();
            $table->string('name');
            $table->string('discount_type');
            $table->decimal('discount_value', 20, 4);
            $table->string('apply_to');
            $table->unsignedBigInteger('product_id')->nullable();
            $table->string('customer_tier')->nullable();
            $table->unsignedBigInteger('location_id')->nullable();
            $table->decimal('min_quantity', 20, 4)->nullable();
            $table->date('valid_from')->nullable();
            $table->date('valid_to')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('discount_rules');
    }
};
