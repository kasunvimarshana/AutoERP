<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vehicle_service_job_payment_links', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('row_version')->default(1);
            $table->foreignId('tenant_id');
            $table->foreignId('organization_unit_id')->nullable();
            $table->foreignId('job_card_id');
            $table->foreignId('payment_id');
            $table->foreignId('payment_allocation_id')->nullable();
            $table->decimal('allocated_amount', 20, 4)->default(0);
            $table->decimal('advance_amount', 20, 4)->default(0);
            $table->decimal('refund_amount', 20, 4)->default(0);
            $table->decimal('write_off_amount', 20, 4)->default(0);
            $table->string('status')->default('active');
            $table->foreignId('linked_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('linked_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['tenant_id', 'job_card_id'], 'vehicle_service_payment_links_job_idx');
            $table->index(['tenant_id', 'payment_id'], 'vehicle_service_payment_links_payment_idx');
            $table->index(['tenant_id', 'status'], 'vehicle_service_payment_links_status_idx');

            $table->foreign('tenant_id', 'vs_pay_links_tenant_fk')->references('id')->on('tenants')->cascadeOnDelete();
            $table->foreign('organization_unit_id', 'vs_pay_links_org_fk')->references('id')->on('organization_units')->nullOnDelete();
            $table->foreign('job_card_id', 'vs_pay_links_job_fk')->references('id')->on('vehicle_service_job_cards')->cascadeOnDelete();
            $table->foreign('payment_id', 'vs_pay_links_payment_fk')->references('id')->on('payments')->cascadeOnDelete();
            $table->foreign('payment_allocation_id', 'vs_pay_links_alloc_fk')->references('id')->on('payment_allocations')->nullOnDelete();
        });

        Schema::create('vehicle_service_job_inventory_links', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('row_version')->default(1);
            $table->foreignId('tenant_id');
            $table->foreignId('organization_unit_id')->nullable();
            $table->foreignId('job_card_id');
            $table->foreignId('job_card_line_id')->nullable();
            $table->foreignId('stock_movement_id')->nullable();
            $table->string('movement_type');
            $table->decimal('quantity', 20, 4)->default(0);
            $table->decimal('quantity_base', 20, 4)->default(0);
            $table->decimal('unit_cost', 20, 4)->default(0);
            $table->decimal('total_cost', 20, 4)->default(0);
            $table->string('status')->default('posted');
            $table->foreignId('posted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('posted_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['tenant_id', 'job_card_id'], 'vehicle_service_inventory_links_job_idx');
            $table->index(['tenant_id', 'stock_movement_id'], 'vehicle_service_inventory_links_movement_idx');
            $table->index(['tenant_id', 'status'], 'vehicle_service_inventory_links_status_idx');

            $table->foreign('tenant_id', 'vs_inv_links_tenant_fk')->references('id')->on('tenants')->cascadeOnDelete();
            $table->foreign('organization_unit_id', 'vs_inv_links_org_fk')->references('id')->on('organization_units')->nullOnDelete();
            $table->foreign('job_card_id', 'vs_inv_links_job_fk')->references('id')->on('vehicle_service_job_cards')->cascadeOnDelete();
            $table->foreign('job_card_line_id', 'vs_inv_links_line_fk')->references('id')->on('vehicle_service_job_card_lines')->nullOnDelete();
            $table->foreign('stock_movement_id', 'vs_inv_links_movement_fk')->references('id')->on('stock_movements')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vehicle_service_job_inventory_links');
        Schema::dropIfExists('vehicle_service_job_payment_links');
    }
};
