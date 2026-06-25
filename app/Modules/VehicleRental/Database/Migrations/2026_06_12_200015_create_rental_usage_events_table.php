<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rental_usage_events', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('row_version')->default(1);
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('organization_unit_id')->nullable();
            $table->json('metadata')->nullable();
            $table->foreignId('usage_log_id');
            $table->unsignedInteger('sequence');
            $table->string('event_type', 40);
            $table->dateTime('occurred_at')->nullable();
            $table->decimal('quantity', 20, 6);
            $table->string('unit', 30)->nullable();
            $table->string('reference_number', 100)->nullable();
            $table->text('remarks')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();

            $table->unique(['usage_log_id', 'sequence'], 'rental_usage_events_sequence_uk');
            $table->index(['usage_log_id', 'event_type'], 'rental_usage_events_type_idx');

            $table->unique(['id', 'tenant_id'], 'rental_usage_events_id_tenant_uk');
            $table->foreign(['organization_unit_id', 'tenant_id'], 'rental_usage_events_organization_unit_id_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('organization_units')
                ->restrictOnDelete();
            $table->foreign(['usage_log_id', 'tenant_id'], 'rental_usage_events_usage_log_id_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('rental_usage_logs')
                ->cascadeOnDelete();

            $table->foreign(['created_by', 'tenant_id'], 'rental_usage_events_created_by_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('users')
                ->restrictOnDelete();
            $table->foreign(['updated_by', 'tenant_id'], 'rental_usage_events_updated_by_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('users')
                ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rental_usage_events');
    }
};
