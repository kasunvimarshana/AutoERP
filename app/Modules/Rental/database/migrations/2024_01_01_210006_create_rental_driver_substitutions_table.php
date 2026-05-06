<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rental_driver_substitutions', function (Blueprint $table) {
            $table->id();

            $table->foreignId('tenant_id')->constrained('tenants', 'id')->cascadeOnDelete();
            $table->foreignId('org_unit_id')->nullable()->constrained('org_units', 'id')->nullOnDelete();
            $table->unsignedBigInteger('row_version')->default(1)->comment('Used for optimistic concurrency control');

            $table->foreignId('original_assignment_id')->constrained('rental_driver_assignments');
            $table->foreignId('substitute_driver_id')->constrained('employees');
            $table->dateTime('substitution_date');
            $table->string('reason');
            $table->foreignId('approved_by')->constrained('users');
            $table->enum('status', ['pending','approved','active','completed'])->default('pending');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rental_driver_substitutions');
    }
};
