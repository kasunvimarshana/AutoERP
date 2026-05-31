<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('item_types', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->nullable();
            $table->string('code', 100);
            $table->string('name', 150);
            $table->text('description')->nullable();
            $table->boolean('is_stockable')->default(false);
            $table->boolean('is_service')->default(false);
            $table->boolean('is_rentable')->default(false);
            $table->boolean('is_chargeable')->default(false);
            $table->boolean('is_active')->default(true);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
            $table->unique(['tenant_id', 'code'], 'item_types_tenant_code_uk');
            $table->index(['tenant_id', 'is_active'], 'item_types_tenant_active_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('item_types');
    }
};
