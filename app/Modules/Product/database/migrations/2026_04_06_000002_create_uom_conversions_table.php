<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('uom_conversions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->unsignedBigInteger('tenant_id')->index();
            $table->uuid('product_id')->nullable()->comment('NULL = applies to all products using these UOMs');
            $table->string('from_uom', 30);
            $table->string('to_uom', 30);
            // Number of to_uom units equal to 1 from_uom unit
            $table->decimal('factor', 20, 10);
            $table->boolean('is_active')->default(true);
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['tenant_id', 'from_uom', 'to_uom']);
            $table->index(['product_id']);

            $table->foreign('product_id')->references('id')->on('products')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('uom_conversions');
    }
};
