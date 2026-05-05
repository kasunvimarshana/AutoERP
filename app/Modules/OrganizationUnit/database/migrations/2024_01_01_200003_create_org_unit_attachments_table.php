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
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('org_unit_id');
            $table->string('uuid')->unique('org_unit_attachments_uuid_uk');
            $table->string('name');
            $table->string('file_path');
            $table->string('mime_type');
            $table->unsignedInteger('size');
            $table->string('type')->nullable();
            $table->json('metadata')->nullable();

            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();

            $table->timestamps();
            $table->softDeletes();

            $table->foreign('org_unit_id', 'org_unit_attachments_org_unit_id_fk')->references('id')->on('org_units')->onDelete('cascade');
            $table->index(['tenant_id', 'org_unit_id', 'type'], 'org_unit_attachments_tenant_id_org_unit_id_type_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('org_unit_attachments');
    }
};
