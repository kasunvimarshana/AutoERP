<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vehicle_rental_owner_agreements_history', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->restrictOnDelete();
            $table->foreignId('agreement_id');
            $table->unsignedBigInteger('row_version');
            $table->foreignId('actor_id');
            $table->string('action');
            $table->text('reason')->nullable();
            $table->json('snapshot');
            $table->timestamp('recorded_at');
            $table->unique(['agreement_id', 'row_version'], 'vroa_history_version_uk');
            $table->foreign(['agreement_id', 'tenant_id'], 'vroa_history_agreement_fk')->references(['id', 'tenant_id'])->on('vehicle_rental_owner_agreements')->restrictOnDelete();
            $table->foreign(['actor_id', 'tenant_id'], 'vroa_history_actor_fk')->references(['id', 'tenant_id'])->on('users')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vehicle_rental_owner_agreements_history');
    }
};
