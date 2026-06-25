<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_documents', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('row_version')->default(1)->comment('Used for optimistic concurrency control');
            $table->foreignId('tenant_id')->constrained('tenants', 'id')->restrictOnDelete()->comment('Multi-tenant owner reference');
            $table->unsignedBigInteger('organization_unit_id')->nullable()->comment('Optional organization-unit scope');
            $table->json('metadata')->nullable()->comment('Extensible custom dynamic data');

            // $table->string('uuid')->unique('user_documents_uuid_uk');
            $table->unsignedBigInteger('user_id');
            $table->string('name');
            $table->string('file_path');
            $table->string('mime_type')->nullable();
            $table->unsignedInteger('size')->nullable();
            $table->string('type')->nullable();

            $table->timestamps();

            $table->foreign(['organization_unit_id', 'tenant_id'], 'ud_org_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('organization_units')
                ->restrictOnDelete();
            $table->foreign(['user_id', 'tenant_id'], 'ud_user_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('users')
                ->cascadeOnDelete();
            $table->unique(['tenant_id', 'user_id', 'name'], 'user_documents_user_name_uk');

            $table->unique(['id', 'tenant_id'], 'user_documents_id_tenant_uk');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_documents');
    }
};
