<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vehicle_rental_extra_charges', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('row_version')->default(1);
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('organization_unit_id')->nullable()->constrained('organization_units')->nullOnDelete();
            $table->foreignId('agreement_id')->nullable()->constrained('vehicle_rental_agreements')->cascadeOnDelete();
            $table->foreignId('running_chart_id')
                ->nullable()
                ->constrained('vehicle_rental_running_charts')
                ->cascadeOnDelete();
            $table->foreignId('running_chart_line_id')
                ->nullable()
                ->constrained('vehicle_rental_running_chart_lines')
                ->cascadeOnDelete();
            $table->foreignId('item_id')->nullable()->constrained('items')->nullOnDelete();
            $table->foreignId('uom_id')->nullable()->constrained('unit_of_measures')->nullOnDelete();
            $table->foreignId('tax_group_id')->nullable()->constrained('tax_groups')->nullOnDelete();
            $table->string('charge_scope')->default('customer')->comment('customer, provider, internal');
            $table->string('charge_type');
            $table->string('status')->default('draft');
            $table->date('charge_date');
            $table->string('description');
            $table->decimal('quantity', 20, 4)->default(0);
            $table->decimal('unit_amount', 20, 4)->default(0);
            $table->decimal('discount_amount', 20, 4)->default(0);
            $table->decimal('tax_amount', 20, 4)->default(0);
            $table->decimal('total_amount', 20, 4)->default(0);
            $table->boolean('is_billable')->default(true);
            $table->boolean('is_payable')->default(false);
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->dateTime('approved_at')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['tenant_id', 'agreement_id'], 'vehicle_rental_extra_charges_agreement_idx');
            $table->index(['tenant_id', 'running_chart_id'], 'vehicle_rental_extra_charges_chart_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vehicle_rental_extra_charges');
    }
};
