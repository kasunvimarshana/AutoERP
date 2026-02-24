<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void
    {
        Schema::create('media_files', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('tenant_id')->nullable()->index();
            $table->uuid('uploaded_by')->nullable()->index();
            $table->string('disk')->default('local');
            $table->string('path');
            $table->string('filename');
            $table->string('original_name');
            $table->string('mime_type');
            $table->bigInteger('size_bytes')->unsigned();
            $table->string('folder')->nullable();
            $table->json('tags')->nullable();
            $table->boolean('is_public')->default(false);
            $table->unsignedInteger('version')->default(1);
            $table->string('model_type')->nullable();
            $table->string('model_id')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['model_type', 'model_id']);
        });
    }
    public function down(): void { Schema::dropIfExists('media_files'); }
};
