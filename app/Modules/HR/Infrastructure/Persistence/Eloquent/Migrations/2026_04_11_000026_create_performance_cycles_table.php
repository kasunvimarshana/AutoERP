<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('performance_cycles', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('row_version')->default(1)->comment('Used for optimistic concurrency control');
            $table->foreignId('tenant_id')->constrained('tenants', 'id')->cascadeOnDelete()->comment('Multi-tenant owner reference');
            $table->foreignId('organization_unit_id')->nullable()->constrained('organization_units', 'id')->nullOnDelete()->comment('Branch or department ownership');
            $table->json('metadata')->nullable()->comment('Extensible custom dynamic data');

            $table->string('name');
            $table->date('period_start');
            $table->date('period_end');
            $table->boolean('is_active')->default(true);
            $table->unsignedBigInteger('created_by')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['tenant_id', 'is_active'], 'performance_cycles_active_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('performance_cycles');
    }
};
