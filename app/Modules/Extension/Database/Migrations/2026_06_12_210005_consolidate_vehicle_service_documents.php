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
        if (! Schema::hasTable('vehicle_service_documents')) {
            return;
        }

        DB::table('vehicle_service_documents')
            ->orderBy('id')
            ->each(function (object $document): void {
                $uuid = (string) Str::uuid();
                $filePath = is_string($document->file_path) ? $document->file_path : '';
                $fileName = $filePath === '' ? 'legacy-document.bin' : basename($filePath);

                DB::table('attachments')->insert([
                    'uuid' => $uuid,
                    'version_group_uuid' => $uuid,
                    'version_number' => 1,
                    'is_current' => true,
                    'row_version' => 1,
                    'tenant_id' => $document->tenant_id,
                    'organization_unit_id' => $document->organization_unit_id,
                    'metadata' => json_encode(['document_type' => $document->document_type]),
                    'attachable_type' => 'vehicle_service_job',
                    'attachable_id' => $document->vehicle_service_job_id,
                    'source_module' => 'vehicle_service',
                    'source_type' => 'vehicle_service_job',
                    'source_id' => $document->vehicle_service_job_id,
                    'category' => $this->category((string) $document->document_type),
                    'visibility' => 'private',
                    'display_name' => $fileName,
                    'original_file_name' => $fileName,
                    'stored_file_name' => $fileName,
                    'file_name' => $fileName,
                    'disk' => 'public',
                    'file_path' => $filePath,
                    'description' => $document->description,
                    'preview_status' => 'unsupported',
                    'uploaded_by' => $document->uploaded_by,
                    'created_by' => $document->uploaded_by,
                    'updated_by' => $document->uploaded_by,
                    'created_at' => $document->created_at,
                    'updated_at' => $document->updated_at,
                    'deleted_at' => $document->deleted_at,
                ]);
            });

        Schema::drop('vehicle_service_documents');
    }

    public function down(): void
    {
        if (Schema::hasTable('vehicle_service_documents')) {
            return;
        }

        Schema::create('vehicle_service_documents', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('organization_unit_id')->nullable()->constrained('organization_units')->nullOnDelete();
            $table->foreignId('vehicle_service_job_id')->constrained('vehicle_service_jobs')->cascadeOnDelete();
            $table->string('document_type', 30);
            $table->string('file_path')->nullable();
            $table->text('description')->nullable();
            $table->unsignedBigInteger('uploaded_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    private function category(string $documentType): string
    {
        return match ($documentType) {
            'image' => 'image',
            'inspection_report' => 'inspection',
            'warranty' => 'warranty',
            'invoice_copy' => 'invoice',
            default => 'other',
        };
    }
};
