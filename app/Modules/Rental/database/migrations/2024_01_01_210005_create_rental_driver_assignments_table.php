<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rental_driver_assignments', function (Blueprint $table) {
            $table->id();

            $table->foreignId('tenant_id')->constrained('tenants', 'id')->cascadeOnDelete();
            $table->foreignId('org_unit_id')->nullable()->constrained('org_units', 'id')->nullOnDelete();
            $table->unsignedBigInteger('row_version')->default(1)->comment('Used for optimistic concurrency control');

            $table->foreignId('booking_id')->constrained('rental_bookings')->cascadeOnDelete();
            $table->foreignId('driver_id')->constrained('employees');
            $table->enum('assignment_type', ['primary','substitute'])->default('primary');
            $table->dateTime('assigned_at');
            $table->dateTime('relieved_at')->nullable();
            $table->string('substitution_reason')->nullable();
            $table->timestamps();
            $table->index(['driver_id','assigned_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rental_driver_assignments');
    }
};
