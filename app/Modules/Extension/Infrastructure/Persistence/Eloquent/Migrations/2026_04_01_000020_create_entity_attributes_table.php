<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('entity_attributes', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('row_version')->default(1)->comment('Used for optimistic concurrency control');
            $table->foreignId('tenant_id')->constrained('tenants', 'id')->cascadeOnDelete();
            $table->foreignId('organization_unit_id')->nullable()->constrained('organization_units', 'id')->nullOnDelete();
            $table->json('metadata')->nullable();

            $table->string('entity_type')->comment('the entity class (e.g., Document, Product, Party)');
            $table->unsignedBigInteger('entity_id')->comment('the primary key of the entity');
            $table->string('attribute_key')->comment('the custom field name');
            $table->text('attribute_value')->nullable()->comment('the value');

            $table->timestamps();

            $table->unique(['tenant_id', 'organization_unit_id', 'entity_type', 'entity_id', 'attribute_key'], 'entity_attributes_type_id_key_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('entity_attributes');
    }
};
