<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('vehicle_ownership_history', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vehicle_id')->constrained()->cascadeOnDelete();
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->timestamp('start_date');
            $table->timestamp('end_date')->nullable();
            $table->decimal('purchase_mileage', 10, 2)->nullable();
            $table->decimal('transfer_mileage', 10, 2)->nullable();
            $table->enum('transfer_reason', ['sale', 'gift', 'trade', 'inheritance', 'other'])->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            
            $table->index('vehicle_id');
            $table->index('customer_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vehicle_ownership_history');
    }
};
