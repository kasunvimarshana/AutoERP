<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('designations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('row_version')->default(1)->comment('Used for optimistic concurrency control');
            $table->foreignId('tenant_id')
                ->constrained('tenants', 'id')
                ->cascadeOnDelete()
                ->comment('Multi-tenant owner reference');
            $table->foreignId('organization_unit_id')
                ->nullable()
                ->constrained('organization_units', 'id')
                ->nullOnDelete()
                ->comment('Branch or department ownership');
            $table->json('metadata')->nullable()->comment('Extensible custom dynamic data');

            $table->foreignId('department_id')->nullable()->constrained('departments', 'id')->nullOnDelete();
            $table->string('designation_code', 50);
            $table->string('designation_name', 160);
            $table->boolean('is_active')->default(true);
            $table->text('description')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->unique(['tenant_id', 'designation_code'], 'designations_code_uk');
            $table->index(['tenant_id', 'designation_name'], 'designations_name_idx');
            $table->index(['tenant_id', 'department_id'], 'designations_department_idx');
            $table->index(['tenant_id', 'is_active'], 'designations_active_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('designations');
    }
};
