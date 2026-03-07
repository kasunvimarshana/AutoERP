<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('categories', function (Blueprint $table) {
            $table->id();

            // Tenant isolation
            $table->unsignedBigInteger('tenant_id')->index();

            // Self-referencing tree
            $table->unsignedBigInteger('parent_id')->nullable()->index();

            $table->string('name');
            $table->text('description')->nullable();
            $table->string('slug')->index();
            $table->boolean('is_active')->default(true)->index();

            $table->timestamps();
            $table->softDeletes();

            // Composite unique: slug per tenant
            $table->unique(['tenant_id', 'slug']);

            // Self-referencing FK (deferred to avoid circular dependency)
            $table->foreign('parent_id')
                  ->references('id')
                  ->on('categories')
                  ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('categories');
    }
};
