<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const TENANT_SCOPE_KEY = 0;

    private const CODE_LENGTH = 50;

    private const NAME_LENGTH = 150;

    private const STATUS_LENGTH = 20;

    public function up(): void
    {
        Schema::create('finance_accounting_periods', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('organization_unit_id')->nullable();
            $table->unsignedBigInteger('organization_scope_key')->default(self::TENANT_SCOPE_KEY);
            $table->string('code', self::CODE_LENGTH);
            $table->string('name', self::NAME_LENGTH);
            $table->date('start_date');
            $table->date('end_date');
            $table->string('status', self::STATUS_LENGTH);
            $table->unsignedBigInteger('row_version')->default(1);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();

            $table->unique(
                ['id', 'tenant_id'],
                'finance_periods_id_tenant_uq',
            );
            $table->unique(
                ['tenant_id', 'organization_scope_key', 'code'],
                'finance_periods_scope_code_uq',
            );
            $table->index(
                ['tenant_id', 'organization_scope_key', 'start_date', 'end_date'],
                'finance_periods_scope_dates_ix',
            );
            $table->foreign('tenant_id', 'finance_periods_tenant_fk')
                ->references('id')
                ->on('tenants')
                ->restrictOnDelete();
            $table->foreign(
                ['organization_unit_id', 'tenant_id'],
                'finance_periods_org_tenant_fk',
            )
                ->references(['id', 'tenant_id'])
                ->on('organization_units')
                ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('finance_accounting_periods');
    }
};
