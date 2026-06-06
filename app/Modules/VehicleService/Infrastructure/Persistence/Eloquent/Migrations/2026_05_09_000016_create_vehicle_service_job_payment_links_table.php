<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vehicle_service_job_payment_links', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('row_version')->default(1);
            $table->foreignId('tenant_id')->constrained('tenants', 'id')->cascadeOnDelete();
            $table->foreignId('organization_unit_id')->nullable()->constrained('organization_units', 'id')->nullOnDelete();
            $table->json('metadata')->nullable();

            $table->foreignId('job_card_id')->constrained('vehicle_service_job_cards', 'id')->cascadeOnDelete();
            $table->unsignedBigInteger('payment_id')->nullable();
            $table->unsignedBigInteger('payment_allocation_id')->nullable();
            $table->unsignedBigInteger('advance_payment_allocation_id')->nullable();
            $table->decimal('allocated_amount', 20, 4)->default(0);
            $table->decimal('advance_amount', 20, 4)->default(0);
            $table->decimal('refund_amount', 20, 4)->default(0);
            $table->decimal('write_off_amount', 20, 4)->default(0);
            $table->string('status')->default('active')->comment('active, voided, reversed');
            $table->unsignedBigInteger('linked_by')->nullable();
            $table->dateTime('linked_at');

            $table->timestamps();
            $table->softDeletes();

            $table->index(['tenant_id', 'job_card_id'], 'vehicle_service_payment_links_job_card_idx');
            $table->index(['tenant_id', 'payment_id'], 'vehicle_service_payment_links_payment_idx');
            $table->unique(
                ['tenant_id', 'job_card_id', 'payment_allocation_id'],
                'vehicle_service_payment_links_allocation_uk'
            );
            $table->unique(
                ['tenant_id', 'job_card_id', 'advance_payment_allocation_id'],
                'vehicle_service_payment_links_advance_allocation_uk'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vehicle_service_job_payment_links');
    }
};
