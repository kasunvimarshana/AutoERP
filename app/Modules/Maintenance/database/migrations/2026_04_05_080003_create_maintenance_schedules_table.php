<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('maintenance_schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->string('name');
            $table->unsignedBigInteger('asset_id')->nullable();
            $table->string('maintenance_type', 30)->default('preventive');
            $table->unsignedSmallInteger('frequency_value')->default(1);
            $table->string('frequency_unit', 10)->default('month');
            $table->dateTime('last_run_at')->nullable();
            $table->dateTime('next_run_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
            $table->index(['tenant_id', 'is_active', 'next_run_at']);
        });
    }
    public function down(): void { Schema::dropIfExists('maintenance_schedules'); }
};
