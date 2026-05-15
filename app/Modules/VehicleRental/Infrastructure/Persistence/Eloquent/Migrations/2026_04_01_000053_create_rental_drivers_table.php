<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rental_drivers', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('row_version')->default(1)->comment('Used for optimistic concurrency control');
            $table->foreignId('tenant_id')->constrained('tenants', 'id')->cascadeOnDelete();
            $table->foreignId('organization_unit_id')->nullable()->constrained('organization_units', 'id')->nullOnDelete();
            $table->json('metadata')->nullable();

            $table->foreignId('agreement_id')->constrained('rental_agreements')->cascadeOnDelete();
            $table->foreignId('employee_id')->constrained('hr_employees');
            $table->date('assignment_date');
            $table->date('release_date')->nullable();
            $table->string('role')->default('driver');

            $table->timestamps();
            $table->softDeletes();

            $table->unique(['tenant_id', 'organization_unit_id', 'agreement_id', 'employee_id', 'assignment_date'], 'rental_drivers_agreement_employee_assignment_uk');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rental_drivers');
    }
};
