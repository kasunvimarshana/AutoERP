<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fleet_vehicle_documents', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('vehicle_id')->constrained('fleet_vehicles', 'id', 'fvdoc_veh_fk')->cascadeOnDelete();
            $table->enum('document_type', ['insurance', 'registration', 'road_worthy', 'permit', 'service_record', 'other']);
            $table->string('document_number')->nullable();
            $table->string('issuing_authority')->nullable();
            $table->date('issue_date')->nullable();
            $table->date('expiry_date')->nullable();
            $table->string('file_path')->nullable();
            $table->text('notes')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['tenant_id', 'vehicle_id', 'document_type'], 'fvdoc_tenant_veh_type_idx');
            $table->index(['tenant_id', 'expiry_date'], 'fvdoc_expiry_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fleet_vehicle_documents');
    }
};
