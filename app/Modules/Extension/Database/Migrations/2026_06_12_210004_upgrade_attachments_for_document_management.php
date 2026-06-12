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
        Schema::table('attachments', function (Blueprint $table): void {
            $table->uuid('uuid')->nullable()->unique('attachments_uuid_uk')->after('id');
            $table->uuid('version_group_uuid')->nullable()->after('uuid');
            $table->unsignedBigInteger('previous_version_id')->nullable()->after('version_group_uuid');
            $table->unsignedInteger('version_number')->default(1)->after('previous_version_id');
            $table->boolean('is_current')->default(true)->after('version_number');

            $table->string('category', 100)->default('general')->after('source_context');
            $table->string('visibility', 20)->default('private')->after('category');
            $table->string('display_name')->nullable()->after('visibility');
            $table->string('original_file_name')->nullable()->after('display_name');
            $table->string('stored_file_name')->nullable()->after('original_file_name');
            $table->string('disk', 100)->default('local')->after('stored_file_name');
            $table->string('extension', 30)->nullable()->after('mime_type');
            $table->string('checksum_sha256', 64)->nullable()->after('size');
            $table->text('description')->nullable()->after('checksum_sha256');
            $table->string('document_number', 150)->nullable()->after('description');
            $table->json('tags')->nullable()->after('document_number');
            $table->date('issued_at')->nullable()->after('tags');
            $table->date('expires_at')->nullable()->after('issued_at');

            $table->string('preview_status', 30)->default('unsupported')->after('expires_at');
            $table->string('preview_path', 2048)->nullable()->after('preview_status');
            $table->string('thumbnail_path', 2048)->nullable()->after('preview_path');

            $table->unsignedBigInteger('uploaded_by')->nullable()->after('thumbnail_path');
            $table->unsignedBigInteger('created_by')->nullable()->after('uploaded_by');
            $table->unsignedBigInteger('updated_by')->nullable()->after('created_by');
            $table->unsignedBigInteger('deleted_by')->nullable()->after('updated_by');
            $table->softDeletes();

            $table->index('previous_version_id', 'attachments_previous_version_idx');
            $table->index(
                ['tenant_id', 'version_group_uuid', 'version_number'],
                'attachments_version_group_idx',
            );
            $table->index(
                ['tenant_id', 'organization_unit_id', 'category', 'is_current'],
                'attachments_category_current_idx',
            );
            $table->index(
                ['tenant_id', 'visibility', 'uploaded_by'],
                'attachments_visibility_uploader_idx',
            );
            $table->index(['tenant_id', 'expires_at'], 'attachments_expiry_idx');
            $table->index('created_by', 'attachments_created_by_idx');
            $table->index('updated_by', 'attachments_updated_by_idx');
            $table->index('deleted_by', 'attachments_deleted_by_idx');
        });

        DB::table('attachments')
            ->whereNull('uuid')
            ->orderBy('id')
            ->each(function (object $attachment): void {
                $uuid = (string) Str::uuid();
                $fileName = (string) ($attachment->file_name ?: basename((string) $attachment->file_path));

                DB::table('attachments')->where('id', $attachment->id)->update([
                    'uuid' => $uuid,
                    'version_group_uuid' => $uuid,
                    'original_file_name' => $fileName,
                    'stored_file_name' => basename((string) $attachment->file_path),
                    'display_name' => $fileName,
                ]);
            });
    }

    public function down(): void
    {
        Schema::table('attachments', function (Blueprint $table): void {
            $table->dropIndex('attachments_previous_version_idx');
            $table->dropIndex('attachments_version_group_idx');
            $table->dropIndex('attachments_category_current_idx');
            $table->dropIndex('attachments_visibility_uploader_idx');
            $table->dropIndex('attachments_expiry_idx');
            $table->dropIndex('attachments_created_by_idx');
            $table->dropIndex('attachments_updated_by_idx');
            $table->dropIndex('attachments_deleted_by_idx');
            $table->dropUnique('attachments_uuid_uk');

            $table->dropColumn([
                'uuid',
                'version_group_uuid',
                'previous_version_id',
                'version_number',
                'is_current',
                'category',
                'visibility',
                'display_name',
                'original_file_name',
                'stored_file_name',
                'disk',
                'extension',
                'checksum_sha256',
                'description',
                'document_number',
                'tags',
                'issued_at',
                'expires_at',
                'preview_status',
                'preview_path',
                'thumbnail_path',
                'uploaded_by',
                'created_by',
                'updated_by',
                'deleted_by',
                'deleted_at',
            ]);
        });
    }
};
