<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('document_versions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('document_id')->constrained('documents')->cascadeOnDelete();
            $table->unsignedInteger('version');
            $table->json('document_snapshot');
            $table->json('items_snapshot');
            $table->unsignedBigInteger('changed_by')->nullable();
            $table->string('change_reason')->nullable();
            $table->timestamps();

            $table->unique(['document_id', 'version'], 'document_versions_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('document_versions');
    }
};
