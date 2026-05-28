<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('item_metadata_definitions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->string('field_key', 120);
            $table->string('label', 180);
            $table->string('value_type', 30)->default('string');
            $table->boolean('is_required')->default(false);
            $table->boolean('is_active')->default(true);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['tenant_id', 'field_key'], 'item_metadata_definitions_tenant_key_uk');
            $table->index(['tenant_id', 'is_active'], 'item_metadata_definitions_tenant_active_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('item_metadata_definitions');
    }
};
