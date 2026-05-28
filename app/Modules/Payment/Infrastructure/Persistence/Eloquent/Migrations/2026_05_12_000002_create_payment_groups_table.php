<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_groups', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('row_version')->default(1)->comment('Used for optimistic concurrency control');
            $table->foreignId('tenant_id')->constrained('tenants', 'id')->cascadeOnDelete()->comment('Multi-tenant owner reference');
            $table->foreignId('organization_unit_id')->nullable()->constrained('organization_units', 'id')->nullOnDelete()->comment('Branch or department ownership');
            $table->json('metadata')->nullable()->comment('Extensible custom dynamic data');

            $table->string('transaction_number')->nullable()->comment('optional consolidated reference');
            $table->string('group_type')->nullable()->comment('batch_supplier, batch_customer, cash_session, bank_deposit');
            $table->string('direction')->nullable()->comment('inbound, outbound');
            $table->decimal('total_amount', 20, 4)->default(0);
            $table->string('status')->default('draft')->comment('draft, posted, reconciled, voided');
            $table->string('reference')->nullable();

            $table->timestamps();

            $table->unique(['tenant_id', 'transaction_number'], 'payment_groups_transaction_number_uk');
            $table->index(['tenant_id', 'direction', 'status'], 'payment_groups_direction_status_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_groups');
    }
};
