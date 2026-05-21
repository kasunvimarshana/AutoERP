<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vehicle_service_types', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('row_version')->default(1)->comment('Used for optimistic concurrency control');
            $table->foreignId('tenant_id')->constrained('tenants', 'id')->cascadeOnDelete()->comment('Multi-tenant owner reference');
            $table->foreignId('organization_unit_id')->nullable()->constrained('organization_units', 'id')->nullOnDelete()->comment('Branch or department ownership');
            $table->json('metadata')->nullable()->comment('Extensible custom dynamic data');

            $table->foreignId('parent_id')->nullable()->constrained('vehicle_service_types')->nullOnDelete();
            $table->string('name');
            $table->string('code')->nullable();
            $table->string('path')->nullable();
            $table->unsignedInteger('depth')->default(0);
            $table->text('description')->nullable();
            $table->decimal('standard_hours', 20, 4)->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->unique(['tenant_id', 'name'], 'vehicle_service_types_name_uk');
            $table->index(['tenant_id', 'parent_id'], 'vehicle_service_types_parent_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vehicle_service_types');
    }
};
