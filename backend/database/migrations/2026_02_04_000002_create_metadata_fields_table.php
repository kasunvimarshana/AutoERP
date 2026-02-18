<?php

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
        Schema::create('metadata_fields', function (Blueprint $table) {
            $table->id();
            $table->foreignId('entity_id')->constrained('metadata_entities')->onDelete('cascade');
            $table->string('name'); // Field name (e.g., 'product_name', 'price')
            $table->string('label'); // Display label
            $table->string('type'); // text, number, date, select, multiselect, file, etc.
            $table->string('column_name')->nullable(); // Database column name
            $table->text('description')->nullable();
            $table->boolean('is_required')->default(false);
            $table->boolean('is_unique')->default(false);
            $table->boolean('is_searchable')->default(true);
            $table->boolean('is_filterable')->default(true);
            $table->boolean('is_sortable')->default(true);
            $table->boolean('is_visible_list')->default(true); // Show in list view
            $table->boolean('is_visible_detail')->default(true); // Show in detail view
            $table->boolean('is_visible_form')->default(true); // Show in create/edit form
            $table->boolean('is_readonly')->default(false);
            $table->boolean('is_system')->default(false); // System fields can't be deleted
            $table->integer('order')->default(0); // Display order
            $table->string('default_value')->nullable();
            $table->json('validation_rules')->nullable(); // Validation rules as JSON
            $table->json('options')->nullable(); // For select/multiselect: [{value, label}]
            $table->json('ui_config')->nullable(); // UI-specific config (placeholder, help text, etc.)
            $table->json('permissions')->nullable(); // Field-level permissions
            $table->string('relationship_entity')->nullable(); // For relationship fields
            $table->string('relationship_type')->nullable(); // belongsTo, hasMany, belongsToMany
            $table->timestamps();
            $table->softDeletes();

            $table->index(['entity_id', 'is_visible_list']);
            $table->index(['entity_id', 'is_searchable']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('metadata_fields');
    }
};
