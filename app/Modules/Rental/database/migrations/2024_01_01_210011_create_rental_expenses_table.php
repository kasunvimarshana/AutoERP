<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rental_expenses', function (Blueprint $table) {
            $table->id();

            $table->foreignId('tenant_id')->constrained('tenants', 'id')->cascadeOnDelete();
            $table->foreignId('org_unit_id')->nullable()->constrained('org_units', 'id')->nullOnDelete();
            $table->unsignedBigInteger('row_version')->default(1)->comment('Used for optimistic concurrency control');

            $table->foreignId('booking_id')->nullable()->constrained('rental_bookings')->nullOnDelete();
            $table->string('expense_type');
            $table->decimal('amount', 20, 6);
            $table->foreignId('currency_id')->constrained('currencies');
            $table->date('expense_date');
            $table->text('description')->nullable();
            $table->string('paid_by');
            $table->boolean('reimbursable')->default(false);
            $table->enum('reimbursement_status', ['pending','approved','paid','not_applicable'])->default('not_applicable');
            $table->foreignId('expense_account_id')->constrained('accounts');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rental_expenses');
    }
};
