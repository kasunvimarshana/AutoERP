<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('job_cards', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->onDelete('cascade');
            $table->foreignId('appointment_id')->nullable()->constrained('appointments')->onDelete('set null');
            $table->foreignId('customer_id')->constrained('customers')->onDelete('restrict');
            $table->foreignId('vehicle_id')->constrained('vehicles')->onDelete('restrict');
            $table->string('job_card_number')->unique();
            $table->dateTime('opened_date');
            $table->dateTime('closed_date')->nullable();
            $table->enum('status', ['open', 'in_progress', 'completed', 'cancelled'])->default('open');
            $table->foreignId('technician_id')->nullable()->constrained('users')->onDelete('set null');
            $table->decimal('estimated_cost', 15, 2)->default(0);
            $table->decimal('actual_cost', 15, 2)->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('tenant_id');
            $table->index('appointment_id');
            $table->index('customer_id');
            $table->index('vehicle_id');
            $table->index('technician_id');
            $table->index('status');
            $table->index('opened_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('job_cards');
    }
};
