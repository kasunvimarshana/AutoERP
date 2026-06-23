<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_reversals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants', 'id')->cascadeOnDelete();
            $table->foreignId('organization_unit_id')->nullable();
            $table->foreignId('payment_id');
            $table->string('reversal_number', 100);
            $table->date('reversal_date');
            $table->text('reason');
            $table->unsignedBigInteger('reversed_by')->nullable();
            $table->decimal('original_amount', 20, 6);
            $table->decimal('reversed_amount', 20, 6);
            $table->foreignId('finance_reversal_journal_entry_id')->nullable();
            $table->string('status')->default('posted');
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->unique(['tenant_id', 'reversal_number'], 'payment_reversals_tenant_number_uk');
            $table->unique('payment_id', 'payment_reversals_payment_uk');

            $table->unique(['id', 'tenant_id'], 'payment_reversals_id_tenant_uk');
            $table->foreign(['organization_unit_id', 'tenant_id'], 'payment_reversals_organization_unit_id_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('organization_units')
                ->restrictOnDelete();
            $table->foreign(['payment_id', 'tenant_id'], 'payment_reversals_payment_id_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('payments')
                ->cascadeOnDelete();
            $table->foreign(['finance_reversal_journal_entry_id', 'tenant_id'], 'payment_reversals_finance_reversal_journal_entry_6e6b2c1d_fk')
                ->references(['id', 'tenant_id'])
                ->on('finance_journal_entries')
                ->restrictOnDelete();

            $table->foreign(['reversed_by', 'tenant_id'], 'payment_reversals_reversed_by_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('users')
                ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_reversals');
    }
};
