<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('gs1_barcodes', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->index();
            $table->unsignedBigInteger('gs1_identifier_id');
            $table->string('barcode_type', 50);
            $table->text('barcode_data');
            $table->string('application_identifiers', 1000)->nullable();
            $table->boolean('is_primary')->default(false);
            $table->boolean('is_active')->default(true);
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['tenant_id', 'gs1_identifier_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('gs1_barcodes');
    }
};
