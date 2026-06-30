<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rental_usage_facts', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('row_version')->default(1);
            $table->foreignId('tenant_id')->constrained('tenants', indexName: 'rental_usage_facts_tenant_fk')->restrictOnDelete();
            $table->foreignId('organization_unit_id')->nullable();
            $table->json('metadata')->nullable();
            $table->foreignId('usage_context_id');
            $table->foreignId('usage_log_id');
            $table->string('financial_side', 20);
            $table->dateTime('started_at');
            $table->dateTime('ended_at');
            $table->decimal('start_odometer', 20, 6);
            $table->decimal('end_odometer', 20, 6);
            $table->decimal('commercial_distance_km', 20, 6);
            $table->unsignedInteger('working_minutes')->default(0);
            $table->unsignedInteger('normal_overtime_minutes')->default(0);
            $table->unsignedInteger('double_overtime_minutes')->default(0);
            $table->unsignedInteger('triple_overtime_minutes')->default(0);
            $table->decimal('night_out_count', 20, 6)->default('0.000000');
            $table->string('reference_number', 100)->nullable();
            $table->text('variance_reason')->nullable();
            $table->string('status', 30)->default('draft');
            $table->unsignedBigInteger('submitted_by')->nullable();
            $table->dateTime('submitted_at')->nullable();
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->dateTime('approved_at')->nullable();
            $table->unsignedBigInteger('rejected_by')->nullable();
            $table->dateTime('rejected_at')->nullable();
            $table->unsignedBigInteger('reversed_by')->nullable();
            $table->dateTime('reversed_at')->nullable();
            $table->text('reversal_reason')->nullable();
            $table->text('remarks')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();

            $table->unique('usage_context_id', 'rental_usage_facts_context_uk');
            $table->unique(['usage_log_id', 'financial_side'], 'rental_usage_facts_log_side_uk');
            $table->index(['tenant_id', 'financial_side', 'status'], 'rental_usage_facts_side_status_ix');
            $table->unique(['id', 'tenant_id'], 'rental_usage_facts_id_tenant_uk');

            $table->foreign(['organization_unit_id', 'tenant_id'], 'rental_usage_facts_organization_unit_id_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('organization_units')
                ->restrictOnDelete();
            $table->foreign(['usage_context_id', 'tenant_id'], 'rental_usage_facts_usage_context_id_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('rental_usage_contexts')
                ->restrictOnDelete();
            $table->foreign(['usage_log_id', 'tenant_id'], 'rental_usage_facts_usage_log_id_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('rental_usage_logs')
                ->restrictOnDelete();
            $table->foreign(['submitted_by', 'tenant_id'], 'rental_usage_facts_submitted_by_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('users')
                ->restrictOnDelete();
            $table->foreign(['approved_by', 'tenant_id'], 'rental_usage_facts_approved_by_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('users')
                ->restrictOnDelete();
            $table->foreign(['rejected_by', 'tenant_id'], 'rental_usage_facts_rejected_by_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('users')
                ->restrictOnDelete();
            $table->foreign(['reversed_by', 'tenant_id'], 'rental_usage_facts_reversed_by_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('users')
                ->restrictOnDelete();
            $table->foreign(['created_by', 'tenant_id'], 'rental_usage_facts_created_by_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('users')
                ->restrictOnDelete();
            $table->foreign(['updated_by', 'tenant_id'], 'rental_usage_facts_updated_by_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('users')
                ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rental_usage_facts');
    }
};
