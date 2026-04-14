<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('identifiers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->string('entity_type', 100);
            $table->unsignedBigInteger('entity_id');
            $table->enum('identifier_type', ['barcode_1d', 'barcode_2d', 'qr', 'rfid_hf', 'rfid_uhf', 'nfc', 'epc_gs1', 'ean', 'upc', 'code128', 'datamatrix', 'custom']);
            $table->string('value', 500)->unique();
            $table->json('gs1_application_identifiers')->nullable();
            $table->string('epc_uri', 500)->nullable();
            $table->string('format', 50)->nullable();
            $table->boolean('is_primary')->default(false);
            $table->boolean('is_active')->default(true);
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['entity_type', 'entity_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('identifiers');
    }
};