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
            $table->foreignId('tenant_id')->constrained('tenants', 'id')->cascadeOnDelete();
            $table->foreignId('org_unit_id')->nullable()->constrained('org_units', 'id')->nullOnDelete();
            $table->unsignedBigInteger('row_version')->default(1)->comment('Used for optimistic concurrency control');
            $table->foreignId('parent_id')->nullable()->constrained('cost_centers', 'id', 'cost_centers_parent_id_fk')->nullOnDelete();
            $table->string('code');
            $table->string('name');
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->string('path')->nullable();
            $table->unsignedInteger('depth')->default(0);
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['tenant_id', 'code'], 'cost_centers_tenant_code_uk');
            $table->index(['tenant_id', 'parent_id'], 'cost_centers_tenant_parent_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cost_centers');
    }
};
