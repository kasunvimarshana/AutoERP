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
        Schema::create('metadata_workflows', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->nullable()->constrained()->onDelete('cascade');
            $table->foreignId('entity_id')->constrained('metadata_entities')->onDelete('cascade');
            $table->string('name');
            $table->string('label');
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->json('states'); // [{name, label, type: initial/intermediate/final, actions}]
            $table->json('transitions'); // [{from, to, action, conditions, permissions}]
            $table->json('config')->nullable(); // Additional workflow configuration
            $table->timestamps();
            $table->softDeletes();

            $table->index(['entity_id', 'is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('metadata_workflows');
    }
};
