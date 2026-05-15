<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hr_departments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('row_version')->default(1)->comment('Used for optimistic concurrency control');
            $table->foreignId('tenant_id')->constrained('tenants', 'id')->cascadeOnDelete();
            $table->foreignId('organization_unit_id')->nullable()->constrained('organization_units', 'id')->nullOnDelete();
            $table->json('metadata')->nullable();

            $table->foreignId('parent_id')->nullable()->constrained('hr_departments')->nullOnDelete();
            $table->string('name');
            $table->string('code', 30)->nullable();
            $table->unsignedInteger('depth')->default(0);
            $table->string('path')->nullable();
            $table->boolean('is_active')->default(true);
            $table->text('description')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->unique(['tenant_id', 'organization_unit_id', 'code'], 'hr_departments_code_uk');
            $table->index(['tenant_id', 'organization_unit_id', 'parent_id'], 'hr_departments_parent_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hr_departments');
    }
};
