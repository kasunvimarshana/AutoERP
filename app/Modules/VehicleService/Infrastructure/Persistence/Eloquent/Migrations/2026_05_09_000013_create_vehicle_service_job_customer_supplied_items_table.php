<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vehicle_service_job_customer_supplied_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('row_version')->default(1);
            $table->foreignId('tenant_id')->constrained('tenants', 'id')->cascadeOnDelete();
            $table->foreignId('organization_unit_id')->nullable()->constrained('organization_units', 'id')->nullOnDelete();
            $table->json('metadata')->nullable();

            $table->foreignId('job_card_id')->constrained('vehicle_service_job_cards', 'id')->cascadeOnDelete();
            $table->string('reference')->nullable();
            $table->foreignId('item_id')->nullable()->constrained('items', 'id')->nullOnDelete();
            $table->string('item_name');
            $table->text('description')->nullable();
            $table->foreignId('uom_id')->nullable()->constrained('unit_of_measures', 'id')->nullOnDelete();
            $table->decimal('quantity', 20, 4)->default(0);
            $table->decimal('accepted_qty', 20, 4)->default(0);
            $table->decimal('used_qty', 20, 4)->default(0);
            $table->decimal('returned_qty', 20, 4)->default(0);
            $table->decimal('scrapped_qty', 20, 4)->default(0);
            $table->decimal('billable_amount', 20, 4)->default(0);
            $table->string('status')->default('received')->comment('received, partially_used, used, returned, scrapped');
            $table->boolean('is_billable')->default(false);
            $table->text('condition_notes')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['tenant_id', 'job_card_id'], 'vehicle_service_cs_items_job_card_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vehicle_service_job_customer_supplied_items');
    }
};
