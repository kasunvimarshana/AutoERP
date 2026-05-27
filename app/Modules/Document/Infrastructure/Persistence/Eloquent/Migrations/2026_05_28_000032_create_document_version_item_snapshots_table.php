<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('document_version_item_snapshots', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('document_version_id')->constrained('document_versions')->cascadeOnDelete();
            $table->unsignedBigInteger('source_item_id')->nullable();
            $table->string('item_type');
            $table->string('description')->nullable();
            $table->decimal('line_total', 20, 4)->default(0);
            $table->unsignedInteger('sequence')->default(1);
            $table->timestamps();

            $table->index(
                ['tenant_id', 'document_version_id', 'sequence'],
                'document_version_item_snapshots_sequence_index'
            );
            $table->index(
                ['tenant_id', 'source_item_id'],
                'document_version_item_snapshots_source_item_index'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('document_version_item_snapshots');
    }
};
