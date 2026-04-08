<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('batch_lots', static function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->string('batch_number', 100);
            $table->string('lot_number', 100)->nullable();
            $table->date('manufacture_date')->nullable();
            $table->date('expiry_date')->nullable();
            $table->decimal('initial_quantity', 20, 6)->default(0);
            $table->decimal('remaining_quantity', 20, 6)->default(0);
            $table->string('supplier_batch', 100)->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['product_id', 'batch_number']);
            $table->index(['tenant_id', 'expiry_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('batch_lots');
    }
};
