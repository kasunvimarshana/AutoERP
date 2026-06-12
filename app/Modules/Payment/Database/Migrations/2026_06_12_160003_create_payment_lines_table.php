<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants', 'id')->cascadeOnDelete();
            $table->foreignId('organization_unit_id')->nullable()->constrained('organization_units', 'id')->nullOnDelete();
            $table->foreignId('payment_id')->constrained('payments', 'id')->cascadeOnDelete();
            $table->foreignId('payment_method_id')->nullable()->constrained('payment_methods', 'id')->nullOnDelete();
            $table->string('reference_number')->nullable();
            $table->decimal('amount', 20, 6);
            $table->decimal('cleared_amount', 20, 6)->default('0');
            $table->string('status')->default('pending');
            $table->text('notes')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index('payment_id', 'payment_lines_payment_idx');
            $table->index(['payment_method_id', 'status'], 'payment_lines_method_status_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_lines');
    }
};
