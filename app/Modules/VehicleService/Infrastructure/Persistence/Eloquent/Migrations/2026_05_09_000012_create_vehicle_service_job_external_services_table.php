<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vehicle_service_job_external_services', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('row_version')->default(1);
            $table->foreignId('tenant_id')->constrained('tenants', 'id')->cascadeOnDelete();
            $table->foreignId('organization_unit_id')->nullable()->constrained('organization_units', 'id')->nullOnDelete();
            $table->json('metadata')->nullable();

            $table->foreignId('job_card_id')->constrained('vehicle_service_job_cards', 'id')->cascadeOnDelete();
            $table->string('reference')->nullable();
            $table->string('vendor_name');
            $table->foreignId('supplier_id')->nullable()->constrained('suppliers', 'id')->nullOnDelete();
            $table->string('service_name');
            $table->text('description')->nullable();
            $table->foreignId('uom_id')->nullable()->constrained('unit_of_measures', 'id')->nullOnDelete();
            $table->decimal('quantity', 20, 4)->default(1);
            $table->decimal('unit_price', 20, 4)->default(0);
            $table->decimal('cost_amount', 20, 4)->default(0);
            $table->decimal('tax_amount', 20, 4)->default(0);
            $table->decimal('discount_amount', 20, 4)->default(0);
            $table->decimal('line_total', 20, 4)->default(0);
            $table->string('status')->default('planned')->comment('planned, ordered, received, billed, cancelled');
            $table->dateTime('expected_at')->nullable();
            $table->dateTime('received_at')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['tenant_id', 'job_card_id'], 'vehicle_service_ext_services_job_card_idx');
            $table->index(['tenant_id', 'status'], 'vehicle_service_ext_services_status_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vehicle_service_job_external_services');
    }
};
