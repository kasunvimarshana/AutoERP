<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        $this->migrateTenantDocuments();
        $this->migrateOrganizationUnitDocuments();
        $this->migrateUserDocuments();
        $this->migrateStructuredDocumentPaths();
    }

    public function down(): void
    {
        if (! Schema::hasTable('tenant_documents')) {
            Schema::create('tenant_documents', function (Blueprint $table): void {
                $table->id();
                $table->unsignedBigInteger('row_version')->default(1);
                $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
                $table->json('metadata')->nullable();
                $table->string('name');
                $table->string('file_path');
                $table->string('mime_type')->nullable();
                $table->unsignedBigInteger('size')->nullable();
                $table->string('checksum_sha256', 64)->nullable();
                $table->string('type')->nullable();
                $table->boolean('is_public')->default(false);
                $table->unsignedBigInteger('created_by')->nullable();
                $table->unsignedBigInteger('updated_by')->nullable();
                $table->unsignedBigInteger('deleted_by')->nullable();
                $table->timestamps();
                $table->softDeletes();
            });
        }

        if (! Schema::hasTable('organization_unit_documents')) {
            Schema::create('organization_unit_documents', function (Blueprint $table): void {
                $table->id();
                $table->unsignedBigInteger('row_version')->default(1);
                $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
                $table->foreignId('organization_unit_id')->constrained('organization_units')->cascadeOnDelete();
                $table->json('metadata')->nullable();
                $table->string('name');
                $table->string('file_path');
                $table->string('mime_type')->nullable();
                $table->unsignedBigInteger('size')->nullable();
                $table->string('type')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('user_documents')) {
            Schema::create('user_documents', function (Blueprint $table): void {
                $table->id();
                $table->unsignedBigInteger('row_version')->default(1);
                $table->foreignId('tenant_id')->nullable()->constrained('tenants')->cascadeOnDelete();
                $table->foreignId('organization_unit_id')->nullable()->constrained('organization_units')->nullOnDelete();
                $table->json('metadata')->nullable();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->string('name');
                $table->string('file_path');
                $table->string('mime_type')->nullable();
                $table->unsignedBigInteger('size')->nullable();
                $table->string('type')->nullable();
                $table->timestamps();
            });
        }
    }

    private function migrateTenantDocuments(): void
    {
        if (! Schema::hasTable('tenant_documents')) {
            return;
        }

        DB::table('tenant_documents')->orderBy('id')->each(function (object $document): void {
            $this->insertAttachment(
                tenantId: (int) $document->tenant_id,
                organizationUnitId: null,
                attachableType: 'tenant',
                attachableId: (int) $document->tenant_id,
                module: 'tenant',
                displayName: (string) $document->name,
                filePath: (string) $document->file_path,
                mimeType: $document->mime_type,
                size: $document->size,
                category: $this->category($document->type),
                visibility: $document->is_public ? 'public' : 'private',
                metadata: $document->metadata,
                checksum: $document->checksum_sha256,
                userId: $document->created_by,
                createdAt: $document->created_at,
                updatedAt: $document->updated_at,
                deletedAt: $document->deleted_at ?? null,
            );
        });

        Schema::drop('tenant_documents');
    }

    private function migrateOrganizationUnitDocuments(): void
    {
        if (! Schema::hasTable('organization_unit_documents')) {
            return;
        }

        DB::table('organization_unit_documents')->orderBy('id')->each(function (object $document): void {
            $this->insertAttachment(
                tenantId: (int) $document->tenant_id,
                organizationUnitId: (int) $document->organization_unit_id,
                attachableType: 'organization_unit',
                attachableId: (int) $document->organization_unit_id,
                module: 'organization_unit',
                displayName: (string) $document->name,
                filePath: (string) $document->file_path,
                mimeType: $document->mime_type,
                size: $document->size,
                category: $this->category($document->type),
                metadata: $document->metadata,
                createdAt: $document->created_at,
                updatedAt: $document->updated_at,
            );
        });

        Schema::drop('organization_unit_documents');
    }

    private function migrateUserDocuments(): void
    {
        if (! Schema::hasTable('user_documents')) {
            return;
        }

        DB::table('user_documents')->orderBy('id')->each(function (object $document): void {
            $tenantId = $document->tenant_id
                ?? DB::table('users')->where('id', $document->user_id)->value('tenant_id');
            if (! is_numeric($tenantId) || (int) $tenantId < 1) {
                throw new \RuntimeException('Cannot migrate a user document without tenant ownership.');
            }

            $this->insertAttachment(
                tenantId: (int) $tenantId,
                organizationUnitId: $document->organization_unit_id === null
                    ? null
                    : (int) $document->organization_unit_id,
                attachableType: 'user',
                attachableId: (int) $document->user_id,
                module: 'user',
                displayName: (string) $document->name,
                filePath: (string) $document->file_path,
                mimeType: $document->mime_type,
                size: $document->size,
                category: $this->category($document->type),
                metadata: $document->metadata,
                createdAt: $document->created_at,
                updatedAt: $document->updated_at,
            );
        });

        Schema::drop('user_documents');
    }

    private function migrateStructuredDocumentPaths(): void
    {
        foreach ([
            'customer_documents' => ['customer_document', 'customer'],
            'supplier_documents' => ['supplier_document', 'supplier'],
            'vehicle_documents' => ['vehicle_document', 'vehicle'],
            'hr_employee_documents' => ['employee_document', 'hr'],
        ] as $table => [$attachableType, $module]) {
            if (! Schema::hasTable($table) || ! Schema::hasColumn($table, 'file_path')) {
                continue;
            }

            DB::table($table)
                ->whereNotNull('file_path')
                ->where('file_path', '!=', '')
                ->orderBy('id')
                ->each(function (object $document) use ($attachableType, $module): void {
                    $this->insertAttachment(
                        tenantId: (int) $document->tenant_id,
                        organizationUnitId: $document->organization_unit_id === null
                            ? null
                            : (int) $document->organization_unit_id,
                        attachableType: $attachableType,
                        attachableId: (int) $document->id,
                        module: $module,
                        displayName: basename((string) $document->file_path),
                        filePath: (string) $document->file_path,
                        category: 'compliance',
                        metadata: json_encode(['migrated_from' => $table]),
                        createdAt: $document->created_at,
                        updatedAt: $document->updated_at,
                        deletedAt: $document->deleted_at,
                    );
                });

            DB::table($table)->update(['file_path' => null]);
        }
    }

    private function insertAttachment(
        int $tenantId,
        ?int $organizationUnitId,
        string $attachableType,
        int $attachableId,
        string $module,
        string $displayName,
        string $filePath,
        mixed $mimeType = null,
        mixed $size = null,
        string $category = 'general',
        string $visibility = 'private',
        mixed $metadata = null,
        mixed $checksum = null,
        mixed $userId = null,
        mixed $createdAt = null,
        mixed $updatedAt = null,
        mixed $deletedAt = null,
    ): void {
        $uuid = (string) Str::uuid();
        $fileName = $displayName !== '' ? $displayName : basename($filePath);

        DB::table('attachments')->insert([
            'uuid' => $uuid,
            'version_group_uuid' => $uuid,
            'version_number' => 1,
            'is_current' => true,
            'row_version' => 1,
            'tenant_id' => $tenantId,
            'organization_unit_id' => $organizationUnitId,
            'metadata' => $metadata,
            'attachable_type' => $attachableType,
            'attachable_id' => $attachableId,
            'source_module' => $module,
            'source_type' => $attachableType,
            'source_id' => $attachableId,
            'category' => $category,
            'visibility' => $visibility,
            'display_name' => $fileName,
            'original_file_name' => $fileName,
            'stored_file_name' => basename($filePath),
            'file_name' => $fileName,
            'disk' => 'local',
            'file_path' => $filePath,
            'mime_type' => $mimeType,
            'size' => $size,
            'checksum_sha256' => $checksum,
            'preview_status' => 'unsupported',
            'uploaded_by' => $userId,
            'created_by' => $userId,
            'updated_by' => $userId,
            'created_at' => $createdAt ?? now(),
            'updated_at' => $updatedAt ?? now(),
            'deleted_at' => $deletedAt,
        ]);
    }

    private function category(mixed $type): string
    {
        $type = is_string($type) ? strtolower(trim($type)) : '';

        return in_array($type, [
            'general',
            'contract',
            'identity',
            'compliance',
            'invoice',
            'receipt',
            'tax',
            'finance',
            'hr',
            'vehicle',
            'inspection',
            'warranty',
            'image',
            'other',
        ], true) ? $type : 'general';
    }
};
