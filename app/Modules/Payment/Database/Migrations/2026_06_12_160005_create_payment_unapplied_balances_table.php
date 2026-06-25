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
            $table->string('balance_type', 50)->default('credit');
            $table->string('party_type', 150)->nullable();
            $table->unsignedBigInteger('party_id')->nullable();
            $table->string('source_type', 150)->nullable();
            $table->unsignedBigInteger('source_id')->nullable();
            $table->string('allocation_status', 50)->default('unallocated');
            $table->decimal('original_amount', 20, 6);
            $table->decimal('allocated_amount', 20, 6)->default('0');
            $table->decimal('refunded_amount', 20, 6)->default('0');
            $table->decimal('remaining_amount', 20, 6);
            $table->enum('status', ['available', 'partially_applied', 'fully_applied', 'refunded', 'cancelled'])->default('available');
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['party_type', 'party_id', 'status'], 'payment_unapplied_party_status_idx');
            $table->index(['source_type', 'source_id'], 'payment_unapplied_source_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_unapplied_balances');
    }
};
