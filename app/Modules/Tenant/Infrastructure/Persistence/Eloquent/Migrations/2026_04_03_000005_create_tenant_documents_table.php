<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tenant_documents', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('row_version')->default(1)->comment('Used for optimistic concurrency control');
            $table->foreignId('tenant_id')->constrained('tenants', 'id')->cascadeOnDelete();
            $table->json('metadata')->nullable();

            $table->string('name');
            $table->string('file_path');
            $table->string('mime_type')->nullable();
            $table->unsignedInteger('size')->nullable();
            $table->string('type')->nullable();

            $table->timestamps();

            $table->unique(['tenant_id', 'name'], 'tenant_documents_tenant_name_uk');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tenant_documents');
    }
};
