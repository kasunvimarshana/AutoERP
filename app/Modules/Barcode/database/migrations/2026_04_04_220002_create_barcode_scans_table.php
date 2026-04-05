<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('barcode_scans', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->nullable()->index();
            $table->unsignedBigInteger('barcode_definition_id')->nullable()->index();
            $table->string('scanned_value', 500);
            $table->string('resolved_type', 30)->nullable();
            $table->unsignedBigInteger('scanned_by_user_id')->nullable();
            $table->string('device_id', 100)->nullable();
            $table->string('location_tag', 100)->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('scanned_at');
            $table->timestamps();
            $table->softDeletes();

            $table->index('scanned_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('barcode_scans');
    }
};
