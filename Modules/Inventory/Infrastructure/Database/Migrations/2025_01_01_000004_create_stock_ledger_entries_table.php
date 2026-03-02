<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_ledger_entries', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->index();
            $table->unsignedBigInteger('warehouse_id')->index();
            $table->unsignedBigInteger('product_id')->index();
            $table->string('transaction_type', 50);
            $table->decimal('quantity', 20, 4);
            $table->decimal('unit_cost', 20, 4)->default('0.0000');
            $table->decimal('total_cost', 20, 4)->default('0.0000');
            $table->string('reference_type', 100)->nullable();
            $table->string('reference_id', 100)->nullable();
            $table->text('notes')->nullable();
            $table->timestamp('created_at')->useCurrent();

            // Immutable — no updated_at, no soft deletes
            $table->index(['tenant_id', 'warehouse_id', 'product_id']);
            $table->index(['tenant_id', 'transaction_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_ledger_entries');
    }
};
