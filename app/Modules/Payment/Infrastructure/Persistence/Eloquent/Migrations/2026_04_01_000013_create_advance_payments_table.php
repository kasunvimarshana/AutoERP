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
            $table->foreignId('tenant_id')->constrained('tenants', 'id')->cascadeOnDelete();
            $table->foreignId('organization_unit_id')->nullable()->constrained('organization_units', 'id')->nullOnDelete();
            $table->json('metadata')->nullable();

            $table->foreignId('party_id')->constrained('parties');
            $table->string('advance_number');
            $table->decimal('amount', 20, 4);
            $table->decimal('remaining_amount', 20, 4);
            $table->date('advance_date');
            $table->string('type')->default('customer')->comment('customer, supplier');
            $table->string('status')->default('open')->comment('open, partially_applied, fully_applied, refunded');
            $table->foreignId('payment_id')->nullable()->constrained('payments')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->unique(['tenant_id', 'organization_unit_id', 'advance_number'], 'advance_payments_advance_number_uk');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('advance_payments');
    }
};
