<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vehicle_status_logs', function (Blueprint $table) {
            $table->id();

            $table->foreignId('tenant_id')->constrained('tenants', 'id')->cascadeOnDelete();
            $table->foreignId('org_unit_id')->nullable()->constrained('org_units', 'id')->nullOnDelete();
            $table->unsignedBigInteger('row_version')->default(1)->comment('Used for optimistic concurrency control');

            $table->foreignId('vehicle_id')->constrained('vehicles')->cascadeOnDelete();
            $table->string('from_status');
            $table->string('to_status');
            $table->foreignId('changed_by')->constrained('users');
            $table->dateTime('changed_at');
            $table->string('reason')->nullable();
            $table->string('reference_type')->nullable();
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->timestamps();
            $table->index(['vehicle_id','changed_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vehicle_status_logs');
    }
};
