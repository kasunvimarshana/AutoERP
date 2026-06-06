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
            $table->foreignId('organization_unit_id')->nullable()->constrained('organization_units', 'id')->nullOnDelete();
            $table->foreignId('payment_id')->constrained('payments', 'id')->cascadeOnDelete();
            $table->string('reversal_number', 100);
            $table->date('reversal_date');
            $table->text('reason');
            $table->unsignedBigInteger('reversed_by')->nullable();
            $table->decimal('original_amount', 20, 6);
            $table->decimal('reversed_amount', 20, 6);
            $table->string('status')->default('posted');
            $table->timestamps();

            $table->unique(['tenant_id', 'reversal_number'], 'payment_reversals_tenant_number_uk');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_reversals');
    }
};
