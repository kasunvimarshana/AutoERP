<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('holidays', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('row_version')->default(1)->comment('Used for optimistic concurrency control');
            $table->foreignId('tenant_id')->constrained('tenants', 'id')->cascadeOnDelete()->comment('Multi-tenant owner reference');
            $table->foreignId('organization_unit_id')->nullable()->constrained('organization_units', 'id')->nullOnDelete()->comment('Branch or department ownership');
            $table->json('metadata')->nullable()->comment('Extensible custom dynamic data');

            $table->string('name');
            $table->date('holiday_date');
            $table->string('holiday_type')->default('public');
            $table->boolean('is_recurring')->default(false);
            $table->text('description')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();

            $table->timestamps();

            $table->unique(['tenant_id', 'holiday_date', 'holiday_type'], 'holidays_date_type_uk');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('holidays');
    }
};
