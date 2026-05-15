<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attachments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('row_version')->default(1)->comment('Used for optimistic concurrency control');
            $table->foreignId('tenant_id')->constrained('tenants', 'id')->cascadeOnDelete();
            $table->foreignId('organization_unit_id')->nullable()->constrained('organization_units', 'id')->nullOnDelete();
            $table->json('metadata')->nullable();

            $table->string('attachable_type')->comment('polymorphic target type (e.g., Document, Product, Party)');
            $table->unsignedBigInteger('attachable_id')->comment('polymorphic target ID');
            $table->string('file_name');
            $table->string('file_path');
            $table->string('mime_type')->nullable();
            $table->unsignedBigInteger('size')->nullable()->comment('File size in bytes');

            $table->timestamps();

            $table->index(['tenant_id', 'organization_unit_id', 'attachable_type', 'attachable_id'], 'attachments_type_id_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attachments');
    }
};
