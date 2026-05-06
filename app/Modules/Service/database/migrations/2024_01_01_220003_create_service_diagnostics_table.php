<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('service_diagnostics', function (Blueprint $table) {
            $table->id();

            $table->foreignId('tenant_id')->constrained('tenants', 'id')->cascadeOnDelete();
            $table->foreignId('org_unit_id')->nullable()->constrained('org_units', 'id')->nullOnDelete();
            $table->unsignedBigInteger('row_version')->default(1)->comment('Used for optimistic concurrency control');

            $table->foreignId('job_card_id')->constrained('service_job_cards')->cascadeOnDelete();
            $table->string('diagnostic_code')->nullable();
            $table->string('diagnostic_type');
            $table->text('findings')->nullable();
            $table->text('recommendations')->nullable();
            $table->foreignId('performed_by')->constrained('employees');
            $table->dateTime('performed_at');
            $table->enum('severity', ['info','warning','critical'])->default('info');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('service_diagnostics');
    }
};
