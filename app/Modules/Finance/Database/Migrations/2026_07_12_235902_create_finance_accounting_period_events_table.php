<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const EVENT_TYPE_LENGTH = 30;

    private const STATUS_LENGTH = 20;

    public function up(): void
    {
        Schema::create('finance_accounting_period_events', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('organization_unit_id')->nullable();
            $table->unsignedBigInteger('accounting_period_id');
            $table->string('event_type', self::EVENT_TYPE_LENGTH);
            $table->string('from_status', self::STATUS_LENGTH)->nullable();
            $table->string('to_status', self::STATUS_LENGTH);
            $table->text('reason')->nullable();
            $table->unsignedBigInteger('actor_id')->nullable();
            $table->timestamp('occurred_at');
            $table->timestamps();

            $table->unique(
                ['id', 'tenant_id'],
                'finance_period_events_id_tenant_uq',
            );
            $table->index(
                ['tenant_id', 'accounting_period_id', 'occurred_at'],
                'finance_period_events_period_time_ix',
            );
            $table->foreign('tenant_id', 'finance_period_events_tenant_fk')
                ->references('id')
                ->on('tenants')
                ->restrictOnDelete();
            $table->foreign(
                ['organization_unit_id', 'tenant_id'],
                'finance_period_events_org_tenant_fk',
            )
                ->references(['id', 'tenant_id'])
                ->on('organization_units')
                ->restrictOnDelete();
            $table->foreign(
                ['accounting_period_id', 'tenant_id'],
                'finance_period_events_period_tenant_fk',
            )
                ->references(['id', 'tenant_id'])
                ->on('finance_accounting_periods')
                ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('finance_accounting_period_events');
    }
};
