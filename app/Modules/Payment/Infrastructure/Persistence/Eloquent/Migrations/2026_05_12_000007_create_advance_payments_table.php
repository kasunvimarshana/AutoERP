<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('advance_payments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('row_version')->default(1)->comment('Used for optimistic concurrency control');
            $table->foreignId('tenant_id')->constrained('tenants', 'id')->cascadeOnDelete()->comment('Multi-tenant owner reference');
            $table->foreignId('organization_unit_id')->nullable()->constrained('organization_units', 'id')->nullOnDelete()->comment('Branch or department ownership');
            $table->json('metadata')->nullable()->comment('Extensible custom dynamic data');

            $table->string('party_type');
            $table->unsignedBigInteger('party_id');
            $table->string('reference')->nullable();
            $table->string('advance_number');
            $table->decimal('amount', 20, 4);
            $table->foreignId('currency_id')->nullable()->constrained('currencies')->nullOnDelete();
            $table->decimal('exchange_rate', 20, 4)->default(1);
            $table->decimal('base_amount', 20, 4)->nullable();
            $table->decimal('remaining_amount', 20, 4);
            $table->date('advance_date');
            $table->string('type')->nullable()->comment('customer, supplier');
            $table->string('status')->default('open')->comment('open, partially_applied, fully_applied, refunded');
            $table->foreignId('payment_id')->nullable()->constrained('payments')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->foreignId('refunded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('cancelled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('refunded_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->unique(['tenant_id', 'advance_number'], 'advance_payments_advance_number_uk');
            $table->index(['tenant_id', 'party_type', 'party_id', 'status'], 'advance_payments_party_status_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('advance_payments');
    }
};
