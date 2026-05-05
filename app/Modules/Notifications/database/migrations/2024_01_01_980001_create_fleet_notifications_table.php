<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fleet_notifications', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('tenant_id')->index();
            $table->uuid('org_unit_id')->index();
            $table->unsignedBigInteger('row_version')->default(1);

            $table->string('notification_number', 64);
            $table->enum('notification_type', [
                'rental_overdue',
                'service_due',
                'document_expiry',
                'maintenance_reminder',
                'payment_due',
                'other',
            ]);
            $table->enum('entity_type', [
                'rental',
                'service_job',
                'vehicle',
                'driver',
                'return_refund',
            ]);
            $table->uuid('entity_id')->nullable();
            $table->enum('recipient_type', ['system', 'user', 'customer', 'driver'])->default('system');
            $table->uuid('recipient_id')->nullable();
            $table->string('title');
            $table->text('message');
            $table->enum('channel', ['in_app', 'email', 'sms', 'push'])->default('in_app');
            $table->enum('status', ['pending', 'sent', 'delivered', 'read', 'failed'])->default('pending');
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('read_at')->nullable();
            $table->string('failed_reason')->nullable();
            $table->json('metadata')->nullable();

            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->unique(
                ['tenant_id', 'org_unit_id', 'notification_number'],
                'fleet_notifications_tenant_ou_number_uk'
            );
            $table->index(['tenant_id', 'status'], 'fleet_notifications_tenant_status_idx');
            $table->index(['tenant_id', 'entity_type', 'entity_id'], 'fleet_notifications_tenant_entity_idx');
            $table->index(['tenant_id', 'notification_type'], 'fleet_notifications_tenant_type_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fleet_notifications');
    }
};
