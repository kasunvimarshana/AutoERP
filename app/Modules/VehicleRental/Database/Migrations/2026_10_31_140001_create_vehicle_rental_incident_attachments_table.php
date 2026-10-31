<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vehicle_rental_incident_attachments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->restrictOnDelete();
            $table->foreignId('incident_id');
            $table->foreignId('actor_id');
            $table->string('object_key');
            $table->string('original_filename');
            $table->string('mime_type');
            $table->unsignedBigInteger('size_bytes');
            $table->char('checksum_sha256', 64);
            $table->timestamp('recorded_at');
            $table->unique(['id', 'tenant_id'], 'vria_tenant_uk');
            $table->unique(['incident_id', 'checksum_sha256'], 'vria_content_uk');
            $table->foreign(['incident_id', 'tenant_id'], 'vria_incident_fk')->references(['id', 'tenant_id'])->on('vehicle_rental_incidents')->restrictOnDelete();
            $table->foreign(['actor_id', 'tenant_id'], 'vria_actor_fk')->references(['id', 'tenant_id'])->on('users')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vehicle_rental_incident_attachments');
    }
};
