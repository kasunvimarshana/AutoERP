<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vehicle_documents', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('row_version')->default(1)->comment('Used for optimistic concurrency control');
            $table->foreignId('tenant_id')->constrained('tenants', 'id')->cascadeOnDelete();
            $table->foreignId('organization_unit_id')->constrained('organization_units', 'id')->nullOnDelete();
            $table->json('metadata')->nullable();

            // $table->string('uuid')->unique('vehicle_documents_uuid_uk');
            $table->foreignId('vehicle_id')->constrained('vehicles', 'id')->cascadeOnDelete();
            $table->string('name');
            $table->string('file_path');
            $table->string('mime_type')->nullable();
            $table->unsignedInteger('size')->nullable();
            $table->string('type')->nullable();

            $table->timestamps();

            $table->unique(['tenant_id', 'vehicle_id', 'name'], 'vehicle_documents_vehicle_name_uk');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vehicle_documents');
    }
};
