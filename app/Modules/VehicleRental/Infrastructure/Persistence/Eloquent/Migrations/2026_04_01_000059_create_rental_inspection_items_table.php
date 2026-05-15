<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rental_inspection_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('row_version')->default(1)->comment('Used for optimistic concurrency control');
            $table->foreignId('tenant_id')->constrained('tenants', 'id')->cascadeOnDelete();
            $table->foreignId('organization_unit_id')->nullable()->constrained('organization_units', 'id')->nullOnDelete();
            $table->json('metadata')->nullable();

            $table->foreignId('inspection_id')->constrained('rental_inspections')->cascadeOnDelete();
            $table->string('item_category');
            $table->string('checkpoint');
            $table->string('expected_value')->nullable();
            $table->string('actual_value')->nullable();
            $table->string('result')->default('not_tested');
            $table->text('comment')->nullable();
            $table->integer('sort_order')->default(0);

            $table->timestamps();
        });
    }

    public function down(): void { Schema::dropIfExists('rental_inspection_items'); }
};
