<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_lines', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('row_version')->default(1);
            $table->foreignId('tenant_id')->constrained('tenants', 'id', indexName: 'payment_lines_tenant_fk')->restrictOnDelete();
            $table->foreignId('organization_unit_id')->nullable();
            $table->foreignId('payment_id');
            $table->unsignedInteger('line_number');
            $table->foreignId('payment_method_id');
            $table->string('payment_method_code_snapshot', 100);
            $table->string('payment_method_name_snapshot');
            $table->string('payment_method_type_snapshot', 40);
            $table->boolean('requires_reference_snapshot')->default(false);
            $table->boolean('requires_instrument_details_snapshot')->default(false);
            $table->string('reference_number')->nullable();
            $table->decimal('amount', 20, 6);
            $table->decimal('cleared_amount', 20, 6)->default('0');
            $table->string('status')->default('pending');
            $table->string('instrument_direction', 20)->nullable();
            $table->string('external_bank_name')->nullable();
            $table->string('external_bank_branch')->nullable();
            $table->string('instrument_number', 150)->nullable();
            $table->date('instrument_date')->nullable();
            $table->date('deposit_date')->nullable();
            $table->date('realized_date')->nullable();
            $table->date('clearing_date')->nullable();
            $table->date('bounced_date')->nullable();
            $table->date('returned_date')->nullable();
            $table->text('cancellation_reason')->nullable();
            $table->text('notes')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->unique(['payment_id', 'line_number'], 'payment_lines_payment_number_uk');
            $table->index(['payment_method_id', 'status'], 'payment_lines_method_status_ix');
            $table->index(['instrument_number', 'external_bank_name'], 'payment_lines_instrument_ix');

            $table->unique(['id', 'tenant_id'], 'payment_lines_id_tenant_uk');
            $table->foreign(['organization_unit_id', 'tenant_id'], 'payment_lines_organization_unit_id_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('organization_units')
                ->restrictOnDelete();
            $table->foreign(['payment_id', 'tenant_id'], 'payment_lines_payment_id_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('payments')
                ->cascadeOnDelete();
            $table->foreign(['payment_method_id', 'tenant_id'], 'payment_lines_payment_method_id_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('payment_methods')
                ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_lines');
    }
};
