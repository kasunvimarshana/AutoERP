<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('leave_types', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('row_version')->default(1)->comment('Used for optimistic concurrency control');
            $table->foreignId('tenant_id')->constrained('tenants', 'id')->cascadeOnDelete()->comment('Multi-tenant owner reference');
            $table->foreignId('organization_unit_id')->nullable()->constrained('organization_units', 'id')->nullOnDelete()->comment('Branch or department ownership');
            $table->json('metadata')->nullable()->comment('Extensible custom dynamic data');

            $table->string('name');
            $table->string('code');
            $table->text('description')->nullable();
            $table->boolean('is_paid')->default(true);
            $table->boolean('requires_approval')->default(true);
            $table->boolean('is_active')->default(true);
            $table->decimal('max_days_per_year', 20, 4)->nullable();
            $table->decimal('carry_forward_max', 20, 4)->default(0);
            $table->boolean('allow_negative_balance')->default(false);
            $table->string('applicable_gender')->nullable();
            $table->integer('min_service_days')->default(0);
            $table->unsignedBigInteger('created_by')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->unique(['tenant_id', 'code'], 'leave_types_code_uk');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('leave_types');
    }
};
