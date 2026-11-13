<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vehicle_rental_agreement_adjustments_history', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->restrictOnDelete();
            $table->foreignId('adjustment_id');
            $table->string('from_status', 32)->nullable();
            $table->string('to_status', 32);
            $table->text('reason');
            $table->foreignId('user_id')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'adjustment_id'], 'vraah_adj_ix');
            $table->foreign(['adjustment_id', 'tenant_id'], 'vraah_adj_fk')
                ->references(['id', 'tenant_id'])
                ->on('vehicle_rental_agreement_adjustments')
                ->cascadeOnDelete();
            $table->foreign(['user_id', 'tenant_id'], 'vraah_user_fk')
                ->references(['id', 'tenant_id'])
                ->on('users')
                ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vehicle_rental_agreement_adjustments_history');
    }
};
