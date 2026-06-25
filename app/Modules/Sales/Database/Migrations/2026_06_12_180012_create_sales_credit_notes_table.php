<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sales_credit_notes', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('organization_unit_id')->nullable()->constrained('organization_units')->nullOnDelete();
            $table->foreignId('customer_id')->constrained('customers')->restrictOnDelete();
            $table->foreignId('sales_return_id')->nullable()->constrained('sales_returns')->nullOnDelete();
            $table->string('credit_note_number');
            $table->date('credit_note_date');
            $table->string('status')->default('draft');
            $table->decimal('amount', 20, 6);
            $table->decimal('allocated_amount', 20, 6)->default('0.000000');
            $table->decimal('remaining_amount', 20, 6);
            $table->text('reason')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['tenant_id', 'credit_note_number'], 'sales_credit_notes_tenant_number_uk');
            $table->index(['tenant_id', 'organization_unit_id'], 'sales_credit_notes_scope_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sales_credit_notes');
    }
};
