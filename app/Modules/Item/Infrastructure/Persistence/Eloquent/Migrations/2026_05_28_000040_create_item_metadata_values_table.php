<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('item_metadata_values', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('item_id')->constrained('items')->cascadeOnDelete();
            $table->foreignId('definition_id')->constrained('item_metadata_definitions')->cascadeOnDelete();
            $table->string('value_type', 30)->default('string');
            $table->string('string_value')->nullable();
            $table->longText('text_value')->nullable();
            $table->decimal('numeric_value', 20, 6)->nullable();
            $table->boolean('boolean_value')->nullable();
            $table->date('date_value')->nullable();
            $table->dateTime('datetime_value')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['tenant_id', 'item_id', 'definition_id'], 'item_metadata_values_tenant_item_definition_uk');
            $table->index(['tenant_id', 'item_id'], 'item_metadata_values_tenant_item_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('item_metadata_values');
    }
};
