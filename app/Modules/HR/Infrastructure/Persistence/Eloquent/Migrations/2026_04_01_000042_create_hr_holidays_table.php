<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hr_holidays', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('row_version')->default(1)->comment('Used for optimistic concurrency control');
            $table->foreignId('tenant_id')->constrained('tenants', 'id')->cascadeOnDelete();
            $table->foreignId('organization_unit_id')->nullable()->constrained('organization_units', 'id')->nullOnDelete();
            $table->json('metadata')->nullable();

            $table->string('name');
            $table->date('holiday_date');
            $table->string('holiday_type')->default('public');
            $table->boolean('is_recurring')->default(false);
            $table->text('description')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();

            $table->timestamps();

            $table->unique(['tenant_id', 'organization_unit_id', 'holiday_date', 'holiday_type'], 'hr_holidays_date_type_uk');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hr_holidays');
    }
};
