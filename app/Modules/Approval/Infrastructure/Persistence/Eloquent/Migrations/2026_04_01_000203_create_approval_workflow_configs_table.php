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
            $table->unsignedBigInteger('row_version')->default(1)->comment('Used for optimistic concurrency control');
            $table->foreignId('tenant_id')->constrained('tenants', 'id')->cascadeOnDelete();
            $table->foreignId('organization_unit_id')->nullable()->constrained('organization_units', 'id')->nullOnDelete();
            $table->json('metadata')->nullable();

            $table->string('module')->comment('sales, purchase, etc');
            $table->string('entity_type')->comment('Document, JournalEntry, etc');
            $table->string('name');
            $table->decimal('min_amount', 20, 4)->nullable();
            $table->decimal('max_amount', 20, 4)->nullable();
            $table->json('steps')->comment('snapshot of steps (or we use dedicated approval_steps table)');
            $table->boolean('is_active')->default(true);

            $table->timestamps();
            $table->softDeletes();

            $table->index(['tenant_id', 'organization_unit_id', 'module', 'entity_type'], 'approval_workflow_configs_module_entity_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('approval_workflow_configs');
    }
};
