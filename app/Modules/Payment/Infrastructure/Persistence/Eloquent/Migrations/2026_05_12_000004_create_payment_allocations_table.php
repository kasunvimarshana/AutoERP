<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_allocations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('row_version')->default(1)->comment('Used for optimistic concurrency control');
            $table->foreignId('tenant_id')->constrained('tenants', 'id')->cascadeOnDelete()->comment('Multi-tenant owner reference');
            $table->foreignId('organization_unit_id')->nullable()->constrained('organization_units', 'id')->nullOnDelete()->comment('Branch or department ownership');
            $table->json('metadata')->nullable()->comment('Extensible custom dynamic data');

            $table->foreignId('payment_id')->constrained('payments')->cascadeOnDelete();
            $table->string('document_type')->comment('the invoice/credit note being settled');
            $table->unsignedBigInteger('document_id');
            $table->string('reference')->nullable();
            $table->decimal('allocated_amount', 20, 4);

            $table->timestamps();

            $table->unique(['tenant_id', 'payment_id', 'document_type', 'document_id'], 'payment_allocations_payment_document_uk');
            $table->index(['tenant_id', 'document_type', 'document_id'], 'payment_allocations_document_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_allocations');
    }
};
