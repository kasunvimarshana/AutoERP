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
        Schema::create('folders', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->ulid('tenant_id');
            $table->ulid('organization_id');
            $table->ulid('parent_folder_id')->nullable();
            $table->string('name', 255);
            $table->text('description')->nullable();
            $table->string('path', 1000)->nullable();
            $table->boolean('is_system')->default(false);
            $table->json('metadata')->nullable();
            $table->ulid('created_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            // Foreign keys
            $table->foreign('parent_folder_id')->references('id')->on('folders')->onDelete('cascade');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');

            // Indexes
            $table->index(['tenant_id', 'organization_id']);
            $table->index(['tenant_id', 'parent_folder_id']);
            $table->index(['tenant_id', 'name']);
            $table->index(['tenant_id', 'path']);
            $table->index(['tenant_id', 'is_system']);
            $table->index(['tenant_id', 'created_by']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('folders');
    }
};
