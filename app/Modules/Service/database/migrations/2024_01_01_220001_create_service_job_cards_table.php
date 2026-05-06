<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('service_job_cards', function (Blueprint $table) {
            $table->id();

            $table->foreignId('tenant_id')->constrained('tenants', 'id')->cascadeOnDelete();
            $table->foreignId('org_unit_id')->nullable()->constrained('org_units', 'id')->nullOnDelete();
            $table->unsignedBigInteger('row_version')->default(1)->comment('Used for optimistic concurrency control');

            $table->string('job_card_number');
            $table->foreignId('vehicle_id')->constrained('vehicles');
            $table->foreignId('customer_id')->nullable()->constrained('customers')->nullOnDelete();
            $table->enum('service_type', ['preventive','corrective','diagnostic','recall','warranty'])->default('corrective');
            $table->enum('priority', ['low','medium','high','critical'])->default('medium');
            $table->enum('status', ['open','in_progress','waiting_parts','completed','invoiced','cancelled'])->default('open');
            $table->text('reported_issue')->nullable();
            $table->text('diagnostic_notes')->nullable();
            $table->text('resolution_notes')->nullable();
            $table->dateTime('start_datetime')->nullable();
            $table->dateTime('completed_datetime')->nullable();
            $table->decimal('estimated_hours', 8, 2)->nullable();
            $table->decimal('actual_hours', 8, 2)->nullable();
            $table->decimal('labor_total', 20, 6)->default(0);
            $table->decimal('parts_total', 20, 6)->default(0);
            $table->decimal('non_inventory_total', 20, 6)->default(0);
            $table->decimal('tax_total', 20, 6)->default(0);
            $table->decimal('discount_total', 20, 6)->default(0);
            $table->decimal('grand_total', 20, 6)->default(0);
            $table->foreignId('currency_id')->constrained('currencies');
            $table->boolean('warranty_eligible')->default(false);
            $table->foreignId('assigned_to')->nullable()->constrained('employees')->nullOnDelete();
            $table->foreignId('created_by')->constrained('users');
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->unique(['tenant_id','org_unit_id','job_card_number'], 'job_cards_number_uk');
            $table->index(['tenant_id','status'], 'job_cards_status_idx');
            $table->index(['vehicle_id','start_datetime'], 'job_cards_vehicle_date_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('service_job_cards');
    }
};
