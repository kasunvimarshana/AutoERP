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

            $table->foreignId('tenant_id')->constrained('tenants', 'id')->cascadeOnDelete();
            $table->foreignId('org_unit_id')->nullable()->constrained('org_units', 'id')->nullOnDelete();
            $table->unsignedBigInteger('row_version')->default(1)->comment('Used for optimistic concurrency control');

            $table->foreignId('vehicle_id')->constrained('vehicles')->cascadeOnDelete();
            $table->date('issue_date')->nullable();
            $table->date('expiry_date')->nullable();

            $table->string('uuid', 36)->unique('vehicle_documents_uuid_uk');
            $table->string('name');
            $table->string('file_path');
            $table->string('mime_type', 127);
            $table->unsignedBigInteger('size')->default(0);
            $table->string('type')->nullable()->index('vehicle_documents_type_idx');
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vehicle_documents');
    }
};
