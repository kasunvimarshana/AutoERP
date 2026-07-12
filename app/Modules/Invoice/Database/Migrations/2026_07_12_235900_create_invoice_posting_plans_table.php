<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const PROFILE_CODE_LENGTH = 100;

    private const STATUS_LENGTH = 30;

    private const REFERENCE_LENGTH = 100;

    public function up(): void
    {
        Schema::create('invoice_posting_plans', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('organization_unit_id')->nullable();
            $table->unsignedBigInteger('invoice_id');
            $table->string('posting_profile_code', self::PROFILE_CODE_LENGTH);
            $table->date('posting_date');
            $table->json('lines');
            $table->string('status', self::STATUS_LENGTH);
            $table->string('finance_posting_reference', self::REFERENCE_LENGTH)->nullable();
            $table->string('finance_reversal_reference', self::REFERENCE_LENGTH)->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('posted_by')->nullable();
            $table->timestamp('posted_at')->nullable();
            $table->unsignedBigInteger('reversed_by')->nullable();
            $table->timestamp('reversed_at')->nullable();
            $table->text('reversal_reason')->nullable();
            $table->unsignedBigInteger('row_version')->default(1);
            $table->timestamps();

            $table->unique(
                ['tenant_id', 'invoice_id'],
                'invoice_posting_plans_tenant_invoice_uq',
            );
            $table->index(
                ['tenant_id', 'organization_unit_id', 'status'],
                'invoice_posting_plans_scope_status_ix',
            );
            $table->foreign('tenant_id', 'invoice_posting_plans_tenant_fk')
                ->references('id')
                ->on('tenants')
                ->restrictOnDelete();
            $table->foreign(
                ['organization_unit_id', 'tenant_id'],
                'invoice_posting_plans_org_tenant_fk',
            )
                ->references(['id', 'tenant_id'])
                ->on('organization_units')
                ->restrictOnDelete();
            $table->foreign(
                ['invoice_id', 'tenant_id'],
                'invoice_posting_plans_invoice_tenant_fk',
            )
                ->references(['id', 'tenant_id'])
                ->on('invoices')
                ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoice_posting_plans');
    }
};
