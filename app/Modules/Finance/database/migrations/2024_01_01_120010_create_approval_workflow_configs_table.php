<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('approval_workflow_configs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants', 'id', 'approval_workflow_configs_tenant_id_fk')->cascadeOnDelete();
            $table->string('module');
            $table->string('entity_type');
            $table->string('name');
            $table->decimal('min_amount', 20, 6)->nullable();
            $table->decimal('max_amount', 20, 6)->nullable();
            $table->json('steps');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['tenant_id', 'module', 'entity_type'], 'approval_workflow_configs_scope_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('approval_workflow_configs');
    }
};
