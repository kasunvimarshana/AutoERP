<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('service_inspection_lines', function (Blueprint $table) {
            $table->id();

            $table->foreignId('tenant_id')->constrained('tenants', 'id')->cascadeOnDelete();
            $table->foreignId('org_unit_id')->nullable()->constrained('org_units', 'id')->nullOnDelete();
            $table->unsignedBigInteger('row_version')->default(1)->comment('Used for optimistic concurrency control');

            $table->foreignId('inspection_id')->constrained('service_inspections', 'id')->cascadeOnDelete();

            $table->string('inspection_item');      // e.g. "Tyre tread depth", "Brake fluid level"
            $table->string('expected_value')->nullable();
            $table->string('actual_value')->nullable();
            $table->enum('result', ['pass', 'fail', 'flag', 'not_tested'])->default('not_tested');
            $table->text('comment')->nullable();

            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('service_inspection_lines');
    }
};
