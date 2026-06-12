<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_refunds', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants', 'id')->cascadeOnDelete();
            $table->foreignId('organization_unit_id')->nullable()->constrained('organization_units', 'id')->nullOnDelete();
            $table->foreignId('payment_id')->constrained('payments', 'id')->cascadeOnDelete();
            $table->string('refund_number', 100);
            $table->date('refund_date');
            $table->string('party_type')->nullable();
            $table->unsignedBigInteger('party_id')->nullable();
            $table->foreignId('payment_method_id')->nullable()->constrained('payment_methods', 'id')->nullOnDelete();
            $table->decimal('amount', 20, 6);
            $table->text('reason')->nullable();
            $table->string('status')->default('posted');
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['tenant_id', 'refund_number'], 'payment_refunds_tenant_number_uk');
            $table->index('payment_id', 'payment_refunds_payment_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_refunds');
    }
};
