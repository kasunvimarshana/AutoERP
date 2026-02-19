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
        Schema::create('notification_templates', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->ulid('tenant_id');
            $table->ulid('organization_id');
            $table->string('code', 100)->unique();
            $table->string('name', 255);
            $table->text('description')->nullable();
            $table->string('type', 50);
            $table->string('subject', 500)->nullable();
            $table->text('body_text')->nullable();
            $table->text('body_html')->nullable();
            $table->json('variables')->nullable();
            $table->json('default_data')->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('is_system')->default(false);
            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index(['tenant_id', 'organization_id']);
            $table->index(['tenant_id', 'code']);
            $table->index(['tenant_id', 'type']);
            $table->index(['tenant_id', 'is_active']);
            $table->index(['tenant_id', 'is_system']);

            // Composite indexes
            $table->index(['tenant_id', 'type', 'is_active']);
            $table->index(['tenant_id', 'organization_id', 'is_active']);
            $table->unique(['tenant_id', 'code']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notification_templates');
    }
};
