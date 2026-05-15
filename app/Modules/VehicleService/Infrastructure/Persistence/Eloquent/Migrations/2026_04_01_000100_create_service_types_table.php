<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('service_types', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('row_version')->default(1)->comment('Used for optimistic concurrency control');
            $table->foreignId('tenant_id')->constrained('tenants', 'id')->cascadeOnDelete();
            $table->foreignId('organization_unit_id')->nullable()->constrained('organization_units', 'id')->nullOnDelete();
            $table->json('metadata')->nullable();

            $table->foreignId('parent_id')->nullable()->constrained('service_types')->nullOnDelete();
            $table->string('name');
            $table->string('code', 30)->nullable();
            $table->string('path')->nullable();
            $table->unsignedInteger('depth')->default(0);
            $table->text('description')->nullable();
            $table->decimal('standard_hours', 8, 2)->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedBigInteger('created_by')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->unique(['tenant_id', 'organization_unit_id', 'code'], 'service_types_code_uk');
            $table->index(['tenant_id', 'organization_unit_id', 'parent_id'], 'service_types_parent_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('service_types');
    }
};
