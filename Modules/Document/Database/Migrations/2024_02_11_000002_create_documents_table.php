<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('documents', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->ulid('tenant_id');
            $table->ulid('organization_id');
            $table->ulid('folder_id')->nullable();
            $table->ulid('owner_id');
            $table->string('name', 255);
            $table->text('description')->nullable();
            $table->string('type', 50);
            $table->string('mime_type', 255)->nullable();
            $table->bigInteger('size')->nullable();
            $table->string('path', 1000)->nullable();
            $table->string('original_name', 255)->nullable();
            $table->string('extension', 50)->nullable();
            $table->integer('version')->default(1);
            $table->boolean('is_latest_version')->default(true);
            $table->ulid('parent_document_id')->nullable();
            $table->string('access_level', 50)->default('private');
            $table->string('status', 50)->default('draft');
            $table->json('metadata')->nullable();
            $table->integer('download_count')->default(0);
            $table->integer('view_count')->default(0);
            $table->timestamps();
            $table->softDeletes();

            // Foreign keys
            $table->foreign('folder_id')->references('id')->on('folders')->onDelete('set null');
            $table->foreign('owner_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('parent_document_id')->references('id')->on('documents')->onDelete('cascade');

            // Indexes
            $table->index(['tenant_id', 'organization_id']);
            $table->index(['tenant_id', 'folder_id']);
            $table->index(['tenant_id', 'owner_id']);
            $table->index(['tenant_id', 'type']);
            $table->index(['tenant_id', 'status']);
            $table->index(['tenant_id', 'access_level']);
            $table->index(['tenant_id', 'mime_type']);
            $table->index(['tenant_id', 'extension']);
            $table->index(['tenant_id', 'is_latest_version']);
            $table->index(['tenant_id', 'parent_document_id']);
            $table->index(['tenant_id', 'name']);

            // Composite indexes for common queries
            $table->index(['tenant_id', 'folder_id', 'is_latest_version']);
            $table->index(['tenant_id', 'owner_id', 'is_latest_version']);
            $table->index(['tenant_id', 'status', 'is_latest_version']);
            $table->index(['tenant_id', 'access_level', 'status']);

            // Full-text search (only for MySQL/PostgreSQL, not SQLite)
            if (config('database.default') !== 'sqlite') {
                $table->fullText(['name', 'description', 'original_name']);
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('documents');
    }
};
