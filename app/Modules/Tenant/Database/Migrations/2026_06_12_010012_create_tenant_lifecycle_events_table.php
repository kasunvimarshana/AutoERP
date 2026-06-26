<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tenant_lifecycle_events', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants', 'id')->restrictOnDelete();
            $table->string('previous_status', 30);
            $table->string('new_status', 30);
            $table->string('reason', 500)->nullable();
            $table->unsignedBigInteger('actor_id')->nullable()->index('tenant_lifecycle_events_actor_idx');
            $table->string('actor_type', 40)->default('system');
            $table->string('actor_name')->nullable();
            $table->string('actor_email')->nullable();
            $table->dateTime('occurred_at');

            $table->index(['tenant_id', 'occurred_at'], 'tenant_lifecycle_events_tenant_time_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tenant_lifecycle_events');
    }
};
