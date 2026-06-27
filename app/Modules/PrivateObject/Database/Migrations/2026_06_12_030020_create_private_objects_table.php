<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('private_objects', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('row_version')->default(1);
            $table->foreignId('tenant_id')->constrained('tenants', 'id', indexName: 'private_objects_tenant_fk')->restrictOnDelete();
            $table->foreignId('organization_unit_id')->nullable();
            $table->string('disk', 100);
            $table->string('object_key', 1024);
            $table->string('original_filename', 255);
            $table->string('mime_type', 255);
            $table->unsignedBigInteger('size_bytes');
            $table->char('checksum_sha256', 64);
            $table->string('scan_status', 30)->default('pending');
            $table->string('scan_engine', 100)->nullable();
            $table->dateTime('scanned_at')->nullable();
            $table->foreignId('created_by')->nullable();
            $table->foreignId('updated_by')->nullable();
            $table->timestamps();

            $table->unique(['id', 'tenant_id'], 'private_objects_id_tenant_uk');
            $table->unique(['tenant_id', 'object_key'], 'private_objects_tenant_object_key_uk');
            $table->index(['tenant_id', 'organization_unit_id', 'scan_status'], 'private_objects_scope_scan_ix');
            $table->foreign(['organization_unit_id', 'tenant_id'], 'private_objects_ou_tenant_fk')
                ->references(['id', 'tenant_id'])->on('organization_units')->restrictOnDelete();
            $table->foreign(['created_by', 'tenant_id'], 'private_objects_created_by_tenant_fk')
                ->references(['id', 'tenant_id'])->on('users')->restrictOnDelete();
            $table->foreign(['updated_by', 'tenant_id'], 'private_objects_updated_by_tenant_fk')
                ->references(['id', 'tenant_id'])->on('users')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('private_objects');
    }
};
