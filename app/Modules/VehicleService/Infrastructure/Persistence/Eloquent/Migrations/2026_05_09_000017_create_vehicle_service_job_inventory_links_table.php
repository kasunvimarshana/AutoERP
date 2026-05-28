<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vehicle_service_job_inventory_links', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('row_version')->default(1);
            $table->foreignId('tenant_id')->constrained('tenants', 'id')->cascadeOnDelete();
            $table->foreignId('organization_unit_id')->nullable()->constrained('organization_units', 'id')->nullOnDelete();
            $table->json('metadata')->nullable();

            $table->foreignId('job_card_id')->constrained('vehicle_service_job_cards', 'id')->cascadeOnDelete();
            $table->unsignedBigInteger('job_card_line_id')->nullable();
            $table->unsignedBigInteger('stock_movement_id')->nullable();
            $table->string('movement_type')->comment('reserve, consume, return, reverse');
            $table->decimal('quantity', 20, 4)->default(0);
            $table->decimal('quantity_base', 20, 4)->default(0);
            $table->decimal('unit_cost', 20, 4)->default(0);
            $table->decimal('total_cost', 20, 4)->default(0);
            $table->string('status')->default('posted')->comment('posted, reversed');
            $table->unsignedBigInteger('posted_by')->nullable();
            $table->dateTime('posted_at');

            $table->timestamps();
            $table->softDeletes();

            $table->index(['tenant_id', 'job_card_id'], 'vehicle_service_inventory_links_job_card_idx');
            $table->index(['tenant_id', 'stock_movement_id'], 'vehicle_service_inventory_links_movement_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vehicle_service_job_inventory_links');
    }
};
