<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rental_security_deposits', function (Blueprint $table) {
            $table->id();

            $table->foreignId('tenant_id')->constrained('tenants', 'id')->cascadeOnDelete();
            $table->foreignId('org_unit_id')->nullable()->constrained('org_units', 'id')->nullOnDelete();
            $table->unsignedBigInteger('row_version')->default(1)->comment('Used for optimistic concurrency control');

            $table->foreignId('booking_id')->constrained('rental_bookings')->cascadeOnDelete();
            $table->decimal('deposit_amount', 20, 6);
            $table->foreignId('payment_id')->nullable()->constrained('payments')->nullOnDelete();
            $table->enum('hold_type', ['pre_auth','full_capture'])->default('full_capture');
            $table->dateTime('collected_at')->nullable();
            $table->dateTime('released_at')->nullable();
            $table->dateTime('forfeited_at')->nullable();
            $table->decimal('forfeited_amount', 20, 6)->nullable();
            $table->decimal('refunded_amount', 20, 6)->nullable();
            $table->foreignId('refund_payment_id')->nullable()->constrained('payments')->nullOnDelete();
            $table->enum('status', ['pending','collected','partially_released','released','forfeited'])->default('pending');
            $table->foreignId('liability_account_id')->nullable()->constrained('accounts')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rental_security_deposits');
    }
};
