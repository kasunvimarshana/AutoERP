<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rental_vehicle_allocations', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('row_version')->default(1);
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('organization_unit_id')->nullable()->constrained('organization_units')->nullOnDelete();
            $table->json('metadata')->nullable();
            $table->string('allocation_number', 100);
            $table->foreignId('agreement_id')->constrained('rental_agreements')->cascadeOnDelete();
            $table->foreignId('vehicle_id')->constrained('vehicles')->restrictOnDelete();
            $table->foreignId('vehicle_ownership_id')->nullable()->constrained('vehicle_ownerships')->nullOnDelete();
            $table->string('vehicle_source_type', 30);
            $table->foreignId('source_allocation_id')->nullable()->constrained('rental_vehicle_allocations')->nullOnDelete();
            $table->foreignId('vehicle_finance_agreement_id')->nullable()->constrained('vehicle_finance_agreements')->nullOnDelete();
            $table->foreignId('replaces_allocation_id')->nullable()->constrained('rental_vehicle_allocations')->nullOnDelete();
            $table->dateTime('allocated_from');
            $table->dateTime('allocated_to')->nullable();
            $table->dateTime('actual_returned_at')->nullable();
            $table->decimal('start_odometer', 20, 6)->nullable();
            $table->decimal('end_odometer', 20, 6)->nullable();
            $table->string('status', 30)->default('planned');
            $table->text('remarks')->nullable();
            $table->unsignedBigInteger('activated_by')->nullable();
            $table->dateTime('activated_at')->nullable();
            $table->unsignedBigInteger('closed_by')->nullable();
            $table->dateTime('closed_at')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();

            $table->unique(['tenant_id', 'allocation_number'], 'rental_vehicle_allocations_tenant_number_uk');
            $table->index(['vehicle_id', 'allocated_from', 'allocated_to', 'status'], 'rental_vehicle_allocations_vehicle_period_idx');
            $table->index(['agreement_id', 'status', 'allocated_from'], 'rental_vehicle_allocations_agreement_status_idx');
            $table->index(['source_allocation_id', 'allocated_from', 'allocated_to'], 'rental_vehicle_allocations_source_period_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rental_vehicle_allocations');
    }
};
