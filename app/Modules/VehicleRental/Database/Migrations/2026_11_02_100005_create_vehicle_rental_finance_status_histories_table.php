<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vehicle_rental_finance_status_histories', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->restrictOnDelete();
            $table->foreignId('organization_unit_id');
            $table->foreignId('finance_agreement_id')->nullable();
            $table->foreignId('installment_id')->nullable();
            $table->string('old_status', 30)->nullable();
            $table->string('new_status', 30);
            $table->text('reason')->nullable();
            $table->unsignedBigInteger('changed_by')->nullable();
            $table->dateTime('changed_at');
            $table->timestamps();

            $table->index(['finance_agreement_id', 'changed_at'], 'vr_fin_hist_agree_ix');
            $table->index(['installment_id', 'changed_at'], 'vr_fin_hist_inst_ix');

            $table->unique(['id', 'tenant_id'], 'vr_fin_hist_id_tenant_uk');
            $table->foreign(['organization_unit_id', 'tenant_id'], 'vr_fin_hist_org_fk')
                ->references(['id', 'tenant_id'])
                ->on('organization_units')
                ->restrictOnDelete();
            $table->foreign(['finance_agreement_id', 'tenant_id'], 'vr_fin_hist_agree_fk')
                ->references(['id', 'tenant_id'])
                ->on('vehicle_rental_finance_agreements')
                ->restrictOnDelete();
            $table->foreign(['installment_id', 'tenant_id'], 'vr_fin_hist_inst_fk')
                ->references(['id', 'tenant_id'])
                ->on('vehicle_rental_finance_installments')
                ->restrictOnDelete();
            $table->foreign(['changed_by', 'tenant_id'], 'vr_fin_hist_user_fk')
                ->references(['id', 'tenant_id'])
                ->on('users')
                ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vehicle_rental_finance_status_histories');
    }
};
