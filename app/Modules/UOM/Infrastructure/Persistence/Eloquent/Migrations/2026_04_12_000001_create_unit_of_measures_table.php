<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('unit_of_measures', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('row_version')->default(1)->comment('Used for optimistic concurrency control');
            $table->foreignId('tenant_id')->constrained('tenants', 'id')->cascadeOnDelete()->comment('Multi-tenant owner reference');
            $table->foreignId('organization_unit_id')->nullable()->constrained('organization_units', 'id')->nullOnDelete()->comment('Branch or department ownership');

            $table->string('uom_code', 50);
            $table->string('name', 180);
            $table->string('symbol', 50)->nullable();
            $table->unsignedTinyInteger('decimal_precision')->default(2);
            $table->boolean('is_base')->default(false);
            $table->string('status', 60)->default('active')->comment('active, inactive');
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->unique(['tenant_id', 'uom_code'], 'unit_of_measures_code_uk');
            $table->index(['tenant_id', 'organization_unit_id'], 'unit_of_measures_organization_unit_idx');
            $table->index(['tenant_id', 'name'], 'unit_of_measures_name_idx');
            $table->index(['tenant_id', 'status'], 'unit_of_measures_status_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('unit_of_measures');
    }
};
