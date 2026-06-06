<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_unapplied_balances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants', 'id')->cascadeOnDelete();
            $table->foreignId('organization_unit_id')->nullable()->constrained('organization_units', 'id')->nullOnDelete();
            $table->foreignId('payment_id')->unique('payment_unapplied_balances_payment_uk')->constrained('payments', 'id')->cascadeOnDelete();
            $table->decimal('original_amount', 20, 6);
            $table->decimal('allocated_amount', 20, 6)->default('0');
            $table->decimal('refunded_amount', 20, 6)->default('0');
            $table->decimal('remaining_amount', 20, 6);
            $table->enum('status', ['available', 'partially_applied', 'fully_applied', 'refunded', 'cancelled'])->default('available');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_unapplied_balances');
    }
};
