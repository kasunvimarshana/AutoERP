<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('org_unit_attachments', function (Blueprint $table) {
            $table->id();

            $table->foreignId('tenant_id')->constrained('tenants', 'id')->cascadeOnDelete();
            $table->foreignId('org_unit_id')->nullable()->constrained('org_units', 'id')->cascadeOnDelete();
            $table->unsignedBigInteger('row_version')->default(1)->comment('Used for optimistic concurrency control');

            $table->string('uuid')->unique('org_unit_attachments_uuid_uk');
            $table->string('name');
            $table->string('file_path');
            $table->string('mime_type');
            $table->unsignedInteger('size');
            $table->string('type')->nullable();
            $table->json('metadata')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['tenant_id', 'org_unit_id', 'type'], 'org_unit_attachments_tenant_id_org_unit_id_type_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('org_unit_attachments');
    }
};
