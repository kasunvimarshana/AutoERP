<?php

declare(strict_types=1);
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vehicle_rental_use_history', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->restrictOnDelete();
            $table->foreignId('vehicle_use_id');
            $table->unsignedBigInteger('row_version');
            $table->foreignId('actor_id');
            $table->string('action');
            $table->text('reason')->nullable();
            $table->json('snapshot');
            $table->dateTime('recorded_at');
            $table->unique(['id', 'tenant_id'], 'vruh_tenant_uk');
            $table->unique(['vehicle_use_id', 'row_version'], 'vruh_revision_uk');
            $table->foreign(['vehicle_use_id', 'tenant_id'], 'vruh_use_fk')->references(['id', 'tenant_id'])->on('vehicle_rental_uses')->restrictOnDelete();
            $table->foreign(['actor_id', 'tenant_id'], 'vruh_actor_fk')->references(['id', 'tenant_id'])->on('users')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vehicle_rental_use_history');
    }
};
