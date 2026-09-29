<?php

declare(strict_types=1);
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vehicle_rental_running_chart_history', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->restrictOnDelete();
            $table->foreignId('running_chart_id');
            $table->unsignedBigInteger('row_version');
            $table->foreignId('actor_id');
            $table->string('action');
            $table->text('reason')->nullable();
            $table->json('snapshot');
            $table->dateTime('recorded_at');
            $table->unique(['id', 'tenant_id'], 'vrch_tenant_uk');
            $table->unique(['running_chart_id', 'row_version'], 'vrch_revision_uk');
            $table->foreign(['running_chart_id', 'tenant_id'], 'vrch_chart_fk')->references(['id', 'tenant_id'])->on('vehicle_rental_running_charts')->restrictOnDelete();
            $table->foreign(['actor_id', 'tenant_id'], 'vrch_actor_fk')->references(['id', 'tenant_id'])->on('users')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vehicle_rental_running_chart_history');
    }
};
