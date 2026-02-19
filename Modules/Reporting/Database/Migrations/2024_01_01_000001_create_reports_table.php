<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('organization_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('type', 50);
            $table->string('format', 50);
            $table->string('status', 50)->default('draft');
            $table->json('query_config');
            $table->json('fields');
            $table->json('filters')->nullable();
            $table->json('grouping')->nullable();
            $table->json('sorting')->nullable();
            $table->json('aggregations')->nullable();
            $table->json('metadata')->nullable();
            $table->boolean('is_template')->default(false);
            $table->boolean('is_shared')->default(false);
            $table->timestamp('published_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['tenant_id', 'type']);
            $table->index(['tenant_id', 'status']);
            $table->index(['tenant_id', 'user_id']);
            $table->index('is_template');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reports');
    }
};
