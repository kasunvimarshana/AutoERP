<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rental_revenue_splits', function (Blueprint $table) {
            $table->id();

            $table->foreignId('tenant_id')->constrained('tenants', 'id')->cascadeOnDelete();
            $table->foreignId('org_unit_id')->nullable()->constrained('org_units', 'id')->nullOnDelete();
            $table->unsignedBigInteger('row_version')->default(1)->comment('Used for optimistic concurrency control');

            $table->foreignId('booking_id')->constrained('rental_bookings')->cascadeOnDelete();
            $table->string('recipient_type');
            $table->unsignedBigInteger('recipient_id');
            $table->decimal('split_percentage', 7, 4);
            $table->decimal('split_amount', 20, 6);
            $table->string('calculation_basis');
            $table->enum('status', ['calculated','approved','paid'])->default('calculated');
            $table->foreignId('journal_entry_id')->nullable()->constrained('journal_entries')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rental_revenue_splits');
    }
};
