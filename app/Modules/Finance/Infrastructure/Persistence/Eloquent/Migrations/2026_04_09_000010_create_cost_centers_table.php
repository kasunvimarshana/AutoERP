<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cost_centers', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('row_version')->default(1)->comment('Used for optimistic concurrency control');
            $table->foreignId('tenant_id')->constrained('tenants', 'id')->cascadeOnDelete()->comment('Multi-tenant owner reference');
            $table->foreignId('organization_unit_id')->nullable()->constrained('organization_units', 'id')->nullOnDelete()->comment('Branch or department ownership');
            $table->json('metadata')->nullable()->comment('Extensible custom dynamic data');

            $table->foreignId('parent_id')->nullable()->constrained('cost_centers')->nullOnDelete();
            $table->string('code');
            $table->string('name');
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->string('path')->nullable()->comment('materialised path');
            $table->unsignedInteger('depth')->default(0);
            $table->unsignedBigInteger('created_by')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->unique(['tenant_id', 'code'], 'cost_centers_tenant_code_uk');
            $table->index(['tenant_id', 'parent_id'], 'cost_centers_parent_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cost_centers');
    }
};
