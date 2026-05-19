<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('advance_payment_allocations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('row_version')->default(1)->comment('Used for optimistic concurrency control');
            $table->foreignId('tenant_id')->constrained('tenants', 'id')->cascadeOnDelete();
            $table->foreignId('organization_unit_id')->nullable()->constrained('organization_units', 'id')->nullOnDelete();
            $table->json('metadata')->nullable();

            $table->foreignId('advance_payment_id')->constrained('advance_payments')->cascadeOnDelete();
            $table->foreignId('document_type');
            $table->unsignedBigInteger('document_id');
            $table->string('reference')->nullable();
            $table->decimal('allocated_amount', 20, 4);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('advance_payment_allocations');
    }
};
