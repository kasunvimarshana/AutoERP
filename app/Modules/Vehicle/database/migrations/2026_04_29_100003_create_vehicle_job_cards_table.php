<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vehicle_job_cards', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants', 'id')->cascadeOnDelete();
            $table->foreignId('vehicle_id')->constrained('vehicles', 'id')->cascadeOnDelete();
            $table->foreignId('customer_id')->nullable()->constrained('customers', 'id')->nullOnDelete();
            $table->foreignId('assigned_mechanic_id')->nullable()->constrained('users', 'id')->nullOnDelete();
            $table->string('job_card_no', 120);
            $table->enum('workflow_status', ['draft', 'scheduled', 'in_progress', 'awaiting_parts', 'quality_check', 'completed', 'cancelled'])->default('draft');
            $table->enum('service_type', ['maintenance', 'repair', 'inspection', 'accident', 'other'])->default('maintenance');
            $table->timestamp('scheduled_at')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->decimal('labor_cost_total', 20, 6)->default('0.000000');
            $table->decimal('parts_cost_total', 20, 6)->default('0.000000');
            $table->decimal('subtotal', 20, 6)->default('0.000000');
            $table->decimal('tax_amount', 20, 6)->default('0.000000');
            $table->decimal('grand_total', 20, 6)->default('0.000000');
            $table->text('notes')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['tenant_id', 'job_card_no'], 'vehicle_job_cards_tenant_job_card_no_uk');
            $table->index(['tenant_id', 'vehicle_id', 'workflow_status'], 'vehicle_job_cards_tenant_vehicle_status_idx');
            $table->index(['tenant_id', 'scheduled_at'], 'vehicle_job_cards_tenant_scheduled_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vehicle_job_cards');
    }
};
