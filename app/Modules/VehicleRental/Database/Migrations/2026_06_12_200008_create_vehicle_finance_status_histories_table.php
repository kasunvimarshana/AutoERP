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
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('organization_unit_id')->nullable()->constrained('organization_units')->nullOnDelete();
            $table->json('metadata')->nullable();
            $table->foreignId('finance_agreement_id')->nullable()->constrained('vehicle_finance_agreements')->cascadeOnDelete();
            $table->foreignId('installment_id')->nullable()->constrained('vehicle_finance_installments')->cascadeOnDelete();
            $table->string('old_status', 30)->nullable();
            $table->string('new_status', 30);
            $table->text('reason')->nullable();
            $table->unsignedBigInteger('changed_by')->nullable();
            $table->dateTime('changed_at');
            $table->timestamps();

            $table->index(['finance_agreement_id', 'changed_at'], 'vehicle_finance_history_agreement_idx');
            $table->index(['installment_id', 'changed_at'], 'vehicle_finance_history_installment_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vehicle_finance_status_histories');
    }
};
