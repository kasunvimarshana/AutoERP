<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vehicle_finance_status_histories', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('row_version')->default(1);
            $table->foreignId('tenant_id')->constrained('tenants', indexName: 'vehicle_finance_status_histories_tenant_fk')->restrictOnDelete();
            $table->foreignId('organization_unit_id')->nullable();
            $table->json('metadata')->nullable();
            $table->foreignId('finance_agreement_id')->nullable();
            $table->foreignId('installment_id')->nullable();
            $table->string('old_status', 30)->nullable();
            $table->string('new_status', 30);
            $table->text('reason')->nullable();
            $table->unsignedBigInteger('changed_by')->nullable();
            $table->dateTime('changed_at');
            $table->timestamps();

            $table->index(['finance_agreement_id', 'changed_at'], 'vehicle_finance_history_agreement_ix');
            $table->index(['installment_id', 'changed_at'], 'vehicle_finance_history_installment_ix');

            $table->unique(['id', 'tenant_id'], 'vehicle_finance_status_histories_id_tenant_uk');
            $table->foreign(['organization_unit_id', 'tenant_id'], 'vehicle_finance_status_histories_org_unit_fk')
                ->references(['id', 'tenant_id'])
                ->on('organization_units')
                ->restrictOnDelete();
            $table->foreign(['finance_agreement_id', 'tenant_id'], 'vehicle_finance_status_histories_fin_agreement_fk')
                ->references(['id', 'tenant_id'])
                ->on('vehicle_finance_agreements')
                ->restrictOnDelete();
            $table->foreign(['installment_id', 'tenant_id'], 'vehicle_finance_status_histories_installment_id_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('vehicle_finance_installments')
                ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vehicle_finance_status_histories');
    }
};
