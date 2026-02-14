<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('appointments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->foreignId('customer_id')->constrained('customers')->onDelete('cascade');
            $table->foreignId('vehicle_id')->constrained('vehicles')->onDelete('cascade');
            $table->foreignId('service_bay_id')->nullable()->constrained('service_bays')->onDelete('set null');
            $table->string('appointment_number')->unique();
            $table->date('scheduled_date');
            $table->time('scheduled_time');
            $table->integer('duration_minutes')->default(60);
            $table->string('service_type');
            $table->enum('status', ['scheduled', 'confirmed', 'in_progress', 'completed', 'cancelled', 'no_show'])->default('scheduled');
            $table->text('notes')->nullable();
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['tenant_id', 'status']);
            $table->index(['tenant_id', 'scheduled_date']);
            $table->index(['customer_id', 'scheduled_date']);
            $table->index('appointment_number');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('appointments');
    }
};
