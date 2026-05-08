<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('service_diagnostic_lines', function (Blueprint $table) {
            $table->id();

            $table->foreignId('tenant_id')->constrained('tenants', 'id')->cascadeOnDelete();
            $table->foreignId('org_unit_id')->nullable()->constrained('org_units', 'id')->nullOnDelete();
            $table->unsignedBigInteger('row_version')->default(1)->comment('Used for optimistic concurrency control');

            $table->foreignId('diagnostic_id')->constrained('service_diagnostics', 'id')->cascadeOnDelete();

            $table->string('diagnostic_code')->nullable();
            $table->string('diagnostic_type');
            $table->text('findings')->nullable();
            $table->text('recommendations')->nullable();
            $table->string('severity')->default('info')->comment('info, warning, critical');

            $table->text('comment')->nullable();

            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('service_diagnostic_lines');
    }
};
