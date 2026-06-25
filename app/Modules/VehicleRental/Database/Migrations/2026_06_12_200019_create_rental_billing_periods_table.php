<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rental_billing_periods', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('row_version')->default(1);
            $table->foreignId('tenant_id')->constrained('tenants')->restrictOnDelete();
            $table->foreignId('organization_unit_id')->nullable();
            $table->json('metadata')->nullable();
            $table->foreignId('agreement_id');
            $table->string('financial_side', 20);
            $table->foreignId('rate_version_id');
            $table->dateTime('period_start');
            $table->dateTime('period_end');
            $table->string('billing_cycle_key', 100);
            $table->unsignedInteger('period_sequence');
            $table->string('status', 30)->default('open');
            $table->boolean('is_final')->default(false);
            $table->char('fingerprint', 64);
            $table->unsignedBigInteger('closed_by')->nullable();
            $table->dateTime('closed_at')->nullable();
            $table->unsignedBigInteger('reopened_by')->nullable();
            $table->dateTime('reopened_at')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();

            $table->unique(['tenant_id', 'fingerprint'], 'rental_billing_periods_fingerprint_uk');
            $table->unique(['agreement_id', 'financial_side', 'rate_version_id', 'period_start', 'period_end'], 'rental_billing_periods_period_uk');
            $table->index(['agreement_id', 'financial_side', 'status', 'period_start'], 'rental_billing_periods_agreement_side_idx');

            $table->unique(['id', 'tenant_id'], 'rental_billing_periods_id_tenant_uk');
            $table->foreign(['organization_unit_id', 'tenant_id'], 'rental_billing_periods_organization_unit_id_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('organization_units')
                ->restrictOnDelete();
            $table->foreign(['agreement_id', 'tenant_id'], 'rental_billing_periods_agreement_id_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('rental_agreements')
                ->cascadeOnDelete();
            $table->foreign(['rate_version_id', 'tenant_id'], 'rental_billing_periods_rate_version_id_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('rental_agreement_rate_versions')
                ->restrictOnDelete();

            $table->foreign(['closed_by', 'tenant_id'], 'rental_billing_periods_closed_by_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('users')
                ->restrictOnDelete();
            $table->foreign(['created_by', 'tenant_id'], 'rental_billing_periods_created_by_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('users')
                ->restrictOnDelete();
            $table->foreign(['updated_by', 'tenant_id'], 'rental_billing_periods_updated_by_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('users')
                ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rental_billing_periods');
    }
};
