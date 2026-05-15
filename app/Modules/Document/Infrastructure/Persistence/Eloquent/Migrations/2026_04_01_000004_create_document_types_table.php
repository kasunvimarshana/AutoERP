<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('document_types', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('row_version')->default(1)->comment('Used for optimistic concurrency control');
            $table->foreignId('tenant_id')->constrained('tenants', 'id')->cascadeOnDelete();
            $table->foreignId('organization_unit_id')->nullable()->constrained('organization_units', 'id')->nullOnDelete();
            $table->json('metadata')->nullable();

            $table->string('name');
            $table->boolean('requires_source')->default(false);
            $table->boolean('is_return')->default(false);
            $table->string('default_status')->default('draft');

            $table->timestamps();

            $table->unique(['tenant_id', 'organization_unit_id', 'name'], 'document_types_name_uk');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('document_types');
    }
};
