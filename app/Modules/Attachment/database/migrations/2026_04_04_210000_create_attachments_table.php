<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        Schema::create('attachments', function (Blueprint $table) {
            $table->id(); $table->unsignedBigInteger('tenant_id');
            $table->string('attachable_type'); $table->unsignedBigInteger('attachable_id');
            $table->string('filename'); $table->string('original_name'); $table->string('mime_type');
            $table->unsignedBigInteger('size'); $table->string('path'); $table->string('disk')->default('local');
            $table->string('category')->nullable(); $table->unsignedBigInteger('uploaded_by')->nullable();
            $table->timestamps(); $table->softDeletes();
            $table->index(['attachable_type','attachable_id']); $table->index('tenant_id');
        });
    }
    public function down(): void { Schema::dropIfExists('attachments'); }
};
