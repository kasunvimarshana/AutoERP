<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vehicle_service_settings', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('row_version')->default(1);
            $table->foreignId('tenant_id')->constrained('tenants', 'id')->cascadeOnDelete();
            $table->foreignId('organization_unit_id')->nullable()->constrained('organization_units', 'id')->nullOnDelete();
            $table->json('metadata')->nullable();

            $table->boolean('is_active')->default(true);
            $table->boolean('enable_inventory_reservation')->default(true);
            $table->boolean('enable_invoice_generation')->default(true);
            $table->boolean('enable_payment_allocation')->default(true);
            $table->boolean('enable_finance_posting')->default(true);
            $table->boolean('allow_negative_stock_for_service')->default(false);
            $table->unsignedInteger('default_service_due_days')->default(0);
            $table->string('default_priority')->default('medium');
            $table->string('auto_invoice_trigger_status')->default('completed');
            $table->string('inventory_posting_trigger_status')->default('completed');
            $table->string('service_invoice_type')->default('invoice');
            $table->string('service_credit_type')->default('credit_adjustment');
            $table->string('service_number_prefix')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->unique(
                ['tenant_id', 'organization_unit_id'],
                'vehicle_service_settings_tenant_org_unit_uk',
            );
            $table->index(['tenant_id', 'is_active'], 'vehicle_service_settings_active_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vehicle_service_settings');
    }
};
