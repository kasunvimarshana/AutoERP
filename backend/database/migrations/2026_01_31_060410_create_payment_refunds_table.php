<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_refunds', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('payment_id')->index();
            $table->string('refund_number', 100)->unique();
            $table->date('refund_date')->index();
            $table->decimal('amount', 12, 2)->default(0);
            $table->text('reason')->nullable();
            $table->enum('status', ['pending', 'completed', 'failed'])->default('pending')->index();
            $table->uuid('processed_by')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('payment_id')->references('id')->on('payments')->onDelete('cascade');
            $table->foreign('processed_by')->references('id')->on('users')->onDelete('set null');
            
            $table->index(['payment_id', 'status']);
            $table->index(['payment_id', 'refund_date']);
            $table->index(['refund_date', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_refunds');
    }
};
