<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vehicle_documents', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants', 'id')->cascadeOnDelete();
            $table->foreignId('vehicle_id')->constrained('vehicles', 'id')->cascadeOnDelete();
            $table->enum('document_type', ['registration', 'insurance', 'fitness', 'permit', 'other']);
            $table->string('document_number', 120)->nullable();
            $table->date('issued_at')->nullable();
            $table->date('expires_at')->nullable();
            $table->string('file_path', 500)->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['tenant_id', 'vehicle_id', 'document_type'], 'vehicle_documents_tenant_vehicle_type_idx');
            $table->index(['tenant_id', 'expires_at'], 'vehicle_documents_tenant_expires_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vehicle_documents');
    }
};
